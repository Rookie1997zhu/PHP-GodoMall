<?php

/**
 * 图案分类管理组件
 * 
 * 本组件负责图案分类的业务逻辑处理，包括分类的增删改查、排序和层级管理等功能
 * 严格遵循Godomall开发规范，基于继承实现功能扩展
 * 
 * @package Custom\Component
 * @author Custom Development Team
 * @version 1.0
 */

namespace Custom\Component;

use Bundle\Component\Category\Category;
use Custom\Component\Database\CustomTableField;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\DateTimeUtils;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 图案分类管理组件
 * 继承官方分类组件，获得完整的分类管理功能
 */
class CustomPatternCategory extends Category
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
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = \App::load('DB');
        $this->tableField = new CustomTableField();
    }

    /**
     * 获取分类列表
     * 
     * @param array $params 查询参数
     * @return array 分类列表
     */
    public function getCategoryList($params = [])
    {
        try {
            $defaultParams = [
                'page' => 1,
                'limit' => 20,
                'keyword' => '',
                'status' => '',
                'parent_id' => '',
                'sort_field' => 'sort_order',
                'sort_order' => 'ASC'
            ];
            
            $params = array_merge($defaultParams, $params);
            
            // 构建查询条件
            $where = [];
            $bindParams = [];
            
            // 状态筛选
            if ($params['status'] !== '') {
                $where[] = "status = ?";
                $bindParams[] = $params['status'];
            }
            
            // 父分类筛选
            if ($params['parent_id'] !== '') {
                $where[] = "parent_id = ?";
                $bindParams[] = $params['parent_id'];
            }
            
            // 关键词搜索
            if (!empty($params['keyword'])) {
                $searchTerm = '%' . $params['keyword'] . '%';
                $where[] = "(category_name LIKE ? OR category_description LIKE ?)";
                $bindParams[] = $searchTerm;
                $bindParams[] = $searchTerm;
            }
            
            $whereCondition = empty($where) ? '1=1' : implode(' AND ', $where);
            
            // 获取总数
            $countSql = "SELECT COUNT(*) as total FROM custom_pattern_category WHERE {$whereCondition}";
            $countResult = $this->db->query($countSql, $bindParams);
            $total = $countResult[0]['total'] ?? 0;
            
            // 计算分页
            $page = max(1, $params['page']);
            $limit = max(1, min(100, $params['limit']));
            $offset = ($page - 1) * $limit;
            $totalPages = ceil($total / $limit);
            
            // 获取分类列表
            $listSql = "
                SELECT 
                    c1.category_id,
                    c1.category_name,
                    c1.category_description,
                    c1.parent_id,
                    c1.sort_order,
                    c1.status,
                    c1.created_at,
                    c1.updated_at,
                    c2.category_name as parent_name,
                    (SELECT COUNT(*) FROM custom_pattern WHERE category_id = c1.category_id AND status = 1) as pattern_count
                FROM custom_pattern_category c1
                LEFT JOIN custom_pattern_category c2 ON c1.parent_id = c2.category_id
                WHERE {$whereCondition}
                ORDER BY {$params['sort_field']} {$params['sort_order']}
                LIMIT {$limit} OFFSET {$offset}
            ";
            
            $categories = $this->db->query($listSql, $bindParams);
            
            // 格式化数据
            $formattedCategories = $this->formatCategoryList($categories);
            
            return [
                'success' => true,
                'message' => '分类列表获取成功',
                'data' => [
                    'categories' => $formattedCategories,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error('获取分类列表异常', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => '获取分类列表失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 获取分类树形结构
     * 
     * @return array 分类树
     */
    public function getCategoryTree()
    {
        try {
            // 获取所有分类
            $sql = "
                SELECT 
                    category_id,
                    category_name,
                    category_description,
                    parent_id,
                    sort_order,
                    status,
                    created_at
                FROM custom_pattern_category 
                WHERE status = 1
                ORDER BY sort_order ASC, category_id ASC
            ";
            
            $categories = $this->db->query($sql);
            
            // 构建树形结构
            $categoryTree = $this->buildCategoryTree($categories);
            
            return [
                'success' => true,
                'message' => '分类树获取成功',
                'data' => [
                    'category_tree' => $categoryTree
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error('获取分类树异常', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => '获取分类树失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 添加分类
     * 
     * @param array $categoryData 分类数据
     * @return array 添加结果
     */
    public function addCategory($categoryData)
    {
        try {
            // 验证必填字段
            if (empty($categoryData['category_name'])) {
                throw new AlertBackException('分类名称不能为空');
            }
            
            // 检查分类名称是否重复
            $existingCategory = $this->checkCategoryNameExists($categoryData['category_name'], $categoryData['parent_id']);
            if ($existingCategory) {
                throw new AlertBackException('该分类名称已存在');
            }
            
            // 验证父分类
            if ($categoryData['parent_id'] > 0) {
                $parentCategory = $this->getCategoryById($categoryData['parent_id']);
                if (!$parentCategory['success'] || !$parentCategory['data']) {
                    throw new AlertBackException('父分类不存在');
                }
            }
            
            // 准备插入数据
            $insertData = [
                'category_name' => $categoryData['category_name'],
                'category_description' => $categoryData['category_description'] ?? '',
                'parent_id' => $categoryData['parent_id'] ?? 0,
                'sort_order' => $categoryData['sort_order'] ?? 0,
                'status' => $categoryData['status'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 插入数据库
            $categoryId = $this->insertCategory($insertData);
            
            if ($categoryId) {
                Logger::info('分类添加成功', [
                    'category_id' => $categoryId,
                    'category_name' => $insertData['category_name']
                ]);
                
                return [
                    'success' => true,
                    'message' => '分类添加成功',
                    'data' => [
                        'category_id' => $categoryId,
                        'category_name' => $insertData['category_name']
                    ]
                ];
            } else {
                throw new AlertBackException('分类添加失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('分类添加异常', [
                'error' => $e->getMessage(),
                'category_data' => $categoryData
            ]);
            
            return [
                'success' => false,
                'message' => '分类添加失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 更新分类
     * 
     * @param int $categoryId 分类ID
     * @param array $categoryData 分类数据
     * @return array 更新结果
     */
    public function updateCategory($categoryId, $categoryData)
    {
        try {
            // 验证分类是否存在
            $existingCategory = $this->getCategoryById($categoryId);
            if (!$existingCategory['success'] || !$existingCategory['data']) {
                throw new AlertBackException('分类不存在');
            }
            
            // 验证分类名称是否重复（排除当前分类）
            if (!empty($categoryData['category_name'])) {
                $duplicateCategory = $this->checkCategoryNameExists($categoryData['category_name'], $categoryData['parent_id'] ?? 0, $categoryId);
                if ($duplicateCategory) {
                    throw new AlertBackException('该分类名称已存在');
                }
            }
            
            // 验证父分类（不能设置自己为父分类）
            if (isset($categoryData['parent_id']) && $categoryData['parent_id'] == $categoryId) {
                throw new AlertBackException('不能设置自己为父分类');
            }
            
            // 验证父分类是否存在
            if (isset($categoryData['parent_id']) && $categoryData['parent_id'] > 0) {
                $parentCategory = $this->getCategoryById($categoryData['parent_id']);
                if (!$parentCategory['success'] || !$parentCategory['data']) {
                    throw new AlertBackException('父分类不存在');
                }
            }
            
            // 准备更新数据
            $updateData = [];
            if (isset($categoryData['category_name'])) {
                $updateData['category_name'] = $categoryData['category_name'];
            }
            if (isset($categoryData['category_description'])) {
                $updateData['category_description'] = $categoryData['category_description'];
            }
            if (isset($categoryData['parent_id'])) {
                $updateData['parent_id'] = $categoryData['parent_id'];
            }
            if (isset($categoryData['sort_order'])) {
                $updateData['sort_order'] = $categoryData['sort_order'];
            }
            if (isset($categoryData['status'])) {
                $updateData['status'] = $categoryData['status'];
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            // 更新数据库
            $result = $this->updateCategoryData($categoryId, $updateData);
            
            if ($result) {
                Logger::info('分类更新成功', [
                    'category_id' => $categoryId,
                    'update_data' => $updateData
                ]);
                
                return [
                    'success' => true,
                    'message' => '分类更新成功',
                    'data' => [
                        'category_id' => $categoryId,
                        'updated_fields' => array_keys($updateData)
                    ]
                ];
            } else {
                throw new AlertBackException('分类更新失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('分类更新异常', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId,
                'category_data' => $categoryData
            ]);
            
            return [
                'success' => false,
                'message' => '分类更新失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 删除分类
     * 
     * @param int $categoryId 分类ID
     * @return array 删除结果
     */
    public function deleteCategory($categoryId)
    {
        try {
            // 验证分类是否存在
            $existingCategory = $this->getCategoryById($categoryId);
            if (!$existingCategory['success'] || !$existingCategory['data']) {
                throw new AlertBackException('分类不存在');
            }
            
            // 检查是否有子分类
            $childCategories = $this->getChildCategories($categoryId);
            if (!empty($childCategories)) {
                throw new AlertBackException('该分类下还有子分类，无法删除');
            }
            
            // 检查是否有关联的图案
            $patternCount = $this->getCategoryPatternCount($categoryId);
            if ($patternCount > 0) {
                throw new AlertBackException('该分类下还有图案，无法删除');
            }
            
            // 删除分类
            $result = $this->deleteCategoryData($categoryId);
            
            if ($result) {
                Logger::info('分类删除成功', [
                    'category_id' => $categoryId
                ]);
                
                return [
                    'success' => true,
                    'message' => '分类删除成功',
                    'data' => [
                        'category_id' => $categoryId
                    ]
                ];
            } else {
                throw new AlertBackException('分类删除失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('分类删除异常', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId
            ]);
            
            return [
                'success' => false,
                'message' => '分类删除失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 更新分类排序
     * 
     * @param array $sortData 排序数据
     * @return array 更新结果
     */
    public function updateCategorySort($sortData)
    {
        try {
            if (empty($sortData) || !is_array($sortData)) {
                throw new AlertBackException('排序数据无效');
            }
            
            // 开始事务
            $this->db->begin_transaction();
            
            try {
                foreach ($sortData as $categoryId => $sortOrder) {
                    if (!is_numeric($categoryId) || !is_numeric($sortOrder)) {
                        continue;
                    }
                    
                    $sql = "UPDATE custom_pattern_category SET sort_order = ?, updated_at = ? WHERE category_id = ?";
                    $this->db->query($sql, [$sortOrder, date('Y-m-d H:i:s'), $categoryId]);
                }
                
                // 提交事务
                $this->db->commit();
                
                Logger::info('分类排序更新成功', [
                    'sort_data' => $sortData
                ]);
                
                return [
                    'success' => true,
                    'message' => '分类排序更新成功',
                    'data' => [
                        'updated_count' => count($sortData)
                    ]
                ];
                
            } catch (Exception $e) {
                // 回滚事务
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('分类排序更新异常', [
                'error' => $e->getMessage(),
                'sort_data' => $sortData
            ]);
            
            return [
                'success' => false,
                'message' => '分类排序更新失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 根据ID获取分类信息
     * 
     * @param int $categoryId 分类ID
     * @return array 分类信息
     */
    public function getCategoryById($categoryId)
    {
        try {
            $sql = "
                SELECT 
                    c1.category_id,
                    c1.category_name,
                    c1.category_description,
                    c1.parent_id,
                    c1.sort_order,
                    c1.status,
                    c1.created_at,
                    c1.updated_at,
                    c2.category_name as parent_name
                FROM custom_pattern_category c1
                LEFT JOIN custom_pattern_category c2 ON c1.parent_id = c2.category_id
                WHERE c1.category_id = ? AND c1.status = 1
            ";
            
            $result = $this->db->query($sql, [$categoryId]);
            
            if ($result && count($result) > 0) {
                $category = $result[0];
                
                // 格式化时间
                $category['created_at_formatted'] = DateTimeUtils::formatDateTime($category['created_at']);
                $category['updated_at_formatted'] = DateTimeUtils::formatDateTime($category['updated_at']);
                
                return [
                    'success' => true,
                    'message' => '分类信息获取成功',
                    'data' => $category
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '分类不存在',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            Logger::error('获取分类信息异常', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId
            ]);
            
            return [
                'success' => false,
                'message' => '获取分类信息失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 检查分类名称是否重复
     * 
     * @param string $categoryName 分类名称
     * @param int $parentId 父分类ID
     * @param int $excludeId 排除的分类ID（用于更新时检查）
     * @return bool 是否重复
     */
    public function checkCategoryNameExists($categoryName, $parentId = 0, $excludeId = 0)
    {
        try {
            $sql = "SELECT category_id FROM custom_pattern_category WHERE category_name = ? AND parent_id = ?";
            $params = [$categoryName, $parentId];
            
            if ($excludeId > 0) {
                $sql .= " AND category_id != ?";
                $params[] = $excludeId;
            }
            
            $result = $this->db->query($sql, $params);
            
            return !empty($result);
            
        } catch (Exception $e) {
            Logger::warning('检查分类名称重复性异常', [
                'error' => $e->getMessage(),
                'category_name' => $categoryName,
                'parent_id' => $parentId
            ]);
            return false;
        }
    }

    /**
     * 获取子分类
     * 
     * @param int $parentId 父分类ID
     * @return array 子分类列表
     */
    private function getChildCategories($parentId)
    {
        try {
            $sql = "SELECT category_id FROM custom_pattern_category WHERE parent_id = ? AND status = 1";
            $result = $this->db->query($sql, [$parentId]);
            
            return $result ?: [];
            
        } catch (Exception $e) {
            Logger::warning('获取子分类异常', [
                'error' => $e->getMessage(),
                'parent_id' => $parentId
            ]);
            return [];
        }
    }

    /**
     * 获取分类下的图案数量
     * 
     * @param int $categoryId 分类ID
     * @return int 图案数量
     */
    private function getCategoryPatternCount($categoryId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM custom_pattern WHERE category_id = ? AND status = 1";
            $result = $this->db->query($sql, [$categoryId]);
            
            return $result[0]['count'] ?? 0;
            
        } catch (Exception $e) {
            Logger::warning('获取分类图案数量异常', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId
            ]);
            return 0;
        }
    }

    /**
     * 插入分类数据
     * 
     * @param array $insertData 插入数据
     * @return int|false 插入ID或false
     */
    private function insertCategory($insertData)
    {
        try {
            $sql = "
                INSERT INTO custom_pattern_category 
                (category_name, category_description, parent_id, sort_order, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            
            $params = [
                $insertData['category_name'],
                $insertData['category_description'],
                $insertData['parent_id'],
                $insertData['sort_order'],
                $insertData['status'],
                $insertData['created_at'],
                $insertData['updated_at']
            ];
            
            $result = $this->db->query($sql, $params);
            
            if ($result) {
                return $this->db->insert_id();
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('插入分类数据异常', [
                'error' => $e->getMessage(),
                'insert_data' => $insertData
            ]);
            return false;
        }
    }

    /**
     * 更新分类数据
     * 
     * @param int $categoryId 分类ID
     * @param array $updateData 更新数据
     * @return bool 是否成功
     */
    private function updateCategoryData($categoryId, $updateData)
    {
        try {
            $setParts = [];
            $params = [];
            
            foreach ($updateData as $field => $value) {
                $setParts[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $params[] = $categoryId;
            
            $sql = "UPDATE custom_pattern_category SET " . implode(', ', $setParts) . " WHERE category_id = ?";
            
            $result = $this->db->query($sql, $params);
            
            return $result !== false;
            
        } catch (Exception $e) {
            Logger::error('更新分类数据异常', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId,
                'update_data' => $updateData
            ]);
            return false;
        }
    }

    /**
     * 删除分类数据
     * 
     * @param int $categoryId 分类ID
     * @return bool 是否成功
     */
    private function deleteCategoryData($categoryId)
    {
        try {
            $sql = "DELETE FROM custom_pattern_category WHERE category_id = ?";
            $result = $this->db->query($sql, [$categoryId]);
            
            return $result !== false;
            
        } catch (Exception $e) {
            Logger::error('删除分类数据异常', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId
            ]);
            return false;
        }
    }

    /**
     * 构建分类树形结构
     * 
     * @param array $categories 分类列表
     * @return array 树形结构
     */
    private function buildCategoryTree($categories)
    {
        $categoryMap = [];
        $categoryTree = [];
        
        // 创建分类映射
        foreach ($categories as $category) {
            $categoryMap[$category['category_id']] = $category;
            $categoryMap[$category['category_id']]['children'] = [];
        }
        
        // 构建树形结构
        foreach ($categoryMap as $categoryId => $category) {
            if ($category['parent_id'] == 0) {
                // 顶级分类
                $categoryTree[] = &$categoryMap[$categoryId];
            } else {
                // 子分类
                if (isset($categoryMap[$category['parent_id']])) {
                    $categoryMap[$category['parent_id']]['children'][] = &$categoryMap[$categoryId];
                }
            }
        }
        
        return $categoryTree;
    }

    /**
     * 格式化分类列表
     * 
     * @param array $categories 原始分类数据
     * @return array 格式化后的数据
     */
    private function formatCategoryList($categories)
    {
        $formatted = [];
        
        foreach ($categories as $category) {
            $formatted[] = [
                'category_id' => (int)$category['category_id'],
                'category_name' => $category['category_name'],
                'category_description' => $category['category_description'],
                'parent_id' => (int)$category['parent_id'],
                'parent_name' => $category['parent_name'] ?: null,
                'sort_order' => (int)$category['sort_order'],
                'status' => (int)$category['status'],
                'pattern_count' => (int)$category['pattern_count'],
                'created_at' => $category['created_at'],
                'created_at_formatted' => DateTimeUtils::formatDateTime($category['created_at']),
                'updated_at' => $category['updated_at']
            ];
        }
        
        return $formatted;
    }
}
