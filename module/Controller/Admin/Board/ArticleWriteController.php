<?php

/**
 * 관리자 게시글 작성 화면: lookbook 일 때 상품분류 선택 데이터 주입
 */

namespace Controller\Admin\Board;

use Request;

class ArticleWriteController extends \Bundle\Controller\Admin\Board\ArticleWriteController
{
    public function index()
    {
        parent::index();

        $req = Request::get()->toArray();
        if (($req['bdId'] ?? '') !== 'lookbook') {
            return;
        }

        $boardGoodsCategory = \App::load('\\Custom\\Component\\Board\\BoardGoodsCategory');
        $this->setData('customGoodsCategoryList', $boardGoodsCategory->getGoodsCategoryOptions());

        $selectedGoodsCateCd = '';
        $selectedGoodsCateCdList = [];
        $selectedSubTitle = '';
        if (($req['mode'] ?? 'write') === 'modify' && !empty($req['sno'])) {
            $articleCategory = $boardGoodsCategory->getArticleCategory('lookbook', (int)$req['sno']);
            $selectedGoodsCateCd = $articleCategory['goodsCateCd'] ?? '';
            $selectedGoodsCateCdList = $articleCategory['goodsCateCdList'] ?? [];
            $selectedSubTitle = $articleCategory['subTitle'] ?? '';
        }

        $this->setData('customSelectedGoodsCateCd', $selectedGoodsCateCd);
        $this->setData('customSelectedGoodsCateCdList', $selectedGoodsCateCdList);
        $this->setData('customSelectedSubTitle', $selectedSubTitle);
    }
}
