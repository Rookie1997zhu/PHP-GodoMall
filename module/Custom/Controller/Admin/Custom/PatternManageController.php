<?php
/**
 * 图案管理控制器
 * 
 * @package Custom\Controller\Admin\Custom
 * @author Custom Developer
 * @since 2024-01-01
 */

namespace Custom\Controller\Admin\Custom;

use Exception;
use Godomall\Bundle\Component\Admin\AdminController;
use Godomall\Bundle\Component\Admin\AdminComponent;
use Godomall\Bundle\Component\Admin\AdminRequest;
use Godomall\Bundle\Component\Admin\AdminResponse;
use Godomall\Bundle\Component\Admin\AdminSession;
use Godomall\Bundle\Component\Admin\AdminCookie;
use Godomall\Bundle\Component\Admin\AdminDatabase;
use Godomall\Bundle\Component\Admin\AdminFile;
use Godomall\Bundle\Component\Admin\AdminValidation;
use Godomall\Bundle\Component\Admin\AdminSecurity;
use Godomall\Bundle\Component\Admin\AdminLog;
use Custom\Component\CustomPattern;

/**
 * 图案管理控制器
 */
class PatternManageController extends AdminController
{
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
        $this->customPattern = new CustomPattern();
    }

    /**
     * 图案管理页面
     */
    public function index()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.view');
            
            // 获取页面数据
            $data = $this->getPageData();
            
            // 渲染页面
            $this->render('custom/pattern_manage', $data);
            
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * 获取图案列表API
     */
    public function getPatternListAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.view');
            
            // 获取请求参数（统一使用接口文档命名）
            $params = [
                'page' => max(1, intval($this->request->get('page', 1))),
                'page_size' => max(1, min(100, intval($this->request->get('page_size', 20)))),  // ✅ 统一命名
                'keyword' => trim($this->request->get('keyword', '')),                          // ✅ 统一命名
                'category' => trim($this->request->get('category', '')),
                'status' => $this->request->get('status', ''),
                'sort_by' => 'created_at',
                'sort_order' => 'DESC'
            ];
            
            // 使用CustomPattern组件获取数据（包含缓存）
            $result = $this->customPattern->getPatternListForAPI($params);
            
            if ($result['success']) {
                // 格式化数据以匹配前端期望
                $formattedPatterns = [];
                foreach ($result['data']['patterns'] as $pattern) {
                    $formattedPatterns[] = [
                        'id' => $pattern['pattern_id'],
                        'name' => $pattern['pattern_name'],
                        'thumb' => $this->generateThumbnailUrl($pattern['pattern_url']),
                        'category' => $pattern['category'],
                        'group' => $pattern['tags'] ?? '',
                        'uploader' => 'admin', // 从pattern数据中获取
                        'size' => $this->formatFileSize(0), // 从pattern数据中获取
                        'status' => $pattern['status'],
                        'created_at' => $pattern['created_at']
                    ];
                }
                
                // 返回成功响应
                $this->jsonResponse([
                    'success' => true,
                    'data' => $formattedPatterns,
                    'pagination' => $result['data']['pagination']
                ]);
            } else {
                $this->jsonError($result['message']);
            }
            
        } catch (Exception $e) {
            $this->jsonError('获取图案列表失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取图案信息API
     */
    public function getPatternAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.view');
            
            // 获取请求参数
            $patternId = intval($this->request->get('pattern_id', 0));
            if ($patternId <= 0) {
                throw new Exception('无效的图案ID');
            }
            
            // 使用CustomPattern组件获取图案信息（包含缓存）
            $pattern = $this->customPattern->getPatternById($patternId);
            if (!$pattern) {
                throw new Exception('图案不存在');
            }
            
            // 格式化数据
            $formattedPattern = [
                'id' => $pattern['pattern_id'],
                'name' => $pattern['pattern_name'],
                'pattern_url' => $pattern['pattern_url'],
                'category' => $pattern['category'],
                'group' => $pattern['pattern_group'],
                'description' => $pattern['description'],
                'uploader' => $pattern['uploader'],
                'file_size' => $pattern['file_size'],
                'file_type' => $pattern['file_type'],
                'status' => $pattern['status'],
                'created_at' => $pattern['created_at'],
                'updated_at' => $pattern['updated_at']
            ];
            
            // 返回成功响应
            $this->jsonResponse([
                'success' => true,
                'data' => $formattedPattern
            ]);
            
        } catch (Exception $e) {
            $this->jsonError('获取图案信息失败：' . $e->getMessage());
        }
    }
    
    /**
     * 图案上传API
     */
    public function uploadPatternAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.upload');
            
            // 验证CSRF令牌
            $this->validateCsrfToken();
            
            // 获取上传文件
            $uploadedFile = $this->request->files()->get('pattern_file');
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败');
            }
            
            // 验证文件
            $this->validateUploadedFile($uploadedFile);
            
            // 获取表单数据
            $patternData = [
                'pattern_name' => trim($this->request->post()->get('pattern_name', '')),
                'category' => trim($this->request->post()->get('category', '')),
                'pattern_group' => trim($this->request->post()->get('pattern_group', '')),
                'description' => trim($this->request->post()->get('description', '')),
                'status' => intval($this->request->post()->get('status', 1)),
                'uploader' => $this->session->get('admin_id') ?? 'admin',
                'file_path' => $uploadedFile['tmp_name'],
                'file_name' => $uploadedFile['name']
            ];
            
            // 验证必填字段
            if (empty($patternData['pattern_name'])) {
                throw new Exception('图案名称不能为空');
            }
            
            // 检查图案名称是否重复
            if ($this->isPatternNameExists($patternData['pattern_name'])) {
                throw new Exception('图案名称已存在');
            }
            
            // 上传文件
            $uploadResult = $this->uploadPatternFile($uploadedFile);
            
            // 保存图案信息到数据库
            $patternId = $this->savePatternToDatabase($patternData, $uploadResult);
            
            // 记录操作日志
            $this->logAction('pattern_upload', [
                'pattern_id' => $patternId,
                'pattern_name' => $patternData['pattern_name'],
                'file_name' => $uploadedFile['name']
            ]);
            
            // 返回成功响应
            $this->jsonResponse([
                'success' => true,
                'message' => '图案上传成功',
                'data' => [
                    'pattern_id' => $patternId,
                    'pattern_url' => $uploadResult['url']
                ]
            ]);
            
        } catch (Exception $e) {
            $this->jsonError('图案上传失败：' . $e->getMessage());
        }
    }
    
    /**
     * 更新图案API
     */
    public function updatePatternAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.edit');
            
            // 验证CSRF令牌
            $this->validateCsrfToken();
            
            // 获取请求参数
            $patternId = intval($this->request->post()->get('pattern_id', 0));
            if ($patternId <= 0) {
                throw new Exception('无效的图案ID');
            }
            
            // 检查图案是否存在
            $existingPattern = $this->getPatternById($patternId);
            if (!$existingPattern) {
                throw new Exception('图案不存在');
            }
            
            // 获取更新数据
            $updateData = [
                'pattern_name' => trim($this->request->post()->get('pattern_name', '')),
                'category' => trim($this->request->post()->get('category', '')),
                'pattern_group' => trim($this->request->post()->get('pattern_group', '')),
                'description' => trim($this->request->post()->get('description', '')),
                'status' => intval($this->request->post()->get('status', 1)),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 验证必填字段
            if (empty($updateData['pattern_name'])) {
                throw new Exception('图案名称不能为空');
            }
            
            // 检查图案名称是否重复（排除当前图案）
            if ($this->isPatternNameExists($updateData['pattern_name'], $patternId)) {
                throw new Exception('图案名称已存在');
            }
            
            // 更新数据库
            $this->updatePatternInDatabase($patternId, $updateData);
            
            // 记录操作日志
            $this->logAction('pattern_update', [
                'pattern_id' => $patternId,
                'pattern_name' => $updateData['pattern_name']
            ]);
            
            // 返回成功响应
            $this->jsonResponse([
                'success' => true,
                'message' => '图案更新成功'
            ]);
            
        } catch (Exception $e) {
            $this->jsonError('图案更新失败：' . $e->getMessage());
        }
    }
    
    /**
     * 删除图案API
     */
    public function deletePatternAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.delete');
            
            // 验证CSRF令牌
            $this->validateCsrfToken();
            
            // 获取请求参数
            $patternId = intval($this->request->post()->get('pattern_id', 0));
            if ($patternId <= 0) {
                throw new Exception('无效的图案ID');
            }
            
            // 检查图案是否存在
            $existingPattern = $this->getPatternById($patternId);
            if (!$existingPattern) {
                throw new Exception('图案不存在');
            }
            
            // 检查图案是否被使用
            if ($this->isPatternInUse($patternId)) {
                throw new Exception('图案正在被使用，无法删除');
            }
            
            // 删除文件
            $this->deletePatternFile($existingPattern['pattern_url']);
            
            // 删除数据库记录
            $this->deletePatternFromDatabase($patternId);
            
            // 记录操作日志
            $this->logAction('pattern_delete', [
                'pattern_id' => $patternId,
                'pattern_name' => $existingPattern['pattern_name']
            ]);
            
            // 返回成功响应
            $this->jsonResponse([
                'success' => true,
                'message' => '图案删除成功'
            ]);
            
        } catch (Exception $e) {
            $this->jsonError('图案删除失败：' . $e->getMessage());
        }
    }
    
    /**
     * 批量更新图案状态API
     */
    public function batchUpdatePatternStatusAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.edit');
            
            // 验证CSRF令牌
            $this->validateCsrfToken();
            
            // 获取请求参数
            $patternIds = $this->request->post()->get('pattern_ids', []);
            $status = intval($this->request->post()->get('status', 1));
            
            if (empty($patternIds) || !is_array($patternIds)) {
                throw new Exception('请选择要操作的图案');
            }
            
            // 验证状态值
            if (!in_array($status, [0, 1])) {
                throw new Exception('无效的状态值');
            }
            
            // 批量更新状态
            $this->batchUpdatePatternStatus($patternIds, $status);
            
            // 记录操作日志
            $statusText = $status == 1 ? '启用' : '禁用';
            $this->logAction('pattern_batch_status_update', [
                'pattern_ids' => $patternIds,
                'status' => $status,
                'status_text' => $statusText
            ]);
            
            // 返回成功响应
            $this->jsonResponse([
                'success' => true,
                'message' => "批量{$statusText}成功"
            ]);
            
        } catch (Exception $e) {
            $this->jsonError('批量操作失败：' . $e->getMessage());
        }
    }
    
    /**
     * 批量删除图案API
     */
    public function batchDeletePatternsAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.delete');
            
            // 验证CSRF令牌
            $this->validateCsrfToken();
            
            // 获取请求参数
            $patternIds = $this->request->post()->get('pattern_ids', []);
            
            if (empty($patternIds) || !is_array($patternIds)) {
                throw new Exception('请选择要删除的图案');
            }
            
            // 使用CustomPattern组件进行批量删除（包含缓存清理）
            $result = $this->customPattern->batchDeletePatterns($patternIds);
            
            if ($result['success']) {
                // 记录操作日志
                $this->logAction('pattern_batch_delete', [
                    'pattern_ids' => $patternIds,
                    'result' => $result['data']
                ]);
                
                // 返回成功响应
                $this->jsonResponse([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                throw new Exception($result['message']);
            }
            
        } catch (Exception $e) {
            $this->jsonError('批量删除失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取图案分类列表API
     */
    public function getPatternCategoriesAPI()
    {
        try {
            // 检查权限
            $this->checkPermission('custom.pattern.view');
            
            // 获取分类列表
            $sql = "SELECT id, category_name, parent_id, sort_order, status FROM custom_pattern_category WHERE status = 1 ORDER BY sort_order ASC, id ASC";
            $categories = $this->db->query_fetch_all($sql);
            
            // 格式化分类数据
            $formattedCategories = [];
            foreach ($categories as $category) {
                $formattedCategories[] = [
                    'id' => $category['id'],
                    'name' => $category['category_name'],
                    'parent_id' => $category['parent_id'],
                    'sort_order' => $category['sort_order'],
                    'status' => $category['status']
                ];
            }
            
            // 返回成功响应
            $this->jsonResponse([
                'success' => true,
                'data' => $formattedCategories
            ]);
            
        } catch (Exception $e) {
            $this->jsonError('获取分类列表失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取页面数据
     */
    private function getPageData()
    {
        // 获取图案分类列表
        $categories = [];
        try {
            $sql = "SELECT id, category_name FROM custom_pattern_category WHERE status = 1 ORDER BY sort_order ASC, id ASC";
            $categories = $this->db->query_fetch_all($sql);
        } catch (Exception $e) {
            // 忽略错误，使用空数组
        }
        
        return [
            'categories' => $categories
        ];
    }
    
    /**
     * 验证上传文件
     */
    private function validateUploadedFile($file)
    {
        // 检查文件大小（5MB）
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('文件大小不能超过5MB');
        }
        
        // 检查文件类型
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('不支持的文件类型，请选择JPEG、PNG、GIF或SVG格式的图片');
        }
        
        // 检查文件扩展名
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('不支持的文件扩展名');
        }
    }
    
    /**
     * 上传图案文件
     */
    private function uploadPatternFile($file)
    {
        // 创建上传目录
        $uploadDir = 'data/custom_images/patterns/' . date('Y/m/d');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 生成唯一文件名
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('pattern_') . '.' . $extension;
        $filePath = $uploadDir . '/' . $fileName;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('文件保存失败');
        }
        
        return [
            'url' => $filePath,
            'path' => $filePath,
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }
    
    /**
     * 保存图案到数据库
     */
    private function savePatternToDatabase($patternData, $uploadResult)
    {
        $sql = "
            INSERT INTO custom_pattern (
                pattern_name, pattern_url, category, pattern_group, 
                description, uploader, file_size, file_type, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        
        $params = [
            $patternData['pattern_name'],
            $uploadResult['url'],
            $patternData['category'] ?: null,
            $patternData['pattern_group'] ?: null,
            $patternData['description'] ?: null,
            $patternData['uploader'],
            $uploadResult['size'],
            $uploadResult['type'],
            $patternData['status']
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * 检查图案名称是否存在
     */
    private function isPatternNameExists($patternName, $excludeId = 0)
    {
        $sql = "SELECT COUNT(*) as count FROM custom_pattern WHERE pattern_name = ?";
        $params = [$patternName];
        
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->query_fetch($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * 根据ID获取图案
     */
    private function getPatternById($patternId)
    {
        $sql = "SELECT * FROM custom_pattern WHERE id = ?";
        return $this->db->query_fetch($sql, [$patternId]);
    }
    
    /**
     * 更新图案数据库记录
     */
    private function updatePatternInDatabase($patternId, $updateData)
    {
        $sql = "
            UPDATE custom_pattern SET 
                pattern_name = ?, category = ?, pattern_group = ?, 
                description = ?, status = ?, updated_at = ?
            WHERE id = ?
        ";
        
        $params = [
            $updateData['pattern_name'],
            $updateData['category'] ?: null,
            $updateData['pattern_group'] ?: null,
            $updateData['description'] ?: null,
            $updateData['status'],
            $updateData['updated_at'],
            $patternId
        ];
        
        $this->db->query($sql, $params);
    }
    
    /**
     * 检查图案是否被使用
     */
    private function isPatternInUse($patternId)
    {
        // 检查是否在商品定制图案关联表中被使用
        $sql = "SELECT COUNT(*) as count FROM goods_custom_pattern WHERE pattern_id = ?";
        $result = $this->db->query_fetch($sql, [$patternId]);
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * 删除图案文件
     */
    private function deletePatternFile($fileUrl)
    {
        if (file_exists($fileUrl)) {
            unlink($fileUrl);
        }
    }
    
    /**
     * 从数据库删除图案
     */
    private function deletePatternFromDatabase($patternId)
    {
        $sql = "DELETE FROM custom_pattern WHERE id = ?";
        $this->db->query($sql, [$patternId]);
    }
    
    /**
     * 批量更新图案状态
     */
    private function batchUpdatePatternStatus($patternIds, $status)
    {
        $placeholders = str_repeat('?,', count($patternIds) - 1) . '?';
        $sql = "UPDATE custom_pattern SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})";
        
        $params = array_merge([$status], $patternIds);
        $this->db->query($sql, $params);
    }
    
    /**
     * 获取被使用的图案
     */
    private function getUsedPatterns($patternIds)
    {
        $placeholders = str_repeat('?,', count($patternIds) - 1) . '?';
        $sql = "
            SELECT p.id, p.pattern_name 
            FROM custom_pattern p
            INNER JOIN goods_custom_pattern gcp ON p.id = gcp.pattern_id
            WHERE p.id IN ({$placeholders})
        ";
        
        return $this->db->query_fetch_all($sql, $patternIds);
    }
    
    /**
     * 批量删除图案
     */
    private function batchDeletePatterns($patternIds)
    {
        // 获取要删除的图案信息
        $placeholders = str_repeat('?,', count($patternIds) - 1) . '?';
        $sql = "SELECT id, pattern_url FROM custom_pattern WHERE id IN ({$placeholders})";
        $patterns = $this->db->query_fetch_all($sql, $patternIds);
        
        // 删除文件
        foreach ($patterns as $pattern) {
            $this->deletePatternFile($pattern['pattern_url']);
        }
        
        // 删除数据库记录
        $sql = "DELETE FROM custom_pattern WHERE id IN ({$placeholders})";
        $this->db->query($sql, $patternIds);
    }
    
    /**
     * 验证CSRF令牌
     */
    private function validateCsrfToken()
    {
        $token = $this->request->headers()->get('X-CSRF-TOKEN');
        if (!$token) {
            $token = $this->request->post()->get('_token');
        }
        
        if (!$token || !$this->session->get('csrf_token') || $token !== $this->session->get('csrf_token')) {
            throw new Exception('CSRF令牌验证失败');
        }
    }
    
    /**
     * 检查权限
     */
    private function checkPermission($permission)
    {
        // 这里应该实现具体的权限检查逻辑
        // 暂时跳过权限检查，在实际使用时需要实现
        return true;
    }
    
    /**
     * 记录操作日志
     */
    private function logAction($action, $data = [])
    {
        // 这里应该实现具体的日志记录逻辑
        // 暂时跳过日志记录，在实际使用时需要实现
    }
    
    /**
     * 返回JSON响应
     */
    private function jsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 返回JSON错误响应
     */
    private function jsonError($message, $code = 400)
    {
        http_response_code($code);
        $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'code' => $code
        ]);
    }
    
    /**
     * 处理错误
     */
    private function handleError($e)
    {
        // 记录错误日志
        error_log("PatternManageController Error: " . $e->getMessage());
        
        // 显示错误页面
        $this->render('error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    /**
     * 生成缩略图URL
     */
    private function generateThumbnailUrl($originalUrl)
    {
        if (empty($originalUrl)) {
            return '/assets/images/no-image.png';
        }
        
        // 生成缩略图路径
        $pathInfo = pathinfo($originalUrl);
        $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        
        // 检查缩略图是否存在，不存在则返回原图
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $thumbnailPath)) {
            return $thumbnailPath;
        }
        
        return $originalUrl;
    }
    
    /**
     * 格式化文件大小
     */
    private function formatFileSize($bytes)
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $factor), 2) . ' ' . $units[$factor];
    }
}
