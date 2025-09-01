<?php

/**
 * 商品定制组件
 * 
 * 本组件负责商品定制功能的业务逻辑处理，包括商品定制属性配置、图案关联管理等功能
 * 严格遵循Godomall开发规范，基于继承实现功能扩展
 * 
 * @package Component\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Component\Custom;

use Component\Storage\CustomStorage;
use Component\Database\CustomTableField;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\DateTimeUtils;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 商品定制组件
 */
class CustomGoods extends \Component\AbstractComponent
{
    /**
     * 数据库连接对象
     * @var object
     */
    private $db;

    /**
     * 表结构扩展对象
     * @var CustomTableField
     */
    private $tableField;

    /**
     * 定制图案组件
     * @var CustomPattern
     */
    private $customPattern;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = \App::load('DB');
        $this->tableField = new CustomTableField();
        $this->customPattern = new CustomPattern();
    }

    /**
     * 设置商品定制属性
     * 
     * @param int $goodsNo 商品编号
     * @param array $customData 定制数据
     * @return array 设置结果
     */
    public function setGoodsCustomizable($goodsNo, $customData)
    {
        try {
            // 验证商品是否存在
            if (!$this->isGoodsExists($goodsNo)) {
                throw new AlertBackException('商品不存在');
            }

            // 验证数据
            $this->validateCustomData($customData);

            // 准备更新数据
            $updateData = [
                'is_customizable' => $customData['is_customizable'] ? 1 : 0
            ];

            // 更新商品表
            $result = $this->updateGoodsCustomField($goodsNo, $updateData);

            if ($result) {
                Logger::info('商品定制属性设置成功', [
                    'goods_no' => $goodsNo,
                    'is_customizable' => $updateData['is_customizable']
                ]);

                return [
                    'success' => true,
                    'message' => '商品定制属性设置成功'
                ];
            } else {
                throw new AlertBackException('数据库更新失败');
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('设置商品定制属性异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo,
                'data' => $customData
            ]);
            throw new AlertBackException('设置商品定制属性失败：' . $e->getMessage());
        }
    }

    /**
     * 获取商品定制属性
     * 
     * @param int $goodsNo 商品编号
     * @return array|false 定制属性或false
     */
    public function getGoodsCustomizable($goodsNo)
    {
        try {
            $sql = "SELECT is_customizable FROM es_goods WHERE goodsNo = ?";
            $result = $this->db->query($sql, [$goodsNo]);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return [
                    'is_customizable' => (bool)$row['is_customizable']
                ];
            }

            return false;

        } catch (Exception $e) {
            Logger::error('获取商品定制属性异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return false;
        }
    }

    /**
     * 设置商品定制图案
     * 
     * @param int $goodsNo 商品编号
     * @param array $patternIds 图案ID数组
     * @return array 设置结果
     */
    public function setGoodsPatterns($goodsNo, $patternIds)
    {
        try {
            // 验证商品是否存在
            if (!$this->isGoodsExists($goodsNo)) {
                throw new AlertBackException('商品不存在');
            }

            // 验证图案ID
            if (!is_array($patternIds)) {
                $patternIds = [$patternIds];
            }

            // 验证图案是否存在且启用
            foreach ($patternIds as $patternId) {
                $pattern = $this->customPattern->getPatternById($patternId);
                if (!$pattern) {
                    throw new AlertBackException("图案ID {$patternId} 不存在");
                }
                if ($pattern['status'] != 1) {
                    throw new AlertBackException("图案ID {$patternId} 已禁用");
                }
            }

            // 删除现有关联
            $this->deleteGoodsPatterns($goodsNo);

            // 添加新关联
            $successCount = 0;
            foreach ($patternIds as $patternId) {
                $result = $this->insertGoodsPattern($goodsNo, $patternId);
                if ($result) {
                    $successCount++;
                }
            }

            Logger::info('商品定制图案设置成功', [
                'goods_no' => $goodsNo,
                'pattern_ids' => $patternIds,
                'success_count' => $successCount
            ]);

            return [
                'success' => true,
                'message' => "成功设置 {$successCount} 个图案",
                'data' => [
                    'goods_no' => $goodsNo,
                    'pattern_count' => $successCount
                ]
            ];

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('设置商品定制图案异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo,
                'pattern_ids' => $patternIds ?? []
            ]);
            throw new AlertBackException('设置商品定制图案失败：' . $e->getMessage());
        }
    }

    /**
     * 获取商品定制图案
     * 
     * @param int $goodsNo 商品编号
     * @param array $params 查询参数
     * @return array 图案列表
     */
    public function getGoodsPatterns($goodsNo, $params = [])
    {
        try {
            $defaultParams = [
                'status' => 1, // 只获取启用的图案
                'sort_field' => 'gcp.id',
                'sort_order' => 'ASC'
            ];

            $params = array_merge($defaultParams, $params);

            $sql = "SELECT cp.*, gcp.id as relation_id 
                    FROM goods_custom_pattern gcp 
                    INNER JOIN custom_pattern cp ON gcp.pattern_id = cp.pattern_id 
                    WHERE gcp.goods_id = ?";

            $bindParams = [$goodsNo];

            if ($params['status'] !== null) {
                $sql .= " AND cp.status = ?";
                $bindParams[] = $params['status'];
            }

            $sql .= " ORDER BY {$params['sort_field']} {$params['sort_order']}";

            $result = $this->db->query($sql, $bindParams);

            $patterns = [];
            while ($row = $result->fetch_assoc()) {
                $patterns[] = $row;
            }

            return [
                'success' => true,
                'data' => [
                    'patterns' => $patterns,
                    'total' => count($patterns)
                ]
            ];

        } catch (Exception $e) {
            Logger::error('获取商品定制图案异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo,
                'params' => $params
            ]);

            return [
                'success' => false,
                'message' => '获取商品定制图案失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 删除商品定制图案
     * 
     * @param int $goodsNo 商品编号
     * @param array $patternIds 图案ID数组（可选，不传则删除所有）
     * @return array 删除结果
     */
    public function deleteGoodsPatterns($goodsNo, $patternIds = [])
    {
        try {
            $sql = "DELETE FROM goods_custom_pattern WHERE goods_id = ?";
            $bindParams = [$goodsNo];

            if (!empty($patternIds)) {
                $placeholders = str_repeat('?,', count($patternIds) - 1) . '?';
                $sql .= " AND pattern_id IN ({$placeholders})";
                $bindParams = array_merge($bindParams, $patternIds);
            }

            $result = $this->db->query($sql, $bindParams);

            if ($result !== false) {
                $affectedRows = $this->db->affected_rows();
                
                Logger::info('商品定制图案删除成功', [
                    'goods_no' => $goodsNo,
                    'pattern_ids' => $patternIds,
                    'affected_rows' => $affectedRows
                ]);

            return [
                    'success' => true,
                    'message' => "成功删除 {$affectedRows} 个关联",
                    'data' => [
                        'affected_rows' => $affectedRows
                    ]
                ];
            } else {
                throw new AlertBackException('数据库删除失败');
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('删除商品定制图案异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo,
                'pattern_ids' => $patternIds
            ]);
            throw new AlertBackException('删除商品定制图案失败：' . $e->getMessage());
        }
    }

    /**
     * 获取可选图案库（用于商品注册时选择）
     * 
     * @param array $params 查询参数
     * @return array 图案列表
     */
    public function getAvailablePatterns($params = [])
    {
        try {
            $defaultParams = [
                'page' => 1,
                'limit' => 50,
                'category' => null,
                'group' => null,
                'keyword' => null,
                'status' => 1,
                'sort_field' => 'create_time',
                'sort_order' => 'DESC'
            ];

            $params = array_merge($defaultParams, $params);

            // 构建查询条件
            $where = ['cp.status = ?'];
            $bindParams = [$params['status']];

            if ($params['category']) {
                $where[] = "cp.pattern_category = ?";
                $bindParams[] = $params['category'];
            }

            if ($params['group']) {
                $where[] = "cp.pattern_group = ?";
                $bindParams[] = $params['group'];
            }

            if ($params['keyword']) {
                $where[] = "(cp.pattern_name LIKE ? OR cp.pattern_category LIKE ? OR cp.pattern_group LIKE ?)";
                $keyword = '%' . $params['keyword'] . '%';
                $bindParams[] = $keyword;
                $bindParams[] = $keyword;
                $bindParams[] = $keyword;
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            // 计算总数
            $countSql = "SELECT COUNT(*) as total FROM custom_pattern cp {$whereClause}";
            $countResult = $this->db->query($countSql, $bindParams);
            $total = $countResult->fetch_assoc()['total'];

            // 计算分页
            $offset = ($params['page'] - 1) * $params['limit'];
            $limit = $params['limit'];

            // 查询数据
            $sql = "SELECT cp.* FROM custom_pattern cp {$whereClause} 
                    ORDER BY cp.{$params['sort_field']} {$params['sort_order']} 
                    LIMIT {$offset}, {$limit}";

            $result = $this->db->query($sql, $bindParams);

            $patterns = [];
            while ($row = $result->fetch_assoc()) {
                $patterns[] = $row;
            }

            return [
                'success' => true,
                'data' => [
                    'patterns' => $patterns,
                    'total' => $total,
                    'page' => $params['page'],
                    'limit' => $params['limit'],
                    'total_pages' => ceil($total / $params['limit'])
                ]
            ];

        } catch (Exception $e) {
            Logger::error('获取可选图案库异常', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);

            return [
                'success' => false,
                'message' => '获取可选图案库失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量设置商品定制图案
     * 
     * @param array $goodsNos 商品编号数组
     * @param array $patternIds 图案ID数组
     * @return array 批量设置结果
     */
    public function batchSetGoodsPatterns($goodsNos, $patternIds)
    {
        try {
            if (empty($goodsNos) || !is_array($goodsNos)) {
                throw new AlertBackException('商品编号不能为空');
            }

            if (empty($patternIds) || !is_array($patternIds)) {
                throw new AlertBackException('图案ID不能为空');
            }

            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($goodsNos as $goodsNo) {
                try {
                    $result = $this->setGoodsPatterns($goodsNo, $patternIds);
                    if ($result['success']) {
                        $successCount++;
            } else {
                        $failedCount++;
                        $errors[] = "商品 {$goodsNo}: " . $result['message'];
                    }
                } catch (Exception $e) {
                    $failedCount++;
                    $errors[] = "商品 {$goodsNo}: " . $e->getMessage();
                }
            }

            Logger::info('批量设置商品定制图案完成', [
                'total' => count($goodsNos),
                'success' => $successCount,
                'failed' => $failedCount
            ]);

            return [
                'success' => true,
                'data' => [
                    'total' => count($goodsNos),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors
                ],
                'message' => "批量设置完成：成功 {$successCount} 个，失败 {$failedCount} 个"
            ];

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('批量设置商品定制图案异常', [
                'error' => $e->getMessage(),
                'goods_nos' => $goodsNos ?? [],
                'pattern_ids' => $patternIds ?? []
            ]);
            throw new AlertBackException('批量设置失败：' . $e->getMessage());
        }
    }

    /**
     * 检查商品是否支持定制
     * 
     * @param int $goodsNo 商品编号
     * @return bool 支持定制返回true，不支持返回false
     */
    public function isGoodsCustomizable($goodsNo)
    {
        try {
            $customData = $this->getGoodsCustomizable($goodsNo);
            return $customData ? $customData['is_customizable'] : false;

        } catch (Exception $e) {
            Logger::error('检查商品定制支持异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return false;
        }
    }

    /**
     * 获取商品定制信息（前台使用）
     * 
     * @param int $goodsNo 商品编号
     * @return array 定制信息
     */
    public function getGoodsCustomInfo($goodsNo)
    {
        try {
            // 检查商品是否存在
            if (!$this->isGoodsExists($goodsNo)) {
            return [
                    'success' => false,
                    'message' => '商品不存在'
                ];
            }

            // 获取定制属性
            $customData = $this->getGoodsCustomizable($goodsNo);
            if (!$customData || !$customData['is_customizable']) {
                return [
                    'success' => false,
                    'message' => '该商品不支持定制'
                ];
            }

            // 获取定制图案
            $patternsResult = $this->getGoodsPatterns($goodsNo, ['status' => 1]);
            
            if (!$patternsResult['success']) {
                return [
                    'success' => false,
                    'message' => '获取定制图案失败'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'goods_no' => $goodsNo,
                    'is_customizable' => true,
                    'patterns' => $patternsResult['data']['patterns'],
                    'pattern_count' => $patternsResult['data']['total']
                ]
            ];

        } catch (Exception $e) {
            Logger::error('获取商品定制信息异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);

            return [
                'success' => false,
                'message' => '获取商品定制信息失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 验证定制数据
     * 
     * @param array $customData 定制数据
     * @throws AlertBackException
     */
    private function validateCustomData($customData)
    {
        if (!isset($customData['is_customizable'])) {
            throw new AlertBackException('定制属性不能为空');
        }

        if (!is_bool($customData['is_customizable']) && !in_array($customData['is_customizable'], [0, 1])) {
            throw new AlertBackException('定制属性值无效');
        }
    }

    /**
     * 检查商品是否存在
     * 
     * @param int $goodsNo 商品编号
     * @return bool 存在返回true，不存在返回false
     */
    private function isGoodsExists($goodsNo)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM es_goods WHERE goodsNo = ? AND delFl = 'n'";
            $result = $this->db->query($sql, [$goodsNo]);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['count'] > 0;
            }

            return false;

        } catch (Exception $e) {
            Logger::error('检查商品存在性异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return false;
        }
    }

    /**
     * 更新商品定制字段
     * 
     * @param int $goodsNo 商品编号
     * @param array $updateData 更新数据
     * @return bool 更新成功返回true，失败返回false
     */
    private function updateGoodsCustomField($goodsNo, $updateData)
    {
        try {
            $sql = "UPDATE es_goods SET is_customizable = ? WHERE goodsNo = ?";
            $result = $this->db->query($sql, [$updateData['is_customizable'], $goodsNo]);

            return $result !== false;

        } catch (Exception $e) {
            Logger::error('更新商品定制字段异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo,
                'update_data' => $updateData
            ]);
            return false;
        }
    }

    /**
     * 插入商品图案关联
     * 
     * @param int $goodsNo 商品编号
     * @param int $patternId 图案ID
     * @return bool 插入成功返回true，失败返回false
     */
    private function insertGoodsPattern($goodsNo, $patternId)
    {
        try {
            $sql = "INSERT INTO goods_custom_pattern (goods_id, pattern_id, create_time) VALUES (?, ?, NOW())";
            $result = $this->db->query($sql, [$goodsNo, $patternId]);

            return $result !== false;

        } catch (Exception $e) {
            Logger::error('插入商品图案关联异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo,
                'pattern_id' => $patternId
            ]);
            return false;
        }
    }

    /**
     * 添加定制商品到购物车
     * 
     * @param array $cartData 购物车数据
     * @return array 添加结果
     */
    public function addCustomGoodsToCart($cartData)
    {
        try {
            // 验证商品是否存在
            if (!$this->isGoodsExists($cartData['goods_no'])) {
                throw new AlertBackException('商品不存在');
            }

            // 验证用户登录状态
            if (empty($cartData['user_id'])) {
                throw new AlertBackException('请先登录');
            }

            // 验证数量
            if (empty($cartData['quantity']) || $cartData['quantity'] < 1) {
                throw new AlertBackException('商品数量无效');
            }

            // 处理定制图片数据
            $customizationData = $cartData['customization_data'];
            if (is_array($customizationData) && !empty($customizationData['customImage'])) {
                // 保存定制图片文件
                $imageResult = $this->saveCustomImageFile(
                    $customizationData['customImage'],
                    $cartData['user_id'],
                    $cartData['goods_no']
                );
                
                if ($imageResult['success']) {
                    // 将图片URL存储到定制数据中，替换Base64数据
                    $customizationData['customImageUrl'] = $imageResult['data']['image_url'];
                    $customizationData['customImage'] = null; // 清空Base64数据，避免数据库过大
                    
                    Logger::info('定制图片保存成功，已替换为文件URL', [
                        'image_url' => $imageResult['data']['image_url'],
                        'user_id' => $cartData['user_id'],
                        'goods_no' => $cartData['goods_no']
                    ]);
            } else {
                    Logger::warning('定制图片保存失败，将使用Base64数据', [
                        'error' => $imageResult['message'],
                        'user_id' => $cartData['user_id']
                    ]);
                }
            }

            // 准备购物车数据
            $cartItemData = [
                'goods_no' => $cartData['goods_no'],
                'quantity' => $cartData['quantity'],
                'customization_data' => $customizationData ? json_encode($customizationData) : null,
                'selected_color' => $cartData['selected_color'],
                'selected_size' => $cartData['selected_size'],
                'is_customized' => 1,
                'user_id' => $cartData['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            // 插入购物车表
            $cartItemId = $this->insertCartItem($cartItemData);

            if ($cartItemId) {
                Logger::info('定制商品添加到购物车成功', [
                'cart_item_id' => $cartItemId,
                    'goods_no' => $cartData['goods_no'],
                    'user_id' => $cartData['user_id']
                ]);

            return [
                    'success' => true,
                    'message' => '定制商品已添加到购物车',
                    'data' => [
                        'cart_item_id' => $cartItemId
                    ]
                ];
            } else {
                throw new AlertBackException('添加到购物车失败');
            }

        } catch (Exception $e) {
            Logger::error('添加定制商品到购物车异常', [
                'error' => $e->getMessage(),
                'cart_data' => $cartData
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 插入购物车项目
     * 
     * @param array $cartItemData 购物车项目数据
     * @return int|false 插入的ID或false
     */
    private function insertCartItem($cartItemData)
    {
        try {
            $sql = "INSERT INTO es_cart_items (
                goods_no, quantity, customization_data, selected_color, 
                selected_size, is_customized, user_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
                $cartItemData['goods_no'],
                $cartItemData['quantity'],
                $cartItemData['customization_data'],
                $cartItemData['selected_color'],
                $cartItemData['selected_size'],
                $cartItemData['is_customized'],
                $cartItemData['user_id'],
                $cartItemData['created_at']
        ];

        $result = $this->db->query($sql, $params);
            
            return $result ? $this->db->insert_id : false;

        } catch (Exception $e) {
            Logger::error('插入购物车项目异常', [
                'error' => $e->getMessage(),
                'cart_item_data' => $cartItemData
            ]);
            return false;
        }
    }

    /**
     * 获取用户购物车中的定制商品
     * 
     * @param int $userId 用户ID
     * @return array 购物车项目列表
     */
    public function getUserCustomCartItems($userId)
    {
        try {
            $sql = "SELECT 
                        ci.id,
                        ci.goods_no,
                        ci.quantity,
                        ci.customization_data,
                        ci.selected_color,
                        ci.selected_size,
                        ci.is_customized,
                        ci.created_at,
                        g.goodsNm,
                        g.goodsPrice,
                        g.goodsImage
                    FROM es_cart_items ci
                    INNER JOIN es_goods g ON ci.goods_no = g.goodsNo
                    WHERE ci.user_id = ? AND ci.is_customized = 1 AND g.delFl = 'n'
                    ORDER BY ci.created_at DESC";

            $result = $this->db->query($sql, [$userId]);

            if ($result && $result->num_rows > 0) {
                $cartItems = [];
                while ($row = $result->fetch_assoc()) {
                    // 解析定制数据
                    if ($row['customization_data']) {
                        $row['customization_data'] = json_decode($row['customization_data'], true);
                    }
                    $cartItems[] = $row;
                }

                return [
                    'success' => true,
                    'data' => [
                        'cart_items' => $cartItems,
                        'total_count' => count($cartItems)
                    ]
                ];
            } else {
                return [
                    'success' => true,
                    'data' => [
                        'cart_items' => [],
                        'total_count' => 0
                    ]
                ];
            }

        } catch (Exception $e) {
            Logger::error('获取用户定制购物车异常', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'message' => '获取购物车失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 从购物车移除定制商品
     * 
     * @param int $cartItemId 购物车项目ID
     * @param int $userId 用户ID
     * @return array 移除结果
     */
    public function removeCustomCartItem($cartItemId, $userId)
    {
        try {
            // 验证购物车项目是否属于该用户
            if (!$this->isCartItemBelongsToUser($cartItemId, $userId)) {
                throw new AlertBackException('购物车项目不存在或无权限');
            }

            $sql = "DELETE FROM es_cart_items WHERE id = ? AND user_id = ?";
            $result = $this->db->query($sql, [$cartItemId, $userId]);

            if ($result) {
                Logger::info('定制商品从购物车移除成功', [
                    'cart_item_id' => $cartItemId,
                    'user_id' => $userId
                ]);

                return [
                    'success' => true,
                    'message' => '商品已从购物车移除'
                ];
            } else {
                throw new AlertBackException('移除购物车项目失败');
            }

        } catch (Exception $e) {
            Logger::error('移除定制购物车项目异常', [
                'error' => $e->getMessage(),
                'cart_item_id' => $cartItemId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 检查购物车项目是否属于指定用户
     * 
     * @param int $cartItemId 购物车项目ID
     * @param int $userId 用户ID
     * @return bool 属于返回true，不属于返回false
     */
    private function isCartItemBelongsToUser($cartItemId, $userId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM es_cart_items WHERE id = ? AND user_id = ?";
            $result = $this->db->query($sql, [$cartItemId, $userId]);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['count'] > 0;
            }

            return false;

        } catch (Exception $e) {
            Logger::error('检查购物车项目归属异常', [
                'error' => $e->getMessage(),
                'cart_item_id' => $cartItemId,
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * 保存定制图片文件
     * 
     * @param string $base64Data Base64图片数据
     * @param int $userId 用户ID
     * @param string $goodsNo 商品编号
     * @return array 保存结果
     */
    public function saveCustomImageFile($base64Data, $userId, $goodsNo = '')
    {
        try {
            // 验证Base64数据
            if (empty($base64Data) || !preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $base64Data)) {
                throw new AlertBackException('无效的图片数据格式');
            }

            // 提取Base64数据
            $base64String = preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $base64Data);
            $imageData = base64_decode($base64String);

            if ($imageData === false) {
                throw new AlertBackException('Base64解码失败');
            }

            // 生成文件名
            $fileName = 'custom_' . $userId . '_' . $goodsNo . '_' . time() . '.png';
            
            // 保存文件到定制图片目录
            $filePath = $this->saveImageToFile($imageData, $fileName);
            
            if (!$filePath) {
                throw new AlertBackException('图片文件保存失败');
            }

            // 生成访问URL
            $imageUrl = $this->generateImageUrl($filePath);

            Logger::info('定制图片保存成功', [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'image_url' => $imageUrl,
                'user_id' => $userId,
                'goods_no' => $goodsNo
            ]);

            return [
                'success' => true,
                'data' => [
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'image_url' => $imageUrl,
                    'file_size' => strlen($imageData)
                ],
                'message' => '定制图片保存成功'
            ];

        } catch (Exception $e) {
            Logger::error('定制图片保存失败', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'goods_no' => $goodsNo
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 将图片数据保存到文件
     * 
     * @param string $imageData 图片二进制数据
     * @param string $fileName 文件名
     * @return string|false 文件路径或false
     */
    private function saveImageToFile($imageData, $fileName)
    {
        try {
            // 创建定制图片目录
            $customImageDir = $this->getCustomImageDirectory();
            
            if (!is_dir($customImageDir)) {
                if (!mkdir($customImageDir, 0755, true)) {
                    throw new Exception('无法创建定制图片目录');
                }
            }

            // 生成完整文件路径
            $filePath = $customImageDir . '/' . $fileName;
            
            // 保存文件
            if (file_put_contents($filePath, $imageData) === false) {
                throw new Exception('文件写入失败');
            }

            return $filePath;

        } catch (Exception $e) {
            Logger::error('保存图片文件失败', [
                'error' => $e->getMessage(),
                'file_name' => $fileName
            ]);
            return false;
        }
    }

    /**
     * 获取商品信息
     * 
     * @param int $goodsId 商品ID
     * @return array|false 商品信息或false
     */
    public function getGoodsInfo($goodsId)
    {
        try {
            $sql = "SELECT goods_no, goods_name, is_customizable, default_color, default_size 
                    FROM es_goods WHERE goods_no = ?";
            $result = $this->db->query($sql, [$goodsId]);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('获取商品信息异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId
            ]);
            return false;
        }
    }



    /**
     * 添加商品定制图案
     * 在现有图案基础上添加新图案
     * 
     * @param int $goodsId 商品ID
     * @param array $patternIds 要添加的图案ID数组
     * @return array 添加结果
     */
    public function addGoodsPatterns($goodsId, $patternIds)
    {
        try {
            if (empty($patternIds)) {
                return [
                    'success' => true,
                    'message' => '没有需要添加的图案',
                    'data' => ['pattern_count' => 0]
                ];
            }
            
            // 获取现有图案
            $existingPatterns = $this->getGoodsPatterns($goodsId);
            $existingPatternIds = [];
            
            if ($existingPatterns['success']) {
                foreach ($existingPatterns['data']['patterns'] as $pattern) {
                    $existingPatternIds[] = $pattern['pattern_id'];
                }
            }
            
            // 过滤掉已存在的图案
            $newPatternIds = array_diff($patternIds, $existingPatternIds);
            
            if (empty($newPatternIds)) {
                return [
                    'success' => true,
                    'message' => '所有图案都已存在',
                    'data' => ['pattern_count' => count($existingPatternIds)]
                ];
            }
            
            // 插入新图案关联
            $insertSql = "INSERT INTO goods_custom_pattern (goods_id, pattern_id, created_at) VALUES (?, ?, NOW())";
            $insertedCount = 0;
            
            foreach ($newPatternIds as $patternId) {
                if ($this->db->query($insertSql, [$goodsId, $patternId])) {
                    $insertedCount++;
                }
            }
            
            Logger::info('商品图案添加成功', [
                'goods_id' => $goodsId,
                'added_count' => $insertedCount,
                'new_pattern_ids' => $newPatternIds
            ]);
            
            return [
                'success' => true,
                'message' => '商品图案添加成功',
                'data' => [
                    'added_count' => $insertedCount,
                    'total_count' => count($existingPatternIds) + $insertedCount,
                    'new_pattern_ids' => $newPatternIds
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error('添加商品定制图案异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId,
                'pattern_ids' => $patternIds
            ]);
            
            return [
                'success' => false,
                'message' => '商品图案添加失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 移除商品定制图案
     * 从现有图案中移除指定图案
     * 
     * @param int $goodsId 商品ID
     * @param array $patternIds 要移除的图案ID数组
     * @return string 移除结果
     */
    public function removeGoodsPatterns($goodsId, $patternIds)
    {
        try {
            if (empty($patternIds)) {
                return [
                    'success' => true,
                    'message' => '没有需要移除的图案',
                    'data' => ['pattern_count' => 0]
                ];
            }
            
            // 删除指定图案关联
            $deleteSql = "DELETE FROM goods_custom_pattern WHERE goods_id = ? AND pattern_id IN (" . 
                        str_repeat('?,', count($patternIds) - 1) . '?)';
            
            $params = array_merge([$goodsId], $patternIds);
            $result = $this->db->query($deleteSql, $params);
            
            $removedCount = $this->db->affected_rows();
            
            Logger::info('商品图案移除成功', [
                'goods_id' => $goodsId,
                'removed_count' => $removedCount,
                'pattern_ids' => $patternIds
            ]);
            
            return [
                'success' => true,
                'message' => '商品图案移除成功',
                'data' => [
                    'removed_count' => $removedCount,
                    'pattern_ids' => $patternIds
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error('移除商品定制图案异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId,
                'pattern_ids' => $patternIds
            ]);
            
            return [
                'success' => false,
                'message' => '商品图案移除失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }



    /**
     * 获取定制图片目录
     * 
     * @return string 目录路径
     */
    private function getCustomImageDirectory()
    {
        $baseDir = dirname(dirname(__DIR__)) . '/data/custom_images';
        return $baseDir;
    }

    /**
     * 生成图片访问URL
     * 
     * @param string $filePath 文件路径
     * @return string 图片URL
     */
    private function generateImageUrl($filePath)
    {
        // 从文件路径中提取相对路径
        $baseDir = $this->getCustomImageDirectory();
        $relativePath = str_replace($baseDir, '', $filePath);
        
        // 生成访问URL（这里需要根据实际部署环境调整）
        $baseUrl = '/data/custom_images' . $relativePath;
        
        return $baseUrl;
    }

    /**
     * 删除定制图片文件
     * 
     * @param string $imageUrl 图片URL
     * @return bool 删除结果
     */
    public function deleteCustomImageFile($imageUrl)
    {
        try {
            // 从URL中提取文件路径
            $baseDir = $this->getCustomImageDirectory();
            $relativePath = str_replace('/data/custom_images', '', $imageUrl);
            $filePath = $baseDir . $relativePath;
            
            // 检查文件是否存在
            if (!file_exists($filePath)) {
                Logger::warning('要删除的图片文件不存在', ['file_path' => $filePath]);
                return true; // 文件不存在也算删除成功
            }
            
            // 删除文件
            if (unlink($filePath)) {
                Logger::info('定制图片文件删除成功', ['file_path' => $filePath]);
                return true;
            } else {
                throw new Exception('文件删除失败');
            }

        } catch (Exception $e) {
            Logger::error('删除定制图片文件失败', [
                'error' => $e->getMessage(),
                'image_url' => $imageUrl
            ]);
            return false;
        }
    }
} 