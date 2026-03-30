<?php

/**
 * 관리자 게시글 저장 확장：lookbook 신규 글에 대해 handleAfterWrite 시점의 실제 sno 로 상품분류 저장
 *
 * 공식 ArticleActAdmin 을 상속하며 Board::saveData 의 write 분기에서 호출되는 handleAfterWrite 를 확장한다.
 */

namespace Custom\Component\Board;

use Bundle\Component\Board\ArticleActAdmin as BaseArticleActAdmin;

class LookbookArticleActAdmin extends BaseArticleActAdmin
{
    /**
     * @param array $data
     * @param array|null $msgs
     * @return void
     * @throws \Exception
     */
    protected function handleAfterWrite(array $data, &$msgs = null)
    {
        parent::handleAfterWrite($data);

        if (($this->cfg['bdId'] ?? '') !== 'lookbook' || ($this->req['mode'] ?? '') !== 'write') {
            return;
        }

        $goodsCateCdList = $this->extractGoodsCateCdList();
        if (empty($goodsCateCdList)) {
            throw new \Exception('상품 분류를 선택해 주세요.');
        }

        $boardGoodsCategory = \App::load('\\Custom\\Component\\Board\\BoardGoodsCategory');
        if ($boardGoodsCategory->isValidGoodsCateCdList($goodsCateCdList) === false) {
            throw new \Exception('선택한 상품 분류를 다시 확인해 주세요.');
        }
        $subTitle = trim((string)($this->req['subTitle'] ?? ''));

        $sno = (int)($data['sno'] ?? 0);
        if ($sno < 1) {
            throw new \Exception('게시글 번호를 확인할 수 없습니다.');
        }

        $boardGoodsCategory->saveArticleCategories('lookbook', $sno, $goodsCateCdList);
        $boardGoodsCategory->saveArticleMeta('lookbook', $sno, $subTitle);
    }

    /**
     * @return array<int, string>
     */
    private function extractGoodsCateCdList()
    {
        $goodsCateCdList = $this->req['goodsCateCd'] ?? [];
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
