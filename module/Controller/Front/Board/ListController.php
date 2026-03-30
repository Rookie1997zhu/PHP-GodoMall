<?php

/**
 * Lookbook 列表控制器最小覆写
 *
 * 仅在官方列表流程后补充商品分类筛选与分类标签数据。
 */

namespace Controller\Front\Board;

use Component\Board\BoardList;
use Request;

class ListController extends \Bundle\Controller\Front\Board\ListController
{
    public function index()
    {
        parent::index();

        $this->appendLookbookCategoryData();
    }

    /**
     * 在官方列表数据基础上追加 lookbook 分类筛选与标签。
     *
     * 附加逻辑异常不应打断官方列表主流程。
     *
     * @return void
     */
    private function appendLookbookCategoryData()
    {
        try {
            $req = Request::get()->toArray();
            if (($req['bdId'] ?? '') !== 'lookbook') {
                return;
            }

            $boardGoodsCategory = \App::load('\\Custom\\Component\\Board\\BoardGoodsCategory');
            $selectedGoodsCateCd = trim((string)($req['goodsCateCd'] ?? ''));

            $this->setData('customGoodsCategoryList', $boardGoodsCategory->getGoodsCategoryOptions());
            $this->setData('customSelectedGoodsCateCd', $selectedGoodsCateCd);

            $boardList = new BoardList($req);
            $boardList->checkUsePc();

            $arrWhere = [];
            if ($selectedGoodsCateCd !== '') {
                $articleSnoList = $boardGoodsCategory->getArticleSnoListByCategory('lookbook', $selectedGoodsCateCd);
                if (empty($articleSnoList)) {
                    $arrWhere[] = ' 1 = 0 ';
                } else {
                    $arrWhere[] = ' sno IN (' . implode(',', array_map('intval', $articleSnoList)) . ') ';
                }
            }

            $getData = $boardList->getList(true, 0, 0, $arrWhere);
            $bdList['cfg'] = $boardList->cfg;
            $bdList['cnt'] = $getData['cnt'];
            $bdList['list'] = $getData['data'];
            $bdList['noticeList'] = $getData['noticeData'];
            $bdList['categoryBox'] = $boardList->getCategoryBox(gd_isset($req['category']), ' onChange="this.form.submit();" ');
            $bdList['pagination'] = $getData['pagination']->getPage();

            $articleSnos = [];
            foreach ((array)$bdList['list'] as $row) {
                $sno = (int)($row['sno'] ?? 0);
                if ($sno > 0) {
                    $articleSnos[] = $sno;
                }
            }

            $articleCategoryMap = $boardGoodsCategory->getArticleCategoryMap('lookbook', $articleSnos);
            foreach ($bdList['list'] as &$row) {
                $sno = (int)($row['sno'] ?? 0);
                $row['goodsCateCd'] = $articleCategoryMap[$sno]['goodsCateCd'] ?? '';
                $row['goodsCateNm'] = $articleCategoryMap[$sno]['goodsCateNm'] ?? '';
                $row['goodsCateCdList'] = $articleCategoryMap[$sno]['goodsCateCdList'] ?? [];
                $row['goodsCateNmList'] = $articleCategoryMap[$sno]['goodsCateNmList'] ?? [];
                $row['subTitle'] = $articleCategoryMap[$sno]['subTitle'] ?? '';
            }
            unset($row);

            $this->setData('bdList', $bdList);
            $this->setData('req', gd_htmlspecialchars($boardList->req));
        } catch (\Throwable $e) {
            error_log('Lookbook ListController::appendLookbookCategoryData failed: ' . $e->getMessage());
        }
    }
}
