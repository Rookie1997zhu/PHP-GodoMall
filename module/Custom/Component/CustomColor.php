<?php

/**
 * 商品定制颜色管理组件
 * 
 * 本组件负责商品定制颜色的业务逻辑处理
 * 每个商品的颜色配置完全独立，不同商品间不共享
 * 
 * @package Custom\Component
 * @author Custom Development Team
 * @version 1.0
 */

namespace Custom\Component;

use Custom\Component\Database\CustomPDO;
use Exception;
use Logger;

/**
 * 商品定制颜色管理类
 */
class CustomColor
{
    /**
     * PDO数据库连接实例
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
     * 获取商品的所有定制颜色
     * 
     * @param int $goodsId 商品ID
     * @return array 颜色列表
     */
    public function getGoodsColors($goodsId)
    {
        try {
            $sql = "SELECT * FROM custom_goods_colors 
                    WHERE goods_id = ? AND status = 1 
                    ORDER BY sort_order ASC, color_id ASC";
            
            $params = [$goodsId];
            $result = $this->pdo->fetchAll($sql, $params);
            
            return [
                'success' => true,
                'data' => $result,
                'message' => '获取颜色列表成功'
            ];
            
        } catch (Exception $e) {
            Logger::error('获取商品颜色列表异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId
            ]);
            
            return [
                'success' => false,
                'data' => [],
                'message' => '获取颜色列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 添加商品颜色
     * 
     * @param array $colorData 颜色数据
     * @return array 添加结果
     */
    public function addGoodsColor($colorData)
    {
        try {
            // 验证必填字段
            if (empty($colorData['goods_id'])) {
                throw new Exception('商品ID不能为空');
            }
            if (empty($colorData['color_name'])) {
                throw new Exception('颜色名称不能为空');
            }
            // 开始事务
            $this->pdo->beginTransaction();
            
            try {
                // 检查颜色名称唯一性
                $this->validateColorNameUnique($colorData['goods_id'], $colorData['color_name']);
                
                // 准备插入数据
            $sql = "INSERT INTO custom_goods_colors 
                    (goods_id, color_name, color_code, color_hex, svg_image_path, sort_order, stock_status, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
                
                $params = [
                    $colorData['goods_id'],
                    $colorData['color_name'],
                    $colorData['color_code'] ?? '',
                    $colorData['color_hex'] ?? '#000000',
                    $colorData['svg_image_path'] ?? '',
                $colorData['sort_order'] ?? 0,
                isset($colorData['stock_status']) ? (int)$colorData['stock_status'] : 1
                ];
                
                $colorId = $this->pdo->insert($sql, $params);
                
                if (!$colorId) {
                    throw new Exception('插入数据失败');
                }
                
                // 提交事务
                $this->pdo->commit();
                
                Logger::info('添加商品颜色成功', [
                    'color_id' => $colorId,
                    'goods_id' => $colorData['goods_id'],
                    'color_name' => $colorData['color_name']
                ]);
                
                return [
                    'success' => true,
                    'data' => ['color_id' => $colorId],
                    'message' => '颜色添加成功'
                ];
                
            } catch (Exception $e) {
                // 回滚事务
                $this->pdo->rollback();
                throw $e; // 重新抛出异常
            }
            
        } catch (Exception $e) {
            Logger::error('添加商品颜色异常', [
                'error' => $e->getMessage(),
                'color_data' => $colorData
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => '添加颜色失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 验证颜色名称唯一性
     * 
     * @param int $goodsId 商品ID
     * @param string $colorName 颜色名称
     * @throws Exception 如果颜色名称已存在
     */
    private function validateColorNameUnique($goodsId, $colorName)
    {
        $sql = "SELECT COUNT(*) as cnt FROM custom_goods_colors 
                WHERE goods_id = ? AND color_name = ? AND status = 1";
        
        $params = [$goodsId, $colorName];
        $result = $this->pdo->fetch($sql, $params);
        
        if ($result && $result['cnt'] > 0) {
            throw new Exception('该商品已存在同名颜色："' . $colorName . '"');
        }
    }

    /**
     * 删除商品颜色
     * 
     * @param int $colorId 颜色ID
     * @return array 删除结果
     */
    public function deleteGoodsColor($colorId)
    {
        try {
            // 软删除：设置status为0
            $sql = "UPDATE custom_goods_colors SET status = 0, updated_at = NOW() WHERE color_id = ?";
            $params = [$colorId];
            $result = $this->pdo->execute($sql, $params);
            
            if ($result) {
                Logger::info('删除商品颜色成功', ['color_id' => $colorId]);
                
                return [
                    'success' => true,
                    'message' => '颜色删除成功'
                ];
            } else {
                throw new Exception('删除操作失败');
            }
            
        } catch (Exception $e) {
            Logger::error('删除商品颜色异常', [
                'error' => $e->getMessage(),
                'color_id' => $colorId
            ]);
            
            return [
                'success' => false,
                'message' => '删除颜色失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新商品颜色
     * 
     * @param int $colorId 颜色ID
     * @param array $colorData 更新数据
     * @return array 更新结果
     */
    public function updateGoodsColor($colorId, $colorData)
    {
        try {
            $updateFields = [];
            $params = [];
            
            if (isset($colorData['color_name'])) {
                $updateFields[] = "color_name = ?";
                $params[] = $colorData['color_name'];
            }
            if (isset($colorData['color_code'])) {
                $updateFields[] = "color_code = ?";
                $params[] = $colorData['color_code'];
            }
            if (isset($colorData['color_hex'])) {
                $updateFields[] = "color_hex = ?";
                $params[] = $colorData['color_hex'];
            }
            if (isset($colorData['svg_image_path'])) {
                $updateFields[] = "svg_image_path = ?";
                $params[] = $colorData['svg_image_path'];
            }
            if (isset($colorData['sort_order'])) {
                $updateFields[] = "sort_order = ?";
                $params[] = $colorData['sort_order'];
            }
            if (isset($colorData['stock_status'])) {
                $updateFields[] = "stock_status = ?";
                $params[] = (int)$colorData['stock_status'] ? 1 : 0;
            }
            
            if (empty($updateFields)) {
                throw new Exception('没有要更新的字段');
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $colorId;
            
            $sql = "UPDATE custom_goods_colors SET " . implode(', ', $updateFields) . " WHERE color_id = ?";
            $result = $this->pdo->execute($sql, $params);
            
            if ($result) {
                Logger::info('更新商品颜色成功', [
                    'color_id' => $colorId,
                    'update_data' => $colorData
                ]);
                
                return [
                    'success' => true,
                    'message' => '颜色更新成功'
                ];
            } else {
                throw new Exception('更新操作失败');
            }
            
        } catch (Exception $e) {
            Logger::error('更新商品颜色异常', [
                'error' => $e->getMessage(),
                'color_id' => $colorId,
                'color_data' => $colorData
            ]);
            
            return [
                'success' => false,
                'message' => '更新颜色失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新颜色库存状态
     *
     * @param int $colorId
     * @param int $stockStatus 1=재고있음,0=품절
     * @return array
     */
    public function updateColorStockStatus($colorId, $stockStatus)
    {
        try {
            $stockStatus = (int)$stockStatus ? 1 : 0;
            $sql = "UPDATE custom_goods_colors SET stock_status = ?, updated_at = NOW() WHERE color_id = ?";
            $result = $this->pdo->execute($sql, [$stockStatus, $colorId]);
            if ($result) {
                Logger::info('更新颜色库存状态成功', [
                    'color_id' => $colorId,
                    'stock_status' => $stockStatus,
                ]);
                return [
                    'success' => true,
                    'message' => '재고 상태가 업데이트되었습니다',
                ];
            }
            throw new Exception('更新库存状态失败');
        } catch (Exception $e) {
            Logger::error('更新颜色库存状态异常', [
                'error' => $e->getMessage(),
                'color_id' => $colorId,
                'stock_status' => $stockStatus,
            ]);
            return [
                'success' => false,
                'message' => '재고 상태 업데이트 실패: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 获取商品定制化状态
     * 
     * @param int $goodsId 商品ID
     * @return int 定制化状态 (0: 未启用, 1: 已启用)
     */
    public function getCustomizationStatus($goodsId)
    {
        try {
            $sql = "SELECT is_customizable FROM custom_goods_extension WHERE goods_id = ? LIMIT 1";
            $result = $this->pdo->fetch($sql, [$goodsId]);
            
            // 如果记录不存在，返回默认值 0（未启用）
            if ($result === false || $result === null) {
                Logger::info('商品定制化状态不存在，返回默认值', [
                    'goods_id' => $goodsId,
                    'default_status' => 0
                ]);
                return 0;
            }
            
            return (int)$result['is_customizable'];
            
        } catch (Exception $e) {
            Logger::error('获取商品定制化状态异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId
            ]);
            // 出错时返回默认值 0
            return 0;
        }
    }

    /**
     * 更新商品定制化状态
     * 
     * @param int $goodsId 商品ID
     * @param int $isCustomizable 定制化状态 (0: 未启用, 1: 已启用)
     * @return bool 更新是否成功
     */
    public function updateCustomizationStatus($goodsId, $isCustomizable)
    {
        try {
            // 检查记录是否存在
            $checkSql = "SELECT goods_id FROM custom_goods_extension WHERE goods_id = ? LIMIT 1";
            $exists = $this->pdo->fetch($checkSql, [$goodsId]);
            
            if ($exists) {
                // 更新现有记录
                $sql = "UPDATE custom_goods_extension 
                        SET is_customizable = ?, updated_at = NOW() 
                        WHERE goods_id = ?";
                $params = [$isCustomizable, $goodsId];
                
                Logger::info('更新商品定制化状态', [
                    'goods_id' => $goodsId,
                    'is_customizable' => $isCustomizable,
                    'operation' => 'update'
                ]);
            } else {
                // 插入新记录
                $sql = "INSERT INTO custom_goods_extension 
                        (goods_id, is_customizable, created_at, updated_at) 
                        VALUES (?, ?, NOW(), NOW())";
                $params = [$goodsId, $isCustomizable];
                
                Logger::info('插入商品定制化状态', [
                    'goods_id' => $goodsId,
                    'is_customizable' => $isCustomizable,
                    'operation' => 'insert'
                ]);
            }
            
            $result = $this->pdo->execute($sql, $params);
            
            if ($result) {
                Logger::info('商品定制化状态更新成功', [
                    'goods_id' => $goodsId,
                    'is_customizable' => $isCustomizable
                ]);
                return true;
            } else {
                Logger::warning('商品定制化状态更新失败', [
                    'goods_id' => $goodsId,
                    'is_customizable' => $isCustomizable
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            Logger::error('更新商品定制化状态异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId,
                'is_customizable' => $isCustomizable
            ]);
            return false;
        }
    }

    /**
     * 获取商品自定义尺寸
     * 
     * @param int $goodsId 商品ID
     * @return array 尺寸数据
     */
    public function getCustomSize($goodsId)
    {
        try {
            $sql = "SELECT custom_width, custom_height FROM custom_goods_extension WHERE goods_id = ? LIMIT 1";
            $result = $this->pdo->fetch($sql, [$goodsId]);
            
            // 如果记录不存在，返回默认值
            if ($result === false || $result === null) {
                Logger::info('商品自定义尺寸不存在，返回默认值', [
                    'goods_id' => $goodsId
                ]);
                return [
                    'custom_width' => null,
                    'custom_height' => null
                ];
            }
            
            return [
                'custom_width' => $result['custom_width'],
                'custom_height' => $result['custom_height']
            ];
            
        } catch (Exception $e) {
            Logger::error('获取商品自定义尺寸异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId
            ]);
            // 出错时返回默认值
            return [
                'custom_width' => null,
                'custom_height' => null
            ];
        }
    }

    /**
     * 更新商品自定义尺寸
     * 
     * @param int $goodsId 商品ID
     * @param float|null $customWidth 自定义宽度（厘米）
     * @param float|null $customHeight 自定义高度（厘米）
     * @return bool 更新是否成功
     */
    public function updateCustomSize($goodsId, $customWidth, $customHeight)
    {
        try {
            // 检查记录是否存在
            $checkSql = "SELECT goods_id FROM custom_goods_extension WHERE goods_id = ? LIMIT 1";
            $exists = $this->pdo->fetch($checkSql, [$goodsId]);
            
            if ($exists) {
                // 更新现有记录
                $sql = "UPDATE custom_goods_extension 
                        SET custom_width = ?, custom_height = ?, updated_at = NOW() 
                        WHERE goods_id = ?";
                $params = [$customWidth, $customHeight, $goodsId];
                
                Logger::info('更新商品自定义尺寸', [
                    'goods_id' => $goodsId,
                    'custom_width' => $customWidth,
                    'custom_height' => $customHeight,
                    'operation' => 'update'
                ]);
            } else {
                // 插入新记录
                $sql = "INSERT INTO custom_goods_extension 
                        (goods_id, custom_width, custom_height, created_at, updated_at) 
                        VALUES (?, ?, ?, NOW(), NOW())";
                $params = [$goodsId, $customWidth, $customHeight];
                
                Logger::info('插入商品自定义尺寸', [
                    'goods_id' => $goodsId,
                    'custom_width' => $customWidth,
                    'custom_height' => $customHeight,
                    'operation' => 'insert'
                ]);
            }
            
            $result = $this->pdo->execute($sql, $params);
            
            if ($result) {
                Logger::info('商品自定义尺寸更新成功', [
                    'goods_id' => $goodsId,
                    'custom_width' => $customWidth,
                    'custom_height' => $customHeight
                ]);
                return true;
            } else {
                Logger::warning('商品自定义尺寸更新失败', [
                    'goods_id' => $goodsId,
                    'custom_width' => $customWidth,
                    'custom_height' => $customHeight
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            Logger::error('更新商品自定义尺寸异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId,
                'custom_width' => $customWidth,
                'custom_height' => $customHeight
            ]);
            return false;
        }
    }

    /**
     * 获取商品작업 가이드路径
     *
     * @param int $goodsId 商品ID
     * @return string|null 작업 가이드路径
     */
    public function getWorkGuidePath($goodsId)
    {
        try {
            $sql = "SELECT work_guide_path FROM custom_goods_extension WHERE goods_id = ? LIMIT 1";
            $result = $this->pdo->fetch($sql, [$goodsId]);

            if ($result === false || $result === null) {
                Logger::info('작업 가이드记录不存在，返回NULL', ['goods_id' => $goodsId]);
                return null;
            }

            return $result['work_guide_path'] ?: null;
        } catch (Exception $e) {
            Logger::error('获取작업 가이드路径异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId
            ]);
            return null;
        }
    }

    /**
     * 更新商品작업 가이드路径
     *
     * @param int $goodsId 商品ID
     * @param string|null $workGuidePath 작업 가이드路径
     * @return bool 更新结果
     */
    public function updateWorkGuidePath($goodsId, $workGuidePath)
    {
        try {
            $checkSql = "SELECT goods_id FROM custom_goods_extension WHERE goods_id = ? LIMIT 1";
            $exists = $this->pdo->fetch($checkSql, [$goodsId]);

            if ($exists) {
                $sql = "UPDATE custom_goods_extension SET work_guide_path = ?, updated_at = NOW() WHERE goods_id = ?";
                $params = [$workGuidePath, $goodsId];
                Logger::info('更新작업 가이드路径', [
                    'goods_id' => $goodsId,
                    'work_guide_path' => $workGuidePath,
                    'operation' => 'update'
                ]);
            } else {
                $sql = "INSERT INTO custom_goods_extension (goods_id, work_guide_path, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
                $params = [$goodsId, $workGuidePath];
                Logger::info('插入작업 가이드记录', [
                    'goods_id' => $goodsId,
                    'work_guide_path' => $workGuidePath,
                    'operation' => 'insert'
                ]);
            }

            $result = $this->pdo->execute($sql, $params);

            if ($result) {
                Logger::info('작업 가이드路径更新成功', [
                    'goods_id' => $goodsId,
                    'work_guide_path' => $workGuidePath
                ]);
                return true;
            }

            Logger::warning('작업 가이드路径更新失败', [
                'goods_id' => $goodsId,
                'work_guide_path' => $workGuidePath
            ]);
            return false;
        } catch (Exception $e) {
            Logger::error('更新작업 가이드路径异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId,
                'work_guide_path' => $workGuidePath
            ]);
            return false;
        }
    }
}

