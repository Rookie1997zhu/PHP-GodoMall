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
} 