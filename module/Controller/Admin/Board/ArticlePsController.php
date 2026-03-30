<?php

/**
 * 관리자 게시글 저장 시 lookbook 상품분류(custom_board_goods_category) 연동
 *
 * 신규 글은 LookbookArticleActAdmin::handleAfterWrite 에서 실제 sno 로 저장,
 * 수정은 saveData 이후 요청 sno 로 저장, 삭제 시 확장 행 동기 삭제.
 */

namespace Controller\Admin\Board;

use Component\Board\ArticleActAdmin;
use Custom\Component\Board\LookbookArticleActAdmin;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\LayerException;
use Request;

class ArticlePsController extends \Bundle\Controller\Admin\Board\ArticlePsController
{
    public function index()
    {
        $req = Request::post()->toArray();
        $mode = $req['mode'] ?? '';

        if ($mode === 'delete' && ($req['bdId'] ?? '') === 'lookbook') {
            try {
                $articleActAdmin = new ArticleActAdmin($req);
                $boardGoodsCategory = \App::load('\\Custom\\Component\\Board\\BoardGoodsCategory');
                if (is_array($req['sno'])) {
                    foreach ($req['sno'] as $sno) {
                        $articleActAdmin->deleteData($sno);
                        $boardGoodsCategory->deleteArticleCategory('lookbook', (int)$sno);
                    }
                } else {
                    $articleActAdmin->deleteData($req['sno']);
                    $boardGoodsCategory->deleteArticleCategory('lookbook', (int)$req['sno']);
                }
                if ($req['popupMode'] == 'yes') {
                    $this->layer(__('삭제 되었습니다.'), "parent.opener.location.reload();parent.window.close();");
                } else {
                    $this->layer(__('삭제 되었습니다.'));
                }
            } catch (\Exception $e) {
                throw new AlertBackException($e->getMessage());
            }
            return;
        }

        $isLookbookWrite = ($req['bdId'] ?? '') === 'lookbook' && in_array($mode, ['write', 'modify'], true);

        if (!$isLookbookWrite) {
            parent::index();
            return;
        }

        try {
            $this->validateLookbookGoodsCategory($req);

            if ($mode === 'write') {
                $articleActAdmin = new LookbookArticleActAdmin($req);
            } else {
                $articleActAdmin = new ArticleActAdmin($req);
            }
            $msgs = $articleActAdmin->saveData();

            if ($mode === 'modify') {
                $this->saveLookbookGoodsCategoryModify($req);
            }

            $addScrpt = '';
            if ($msgs) {
                $msg = implode('\n', $msgs);
                $addScrpt = 'alert("' . $msg . '");';
            }
            if ($req['queryString'] == 'popupMode=yes') {
                $this->layer(__('저장이 완료되었습니다.'), "parent.opener.location.reload();parent.window.close();");
            } else {
                $this->layer(__('저장이 완료되었습니다.'), $addScrpt . "top.location.href='article_list.php?bdId=" . $req['bdId'] . "&" . $req['queryString'] . "'");
            }
        } catch (\Exception $e) {
            throw new LayerException($e->getMessage());
        }
    }

    /**
     * 수정 시 확장 테이블 갱신（요청 sno 사용）。
     *
     * @param array $req
     * @return void
     * @throws \Exception
     */
    private function saveLookbookGoodsCategoryModify(array $req)
    {
        $goodsCateCdList = $this->extractGoodsCateCdList($req);
        $subTitle = trim((string)($req['subTitle'] ?? ''));
        $articleSno = (int)($req['sno'] ?? 0);
        if ($articleSno < 1) {
            throw new \Exception('잘못된 게시글 번호입니다.');
        }

        $boardGoodsCategory = \App::load('\\Custom\\Component\\Board\\BoardGoodsCategory');
        $boardGoodsCategory->saveArticleCategories('lookbook', $articleSno, $goodsCateCdList);
        $boardGoodsCategory->saveArticleMeta('lookbook', $articleSno, $subTitle);
    }

    /**
     * @param array $req
     * @return void
     * @throws \Exception
     */
    private function validateLookbookGoodsCategory(array $req)
    {
        $goodsCateCdList = $this->extractGoodsCateCdList($req);
        if (empty($goodsCateCdList)) {
            throw new \Exception('상품 분류를 선택해 주세요.');
        }

        $boardGoodsCategory = \App::load('\\Custom\\Component\\Board\\BoardGoodsCategory');
        if ($boardGoodsCategory->isValidGoodsCateCdList($goodsCateCdList) === false) {
            throw new \Exception('선택한 상품 분류를 다시 확인해 주세요.');
        }
    }

    /**
     * @param array $req
     * @return array<int, string>
     */
    private function extractGoodsCateCdList(array $req)
    {
        $goodsCateCdList = $req['goodsCateCd'] ?? [];
        if (!is_array($goodsCateCdList)) {
            $goodsCateCdList = [$goodsCateCdList];
        }

        $goodsCateCdList = array_map(function ($goodsCateCd) {
            return trim((string)$goodsCateCd);
        }, $goodsCateCdList);

        return array_values(array_unique(array_filter($goodsCateCdList, function ($goodsCateCd) {
            return $goodsCateCd !== '';
        })));
    }
}
