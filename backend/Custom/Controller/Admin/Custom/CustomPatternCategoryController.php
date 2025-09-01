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
        $status = Request::get()->get('status', null);
        $query = \Custom\Component\CustomPatternCategory::query();
        if ($status !== null && $status !== '') {
            $query->where('status', '=', (int)$status);
        }
        $categories = $query->orderBy('sort_order', 'asc')->get();
        $this->json(['success' => true, 'data' => ['categories' => $categories->toArray()]]);
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
        $data = [
            'category_name' => trim(Request::post()->get('category_name', '')),
            'category_description' => trim(Request::post()->get('category_description', '')),
            'parent_id' => (int)Request::post()->get('parent_id', 0),
            'sort_order' => (int)Request::post()->get('sort_order', 0),
            'status' => (int)Request::post()->get('status', 1),
        ];
        // 必填项和长度校验
        if ($data['category_name'] === '' || mb_strlen($data['category_name']) > 100) {
            $this->json(['success' => false, 'message' => '分类名称不能为空且不能超过100字符']);
            return;
        }
        if (mb_strlen($data['category_description']) > 500) {
            $this->json(['success' => false, 'message' => '分类描述不能超过500字符']);
            return;
        }
        if (!is_numeric($data['sort_order']) || $data['sort_order'] < 0) {
            $this->json(['success' => false, 'message' => '排序顺序必须为非负整数']);
            return;
        }
        if (!in_array($data['status'], [0, 1], true)) {
            $this->json(['success' => false, 'message' => '状态值非法']);
            return;
        }
        // 唯一性校验
        $exists = \Custom\Component\CustomPatternCategory::where([
            ['category_name', '=', $data['category_name']],
            ['parent_id', '=', $data['parent_id']]
        ])->exists();
        if ($exists) {
            $this->json(['success' => false, 'message' => '同一父分类下已存在同名分类']);
            return;
        }
        // 层级校验：不能自引用
        if ($data['parent_id'] > 0) {
            // 新建时parent_id=自身理论不会发生，但可加防护
            // 编辑时会做更严格校验
        }
        $category = \Custom\Component\CustomPatternCategory::create($data);
        $this->json(['success' => (bool)$category, 'data' => $category ? $category->toArray() : null]);
    }

    /**
     * 更新分类API接口
     * 
     * @return void
     */
    public function updateCategoryAPI()
    {
        $categoryId = (int)Request::post()->get('category_id', 0);
        $category = \Custom\Component\CustomPatternCategory::find($categoryId);
        if (!$category) {
            $this->json(['success' => false, 'message' => '分类不存在']);
            return;
        }
        $data = [
            'category_name' => trim(Request::post()->get('category_name', '')),
            'category_description' => trim(Request::post()->get('category_description', '')),
            'parent_id' => (int)Request::post()->get('parent_id', 0),
            'sort_order' => (int)Request::post()->get('sort_order', 0),
            'status' => (int)Request::post()->get('status', 1),
        ];
        // 必填项和长度校验
        if ($data['category_name'] === '' || mb_strlen($data['category_name']) > 100) {
            $this->json(['success' => false, 'message' => '分类名称不能为空且不能超过100字符']);
            return;
        }
        if (mb_strlen($data['category_description']) > 500) {
            $this->json(['success' => false, 'message' => '分类描述不能超过500字符']);
            return;
        }
        if (!is_numeric($data['sort_order']) || $data['sort_order'] < 0) {
            $this->json(['success' => false, 'message' => '排序顺序必须为非负整数']);
            return;
        }
        if (!in_array($data['status'], [0, 1], true)) {
            $this->json(['success' => false, 'message' => '状态值非法']);
            return;
        }
        // 唯一性校验
        $exists = \Custom\Component\CustomPatternCategory::where([
            ['category_name', '=', $data['category_name']],
            ['parent_id', '=', $data['parent_id']],
            ['category_id', '!=', $categoryId]
        ])->exists();
        if ($exists) {
            $this->json(['success' => false, 'message' => '同一父分类下已存在同名分类']);
            return;
        }
        // 层级校验：不能自引用或形成环
        if ($data['parent_id'] == $categoryId) {
            $this->json(['success' => false, 'message' => '父分类不能为自身']);
            return;
        }
        // 防止形成环：不能将自己的子孙设为父分类
        if ($data['parent_id'] > 0) {
            $descendants = $this->getAllDescendantIds($categoryId);
            if (in_array($data['parent_id'], $descendants)) {
                $this->json(['success' => false, 'message' => '不能将子孙分类设为父分类']);
                return;
            }
        }
        $category->fill($data);
        $result = $category->save();
        $this->json(['success' => $result, 'data' => $category->toArray()]);
    }

    /**
     * 删除分类API接口
     * 
     * @return void
     */
    public function deleteCategoryAPI()
    {
        $categoryId = (int)Request::post()->get('category_id', 0);
        $category = \Custom\Component\CustomPatternCategory::find($categoryId);
        if (!$category) {
            $this->json(['success' => false, 'message' => '分类不存在']);
            return;
        }
        // 禁止删除有子分类的分类
        $hasChildren = \Custom\Component\CustomPatternCategory::where('parent_id', $categoryId)->exists();
        if ($hasChildren) {
            $this->json(['success' => false, 'message' => '请先删除该分类下的子分类']);
            return;
        }
        // 禁止删除有图案的分类
        $hasPatterns = \Custom\Component\CustomPattern::where('pattern_category', $categoryId)->exists();
        if ($hasPatterns) {
            $this->json(['success' => false, 'message' => '请先移除该分类下的所有图案']);
            return;
        }
        $result = $category->delete();
        $this->json(['success' => $result]);
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
        $categoryId = (int)Request::get()->get('category_id', 0);
        if ($categoryId <= 0) {
            $this->json(['success' => false, 'message' => '参数错误']);
            return;
        }
        $category = \Custom\Component\CustomPatternCategory::find($categoryId);
        if (!$category) {
            $this->json(['success' => false, 'message' => '分类不存在']);
            return;
        }
        $this->json(['success' => true, 'data' => $category->toArray()]);
    }

    /**
     * 验证分类名称唯一性API接口
     * 
     * @return void
     */
    public function checkCategoryNameUniqueAPI()
    {
        $categoryName = trim(Request::get()->get('category_name', ''));
        $parentId = (int)Request::get()->get('parent_id', 0);
        $categoryId = (int)Request::get()->get('category_id', 0);

        if ($categoryName === '') {
            $this->json(['exists' => false]);
            return;
        }

        $where = [
            ['category_name', '=', $categoryName],
            ['parent_id', '=', $parentId]
        ];
        if ($categoryId > 0) {
            $where[] = ['category_id', '!=', $categoryId];
        }

        $exists = \Custom\Component\CustomPatternCategory::where($where)->exists();

        $this->json(['exists' => $exists]);
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

        // 每页数量验证
        if ($params['limit'] < 1 || $params['limit'] > 100) {
            $errors[] = '每页数量必须在1-100之间';
        }

        // 父分类ID验证
        if ($params['parent_id'] !== '' && !is_numeric($params['parent_id'])) {
            $errors[] = '父分类ID必须是数字';
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
     * @return array 验证结果
     */
    private function validateCategoryData($categoryData)
    {
        $errors = [];

        // 分类名称验证
        if (empty($categoryData['category_name'])) {
            $errors[] = '分类名称不能为空';
        } elseif (strlen($categoryData['category_name']) > 100) {
            $errors[] = '分类名称不能超过100个字符';
        }

        // 分类描述验证
        if (strlen($categoryData['category_description']) > 500) {
            $errors[] = '分类描述不能超过500个字符';
        }

        // 父分类ID验证
        if ($categoryData['parent_id'] < 0) {
            $errors[] = '父分类ID不能为负数';
        }

        // 排序顺序验证
        if ($categoryData['sort_order'] < 0) {
            $errors[] = '排序顺序不能为负数';
        }

        // 状态验证
        if (!in_array($categoryData['status'], [0, 1])) {
            $errors[] = '状态值无效';
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

    // 获取所有子孙分类ID（递归）
    private function getAllDescendantIds($categoryId)
    {
        $ids = [];
        $children = \Custom\Component\CustomPatternCategory::where('parent_id', $categoryId)->get();
        foreach ($children as $child) {
            $ids[] = $child->category_id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child->category_id));
        }
        return $ids;
    }
}
