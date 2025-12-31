<?php

/**
 * 图案分类管理控制器
 * 
 * 本控制器提供后台图案分类管理的API接口，包括分类的增删改查、排序等功能
 * 严格遵循Godomall开发规范，继承官方Controller
 * 
 * @package Controller\Admin\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Admin\Custom;

use Custom\Component\CustomPatternCategory;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 图案分类管理控制器
 */
class CustomPatternCategoryController extends \Controller\Admin\Controller
{
    /**
     * 图案分类组件
     * @var CustomPatternCategory
     */
    private $categoryComponent;

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
        
        $this->categoryComponent = new CustomPatternCategory();
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
            $this->sendJsonResponse([
                'success' => false,
                'message' => '缺少action或mode参数'
            ]);
            return;
        }
        
        // 根据action调用相应的方法
        switch ($action) {
            case 'getCategoryListAPI':
                $this->getCategoryListAPI();
                break;
            case 'addCategoryAPI':
                $this->addCategoryAPI();
                break;
            case 'updateCategoryAPI':
                $this->updateCategoryAPI();
                break;
            case 'deleteCategoryAPI':
                $this->deleteCategoryAPI();
                break;
            default:
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '未知的action: ' . $action
                ]);
                break;
        }
    }

    /**
     * 获取分类列表API接口
     * 
     * @return void
     */
    public function getCategoryListAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 获取分页和搜索参数（统一使用接口文档命名）
            $page = (int)Request::get()->get('page', 1);
            $pageSize = (int)Request::get()->get('page_size', 20);  // ✅ 统一使用 page_size
            $keyword = trim(Request::get()->get('keyword', ''));
            $parentId = (int)Request::get()->get('parent_id', 0);
            $status = Request::get()->get('status', '');
            
            // 参数验证
            $validation = $this->validateCategoryListParams([
                'page' => $page,
                'page_size' => $pageSize,  // ✅ 统一命名
                'keyword' => $keyword,
                'parent_id' => $parentId,
                'status' => $status
            ]);
            if (!$validation['valid']) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => $validation['details']
                ]);
                return;
            }

            // 准备查询参数
            $params = [
                'page' => $page,
                'page_size' => $pageSize,  // ✅ 统一命名
                'keyword' => $keyword,
                'parent_id' => $parentId,
                'status' => $status
            ];

            // 调用业务组件获取分类列表
            $result = $this->categoryComponent->getCategoryList($params);
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类列表获取成功',
                    'data' => $result['data']
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('获取分类列表API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $params ?? []
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 获取分类树形结构API接口
     * 
     * @return void
     */
    public function getCategoryTreeAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 调用业务组件获取分类树
            $result = $this->categoryComponent->getCategoryTree();
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类树获取成功',
                    'data' => $result['data']
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('分类树API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 添加分类API接口
     * 
     * @return void
     */
    public function addCategoryAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 获取请求数据
            $categoryData = [
                'category_name' => trim(Request::post()->get('category_name', '')),
                'category_description' => trim(Request::post()->get('category_description', '')),
                'parent_id' => (int)Request::post()->get('parent_id', 0),
                'sort_order' => (int)Request::post()->get('sort_order', 0),
                'status' => (int)Request::post()->get('status', 1),
            ];

            // 数据验证
            $validation = $this->validateCategoryData($categoryData);
            if (!$validation['valid']) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => $validation['details']
                ]);
                return;
            }

            // 调用业务组件添加分类
            $result = $this->categoryComponent->addCategory($categoryData);
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类添加成功',
                    'data' => $result['data']
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            }

        } catch (AlertBackException $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        } catch (Exception $e) {
            Logger::error('添加分类API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_data' => $categoryData ?? []
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 更新分类API接口
     * 
     * @return void
     */
    public function updateCategoryAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 获取分类ID
            $categoryId = (int)Request::post()->get('category_id', 0);
            if ($categoryId <= 0) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '分类ID无效',
                    'data' => null
                ]);
                return;
            }

            // 获取请求数据
            $categoryData = [
                'category_name' => trim(Request::post()->get('category_name', '')),
                'category_description' => trim(Request::post()->get('category_description', '')),
                'parent_id' => (int)Request::post()->get('parent_id', 0),
                'sort_order' => (int)Request::post()->get('sort_order', 0),
                'status' => (int)Request::post()->get('status', 1),
            ];

            // 数据验证
            $validation = $this->validateCategoryData($categoryData, $categoryId);
            if (!$validation['valid']) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => $validation['details']
                ]);
                return;
            }

            // 调用业务组件更新分类
            $result = $this->categoryComponent->updateCategory($categoryId, $categoryData);
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类更新成功',
                    'data' => $result['data']
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            }

        } catch (AlertBackException $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        } catch (Exception $e) {
            Logger::error('更新分类API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_id' => $categoryId ?? 0,
                'category_data' => $categoryData ?? []
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 删除分类API接口
     * 
     * @return void
     */
    public function deleteCategoryAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 获取分类ID
            $categoryId = (int)Request::post()->get('category_id', 0);
            if ($categoryId <= 0) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '分类ID无效',
                    'data' => null
                ]);
                return;
            }

            // 调用业务组件删除分类
            $result = $this->categoryComponent->deleteCategory($categoryId);
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类删除成功',
                    'data' => $result['data']
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            }

        } catch (AlertBackException $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        } catch (Exception $e) {
            Logger::error('删除分类API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_id' => $categoryId ?? 0
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 更新分类排序API接口
     * 
     * @return void
     */
    public function updateCategorySortAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 获取请求参数
            $sortData = Request::post()->get('sort_data');

            // 参数验证
            if (!$sortData) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '排序数据不能为空',
                    'data' => null
                ]);
                return;
            }

            // 处理排序数据
            if (is_string($sortData)) {
                $sortData = json_decode($sortData, true);
            }

            if (!is_array($sortData)) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '排序数据格式错误',
                    'data' => null
                ]);
                return;
            }

            // 调用业务组件更新排序
            $result = $this->categoryComponent->updateCategorySort($sortData);
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类排序更新成功',
                    'data' => $result['data']
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('更新分类排序API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sort_data' => $sortData ?? []
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 获取分类信息API接口
     * 
     * @return void
     */
    public function getCategoryInfoAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 获取分类ID
            $categoryId = (int)Request::get()->get('category_id', 0);
            if ($categoryId <= 0) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '分类ID无效',
                    'data' => null
                ]);
                return;
            }

            // 调用业务组件获取分类信息
            $result = $this->categoryComponent->getCategoryById($categoryId);
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类信息获取成功',
                    'data' => $result['data']
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('获取分类信息API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_id' => $categoryId ?? 0
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 验证分类名称唯一性API接口
     * 
     * @return void
     */
    public function checkCategoryNameUniqueAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '请求方法不允许',
                    'data' => ['method' => $_SERVER['REQUEST_METHOD']]
                ]);
                return;
            }

            // 获取请求参数
            $categoryName = trim(Request::get()->get('category_name', ''));
            $parentId = (int)Request::get()->get('parent_id', 0);
            $categoryId = (int)Request::get()->get('category_id', 0);

            if ($categoryName === '') {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => '分类名称为空',
                    'data' => ['exists' => false]
                ]);
                return;
            }

            // 调用业务组件检查分类名称唯一性
            $exists = $this->categoryComponent->checkCategoryNameExists($categoryName, $parentId, $categoryId);
            
            $this->sendJsonResponse([
                'success' => true,
                'message' => '分类名称唯一性检查完成',
                'data' => ['exists' => $exists]
            ]);

        } catch (Exception $e) {
            Logger::error('验证分类名称唯一性API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_name' => $categoryName ?? '',
                'parent_id' => $parentId ?? 0,
                'category_id' => $categoryId ?? 0
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => '服务器内部错误',
                'data' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 验证分类列表参数
     * 
     * @param array $params 请求参数
     * @return array 验证结果
     */
    private function validateCategoryListParams($params)
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

        // 关键词验证
        if (strlen($params['keyword']) > 100) {
            $errors[] = '搜索关键词不能超过100个字符';
        }

        // 父级ID验证
        if ($params['parent_id'] < 0) {
            $errors[] = '父级分类ID不能为负数';
        }

        // 状态验证
        $allowedStatuses = ['', '0', '1'];
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
     * 验证分类数据
     * 
     * @param array $categoryData 分类数据
     * @param int $categoryId 分类ID（更新时使用）
     * @return array 验证结果
     */
    private function validateCategoryData($categoryData, $categoryId = 0)
    {
        $errors = [];

        // 分类名称验证
        if (empty($categoryData['category_name'])) {
            $errors[] = '分类名称不能为空';
        } elseif (strlen($categoryData['category_name']) > 100) {
            $errors[] = '分类名称不能超过100个字符';
        } elseif (preg_match('/[<>"\']/', $categoryData['category_name'])) {
            $errors[] = '分类名称包含非法字符';
        }

        // 分类描述验证
        if (strlen($categoryData['category_description']) > 500) {
            $errors[] = '分类描述不能超过500个字符';
        }

        // 父级ID验证
        if ($categoryData['parent_id'] < 0) {
            $errors[] = '父级分类ID不能为负数';
        }

        // 排序验证
        if ($categoryData['sort_order'] < 0 || $categoryData['sort_order'] > 9999) {
            $errors[] = '排序值必须在0-9999之间';
        }

        // 状态验证
        if (!in_array($categoryData['status'], [0, 1])) {
            $errors[] = '状态值无效';
        }

        // 业务验证：分类名称唯一性
        if (!empty($categoryData['category_name'])) {
            $exists = $this->categoryComponent->checkCategoryNameExists(
                $categoryData['category_name'], 
                $categoryData['parent_id'], 
                $categoryId
            );
            if ($exists) {
                $errors[] = '该分类名称已存在';
            }
        }

        if (empty($errors)) {
            return ['valid' => true, 'message' => '', 'details' => []];
        }

        return [
            'valid' => false, 
            'message' => '数据验证失败', 
            'details' => $errors
        ];
    }

}