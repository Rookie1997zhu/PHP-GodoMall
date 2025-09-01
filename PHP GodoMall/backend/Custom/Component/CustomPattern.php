<?php

/**
 * 定制图案管理组件
 * 
 * 本组件负责定制图案的业务逻辑处理，包括图案的增删改查、分类管理等功能
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
 * 定制图案管理组件
 */
class CustomPattern extends \Component\AbstractComponent
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
     * 添加定制图案
     * 
     * @param array $patternData 图案数据
     * @return array 添加结果
     */
    public function addPattern($patternData)
    {
        try {
            // 验证必填字段
            $this->validatePatternData($patternData);
            
            // 验证文件
            if (!isset($patternData['file_path']) || !CustomStorage::validateImageFile($patternData['file_path'])) {
                throw new AlertBackException('无效的图片文件');
            }
            
            // 上传文件
            $fileUrl = CustomStorage::uploadCustomPattern(
                $patternData['file_path'],
                $patternData['file_name'],
                $patternData['upload_options'] ?? []
            );
            
            if (!$fileUrl) {
                throw new AlertBackException('文件上传失败');
            }
            
            // 准备数据库数据
            $dbData = [
                'pattern_name' => $patternData['pattern_name'],
                'pattern_url' => $fileUrl,
                'pattern_category' => $patternData['pattern_category'] ?? null,
                'pattern_group' => $patternData['pattern_group'] ?? null,
                'uploader' => $patternData['uploader'] ?? 'admin',
                'file_size' => filesize($patternData['file_path']),
                'file_type' => pathinfo($patternData['file_name'], PATHINFO_EXTENSION),
                'status' => $patternData['status'] ?? 1
            ];
            
            // 插入数据库
            $patternId = $this->insertPattern($dbData);
            
            if ($patternId) {
                Logger::info('定制图案添加成功', [
                    'pattern_id' => $patternId,
                    'pattern_name' => $dbData['pattern_name']
                ]);
                
                return [
                    'success' => true,
                    'pattern_id' => $patternId,
                    'message' => '图案添加成功'
                ];
            } else {
                throw new AlertBackException('数据库插入失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案添加异常', [
                'error' => $e->getMessage(),
                'data' => $patternData
            ]);
            throw new AlertBackException('图案添加失败：' . $e->getMessage());
        }
    }

    /**
     * 更新定制图案
     * 
     * @param int $patternId 图案ID
     * @param array $patternData 图案数据
     * @return array 更新结果
     */
    public function updatePattern($patternId, $patternData)
    {
        try {
            // 检查图案是否存在
            $existingPattern = $this->getPatternById($patternId);
            if (!$existingPattern) {
                throw new AlertBackException('图案不存在');
            }
            
            // 验证数据
            $this->validatePatternData($patternData, false);
            
            // 准备更新数据
            $updateData = [];
            
            if (isset($patternData['pattern_name'])) {
                $updateData['pattern_name'] = $patternData['pattern_name'];
            }
            
            if (isset($patternData['pattern_category'])) {
                $updateData['pattern_category'] = $patternData['pattern_category'];
            }
            
            if (isset($patternData['pattern_group'])) {
                $updateData['pattern_group'] = $patternData['pattern_group'];
            }
            
            if (isset($patternData['status'])) {
                $updateData['status'] = $patternData['status'];
            }
            
            // 如果有新文件上传
            if (isset($patternData['file_path']) && isset($patternData['file_name'])) {
                if (!CustomStorage::validateImageFile($patternData['file_path'])) {
                    throw new AlertBackException('无效的图片文件');
                }
                
                // 上传新文件
                $newFileUrl = CustomStorage::uploadCustomPattern(
                    $patternData['file_path'],
                    $patternData['file_name'],
                    $patternData['upload_options'] ?? []
                );
                
                if (!$newFileUrl) {
                    throw new AlertBackException('新文件上传失败');
                }
                
                $updateData['pattern_url'] = $newFileUrl;
                $updateData['file_size'] = filesize($patternData['file_path']);
                $updateData['file_type'] = pathinfo($patternData['file_name'], PATHINFO_EXTENSION);
                
                // 删除旧文件
                $oldFileName = basename($existingPattern['pattern_url']);
                CustomStorage::deleteCustomPattern($oldFileName);
            }
            
            if (empty($updateData)) {
                throw new AlertBackException('没有需要更新的数据');
            }
            
            // 更新数据库
            $result = $this->updatePatternInDb($patternId, $updateData);
            
            if ($result) {
                Logger::info('定制图案更新成功', [
                    'pattern_id' => $patternId,
                    'update_data' => $updateData
                ]);
                
                return [
                    'success' => true,
                    'message' => '图案更新成功'
                ];
            } else {
                throw new AlertBackException('数据库更新失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案更新异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId,
                'data' => $patternData
            ]);
            throw new AlertBackException('图案更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除定制图案
     * 
     * @param int $patternId 图案ID
     * @return array 删除结果
     */
    public function deletePattern($patternId)
    {
        try {
            // 检查图案是否存在
            $pattern = $this->getPatternById($patternId);
            if (!$pattern) {
                throw new AlertBackException('图案不存在');
            }
            
            // 检查是否被商品使用
            if ($this->isPatternUsedByGoods($patternId)) {
                throw new AlertBackException('该图案正在被商品使用，无法删除');
            }
            
            // 删除文件
            $fileName = basename($pattern['pattern_url']);
            CustomStorage::deleteCustomPattern($fileName);
            
            // 删除数据库记录
            $result = $this->deletePatternFromDb($patternId);
            
            if ($result) {
                Logger::info('定制图案删除成功', [
                    'pattern_id' => $patternId,
                    'pattern_name' => $pattern['pattern_name']
                ]);
                
                return [
                    'success' => true,
                    'message' => '图案删除成功'
                ];
            } else {
                throw new AlertBackException('数据库删除失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案删除异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            throw new AlertBackException('图案删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取图案列表
     * 
     * @param array $params 查询参数
     * @return array 图案列表
     */
    public function getPatternList($params = [])
    {
        try {
            $defaultParams = [
                'page' => 1,
                'limit' => 20,
                'category' => null,
                'group' => null,
                'status' => null,
                'keyword' => null,
                'sort_field' => 'create_time',
                'sort_order' => 'DESC'
            ];
            
            $params = array_merge($defaultParams, $params);
            
            // 构建查询条件
            $where = [];
            $bindParams = [];
            
            if ($params['category']) {
                $where[] = "pattern_category = ?";
                $bindParams[] = $params['category'];
            }
            
            if ($params['group']) {
                $where[] = "pattern_group = ?";
                $bindParams[] = $params['group'];
            }
            
            if ($params['status'] !== null) {
                $where[] = "status = ?";
                $bindParams[] = $params['status'];
            }
            
            if ($params['keyword']) {
                $where[] = "(pattern_name LIKE ? OR pattern_category LIKE ? OR pattern_group LIKE ?)";
                $keyword = '%' . $params['keyword'] . '%';
                $bindParams[] = $keyword;
                $bindParams[] = $keyword;
                $bindParams[] = $keyword;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // 计算总数
            $countSql = "SELECT COUNT(*) as total FROM custom_pattern {$whereClause}";
            $countResult = $this->db->query($countSql, $bindParams);
            $total = $countResult->fetch_assoc()['total'];
            
            // 计算分页
            $offset = ($params['page'] - 1) * $params['limit'];
            $limit = $params['limit'];
            
            // 查询数据
            $sql = "SELECT * FROM custom_pattern {$whereClause} 
                    ORDER BY {$params['sort_field']} {$params['sort_order']} 
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
            Logger::error('获取定制图案列表异常', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => '获取图案列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 根据ID获取图案
     * 
     * @param int $patternId 图案ID
     * @return array|false 图案数据或false
     */
    public function getPatternById($patternId)
    {
        try {
            $sql = "SELECT * FROM custom_pattern WHERE pattern_id = ?";
            $result = $this->db->query($sql, [$patternId]);
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('根据ID获取定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            return false;
        }
    }

    /**
     * 获取图案分类列表
     * 
     * @return array 分类列表
     */
    public function getPatternCategories()
    {
        try {
            $sql = "SELECT DISTINCT pattern_category FROM custom_pattern 
                    WHERE pattern_category IS NOT NULL AND pattern_category != '' 
                    ORDER BY pattern_category";
            
            $result = $this->db->query($sql);
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['pattern_category'];
            }
            
            return [
                'success' => true,
                'data' => $categories
            ];
            
        } catch (Exception $e) {
            Logger::error('获取定制图案分类异常', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => '获取分类失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取图案分组列表
     * 
     * @return array 分组列表
     */
    public function getPatternGroups()
    {
        try {
            $sql = "SELECT DISTINCT pattern_group FROM custom_pattern 
                    WHERE pattern_group IS NOT NULL AND pattern_group != '' 
                    ORDER BY pattern_group";
            
            $result = $this->db->query($sql);
            
            $groups = [];
            while ($row = $result->fetch_assoc()) {
                $groups[] = $row['pattern_group'];
            }
            
            return [
                'success' => true,
                'data' => $groups
            ];
            
        } catch (Exception $e) {
            Logger::error('获取定制图案分组异常', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => '获取分组失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量删除图案
     * 
     * @param array $patternIds 图案ID数组
     * @return array 删除结果
     */
    public function batchDeletePatterns($patternIds)
    {
        try {
            if (empty($patternIds)) {
                throw new AlertBackException('请选择要删除的图案');
            }
            
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            
            foreach ($patternIds as $patternId) {
                try {
                    $result = $this->deletePattern($patternId);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "图案ID {$patternId}: " . $result['message'];
                    }
                } catch (Exception $e) {
                    $failedCount++;
                    $errors[] = "图案ID {$patternId}: " . $e->getMessage();
                }
            }
            
            Logger::info('批量删除定制图案完成', [
                'total' => count($patternIds),
                'success' => $successCount,
                'failed' => $failedCount
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'total' => count($patternIds),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors
                ],
                'message' => "批量删除完成：成功 {$successCount} 个，失败 {$failedCount} 个"
            ];
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('批量删除定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_ids' => $patternIds
            ]);
            throw new AlertBackException('批量删除失败：' . $e->getMessage());
        }
    }

    /**
     * 验证图案数据
     * 
     * @param array $patternData 图案数据
     * @param bool $isNew 是否新增
     * @throws AlertBackException
     */
    private function validatePatternData($patternData, $isNew = true)
    {
        if ($isNew) {
            if (empty($patternData['pattern_name'])) {
                throw new AlertBackException('图案名称不能为空');
            }
            
            if (strlen($patternData['pattern_name']) > 100) {
                throw new AlertBackException('图案名称不能超过100个字符');
            }
        }
        
        if (isset($patternData['pattern_name']) && empty($patternData['pattern_name'])) {
            throw new AlertBackException('图案名称不能为空');
        }
        
        if (isset($patternData['pattern_category']) && strlen($patternData['pattern_category']) > 50) {
            throw new AlertBackException('图案分类不能超过50个字符');
        }
        
        if (isset($patternData['pattern_group']) && strlen($patternData['pattern_group']) > 50) {
            throw new AlertBackException('图案分组不能超过50个字符');
        }
    }

    /**
     * 插入图案到数据库
     * 
     * @param array $data 图案数据
     * @return int|false 插入成功返回ID，失败返回false
     */
    private function insertPattern($data)
    {
        try {
            $sql = "INSERT INTO custom_pattern (pattern_name, pattern_url, pattern_category, pattern_group, 
                    uploader, file_size, file_type, status, create_time, update_time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [
                $data['pattern_name'],
                $data['pattern_url'],
                $data['pattern_category'],
                $data['pattern_group'],
                $data['uploader'],
                $data['file_size'],
                $data['file_type'],
                $data['status']
            ];
            
            $result = $this->db->query($sql, $params);
            
            if ($result) {
                return $this->db->insert_id();
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('插入定制图案到数据库异常', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * 更新数据库中的图案
     * 
     * @param int $patternId 图案ID
     * @param array $data 更新数据
     * @return bool 更新成功返回true，失败返回false
     */
    private function updatePatternInDb($patternId, $data)
    {
        try {
            $setParts = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                $setParts[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $setParts[] = "update_time = NOW()";
            
            $sql = "UPDATE custom_pattern SET " . implode(', ', $setParts) . " WHERE pattern_id = ?";
            $params[] = $patternId;
            
            $result = $this->db->query($sql, $params);
            
            return $result !== false;
            
        } catch (Exception $e) {
            Logger::error('更新数据库中的定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * 从数据库删除图案
     * 
     * @param int $patternId 图案ID
     * @return bool 删除成功返回true，失败返回false
     */
    private function deletePatternFromDb($patternId)
    {
        try {
            $sql = "DELETE FROM custom_pattern WHERE pattern_id = ?";
            $result = $this->db->query($sql, [$patternId]);
            
            return $result !== false;
            
        } catch (Exception $e) {
            Logger::error('从数据库删除定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            return false;
        }
    }

    /**
     * 检查图案是否被商品使用
     * 
     * @param int $patternId 图案ID
     * @return bool 被使用返回true，未使用返回false
     */
    private function isPatternUsedByGoods($patternId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM goods_custom_pattern WHERE pattern_id = ?";
            $result = $this->db->query($sql, [$patternId]);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['count'] > 0;
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('检查图案是否被商品使用异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            return false;
        }
    }
} 