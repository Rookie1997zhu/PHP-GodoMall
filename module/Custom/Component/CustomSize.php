<?php

/**
 * 商品自定义尺寸管理组件
 *
 * 对应表：
 * - custom_goods_sizes
 */

namespace Custom\Component;

use Custom\Component\Database\CustomPDO;
use Exception;
use Logger;

class CustomSize
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
     * 获取指定商品的所有自定义尺寸
     *
     * @param int $goodsId
     * @return array
     */
    public function getGoodsSizes($goodsId)
    {
        try {
            $sql = "SELECT custom_size_id, goods_id, size_name, size_width, size_height, size_unit, status, created_at, updated_at
                    FROM custom_goods_sizes
                    WHERE goods_id = ? AND status = 1
                    ORDER BY custom_size_id ASC";

            $rows = $this->pdo->fetchAll($sql, [$goodsId]) ?: [];

            return [
                'success' => true,
                'data'    => $rows,
                'message' => '获取自定义尺寸成功',
            ];
        } catch (Exception $e) {
            Logger::error('获取自定义尺寸失败', [
                'goods_id' => $goodsId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data'    => [],
                'message' => '获取自定义尺寸失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 新增自定义尺寸
     *
     * @param array $data
     * @return array
     */
    public function addGoodsSize(array $data)
    {
        try {
            if (empty($data['goods_id'])) {
                throw new Exception('商品ID不能为空');
            }
            if (empty($data['size_name'])) {
                throw new Exception('尺寸名称不能为空');
            }
            if (!isset($data['size_width']) || !isset($data['size_height'])) {
                throw new Exception('尺寸宽高不能为空');
            }

            $sql = "INSERT INTO custom_goods_sizes 
                    (goods_id, size_name, size_width, size_height, size_unit, is_custom, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())";

            $params = [
                (int)$data['goods_id'],
                $data['size_name'],
                (float)$data['size_width'],
                (float)$data['size_height'],
                $data['size_unit'] ?? 'mm',
                isset($data['status']) ? (int)$data['status'] : 1,
            ];

            $id = $this->pdo->insert($sql, $params);

            if (!$id) {
                throw new Exception('插入自定义尺寸失败');
            }

            Logger::info('新增自定义尺寸成功', [
                'custom_size_id' => $id,
                'goods_id'       => $data['goods_id'],
                'size_name'      => $data['size_name'],
            ]);

            return [
                'success' => true,
                'data'    => ['custom_size_id' => $id],
                'message' => '自定义尺寸新增成功',
            ];
        } catch (Exception $e) {
            Logger::error('新增自定义尺寸失败', [
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => '新增自定义尺寸失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 更新自定义尺寸
     *
     * @param int   $customSizeId
     * @param array $data
     * @return array
     */
    public function updateGoodsSize($customSizeId, array $data)
    {
        try {
            $fields = [];
            $params = [];

            if (isset($data['size_name'])) {
                $fields[] = 'size_name = ?';
                $params[] = $data['size_name'];
            }
            if (isset($data['size_width'])) {
                $fields[] = 'size_width = ?';
                $params[] = (float)$data['size_width'];
            }
            if (isset($data['size_height'])) {
                $fields[] = 'size_height = ?';
                $params[] = (float)$data['size_height'];
            }
            if (isset($data['size_unit'])) {
                $fields[] = 'size_unit = ?';
                $params[] = $data['size_unit'];
            }
            if (isset($data['status'])) {
                $fields[] = 'status = ?';
                $params[] = (int)$data['status'];
            }

            if (empty($fields)) {
                throw new Exception('没有要更新的字段');
            }

            $fields[] = 'updated_at = NOW()';
            $params[] = (int)$customSizeId;

            $sql = 'UPDATE custom_goods_sizes SET ' . implode(', ', $fields) . ' WHERE custom_size_id = ?';

            $result = $this->pdo->execute($sql, $params);

            if ($result) {
                Logger::info('更新自定义尺寸成功', [
                    'custom_size_id' => $customSizeId,
                    'update_data'    => $data,
                ]);

                return [
                    'success' => true,
                    'message' => '自定义尺寸更新成功',
                ];
            }

            throw new Exception('更新操作失败');
        } catch (Exception $e) {
            Logger::error('更新自定义尺寸失败', [
                'custom_size_id' => $customSizeId,
                'data'           => $data,
                'error'          => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '自定义尺寸更新失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 删除自定义尺寸（软删除）
     *
     * @param int $customSizeId
     * @return array
     */
    public function deleteGoodsSize($customSizeId)
    {
        try {
            $sql = "UPDATE custom_goods_sizes 
                    SET status = 0, updated_at = NOW()
                    WHERE custom_size_id = ?";

            $result = $this->pdo->execute($sql, [(int)$customSizeId]);

            if ($result) {
                Logger::info('删除自定义尺寸成功', [
                    'custom_size_id' => $customSizeId,
                ]);

                return [
                    'success' => true,
                    'message' => '自定义尺寸删除成功',
                ];
            }

            throw new Exception('删除操作失败');
        } catch (Exception $e) {
            Logger::error('删除自定义尺寸失败', [
                'custom_size_id' => $customSizeId,
                'error'          => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '自定义尺寸删除失败：' . $e->getMessage(),
            ];
        }
    }
}


