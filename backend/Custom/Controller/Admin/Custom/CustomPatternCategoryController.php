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
        parent::__construct();
        $this->categoryComponent = new CustomPatternCategory();
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
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取分页和搜索参数
            $page = (int)Request::get()->get('page', 1);
            $limit = (int)Request::get()->get('limit', 20);
            $keyword = trim(Request::get()->get('keyword', ''));
            $parentId = (int)Request::get()->get('parent_id', 0);
            $status = Request::get()->get('status', '');

            // 参数验证
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 20;

            // 准备查询参数
            $params = [
                'page' => $page,
                'limit' => $limit,
                'keyword' => $keyword,
                'parent_id' => $parentId,
                'status' => $status
            ];

            // 调用业务组件获取分类列表
            $result = $this->categoryComponent->getCategoryList($params);
            
            if ($result['success']) {
                $this->sendSuccessResponse('分类列表获取成功', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (Exception $e) {
            Logger::error('获取分类列表API异常', [
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
     * 获取分类树形结构API接口
     * 
     * @return void
     */
    public function getCategoryTreeAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 调用业务组件获取分类树
            $result = $this->categoryComponent->getCategoryTree();
            
            if ($result['success']) {
                $this->sendSuccessResponse('分类树获取成功', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (Exception $e) {
            Logger::error('分类树API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
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
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
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

            // 调用业务组件添加分类
            $result = $this->categoryComponent->addCategory($categoryData);
            
            if ($result['success']) {
                $this->sendSuccessResponse('分类添加成功', $result['data']);
            } else {
                $this->sendErrorResponse(400, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('添加分类API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_data' => $categoryData ?? []
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
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
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取分类ID
            $categoryId = (int)Request::post()->get('category_id', 0);
            if ($categoryId <= 0) {
                $this->sendErrorResponse(400, '分类ID无效');
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

            // 调用业务组件更新分类
            $result = $this->categoryComponent->updateCategory($categoryId, $categoryData);
            
            if ($result['success']) {
                $this->sendSuccessResponse('分类更新成功', $result['data']);
            } else {
                $this->sendErrorResponse(400, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('更新分类API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_id' => $categoryId ?? 0,
                'category_data' => $categoryData ?? []
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
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
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取分类ID
            $categoryId = (int)Request::post()->get('category_id', 0);
            if ($categoryId <= 0) {
                $this->sendErrorResponse(400, '分类ID无效');
                return;
            }

            // 调用业务组件删除分类
            $result = $this->categoryComponent->deleteCategory($categoryId);
            
            if ($result['success']) {
                $this->sendSuccessResponse('分类删除成功', $result['data']);
            } else {
                $this->sendErrorResponse(400, $result['message'], $result['data']);
            }

        } catch (AlertBackException $e) {
            $this->sendErrorResponse(400, $e->getMessage());
        } catch (Exception $e) {
            Logger::error('删除分类API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_id' => $categoryId ?? 0
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
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
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取请求参数
            $sortData = Request::post()->get('sort_data');

            // 参数验证
            if (!$sortData) {
                $this->sendErrorResponse(400, '排序数据不能为空');
                return;
            }

            // 处理排序数据
            if (is_string($sortData)) {
                $sortData = json_decode($sortData, true);
            }

            if (!is_array($sortData)) {
                $this->sendErrorResponse(400, '排序数据格式错误');
                return;
            }

            // 调用业务组件更新排序
            $result = $this->categoryComponent->updateCategorySort($sortData);
            
            if ($result['success']) {
                $this->sendSuccessResponse('分类排序更新成功', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data']);
            }

        } catch (Exception $e) {
            Logger::error('更新分类排序API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sort_data' => $sortData ?? []
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
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
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取分类ID
            $categoryId = (int)Request::get()->get('category_id', 0);
            if ($categoryId <= 0) {
                $this->sendErrorResponse(400, '分类ID无效');
                return;
            }

            // 调用业务组件获取分类信息
            $result = $this->categoryComponent->getCategoryById($categoryId);
            
            if ($result['success']) {
                $this->sendSuccessResponse('分类信息获取成功', $result['data']);
            } else {
                $this->sendErrorResponse(404, $result['message'], $result['data']);
            }

        } catch (Exception $e) {
            Logger::error('获取分类信息API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_id' => $categoryId ?? 0
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
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
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取请求参数
            $categoryName = trim(Request::get()->get('category_name', ''));
            $parentId = (int)Request::get()->get('parent_id', 0);
            $categoryId = (int)Request::get()->get('category_id', 0);

            if ($categoryName === '') {
                $this->sendSuccessResponse('分类名称为空', ['exists' => false]);
                return;
            }

            // 调用业务组件检查分类名称唯一性
            $exists = $this->categoryComponent->checkCategoryNameExists($categoryName, $parentId, $categoryId);
            
            $this->sendSuccessResponse('分类名称唯一性检查完成', ['exists' => $exists]);

        } catch (Exception $e) {
            Logger::error('验证分类名称唯一性API异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_name' => $categoryName ?? '',
                'parent_id' => $parentId ?? 0,
                'category_id' => $categoryId ?? 0
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
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


}
