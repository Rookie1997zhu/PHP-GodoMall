<?php

/**
 * Lookbook 상세보기 컨트롤러 최소覆写
 *
 * 官方 ViewController 渲染后，给 lookbook 详情页追加 Sub Title 与商品分类标签数据。
 */

namespace Controller\Front\Board;

use Request;

class ViewController extends \Bundle\Controller\Front\Board\ViewController
{
    public function index()
    {
        parent::index();

        $this->appendLookbookViewData();
    }

    /**
     * 在官方详情数据基础上追加 lookbook 扩展字段。
     *
     * @return void
     */
    private function appendLookbookViewData()
    {
        try {
            $req = array_merge((array)Request::get()->toArray(), (array)Request::post()->toArray());
            if (($req['bdId'] ?? '') !== 'lookbook' || empty($req['sno'])) {
                return;
            }

            $bdView = $this->getData('bdView');
            if (!is_array($bdView) || !isset($bdView['data']) || !is_array($bdView['data'])) {
                return;
            }

            $boardGoodsCategory = \App::load('\\Custom\\Component\\Board\\BoardGoodsCategory');
            $articleCategory = $boardGoodsCategory->getArticleCategory('lookbook', (int)$req['sno']);

            $bdView['data']['subTitle'] = $articleCategory['subTitle'] ?? '';
            $bdView['data']['goodsCateCd'] = $articleCategory['goodsCateCd'] ?? '';
            $bdView['data']['goodsCateNm'] = $articleCategory['goodsCateNm'] ?? '';
            $bdView['data']['goodsCateCdList'] = $articleCategory['goodsCateCdList'] ?? [];
            $bdView['data']['goodsCateNmList'] = $articleCategory['goodsCateNmList'] ?? [];

            $this->setData('bdView', $bdView);
        } catch (\Throwable $e) {
            error_log('Lookbook ViewController::appendLookbookViewData failed: ' . $e->getMessage());
        }
    }
}
