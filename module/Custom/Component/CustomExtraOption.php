<?php

/**
 * 商品定制附加选项管理组件
 *
 * 负责管理单个商品的附加收费/非收费选项（如 LED 단색 지정、손잡이 인쇄 等）
 *
 * 表结构：
 * - custom_goods_extra_options
 * - custom_goods_extra_option_colors（仅当需要颜色盘时使用）
 */

namespace Custom\Component;

use Custom\Component\Database\CustomPDO;
use Exception;
use Logger;

class CustomExtraOption
{
    /**
     * @var CustomPDO
     */
    private $pdo;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->pdo = new CustomPDO();
    }

    /**
     * 获取指定商品的所有附加选项
     *
     * @param int $goodsId
     * @return array
     */
    public function getGoodsExtraOptions($goodsId)
    {
        try {
            $sql = "SELECT * FROM custom_goods_extra_options 
                    WHERE goods_id = ? AND status = 1 
                    ORDER BY sort_order ASC, option_id ASC";

            $data = $this->pdo->fetchAll($sql, [$goodsId]);

            return [
                'success' => true,
                'data' => $data,
                'message' => '获取附加选项成功',
            ];
        } catch (Exception $e) {
            Logger::error('获取商品附加选项异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId,
            ]);

            return [
                'success' => false,
                'data' => [],
                'message' => '获取附加选项失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 新增附加选项
     *
     * @param array $optionData
     * @return array
     */
    public function addGoodsExtraOption(array $optionData)
    {
        try {
            if (empty($optionData['goods_id'])) {
                throw new Exception('商品ID不能为空');
            }
            if (empty($optionData['option_group'])) {
                throw new Exception('选项组(option_group)不能为空');
            }
            if (empty($optionData['option_label'])) {
                throw new Exception('选项标题(option_label)不能为空');
            }

            $this->pdo->beginTransaction();

            try {
                $sql = "INSERT INTO custom_goods_extra_options 
                        (goods_id, option_group, option_label, option_desc, input_type, extra_price, price_type, is_required, status, sort_order, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

                $params = [
                    $optionData['goods_id'],
                    $optionData['option_group'],
                    $optionData['option_label'],
                    $optionData['option_desc'] ?? null,
                    $optionData['input_type'] ?? 'checkbox',
                    isset($optionData['extra_price']) ? (int)$optionData['extra_price'] : 0,
                    $optionData['price_type'] ?? 'per_unit',
                    isset($optionData['is_required']) ? (int)$optionData['is_required'] : 0,
                    isset($optionData['status']) ? (int)$optionData['status'] : 1,
                    isset($optionData['sort_order']) ? (int)$optionData['sort_order'] : 0,
                ];

                $optionId = $this->pdo->insert($sql, $params);

                if (!$optionId) {
                    throw new Exception('插入附加选项失败');
                }

                $this->pdo->commit();

                Logger::info('新增商品附加选项成功', [
                    'option_id' => $optionId,
                    'goods_id' => $optionData['goods_id'],
                    'option_group' => $optionData['option_group'],
                ]);

                return [
                    'success' => true,
                    'data' => ['option_id' => $optionId],
                    'message' => '附加选项新增成功',
                ];
            } catch (Exception $e) {
                $this->pdo->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            Logger::error('新增商品附加选项异常', [
                'error' => $e->getMessage(),
                'option_data' => $optionData,
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => '新增附加选项失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 更新附加选项
     *
     * @param int $optionId
     * @param array $optionData
     * @return array
     */
    public function updateGoodsExtraOption($optionId, array $optionData)
    {
        try {
            $fields = [];
            $params = [];

            if (isset($optionData['option_group'])) {
                $fields[] = 'option_group = ?';
                $params[] = $optionData['option_group'];
            }
            if (isset($optionData['option_label'])) {
                $fields[] = 'option_label = ?';
                $params[] = $optionData['option_label'];
            }
            if (array_key_exists('option_desc', $optionData)) {
                $fields[] = 'option_desc = ?';
                $params[] = $optionData['option_desc'];
            }
            if (isset($optionData['input_type'])) {
                $fields[] = 'input_type = ?';
                $params[] = $optionData['input_type'];
            }
            if (isset($optionData['extra_price'])) {
                $fields[] = 'extra_price = ?';
                $params[] = (int)$optionData['extra_price'];
            }
            if (isset($optionData['price_type'])) {
                $fields[] = 'price_type = ?';
                $params[] = $optionData['price_type'];
            }
            if (isset($optionData['is_required'])) {
                $fields[] = 'is_required = ?';
                $params[] = (int)$optionData['is_required'];
            }
            if (isset($optionData['status'])) {
                $fields[] = 'status = ?';
                $params[] = (int)$optionData['status'];
            }
            if (isset($optionData['sort_order'])) {
                $fields[] = 'sort_order = ?';
                $params[] = (int)$optionData['sort_order'];
            }

            if (empty($fields)) {
                throw new Exception('没有要更新的字段');
            }

            $fields[] = 'updated_at = NOW()';
            $params[] = $optionId;

            $sql = 'UPDATE custom_goods_extra_options SET ' . implode(', ', $fields) . ' WHERE option_id = ?';

            $result = $this->pdo->execute($sql, $params);

            if ($result) {
                Logger::info('更新商品附加选项成功', [
                    'option_id' => $optionId,
                    'update_data' => $optionData,
                ]);

                return [
                    'success' => true,
                    'message' => '附加选项更新成功',
                ];
            }

            throw new Exception('更新操作失败');
        } catch (Exception $e) {
            Logger::error('更新商品附加选项异常', [
                'error' => $e->getMessage(),
                'option_id' => $optionId,
                'option_data' => $optionData,
            ]);

            return [
                'success' => false,
                'message' => '更新附加选项失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 删除附加选项（软删除）
     *
     * @param int $optionId
     * @return array
     */
    public function deleteGoodsExtraOption($optionId)
    {
        try {
            $sql = "UPDATE custom_goods_extra_options 
                    SET status = 0, updated_at = NOW() 
                    WHERE option_id = ?";

            $result = $this->pdo->execute($sql, [$optionId]);

            if ($result) {
                Logger::info('删除商品附加选项成功', [
                    'option_id' => $optionId,
                ]);

                return [
                    'success' => true,
                    'message' => '附加选项删除成功',
                ];
            }

            throw new Exception('删除操作失败');
        } catch (Exception $e) {
            Logger::error('删除商品附加选项异常', [
                'error' => $e->getMessage(),
                'option_id' => $optionId,
            ]);

            return [
                'success' => false,
                'message' => '删除附加选项失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 获取某个附加选项的颜色列表
     *
     * @param int $optionId
     * @return array
     */
    public function getOptionColors($optionId)
    {
        try {
            $sql = "SELECT * FROM custom_goods_extra_option_colors 
                    WHERE option_id = ? AND status = 1 
                    ORDER BY sort_order ASC, color_id ASC";

            $data = $this->pdo->fetchAll($sql, [$optionId]);

            return [
                'success' => true,
                'data' => $data,
                'message' => '获取附加选项颜色成功',
            ];
        } catch (Exception $e) {
            Logger::error('获取附加选项颜色异常', [
                'error' => $e->getMessage(),
                'option_id' => $optionId,
            ]);

            return [
                'success' => false,
                'data' => [],
                'message' => '获取附加选项颜色失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 新增附加选项颜色
     *
     * @param array $colorData
     * @return array
     */
    public function addOptionColor(array $colorData)
    {
        try {
            if (empty($colorData['option_id'])) {
                throw new Exception('option_id 不能为空');
            }
            if (empty($colorData['color_name'])) {
                throw new Exception('颜色名称不能为空');
            }
            if (empty($colorData['color_hex'])) {
                throw new Exception('颜色HEX值不能为空');
            }

            $sql = "INSERT INTO custom_goods_extra_option_colors 
                    (option_id, color_name, color_code, color_hex, sort_order, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())";

            $params = [
                $colorData['option_id'],
                $colorData['color_name'],
                $colorData['color_code'] ?? null,
                $colorData['color_hex'],
                isset($colorData['sort_order']) ? (int)$colorData['sort_order'] : 0,
            ];

            $colorId = $this->pdo->insert($sql, $params);

            if (!$colorId) {
                throw new Exception('插入附加选项颜色失败');
            }

            Logger::info('新增附加选项颜色成功', [
                'color_id' => $colorId,
                'option_id' => $colorData['option_id'],
            ]);

            return [
                'success' => true,
                'data' => ['color_id' => $colorId],
                'message' => '附加选项颜色新增成功',
            ];
        } catch (Exception $e) {
            Logger::error('新增附加选项颜色异常', [
                'error' => $e->getMessage(),
                'color_data' => $colorData,
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => '新增附加选项颜色失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 更新附加选项颜色
     *
     * @param int $colorId
     * @param array $colorData
     * @return array
     */
    public function updateOptionColor($colorId, array $colorData)
    {
        try {
            $fields = [];
            $params = [];

            if (isset($colorData['color_name'])) {
                $fields[] = 'color_name = ?';
                $params[] = $colorData['color_name'];
            }
            if (array_key_exists('color_code', $colorData)) {
                $fields[] = 'color_code = ?';
                $params[] = $colorData['color_code'];
            }
            if (isset($colorData['color_hex'])) {
                $fields[] = 'color_hex = ?';
                $params[] = $colorData['color_hex'];
            }
            if (isset($colorData['sort_order'])) {
                $fields[] = 'sort_order = ?';
                $params[] = (int)$colorData['sort_order'];
            }
            if (isset($colorData['status'])) {
                $fields[] = 'status = ?';
                $params[] = (int)$colorData['status'];
            }

            if (empty($fields)) {
                throw new Exception('没有要更新的字段');
            }

            $fields[] = 'updated_at = NOW()';
            $params[] = $colorId;

            $sql = 'UPDATE custom_goods_extra_option_colors SET ' . implode(', ', $fields) . ' WHERE color_id = ?';

            $result = $this->pdo->execute($sql, $params);

            if ($result) {
                Logger::info('更新附加选项颜色成功', [
                    'color_id' => $colorId,
                    'update_data' => $colorData,
                ]);

                return [
                    'success' => true,
                    'message' => '附加选项颜色更新成功',
                ];
            }

            throw new Exception('更新操作失败');
        } catch (Exception $e) {
            Logger::error('更新附加选项颜色异常', [
                'error' => $e->getMessage(),
                'color_id' => $colorId,
                'color_data' => $colorData,
            ]);

            return [
                'success' => false,
                'message' => '更新附加选项颜色失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 删除附加选项颜色（软删除）
     *
     * @param int $colorId
     * @return array
     */
    public function deleteOptionColor($colorId)
    {
        try {
            $sql = "UPDATE custom_goods_extra_option_colors 
                    SET status = 0, updated_at = NOW() 
                    WHERE color_id = ?";

            $result = $this->pdo->execute($sql, [$colorId]);

            if ($result) {
                Logger::info('删除附加选项颜色成功', [
                    'color_id' => $colorId,
                ]);

                return [
                    'success' => true,
                    'message' => '附加选项颜色删除成功',
                ];
            }

            throw new Exception('删除操作失败');
        } catch (Exception $e) {
            Logger::error('删除附加选项颜色异常', [
                'error' => $e->getMessage(),
                'color_id' => $colorId,
            ]);

            return [
                'success' => false,
                'message' => '删除附加选项颜色失败：' . $e->getMessage(),
            ];
        }
    }
}


