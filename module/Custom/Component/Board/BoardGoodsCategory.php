<?php

/**
 * 게시판 상품분류 확장 컴포넌트
 *
 * lookbook 게시글에 "발행 시점의 상품분류"를 고정 저장/조회한다.
 * 공식 게시판 원본 테이블은 변경하지 않고 custom 확장 테이블로 관리한다.
 */

namespace Custom\Component\Board;

use Custom\Component\Database\CustomPDO;
use Exception;
use Request;

class BoardGoodsCategory
{
    /**
     * @var CustomPDO
     */
    private $pdo;

    public function __construct()
    {
        $this->pdo = new CustomPDO();
    }

    /**
     * 前台可用的商品分类选项。
     *
     * @return array<int, array<string, string|int>>
     * @throws Exception
     */
    public function getGoodsCategoryOptions()
    {
        $rows = $this->pdo->fetchAll(
            "SELECT cateCd, cateNm
             FROM es_categoryGoods
             WHERE divisionFl = 'n' AND cateDisplayFl = 'y'
             ORDER BY cateSort ASC, cateCd ASC"
        );

        $list = [];
        foreach ($rows as $row) {
            $cateCd = (string)($row['cateCd'] ?? '');
            $cateNm = (string)($row['cateNm'] ?? '');
            if ($cateCd === '' || $cateNm === '') {
                continue;
            }
            $depth = (int)(strlen($cateCd) / 3);
            $prefix = $depth > 1 ? str_repeat(' - ', $depth - 1) : '';
            $list[] = [
                'cateCd' => $cateCd,
                'cateNm' => $cateNm,
                'depth' => $depth,
                'label' => $prefix . $cateNm,
            ];
        }

        return $list;
    }

    /**
     * 校验分类代码是否有效。
     *
     * @param string $goodsCateCd
     * @return bool
     * @throws Exception
     */
    public function isValidGoodsCateCd($goodsCateCd)
    {
        $goodsCateCd = trim((string)$goodsCateCd);
        if ($goodsCateCd === '') {
            return true;
        }

        $row = $this->pdo->fetch(
            "SELECT cateCd
             FROM es_categoryGoods
             WHERE divisionFl = 'n' AND cateDisplayFl = 'y' AND cateCd = ?
             LIMIT 1",
            [$goodsCateCd]
        );

        return !empty($row['cateCd']);
    }

    /**
     * 校验分类代码列表是否全部有效。
     *
     * @param array<int, string> $goodsCateCds
     * @return bool
     * @throws Exception
     */
    public function isValidGoodsCateCdList(array $goodsCateCds)
    {
        $goodsCateCds = $this->normalizeGoodsCateCdList($goodsCateCds);
        if (empty($goodsCateCds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($goodsCateCds), '?'));
        $row = $this->pdo->fetch(
            "SELECT COUNT(*) AS cnt
             FROM es_categoryGoods
             WHERE divisionFl = 'n' AND cateDisplayFl = 'y' AND cateCd IN ($placeholders)",
            $goodsCateCds
        );

        return (int)($row['cnt'] ?? 0) === count($goodsCateCds);
    }

    /**
     * 兼容旧调用：保存单个商品分类，并同步保存文章级 Sub Title。
     *
     * @param string $bdId
     * @param int $articleSno
     * @param string $goodsCateCd
     * @param string|null $subTitle
     * @return bool
     * @throws Exception
     */
    public function saveArticleCategory($bdId, $articleSno, $goodsCateCd, $subTitle = null)
    {
        $this->pdo->beginTransaction();
        try {
            $this->saveArticleCategories($bdId, $articleSno, [$goodsCateCd], true);
            $metaOk = $this->saveArticleMeta($bdId, $articleSno, $subTitle);
            if ($metaOk === false) {
                throw new Exception('保存文章扩展信息失败');
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }

    /**
     * 批量保存帖子的固定商品分类。
     *
     * @param string $bdId
     * @param int $articleSno
     * @param array<int, string> $goodsCateCds
     * @param bool $inTransaction 为 true 时由外层事务管理（不再 begin/commit）
     * @return bool
     * @throws Exception
     */
    public function saveArticleCategories($bdId, $articleSno, array $goodsCateCds, $inTransaction = false)
    {
        if (!$this->isBoardIdSafe($bdId)) {
            throw new Exception('无效的게시판 ID');
        }
        $articleSno = (int)$articleSno;
        if ($articleSno < 1) {
            throw new Exception('无效的帖子编号');
        }

        $goodsCateCds = $this->normalizeGoodsCateCdList($goodsCateCds);
        if (empty($goodsCateCds)) {
            throw new Exception('상품 분류를 선택해 주세요.');
        }
        if ($this->isValidGoodsCateCdList($goodsCateCds) === false) {
            throw new Exception('选择的商品分类不存在或不可用');
        }

        $startedHere = !$inTransaction;
        if ($startedHere) {
            $this->pdo->beginTransaction();
        }
        try {
            $this->deleteArticleCategoryMappings($bdId, [$articleSno]);
            foreach ($goodsCateCds as $goodsCateCd) {
                $ok = $this->pdo->execute(
                    "INSERT INTO custom_board_goods_category (bd_id, article_sno, goods_cate_cd, created_at, updated_at)
                     VALUES (?, ?, ?, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE updated_at = NOW()",
                    [$bdId, $articleSno, $goodsCateCd]
                );
                if (!$ok) {
                    throw new Exception('保存商品分类映射失败');
                }
            }
            if ($startedHere) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedHere) {
                $this->pdo->rollback();
            }
            throw $e;
        }
    }

    /**
     * 保存文章级扩展信息。
     *
     * @param string $bdId
     * @param int $articleSno
     * @param string|null $subTitle
     * @return bool
     * @throws Exception
     */
    public function saveArticleMeta($bdId, $articleSno, $subTitle = null)
    {
        if (!$this->isBoardIdSafe($bdId)) {
            throw new Exception('无效的게시판 ID');
        }
        $articleSno = (int)$articleSno;
        if ($articleSno < 1) {
            throw new Exception('无效的帖子编号');
        }

        $subTitle = trim((string)$subTitle);
        if ($subTitle === '') {
            return $this->deleteArticleMeta($bdId, [$articleSno]);
        }

        return $this->pdo->execute(
            "INSERT INTO custom_board_article_meta (bd_id, article_sno, sub_title, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE sub_title = VALUES(sub_title), updated_at = NOW()",
            [$bdId, $articleSno, $subTitle]
        );
    }

    /**
     * 删除单条帖子的扩展分类映射（删帖或数据清理用）。
     *
     * @param string $bdId
     * @param int $articleSno
     * @return bool
     * @throws Exception
     */
    public function deleteArticleCategory($bdId, $articleSno)
    {
        if (!$this->isBoardIdSafe($bdId) || (int)$articleSno < 1) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $metaOk = $this->deleteArticleMeta($bdId, [(int)$articleSno]);
            if (!$metaOk) {
                throw new Exception('删除文章扩展信息失败');
            }
            $ok = $this->deleteArticleCategoryMappings($bdId, [(int)$articleSno]);
            if (!$ok) {
                throw new Exception('删除分类映射失败');
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollback();
            return false;
        }
    }

    /**
     * 批量删除帖子扩展分类映射。
     *
     * @param string $bdId
     * @param int[] $articleSnos
     * @return bool
     * @throws Exception
     */
    public function deleteArticleCategories($bdId, array $articleSnos)
    {
        if (!$this->isBoardIdSafe($bdId)) {
            return false;
        }

        $articleSnos = array_values(array_filter(array_map('intval', $articleSnos), function ($sno) {
            return $sno > 0;
        }));
        if (empty($articleSnos)) {
            return true;
        }

        $this->pdo->beginTransaction();
        try {
            $metaOk = $this->deleteArticleMeta($bdId, $articleSnos);
            if (!$metaOk) {
                throw new Exception('批量删除文章扩展信息失败');
            }
            $ok = $this->deleteArticleCategoryMappings($bdId, $articleSnos);
            if (!$ok) {
                throw new Exception('批量删除分类映射失败');
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollback();
            return false;
        }
    }

    /**
     * 根据分类筛选命中的帖子ID集合。
     *
     * @param string $bdId
     * @param string $goodsCateCd
     * @return int[]
     * @throws Exception
     */
    public function getArticleSnoListByCategory($bdId, $goodsCateCd)
    {
        if (!$this->isBoardIdSafe($bdId) || $goodsCateCd === '') {
            return [];
        }

        $rows = $this->pdo->fetchAll(
            "SELECT article_sno
             FROM custom_board_goods_category
             WHERE bd_id = ? AND goods_cate_cd = ?",
            [$bdId, $goodsCateCd]
        );

        $result = [];
        foreach ($rows as $row) {
            $sno = (int)($row['article_sno'] ?? 0);
            if ($sno > 0) {
                $result[] = $sno;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * 批量读取帖子的分类映射（用于列表标签显示）。
     *
     * @param string $bdId
     * @param int[] $articleSnos
     * @return array<int, array<string, string>>
     * @throws Exception
     */
    public function getArticleCategoryMap($bdId, array $articleSnos)
    {
        if (!$this->isBoardIdSafe($bdId) || empty($articleSnos)) {
            return [];
        }

        $articleSnos = array_values(array_filter(array_map('intval', $articleSnos), function ($sno) {
            return $sno > 0;
        }));
        if (empty($articleSnos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($articleSnos), '?'));
        $params = array_merge([$bdId], $articleSnos);
        $sql = "SELECT cbgc.article_sno, cbgc.goods_cate_cd, cg.cateNm
                FROM custom_board_goods_category cbgc
                LEFT JOIN es_categoryGoods cg ON cg.cateCd = cbgc.goods_cate_cd
                WHERE cbgc.bd_id = ? AND cbgc.article_sno IN ($placeholders)
                ORDER BY cbgc.article_sno ASC, cbgc.id ASC";

        $rows = $this->pdo->fetchAll($sql, $params);
        $metaMap = $this->getArticleMetaMap($bdId, $articleSnos);
        $map = [];
        foreach ($rows as $row) {
            $sno = (int)($row['article_sno'] ?? 0);
            if ($sno < 1) {
                continue;
            }
            if (!isset($map[$sno])) {
                $map[$sno] = $this->createEmptyArticleCategoryData();
                $map[$sno]['subTitle'] = $metaMap[$sno]['subTitle'] ?? '';
            }

            $goodsCateCd = (string)($row['goods_cate_cd'] ?? '');
            $goodsCateNm = (string)($row['cateNm'] ?? '');
            if ($goodsCateCd !== '') {
                $map[$sno]['goodsCateCdList'][] = $goodsCateCd;
                if ($map[$sno]['goodsCateCd'] === '') {
                    $map[$sno]['goodsCateCd'] = $goodsCateCd;
                }
            }
            if ($goodsCateNm !== '') {
                $map[$sno]['goodsCateNmList'][] = $goodsCateNm;
                if ($map[$sno]['goodsCateNm'] === '') {
                    $map[$sno]['goodsCateNm'] = $goodsCateNm;
                }
            }
        }

        foreach ($metaMap as $sno => $metaRow) {
            if (!isset($map[$sno])) {
                $map[$sno] = $this->createEmptyArticleCategoryData();
            }
            $map[$sno]['subTitle'] = $metaRow['subTitle'] ?? '';
        }

        return $map;
    }

    /**
     * 读取单条帖子已保存的分类。
     *
     * @param string $bdId
     * @param int $articleSno
     * @return array<string, string>
     * @throws Exception
     */
    public function getArticleCategory($bdId, $articleSno)
    {
        if (!$this->isBoardIdSafe($bdId) || (int)$articleSno < 1) {
            return $this->createEmptyArticleCategoryData();
        }

        $map = $this->getArticleCategoryMap($bdId, [(int)$articleSno]);

        return $map[(int)$articleSno] ?? $this->createEmptyArticleCategoryData();
    }

    /**
     * 读取单条帖子已保存的文章级元数据。
     *
     * @param string $bdId
     * @param int $articleSno
     * @return array<string, string>
     * @throws Exception
     */
    public function getArticleMeta($bdId, $articleSno)
    {
        if (!$this->isBoardIdSafe($bdId) || (int)$articleSno < 1) {
            return ['subTitle' => ''];
        }

        $map = $this->getArticleMetaMap($bdId, [(int)$articleSno]);

        return $map[(int)$articleSno] ?? ['subTitle' => ''];
    }

    /**
     * write 模式后根据上下文回查最新帖子 sno（휴리스틱，관리자 lookbook 저장에는 사용하지 않음）。
     *
     * @param string $bdId
     * @param array $req
     * @return int
     * @throws Exception
     */
    public function resolveLatestArticleSno($bdId, array $req)
    {
        if (!$this->isBoardIdSafe($bdId)) {
            return 0;
        }

        $subject = trim((string)($req['subject'] ?? ''));
        $memNo = (int)($req['memNo'] ?? 0);
        $writerNm = trim((string)($req['writerNm'] ?? ''));
        $writerIp = Request::getRemoteAddress();

        $table = 'es_bd_' . $bdId;
        if ($subject === '') {
            return 0;
        }

        if ($memNo > 0) {
            $row = $this->pdo->fetch(
                "SELECT sno
                 FROM {$table}
                 WHERE memNo = ? AND subject = ? AND regDt >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                 ORDER BY sno DESC
                 LIMIT 1",
                [$memNo, $subject]
            );
        } elseif ($memNo < 0) {
            // 관리자 작성글 memNo < 0 (ArticleAdmin)
            $row = $this->pdo->fetch(
                "SELECT sno
                 FROM {$table}
                 WHERE memNo = ? AND subject = ? AND regDt >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                 ORDER BY sno DESC
                 LIMIT 1",
                [$memNo, $subject]
            );
        } else {
            $row = $this->pdo->fetch(
                "SELECT sno
                 FROM {$table}
                 WHERE memNo = 0 AND writerNm = ? AND writerIp = ? AND subject = ?
                   AND regDt >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                 ORDER BY sno DESC
                 LIMIT 1",
                [$writerNm, $writerIp, $subject]
            );
        }

        return (int)($row['sno'] ?? 0);
    }

    /**
     * 校验 게시판 ID 安全性（仅允许字母数字下划线）。
     *
     * @param string $bdId
     * @return bool
     */
    private function isBoardIdSafe($bdId)
    {
        return (bool)preg_match('/^[a-zA-Z0-9_]+$/', (string)$bdId);
    }

    /**
     * @param array<int, int> $articleSnos
     * @return array<int, array<string, string>>
     * @throws Exception
     */
    private function getArticleMetaMap($bdId, array $articleSnos)
    {
        if (!$this->isBoardIdSafe($bdId) || empty($articleSnos)) {
            return [];
        }

        $articleSnos = array_values(array_filter(array_map('intval', $articleSnos), function ($sno) {
            return $sno > 0;
        }));
        if (empty($articleSnos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($articleSnos), '?'));
        $params = array_merge([$bdId], $articleSnos);
        $rows = $this->pdo->fetchAll(
            "SELECT article_sno, sub_title
             FROM custom_board_article_meta
             WHERE bd_id = ? AND article_sno IN ($placeholders)",
            $params
        );

        $map = [];
        foreach ($rows as $row) {
            $sno = (int)($row['article_sno'] ?? 0);
            if ($sno < 1) {
                continue;
            }
            $map[$sno] = [
                'subTitle' => (string)($row['sub_title'] ?? ''),
            ];
        }

        return $map;
    }

    /**
     * @param array<int, int> $articleSnos
     * @return bool
     * @throws Exception
     */
    private function deleteArticleCategoryMappings($bdId, array $articleSnos)
    {
        $articleSnos = array_values(array_filter(array_map('intval', $articleSnos), function ($sno) {
            return $sno > 0;
        }));
        if (!$this->isBoardIdSafe($bdId) || empty($articleSnos)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($articleSnos), '?'));
        $params = array_merge([$bdId], $articleSnos);

        return $this->pdo->execute(
            "DELETE FROM custom_board_goods_category WHERE bd_id = ? AND article_sno IN ($placeholders)",
            $params
        );
    }

    /**
     * @param array<int, int> $articleSnos
     * @return bool
     * @throws Exception
     */
    private function deleteArticleMeta($bdId, array $articleSnos)
    {
        $articleSnos = array_values(array_filter(array_map('intval', $articleSnos), function ($sno) {
            return $sno > 0;
        }));
        if (!$this->isBoardIdSafe($bdId) || empty($articleSnos)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($articleSnos), '?'));
        $params = array_merge([$bdId], $articleSnos);

        return $this->pdo->execute(
            "DELETE FROM custom_board_article_meta WHERE bd_id = ? AND article_sno IN ($placeholders)",
            $params
        );
    }

    /**
     * @param array<int, string> $goodsCateCds
     * @return array<int, string>
     */
    private function normalizeGoodsCateCdList(array $goodsCateCds)
    {
        $goodsCateCds = array_map(function ($goodsCateCd) {
            return trim((string)$goodsCateCd);
        }, $goodsCateCds);
        $goodsCateCds = array_values(array_filter($goodsCateCds, function ($goodsCateCd) {
            return $goodsCateCd !== '';
        }));

        return array_values(array_unique($goodsCateCds));
    }

    /**
     * @return array<string, mixed>
     */
    private function createEmptyArticleCategoryData()
    {
        return [
            'goodsCateCd' => '',
            'goodsCateNm' => '',
            'goodsCateCdList' => [],
            'goodsCateNmList' => [],
            'subTitle' => '',
        ];
    }
}
