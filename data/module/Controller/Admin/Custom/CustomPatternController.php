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

use Component\Custom\CustomPattern;
use Component\Storage\CustomStorage;
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
        parent::__construct();
        $this->customPattern = new CustomPattern();
    }

    /**
     * 图案列表页面
     */
    public function index()
    {
        try {
            $params = [
                'page' => Request::get()->get('page', 1),
                'limit' => Request::get()->get('limit', 20),
                'category' => Request::get()->get('category'),
                'group' => Request::get()->get('group'),
                'status' => Request::get()->get('status'),
                'keyword' => Request::get()->get('keyword'),
                'sort_field' => Request::get()->get('sort_field', 'create_time'),
                'sort_order' => Request::get()->get('sort_order', 'DESC')
            ];

            $result = $this->customPattern->getPatternList($params);
            
            if ($result['success']) {
                $this->setData('patterns', $result['data']['patterns']);
                $this->setData('pagination', [
                    'total' => $result['data']['total'],
                    'page' => $result['data']['page'],
                    'limit' => $result['data']['limit'],
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
                'pattern_category' => Request::post()->get('pattern_category'),
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
                'pattern_category' => Request::post()->get('pattern_category'),
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
                'limit' => Request::get()->get('limit', 20),
                'category' => Request::get()->get('category'),
                'group' => Request::get()->get('group'),
                'status' => Request::get()->get('status'),
                'keyword' => Request::get()->get('keyword'),
                'sort_field' => Request::get()->get('sort_field', 'create_time'),
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
} 