<?php

/**
 * 定制图案管理控制器
 * 
 * 本控制器提供后台定制图案管理的接口，包括图案的增删改查、分类管理等功能
 * 严格遵循Godomall开发规范，继承官方Controller
 * 
 * @package Controller\Admin\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Admin\Custom;

use Custom\Component\CustomPattern;
use Custom\Component\Storage\CustomStorage;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 定制图案管理控制器
 */
class CustomPatternController extends \Controller\Admin\Controller
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
        try {
            // 尝试调用父类构造函数来初始化Godomall框架
            parent::__construct();
        } catch (Exception $e) {
            // 如果父类构造函数失败，记录错误但不中断执行
            error_log('Godomall框架初始化失败: ' . $e->getMessage());
        }
        
        $this->customPattern = new CustomPattern();
    }

    /**
     * 发送JSON响应
     */
    private function sendJsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 控制器入口方法
     */
    public function index()
    {
        // 获取请求参数，支持mode和action两种参数名（向后兼容）
        $action = Request::request()->get('action') ?: Request::request()->get('mode');
        
        if (empty($action)) {
            // 如果没有action参数，显示图案列表页面
            $this->showPatternListPage();
            return;
        }
        
        // 根据action调用相应的方法
        switch ($action) {
            case 'getPatternListAPI':
                $this->getPatternListAPI();
                break;
            case 'uploadPatternAPI':
                $this->uploadPatternAPI();
                break;
            case 'deletePatternAPI':
                $this->deletePatternAPI();
                break;
            case 'updatePatternStatusAPI':
                $this->updatePatternStatusAPI();
                break;
            case 'getPatternInfoAPI':
                $this->getPatternInfoAPI();
                break;
            case 'batchUpdatePatternStatusAPI':
                $this->batchUpdatePatternStatusAPI();
                break;
            case 'batchDeletePatternsAPI':
                $this->batchDeletePatternsAPI();
                break;
            case 'updatePatternAPI':
                $this->updatePatternAPI();
                break;
            default:
                $this->sendErrorResponse(400, '未知的action: ' . $action);
                break;
        }
    }

    /**
     * 图案列表页面
     */
    private function showPatternListPage()
    {
        try {
            $params = [
                'page' => Request::get()->get('page', 1),
                'page_size' => Request::get()->get('page_size', 20),  // ✅ 统一命名
                'category' => Request::get()->get('category'),
                'group' => Request::get()->get('group'),
                'status' => Request::get()->get('status'),
                'keyword' => Request::get()->get('keyword'),
                'sort_field' => Request::get()->get('sort_field', 'created_at'),
                'sort_order' => Request::get()->get('sort_order', 'DESC')
            ];

            $result = $this->customPattern->getPatternList($params);
            
            if ($result['success']) {
                $this->setData('patterns', $result['data']['patterns']);
                $this->setData('pagination', [
                    'total' => $result['data']['total'],
                    'page' => $result['data']['page'],
                    'page_size' => $result['data']['page_size'],  // ✅ 统一命名
                    'total_pages' => $result['data']['total_pages']
                ]);
            } else {
                $this->setData('patterns', []);
                $this->setData('pagination', []);
                $this->setData('error_message', $result['message']);
            }

            // 获取分类和分组列表
            $categories = $this->customPattern->getPatternCategories();
            $groups = $this->customPattern->getPatternGroups();
            
            $this->setData('categories', $categories['success'] ? $categories['data'] : []);
            $this->setData('groups', $groups['success'] ? $groups['data'] : []);
            
            $this->setData('page_title', '定制图案管理');
            $this->setData('search_params', $params);

        } catch (Exception $e) {
            Logger::error('定制图案列表页面异常', [
                'error' => $e->getMessage()
            ]);
            $this->setData('error_message', '加载图案列表失败：' . $e->getMessage());
        }
    }

    /**
     * 图案库列表API接口
     * 支持分页、搜索、分类筛选、排序
     * 
     * @return void
     */
    public function getPatternListAPI()
    {
        try {
            // 获取请求参数（统一使用接口文档命名）
            $params = [
                'page' => (int)Request::get()->get('page', 1),
                'page_size' => (int)Request::get()->get('page_size', 20),  // ✅ 统一命名
                'keyword' => Request::get()->get('keyword', ''),           // ✅ 统一命名
                'category' => Request::get()->get('category', ''),
                'status' => Request::get()->get('status', 'active'),
                'sort_by' => Request::get()->get('sort_by', 'sort_order'),
                'sort_order' => Request::get()->get('sort_order', 'ASC')
            ];

            // 参数验证
            $validation = $this->validatePatternListParams($params);
            if (!$validation['valid']) {
                $this->sendErrorResponse(400, $validation['message'], $validation['details']);
                return;
            }

            // 调用业务组件获取图案列表
            $result = $this->customPattern->getPatternListForAPI($params);
            
            if ($result['success']) {
                $this->sendSuccessResponse('图案库获取成功', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (Exception $e) {
            Logger::error('图案库列表API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $params ?? []
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 验证图案列表API参数
     * 
     * @param array $params 请求参数
     * @return array 验证结果
     */
    private function validatePatternListParams($params)
    {
        $errors = [];

        // 页码验证
        if ($params['page'] < 1) {
            $errors[] = '页码必须大于0';
        }

        // 每页数量验证（统一使用 page_size）
        if ($params['page_size'] < 1 || $params['page_size'] > 100) {
            $errors[] = '每页数量必须在1-100之间';
        }

        // 排序字段验证
        $allowedSortFields = ['pattern_id', 'pattern_name', 'category', 'sort_order', 'created_at'];
        if (!in_array($params['sort_by'], $allowedSortFields)) {
            $errors[] = '排序字段无效';
        }

        // 排序方向验证
        if (!in_array(strtoupper($params['sort_order']), ['ASC', 'DESC'])) {
            $errors[] = '排序方向无效';
        }

        // 状态验证
        $allowedStatuses = ['active', 'inactive', 'deleted', ''];
        if (!in_array($params['status'], $allowedStatuses)) {
            $errors[] = '状态值无效';
        }

        if (empty($errors)) {
            return ['valid' => true, 'message' => '', 'details' => []];
        }

        return [
            'valid' => false, 
            'message' => '参数验证失败', 
            'details' => $errors
        ];
    }

    /**
     * 发送成功响应
     * 
     * @param string $message 成功消息
     * @param array $data 响应数据
     * @return void
     */
    private function sendSuccessResponse($message, $data)
    {
        $response = [
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 发送错误响应
     * 
     * @param int $code 错误代码
     * @param string $message 错误消息
     * @param array $details 错误详情
     * @return void
     */
    private function sendErrorResponse($code, $message, $details = null)
    {
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 图案添加页面
     */
    public function add()
    {
        try {
            // 获取分类和分组列表
            $categories = $this->customPattern->getPatternCategories();
            $groups = $this->customPattern->getPatternGroups();
            
            $this->setData('categories', $categories['success'] ? $categories['data'] : []);
            $this->setData('groups', $groups['success'] ? $groups['data'] : []);
            
            $this->setData('page_title', '添加定制图案');

        } catch (Exception $e) {
            Logger::error('定制图案添加页面异常', [
                'error' => $e->getMessage()
            ]);
            $this->setData('error_message', '加载添加页面失败：' . $e->getMessage());
        }
    }

    /**
     * 图案编辑页面
     */
    public function edit()
    {
        try {
            $patternId = Request::get()->get('pattern_id');
            
            if (!$patternId) {
                throw new AlertBackException('图案ID不能为空');
            }

            $pattern = $this->customPattern->getPatternById($patternId);
            
            if (!$pattern) {
                throw new AlertBackException('图案不存在');
            }

            $this->setData('pattern', $pattern);

            // 获取分类和分组列表
            $categories = $this->customPattern->getPatternCategories();
            $groups = $this->customPattern->getPatternGroups();
            
            $this->setData('categories', $categories['success'] ? $categories['data'] : []);
            $this->setData('groups', $groups['success'] ? $groups['data'] : []);
            
            $this->setData('page_title', '编辑定制图案');

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案编辑页面异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId ?? null
            ]);
            throw new AlertBackException('加载编辑页面失败：' . $e->getMessage());
        }
    }

    /**
     * 图案添加处理
     */
    public function addProcess()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            // 获取表单数据
            $patternData = [
                'pattern_name' => Request::post()->get('pattern_name'),
                'category' => Request::post()->get('category'),
                'pattern_group' => Request::post()->get('pattern_group'),
                'status' => Request::post()->get('status', 1),
                'uploader' => Session::get('admin_id') ?? 'admin'
            ];

            // 处理文件上传
            $uploadedFile = Request::files()->get('pattern_file');
            
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                throw new AlertBackException('请选择要上传的图片文件');
            }

            $patternData['file_path'] = $uploadedFile['tmp_name'];
            $patternData['file_name'] = $uploadedFile['name'];

            // 添加图案
            $result = $this->customPattern->addPattern($patternData);

            if ($result['success']) {
                $this->setData('success_message', $result['message']);
                $this->setData('redirect_url', '/admin/custom/pattern');
            } else {
                throw new AlertBackException($result['message']);
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案添加处理异常', [
                'error' => $e->getMessage(),
                'data' => $patternData ?? []
            ]);
            throw new AlertBackException('图案添加失败：' . $e->getMessage());
        }
    }

    /**
     * 图案编辑处理
     */
    public function editProcess()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $patternId = Request::post()->get('pattern_id');
            
            if (!$patternId) {
                throw new AlertBackException('图案ID不能为空');
            }

            // 获取表单数据
            $patternData = [
                'pattern_name' => Request::post()->get('pattern_name'),
                'category' => Request::post()->get('category'),
                'pattern_group' => Request::post()->get('pattern_group'),
                'status' => Request::post()->get('status', 1)
            ];

            // 处理文件上传（如果有）
            $uploadedFile = Request::files()->get('pattern_file');
            
            if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                $patternData['file_path'] = $uploadedFile['tmp_name'];
                $patternData['file_name'] = $uploadedFile['name'];
            }

            // 更新图案
            $result = $this->customPattern->updatePattern($patternId, $patternData);

            if ($result['success']) {
                $this->setData('success_message', $result['message']);
                $this->setData('redirect_url', '/admin/custom/pattern');
            } else {
                throw new AlertBackException($result['message']);
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案编辑处理异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId ?? null,
                'data' => $patternData ?? []
            ]);
            throw new AlertBackException('图案编辑失败：' . $e->getMessage());
        }
    }

    /**
     * 图案删除处理
     */
    public function deleteProcess()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $patternId = Request::post()->get('pattern_id');
            
            if (!$patternId) {
                throw new AlertBackException('图案ID不能为空');
            }

            // 删除图案
            $result = $this->customPattern->deletePattern($patternId);

            if ($result['success']) {
                $this->setData('success_message', $result['message']);
            } else {
                throw new AlertBackException($result['message']);
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案删除处理异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId ?? null
            ]);
            throw new AlertBackException('图案删除失败：' . $e->getMessage());
        }
    }

    /**
     * 批量删除图案处理
     */
    public function batchDeleteProcess()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $patternIds = Request::post()->get('pattern_ids');
            
            if (!$patternIds || !is_array($patternIds)) {
                throw new AlertBackException('请选择要删除的图案');
            }

            // 批量删除图案
            $result = $this->customPattern->batchDeletePatterns($patternIds);

            if ($result['success']) {
                $this->setData('success_message', $result['message']);
                $this->setData('batch_result', $result['data']);
            } else {
                throw new AlertBackException($result['message']);
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('批量删除定制图案处理异常', [
                'error' => $e->getMessage(),
                'pattern_ids' => $patternIds ?? []
            ]);
            throw new AlertBackException('批量删除失败：' . $e->getMessage());
        }
    }

    /**
     * 图案状态切换
     */
    public function toggleStatus()
    {
        try {
            $patternId = Request::post()->get('pattern_id');
            $status = Request::post()->get('status');
            
            if (!$patternId) {
                throw new AlertBackException('图案ID不能为空');
            }

            if (!in_array($status, [0, 1])) {
                throw new AlertBackException('状态值无效');
            }

            // 更新状态
            $result = $this->customPattern->updatePattern($patternId, ['status' => $status]);

            if ($result['success']) {
                $this->setData('success_message', '状态更新成功');
                $this->setData('new_status', $status);
            } else {
                throw new AlertBackException($result['message']);
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案状态切换异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId ?? null,
                'status' => $status ?? null
            ]);
            throw new AlertBackException('状态切换失败：' . $e->getMessage());
        }
    }

    /**
     * 获取图案详情（AJAX）
     */
    public function getPatternDetail()
    {
        try {
            $patternId = Request::get()->get('pattern_id');
            
            if (!$patternId) {
                throw new Exception('图案ID不能为空');
            }

            $pattern = $this->customPattern->getPatternById($patternId);
            
            if (!$pattern) {
                throw new Exception('图案不存在');
            }

            $this->setData('success', true);
            $this->setData('data', $pattern);

        } catch (Exception $e) {
            Logger::error('获取定制图案详情异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId ?? null
            ]);
            
            $this->setData('success', false);
            $this->setData('message', $e->getMessage());
        }
    }

    /**
     * 获取图案列表（AJAX）
     */
    public function getPatternListAjax()
    {
        try {
            $params = [
                'page' => Request::get()->get('page', 1),
                'page_size' => Request::get()->get('page_size', 20),  // ✅ 统一命名
                'category' => Request::get()->get('category'),
                'group' => Request::get()->get('group'),
                'status' => Request::get()->get('status'),
                'keyword' => Request::get()->get('keyword'),
                'sort_field' => Request::get()->get('sort_field', 'created_at'),
                'sort_order' => Request::get()->get('sort_order', 'DESC')
            ];

            $result = $this->customPattern->getPatternList($params);
            
            $this->setData('success', $result['success']);
            
            if ($result['success']) {
                $this->setData('data', $result['data']);
            } else {
                $this->setData('message', $result['message']);
            }

        } catch (Exception $e) {
            Logger::error('获取定制图案列表AJAX异常', [
                'error' => $e->getMessage(),
                'params' => $params ?? []
            ]);
            
            $this->setData('success', false);
            $this->setData('message', '获取图案列表失败：' . $e->getMessage());
        }
    }

    /**
     * 获取分类列表（AJAX）
     */
    public function getCategoriesAjax()
    {
        try {
            $result = $this->customPattern->getPatternCategories();
            
            $this->setData('success', $result['success']);
            
            if ($result['success']) {
                $this->setData('data', $result['data']);
            } else {
                $this->setData('message', $result['message']);
            }

        } catch (Exception $e) {
            Logger::error('获取定制图案分类AJAX异常', [
                'error' => $e->getMessage()
            ]);
            
            $this->setData('success', false);
            $this->setData('message', '获取分类失败：' . $e->getMessage());
        }
    }

    /**
     * 获取分组列表（AJAX）
     */
    public function getGroupsAjax()
    {
        try {
            $result = $this->customPattern->getPatternGroups();
            
            $this->setData('success', $result['success']);
            
            if ($result['success']) {
                $this->setData('data', $result['data']);
            } else {
                $this->setData('message', $result['message']);
            }

        } catch (Exception $e) {
            Logger::error('获取定制图案分组AJAX异常', [
                'error' => $e->getMessage()
            ]);
            
            $this->setData('success', false);
            $this->setData('message', '获取分组失败：' . $e->getMessage());
        }
    }

    /**
     * 验证CSRF Token
     * 
     * @throws AlertBackException
     */
    private function validateCsrfToken()
    {
        $token = Request::post()->get('csrf_token');
        $sessionToken = Session::get('csrf_token');
        
        if (!$token || !$sessionToken || $token !== $sessionToken) {
            throw new AlertBackException('安全验证失败，请重新提交');
        }
    }

    /**
     * 图案上传API接口
     * 支持单个图案上传，返回JSON格式响应
     * 
     * @return void
     */
    public function uploadPatternAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            // 获取上传文件
            $uploadedFile = Request::files()->get('pattern_file');
            
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                $this->sendErrorResponse(400, '请选择要上传的图片文件');
                return;
            }

            // 验证文件类型
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
            $fileType = $uploadedFile['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $this->sendErrorResponse(400, '不支持的文件类型，仅支持JPEG、PNG、GIF、SVG格式');
                return;
            }

            // 验证文件大小（最大5MB）
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($uploadedFile['size'] > $maxSize) {
                $this->sendErrorResponse(400, '文件大小不能超过5MB');
                return;
            }

            // 准备图案数据
            $patternData = [
                'pattern_name' => Request::post()->get('pattern_name', ''),
                'category' => Request::post()->get('category', ''),
                'pattern_group' => Request::post()->get('pattern_group', ''),
                'pattern_tags' => Request::post()->get('pattern_tags', ''),
                'description' => Request::post()->get('description', ''),
                'status' => Request::post()->get('status', 1),
                'uploader' => Session::get('admin_id') ?? 'admin',
                'file_path' => $uploadedFile['tmp_name'],
                'file_name' => $uploadedFile['name']
            ];

            // 验证必填字段
            if (empty($patternData['pattern_name'])) {
                $this->sendErrorResponse(400, '图案名称不能为空');
                return;
            }

            // 添加图案
            $result = $this->customPattern->addPattern($patternData);

            if ($result['success']) {
                $this->sendSuccessResponse('图案上传成功', [
                    'pattern_id' => $result['data']['pattern_id'],
                    'pattern_name' => $result['data']['pattern_name'],
                    'pattern_url' => $result['data']['pattern_url'],
                    'file_size' => $result['data']['file_size']
                ]);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('图案上传API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_info' => $uploadedFile ?? []
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 图案删除API接口
     * 
     * @return void
     */
    public function deletePatternAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $patternId = Request::post()->get('pattern_id');
            if (!$patternId) {
                $this->sendErrorResponse(400, '图案ID不能为空');
                return;
            }

            // 检查是否被商品引用
            $goodsRefCount = \Custom\Component\GoodsCustomPattern::where('pattern_id', $patternId)->count();
            if ($goodsRefCount > 0) {
                $this->sendErrorResponse(400, '该图案已被商品引用，无法删除');
                return;
            }
            // 检查是否被订单引用（如有订单定制表）
            if (class_exists('Custom\\Component\\CustomGoodsCustomization')) {
                $orderRefCount = \Custom\Component\CustomGoodsCustomization::where('pattern_id', $patternId)->count();
                if ($orderRefCount > 0) {
                    $this->sendErrorResponse(400, '该图案已被订单引用，无法删除');
                    return;
                }
            }

            // 删除图案
            $result = $this->customPattern->deletePattern($patternId);
            if ($result['success']) {
                $this->sendSuccessResponse('图案删除成功', [
                    'pattern_id' => $patternId,
                    'deleted_files' => $result['data']['deleted_files'] ?? []
                ]);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }
        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('图案删除API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pattern_id' => $patternId ?? null
            ]);
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 图案状态更新API接口
     * 
     * @return void
     */
    public function updatePatternStatusAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $patternId = Request::post()->get('pattern_id');
            $status = Request::post()->get('status');
            
            if (!$patternId) {
                $this->sendErrorResponse(400, '图案ID不能为空');
                return;
            }

            if (!in_array($status, [0, 1])) {
                $this->sendErrorResponse(400, '状态值无效');
                return;
            }

            // 更新图案状态
            $result = $this->customPattern->updatePatternStatus($patternId, $status);

            if ($result['success']) {
                $this->sendSuccessResponse('图案状态更新成功', [
                    'pattern_id' => $patternId,
                    'status' => $status
                ]);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('图案状态更新API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pattern_id' => $patternId ?? null,
                'status' => $status ?? null
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取图案详情API接口
     * 用于图案编辑和预览功能
     * 
     * @return void
     */
    public function getPatternInfoAPI()
    {
        try {
            $patternId = Request::get()->get('pattern_id');
            
            if (!$patternId) {
                $this->sendErrorResponse(400, '图案ID不能为空');
                return;
            }

            // 获取图案详情
            $pattern = $this->customPattern->getPatternById($patternId);
            
            if (!$pattern) {
                $this->sendErrorResponse(404, '图案不存在');
                return;
            }

            // 获取分类名称（如果category是ID）
            if (isset($pattern['category']) && is_numeric($pattern['category'])) {
                $categoryResult = $this->customPattern->getPatternCategoryById($pattern['category']);
                if ($categoryResult['success']) {
                    $pattern['category_name'] = $categoryResult['data']['category_name'];
                } else {
                    $pattern['category_name'] = '';
                }
            } else {
                $pattern['category_name'] = $pattern['category'] ?? '';
            }

            $this->sendSuccessResponse('获取图案详情成功', $pattern);

        } catch (Exception $e) {
            Logger::error('获取图案详情API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pattern_id' => $patternId ?? null
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 批量更新图案状态API接口
     * 
     * @return void
     */
    public function batchUpdatePatternStatusAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $patternIds = Request::post()->get('pattern_ids');
            $status = Request::post()->get('status');
            
            if (!$patternIds || !is_array($patternIds)) {
                $this->sendErrorResponse(400, '请选择要操作的图案');
                return;
            }

            if (!in_array($status, [0, 1])) {
                $this->sendErrorResponse(400, '状态值无效');
                return;
            }

            // 批量更新图案状态
            $result = $this->customPattern->batchUpdatePatternStatus($patternIds, $status);

            if ($result['success']) {
                $this->sendSuccessResponse('批量状态更新成功', [
                    'updated_count' => $result['data']['updated_count'] ?? count($patternIds),
                    'pattern_ids' => $patternIds,
                    'status' => $status
                ]);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('批量更新图案状态API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pattern_ids' => $patternIds ?? [],
                'status' => $status ?? null
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 批量删除图案API接口
     * 
     * @return void
     */
    public function batchDeletePatternsAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $patternIds = Request::post()->get('pattern_ids');
            
            if (!$patternIds || !is_array($patternIds)) {
                $this->sendErrorResponse(400, '请选择要删除的图案');
                return;
            }

            // 批量删除图案
            $result = $this->customPattern->batchDeletePatterns($patternIds);

            if ($result['success']) {
                $this->sendSuccessResponse('批量删除成功', [
                    'deleted_count' => $result['data']['deleted_count'] ?? count($patternIds),
                    'pattern_ids' => $patternIds,
                    'deleted_files' => $result['data']['deleted_files'] ?? []
                ]);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('批量删除图案API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pattern_ids' => $patternIds ?? []
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新图案信息API接口
     * 用于图案编辑功能
     * 
     * @return void
     */
    public function updatePatternAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            // 获取请求参数
            $patternId = Request::post()->get('pattern_id');
            $patternName = Request::post()->get('pattern_name');
            $category = Request::post()->get('category');
            $patternGroup = Request::post()->get('pattern_group');
            $description = Request::post()->get('description');
            $status = Request::post()->get('status');

            // 参数验证
            if (!$patternId) {
                $this->sendErrorResponse(400, '图案ID不能为空');
                return;
            }

            if (!$patternName || trim($patternName) === '') {
                $this->sendErrorResponse(400, '图案名称不能为空');
                return;
            }

            if (strlen(trim($patternName)) > 100) {
                $this->sendErrorResponse(400, '图案名称不能超过100个字符');
                return;
            }

            if ($patternGroup && strlen(trim($patternGroup)) > 50) {
                $this->sendErrorResponse(400, '图案分组不能超过50个字符');
                return;
            }

            if ($patternDescription && strlen(trim($patternDescription)) > 500) {
                $this->sendErrorResponse(400, '图案描述不能超过500个字符');
                return;
            }

            if (!in_array($status, ['0', '1'])) {
                $this->sendErrorResponse(400, '状态值无效');
                return;
            }

            // 准备更新数据
            $updateData = [
                'pattern_name' => trim($patternName),
                'category' => $category ?: null,
                'pattern_group' => $patternGroup ? trim($patternGroup) : null,
                'status' => (int)$status
            ];

            // 如果有描述字段，添加到更新数据中
            if ($patternDescription !== null) {
                $updateData['description'] = trim($description);
            }

            // 调用业务组件更新图案
            $result = $this->customPattern->updatePattern($patternId, $updateData);

            if ($result['success']) {
                // 获取更新后的图案信息
                $updatedPattern = $this->customPattern->getPatternById($patternId);
                
                $this->sendSuccessResponse('图案更新成功', $updatedPattern);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('图案更新API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pattern_id' => $patternId ?? null,
                'update_data' => $updateData ?? []
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

} 