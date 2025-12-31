<?php

/**
 * 商品附加图片管理组件
 *
 * 用于管理与单个商品关联的「附加商品图片」（包装、附属品等，供前台定制使用）
 *
 * 表结构建议：
 * - custom_goods_extra_images
 *   - image_id, goods_id, image_type, image_name, image_path, status, created_at, updated_at
 */

namespace Custom\Component;

use Custom\Component\Database\CustomPDO;
use Exception;
use Logger;

class CustomExtraImage
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
     * 获取指定商品的所有附加图片（启用状态）
     *
     * @param int $goodsId
     * @return array{success:bool,data:array,message:string}
     */
    public function getGoodsExtraImages($goodsId)
    {
        try {
            $sql = "SELECT image_id, goods_id, image_type, image_name, image_path, status, created_at, updated_at
                    FROM custom_goods_extra_images
                    WHERE goods_id = ? AND status = 1
                    ORDER BY image_id ASC";

            $rows = $this->pdo->fetchAll($sql, [$goodsId]) ?: [];

            return [
                'success' => true,
                'data'    => $rows,
                'message' => '获取附加图片成功',
            ];
        } catch (Exception $e) {
            Logger::error('获取附加图片失败', [
                'goods_id' => $goodsId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data'    => [],
                'message' => '获取附加图片失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 新增附加图片
     *
     * @param array $data
     * @return array
     */
    public function addGoodsExtraImage(array $data)
    {
        try {
            if (empty($data['goods_id'])) {
                throw new Exception('商品ID不能为空');
            }
            if (empty($data['image_path'])) {
                throw new Exception('图片路径不能为空');
            }

            $sql = "INSERT INTO custom_goods_extra_images 
                    (goods_id, image_type, image_name, image_path, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
                (int)$data['goods_id'],
                $data['image_type'] ?? 'package',
                $data['image_name'] ?? '',
                $data['image_path'],
                isset($data['status']) ? (int)$data['status'] : 1,
            ];

            $imageId = $this->pdo->insert($sql, $params);

            if (!$imageId) {
                throw new Exception('插入附加图片失败');
            }

            Logger::info('新增附加图片成功', [
                'image_id' => $imageId,
                'goods_id' => $data['goods_id'],
                'image_path' => $data['image_path'],
            ]);

            return [
                'success' => true,
                'data'    => ['image_id' => $imageId],
                'message' => '附加图片新增成功',
            ];
        } catch (Exception $e) {
            Logger::error('新增附加图片失败', [
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => '新增附加图片失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 更新附加图片元数据
     *
     * @param int   $imageId
     * @param array $data
     * @return array
     */
    public function updateGoodsExtraImage($imageId, array $data)
    {
        try {
            $fields = [];
            $params = [];

            if (isset($data['image_type'])) {
                $fields[] = 'image_type = ?';
                $params[] = $data['image_type'];
            }
            if (isset($data['image_name'])) {
                $fields[] = 'image_name = ?';
                $params[] = $data['image_name'];
            }
            if (isset($data['image_path'])) {
                $fields[] = 'image_path = ?';
                $params[] = $data['image_path'];
            }
            if (isset($data['status'])) {
                $fields[] = 'status = ?';
                $params[] = (int)$data['status'];
            }

            if (empty($fields)) {
                throw new Exception('没有要更新的字段');
            }

            $fields[] = 'updated_at = NOW()';
            $params[] = (int)$imageId;

            $sql = 'UPDATE custom_goods_extra_images SET ' . implode(', ', $fields) . ' WHERE image_id = ?';

            $result = $this->pdo->execute($sql, $params);

            if ($result) {
                Logger::info('更新附加图片成功', [
                    'image_id'    => $imageId,
                    'update_data' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => '附加图片更新成功',
                ];
            }

            throw new Exception('更新操作失败');
        } catch (Exception $e) {
            Logger::error('更新附加图片失败', [
                'image_id' => $imageId,
                'data'     => $data,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '附加图片更新失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 删除附加图片（软删除）
     *
     * @param int $imageId
     * @return array
     */
    public function deleteGoodsExtraImage($imageId)
    {
        try {
            $sql = "UPDATE custom_goods_extra_images 
                    SET status = 0, updated_at = NOW()
                    WHERE image_id = ?";

            $result = $this->pdo->execute($sql, [(int)$imageId]);

            if ($result) {
                Logger::info('删除附加图片成功', [
                    'image_id' => $imageId,
                ]);

                return [
                    'success' => true,
                    'message' => '附加图片删除成功',
                ];
            }

            throw new Exception('删除操作失败');
        } catch (Exception $e) {
            Logger::error('删除附加图片失败', [
                'image_id' => $imageId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '附加图片删除失败：' . $e->getMessage(),
            ];
        }
    }
}


