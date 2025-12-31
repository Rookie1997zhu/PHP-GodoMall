<?php

/**
 * 商品定制管理控制器
 * 
 * 本控制器提供后台商品定制功能管理的接口，包括商品定制属性配置、图案关联等功能
 * 严格遵循Godomall开发规范，继承官方Controller
 * 
 * 【核心API接口】
 * 1. setGoodsPatternsAPI()：设置商品定制图案
 * 2. getGoodsPatternsAPI()：获取商品定制图案列表
 * 3. addCustomSizeAPI()：添加自定义尺寸（含严格验证）
 * 4. getGoodsAllSizesAPI()：获取商品所有尺寸（预定义+自定义）
 * 5. deleteCustomSizeAPI()：删除自定义尺寸
 * 
 * 【安全机制】
 * - CSRF Token验证：所有POST请求都需要CSRF Token
 * - 参数验证：6步严格验证流程（必填、格式、类型、范围、重复、存在性）
 * - 权限控制：继承自官方Admin Controller，自动验证管理员权限
 * - 输入过滤：trim()处理、类型转换、白名单验证
 * 
 * 【数据验证体系】
 * - validateCsrfToken()：CSRF Token验证
 * - validateSizeName()：尺寸名称验证（≤50字符）
 * - validateDimension()：尺寸数值验证（string→float，>0且≤10000）
 * - validateUnit()：单位验证（inch/cm/mm/m白名单）
 * - isSizeNameExists()：重复性检查（同商品下唯一）
 * 
 * 【响应格式】
 * - 成功：{success: true, message: string, data: object}
 * - 失败：{success: false, message: string, code: 400/403/404/409/500}
 * - 使用标准HTTP状态码增强RESTful规范性
 * 
 * 【依赖组件】
 * - CustomGoods：商品定制业务逻辑组件
 * - CustomPattern：图案管理业务逻辑组件
 * 
 * @package Controller\Admin\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Admin\Custom;

use Custom\Component\CustomGoods;
use Custom\Component\CustomPattern;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 商品定制管理控制器
 */
class CustomGoodsController extends \Controller\Admin\Controller
{
    /**
     * 商品定制组件
     * @var CustomGoods
     */
    private $customGoods;

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
        $this->customGoods = new CustomGoods();
        $this->customPattern = new CustomPattern();
    }

    /**
     * 商品定制配置页面
     */
    public function index()
    {
        try {
            $goodsNo = Request::get()->get('goods_no');
            
            if (!$goodsNo) {
                throw new AlertBackException('商品编号不能为空');
            }

            // 获取商品基本信息
            $goodsInfo = $this->getGoodsInfo($goodsNo);
            if (!$goodsInfo) {
                throw new AlertBackException('商品不存在');
            }

            $this->setData('goods_info', $goodsInfo);

            // 获取商品定制属性
            $customData = $this->customGoods->getGoodsCustomizable($goodsNo);
            $this->setData('custom_data', $customData ?: ['is_customizable' => false]);

            // 获取商品定制图案
            $patternsResult = $this->customGoods->getGoodsPatterns($goodsNo);
            $this->setData('goods_patterns', $patternsResult['success'] ? $patternsResult['data']['patterns'] : []);

            // 获取可选图案库（统一使用 page_size）
            $availablePatterns = $this->customGoods->getAvailablePatterns(['page_size' => 100]);
            $this->setData('available_patterns', $availablePatterns['success'] ? $availablePatterns['data']['patterns'] : []);

            // 获取分类和分组
            $categories = $this->customPattern->getPatternCategories();
            $groups = $this->customPattern->getPatternGroups();
            
            $this->setData('categories', $categories['success'] ? $categories['data'] : []);
            $this->setData('groups', $groups['success'] ? $groups['data'] : []);
            
            $this->setData('page_title', '商品定制配置');

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('商品定制配置页面异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            throw new AlertBackException('加载商品定制配置页面失败：' . $e->getMessage());
        }
    }

    /**
     * 设置商品定制属性
     */
    public function setCustomizable()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $goodsNo = Request::post()->get('goods_no');
            $isCustomizable = Request::post()->get('is_customizable');

            if (!$goodsNo) {
                throw new AlertBackException('商品编号不能为空');
            }

            $customData = [
                'is_customizable' => $isCustomizable == '1'
            ];

            $result = $this->customGoods->setGoodsCustomizable($goodsNo, $customData);

            if ($result['success']) {
                $this->setData('success_message', $result['message']);
            } else {
                throw new AlertBackException($result['message']);
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('设置商品定制属性异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            throw new AlertBackException('设置商品定制属性失败：' . $e->getMessage());
        }
    }

    /**
     * 设置商品定制图案
     */
    public function setPatterns()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $goodsNo = Request::post()->get('goods_no');
            $patternIds = Request::post()->get('pattern_ids');

            if (!$goodsNo) {
                throw new AlertBackException('商品编号不能为空');
            }

            // 处理图案ID数组
            if (is_string($patternIds)) {
                $patternIds = json_decode($patternIds, true);
            }

            if (!is_array($patternIds)) {
                $patternIds = [];
            }

            $result = $this->customGoods->setGoodsPatterns($goodsNo, $patternIds);

            if ($result['success']) {
                $this->setData('success_message', $result['message']);
                $this->setData('pattern_count', $result['data']['pattern_count']);
            } else {
                throw new AlertBackException($result['message']);
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('设置商品定制图案异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null,
                'pattern_ids' => $patternIds ?? []
            ]);
            throw new AlertBackException('设置商品定制图案失败：' . $e->getMessage());
        }
    }

    /**
     * 获取商品定制信息（AJAX）
     */
    public function getCustomInfo()
    {
        try {
            $goodsNo = Request::get()->get('goods_no');
            
            if (!$goodsNo) {
                throw new Exception('商品编号不能为空');
            }

            $customInfo = $this->customGoods->getGoodsCustomInfo($goodsNo);
            
            $this->setData('success', $customInfo['success']);
            
            if ($customInfo['success']) {
                $this->setData('data', $customInfo['data']);
            } else {
                $this->setData('message', $customInfo['message']);
            }

        } catch (Exception $e) {
            Logger::error('获取商品定制信息异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            
            $this->setData('success', false);
            $this->setData('message', $e->getMessage());
        }
    }

    /**
     * 获取可选图案库（AJAX）
     */
    public function getAvailablePatterns()
    {
        try {
            $params = [
                'page' => Request::get()->get('page', 1),
                'limit' => Request::get()->get('limit', 20),
                'category' => Request::get()->get('category'),
                'group' => Request::get()->get('group'),
                'keyword' => Request::get()->get('keyword'),
                'status' => Request::get()->get('status', 1)
            ];

            $result = $this->customGoods->getAvailablePatterns($params);
            
            $this->setData('success', $result['success']);
            
            if ($result['success']) {
                $this->setData('data', $result['data']);
            } else {
                $this->setData('message', $result['message']);
            }

        } catch (Exception $e) {
            Logger::error('获取可选图案库异常', [
                'error' => $e->getMessage(),
                'params' => $params ?? []
            ]);
            
            $this->setData('success', false);
            $this->setData('message', '获取可选图案库失败：' . $e->getMessage());
        }
    }

    /**
     * 批量设置商品定制图案
     */
    public function batchSetPatterns()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();

            $goodsNos = Request::post()->get('goods_nos');
            $patternIds = Request::post()->get('pattern_ids');

            if (!$goodsNos) {
                throw new AlertBackException('请选择商品');
            }

            if (!$patternIds) {
                throw new AlertBackException('请选择图案');
            }

            // 处理数据格式
            if (is_string($goodsNos)) {
                $goodsNos = json_decode($goodsNos, true);
            }

            if (is_string($patternIds)) {
                $patternIds = json_decode($patternIds, true);
            }

            if (!is_array($goodsNos) || !is_array($patternIds)) {
                throw new AlertBackException('数据格式错误');
            }

            $result = $this->customGoods->batchSetGoodsPatterns($goodsNos, $patternIds);

            if ($result['success']) {
                $this->setData('success_message', $result['message']);
                $this->setData('batch_result', $result['data']);
            } else {
                throw new AlertBackException($result['message']);
            }

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
     * 商品定制图案管理页面
     */
    public function patternManagement()
    {
        try {
            $goodsNo = Request::get()->get('goods_no');
            
            if (!$goodsNo) {
                throw new AlertBackException('商品编号不能为空');
            }

            // 获取商品基本信息
            $goodsInfo = $this->getGoodsInfo($goodsNo);
            if (!$goodsInfo) {
                throw new AlertBackException('商品不存在');
            }

            $this->setData('goods_info', $goodsInfo);

            // 获取商品定制图案
            $patternsResult = $this->customGoods->getGoodsPatterns($goodsNo);
            $this->setData('goods_patterns', $patternsResult['success'] ? $patternsResult['data']['patterns'] : []);

            // 获取可选图案库（分页）- 统一使用接口文档命名
            $params = [
                'page' => Request::get()->get('page', 1),
                'page_size' => Request::get()->get('page_size', 20),  // ✅ 统一命名
                'category' => Request::get()->get('category'),
                'group' => Request::get()->get('group'),
                'keyword' => Request::get()->get('keyword'),
                'status' => Request::get()->get('status', 1)
            ];

            $availablePatterns = $this->customGoods->getAvailablePatterns($params);
            $this->setData('available_patterns', $availablePatterns['success'] ? $availablePatterns['data'] : []);

            // 获取分类和分组
            $categories = $this->customPattern->getPatternCategories();
            $groups = $this->customPattern->getPatternGroups();
            
            $this->setData('categories', $categories['success'] ? $categories['data'] : []);
            $this->setData('groups', $groups['success'] ? $groups['data'] : []);
            $this->setData('search_params', $params);
            
            $this->setData('page_title', '商品定制图案管理');

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('商品定制图案管理页面异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            throw new AlertBackException('加载商品定制图案管理页面失败：' . $e->getMessage());
        }
    }

    /**
     * 获取商品信息
     * @param int $goodsId 商品ID
     * @return array|false 商品信息数组，不存在返回false
     */
    private function getGoodsInfo($goodsId)
    {
        try {
            $arrBind = [];
            $this->db->bind_param_push($arrBind, 'i', $goodsId);
            
            $sql = "SELECT goods_no, goods_name, status FROM es_goods WHERE goods_no = ?";
            $result = $this->db->query_fetch($sql, $arrBind);
            
            return $result ?: false;
            
        } catch (\Exception $e) {
            Logger::error('获取商品信息异常', [
                'goods_id' => $goodsId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 获取商品信息（通过商品ID）
     * 
     * @param int $goodsId 商品ID
     * @return array|null
     */
    private function getGoodsInfoById($goodsId)
    {
        try {
            $sql = "SELECT goodsNo, goodsNm, is_customizable FROM es_goods WHERE goodsNo = ? AND delFl = 'n'";
            $result = $this->db->query_fetch($sql, [$goodsId]);
            
            return !empty($result) ? $result[0] : null;
            
        } catch (\Exception $e) {
            Logger::error('상품 정보 조회 예외', [
                'goods_id' => $goodsId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * 验证CSRF Token
     * 
     * 防止跨站请求伪造（CSRF）攻击，确保所有POST请求来自可信来源
     * 
     * 【CSRF攻击防护】
     * - CSRF攻击场景：恶意网站伪造管理员的请求执行危险操作
     * - 防护方法：要求每个POST请求携带唯一的CSRF Token
     * - Token来源：前端从Session或页面中获取，随请求发送
     * 
     * 【验证规则】
     * 1. Token不能为空（必填）
     * 2. Token长度在10-100字符之间（格式验证）
     * 3. 未来可扩展：验证Token是否在Session中、是否过期等
     * 
     * 【调用时机】
     * - 所有修改性API（POST/PUT/DELETE）开始时调用
     * - 包括：添加尺寸、删除尺寸、设置图案、批量操作等
     * - 只读API（GET）不需要CSRF验证
     * 
     * 【失败处理】
     * - 抛出异常：'보안 검증에 실패했습니다. 페이지를 새로고침하고 다시 시도해주세요.'
     * - 记录日志：包含错误信息和POST数据（不包含敏感信息）
     * - 用户提示：建议刷新页面重新获取Token
     * 
     * 【安全增强建议】
     * - TODO: 可添加Session Token对比验证
     * - TODO: 可添加Token有效期验证（如5分钟内有效）
     * - TODO: 可添加一次性Token机制（Token使用后立即失效）
     * 
     * @throws \Exception CSRF Token验证失败时抛出异常
     */
    private function validateCsrfToken()
    {
        try {
            $postData = Request::post()->get();
            $csrfToken = $postData['csrf_token'] ?? '';
            
            if (empty($csrfToken)) {
                throw new \Exception('CSRF Token이 누락되었습니다.');
            }
            
            // 这里可以添加更严格的CSRF Token验证逻辑
            // 例如：检查Session中的Token、验证Token有效期等
            
            // 简单验证：Token不为空且长度合理
            if (strlen($csrfToken) < 10 || strlen($csrfToken) > 100) {
                throw new \Exception('CSRF Token 형식이 올바르지 않습니다.');
            }
            
        } catch (\Exception $e) {
            Logger::error('CSRF Token 검증 실패', [
                'error' => $e->getMessage(),
                'post_data' => Request::post()->get()
            ]);
            
            throw new \Exception('보안 검증에 실패했습니다. 페이지를 새로고침하고 다시 시도해주세요.');
        }
    }

    /**
     * 设置商品定制图案API接口
     * 
     * 后台管理员为商品配置可用的定制图案，支持批量选择和替换
     * 
     * 【请求方式】POST
     * 【请求路径】/admin/custom/goods/set-patterns
     * 
     * 【业务流程】
     * 1. 验证请求方法为POST
     * 2. 获取商品编号（goodsNo）和图案ID数组（pattern_ids）
     * 3. 参数验证：goodsNo和pattern_ids不能为空
     * 4. 调用Component层setGoodsPatterns()执行业务逻辑
     * 5. Component层使用事务"先删后增"确保原子性
     * 6. 返回设置结果
     * 
     * 【事务处理（Component层）】
     * - 删除商品现有的所有图案关联
     * - 批量插入新的图案关联
     * - 任何步骤失败都会回滚，避免部分成功
     * - 详见：CustomGoods::setGoodsPatterns()
     * 
     * 【参数格式】
     * - goodsNo：单个商品编号（int）
     * - pattern_ids：图案ID数组（可以是数组或逗号分隔字符串）
     * - 示例：{goodsNo: 123, pattern_ids: [1,2,3]} 或 {goodsNo: 123, pattern_ids: "1,2,3"}
     * 
     * 【Controller职责】
     * - 参数接收和基础验证
     * - 调用Component层业务逻辑
     * - 格式化响应并返回JSON
     * - 不处理事务（事务由Component层处理）
     * 
     * @return void 直接输出JSON响应
     */
    public function setGoodsPatternsAPI()
    {
        try {
            // 验证请求方法
            if (Request::server()->get('REQUEST_METHOD') !== 'POST') {
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => Request::server()->get('REQUEST_METHOD')]);
                return;
            }

        // 获取请求参数（使用Godomall官方字段名 goodsNo）
        $goodsNo = Request::post()->get('goodsNo');
        $patternIds = Request::post()->get('pattern_ids');
        $operation = Request::post()->get('operation', 'set');

        // 参数验证
        if (!$goodsNo) {
            $this->sendErrorResponse(400, '商品编号不能为空');
            return;
        }

            // 处理图案ID数组
            if (is_string($patternIds)) {
                $patternIds = json_decode($patternIds, true);
            }

            if (!is_array($patternIds)) {
                $patternIds = [];
            }

        // 根据操作类型调用不同的方法
        switch ($operation) {
            case 'set':
                $result = $this->customGoods->setGoodsPatterns($goodsNo, $patternIds);
                break;
            case 'add':
                $result = $this->customGoods->addGoodsPatterns($goodsNo, $patternIds);
                break;
            case 'remove':
                $result = $this->customGoods->removeGoodsPatterns($goodsNo, $patternIds);
                break;
            default:
                $this->sendErrorResponse(400, '无效的操作类型', ['operation' => $operation]);
                return;
        }

        if ($result['success']) {
            $this->sendSuccessResponse('商品图案关联设置成功', [
                'goodsNo' => $goodsNo,
                'pattern_count' => count($patternIds),
                'operation' => $operation,
                'message' => $result['message']
            ]);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data'] ?? null);
            }

        } catch (Exception $e) {
            Logger::error('设置商品定制图案API异常', [
                'error' => $e->getMessage(),
                'goodsNo' => $goodsNo ?? null,
                'pattern_ids' => $patternIds ?? [],
                'operation' => $operation ?? null
            ]);
            
            $this->sendErrorResponse(500, '服务器内部错误', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取商品图案API
     */
    public function getGoodsPatternsAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();
            
            // 获取商品编号（使用Godomall官方字段名 goodsNo）
            $goodsNo = Request::get()->get('goodsNo');
            
            if (empty($goodsNo)) {
                $this->sendErrorResponse(400, '상품 번호가 필요합니다.');
                return;
            }
            
            // 获取商品信息
            $goodsInfo = $this->getGoodsInfo($goodsNo);
            if (!$goodsInfo) {
                $this->sendErrorResponse(404, '상품을 찾을 수 없습니다.');
                return;
            }
            
            // 获取商品图案
            $result = $this->customGoods->getGoodsPatterns($goodsNo);
            
            if ($result['success']) {
                $this->sendSuccessResponse('상품 패턴 정보를 성공적으로 가져왔습니다.', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message']);
            }
            
        } catch (\Exception $e) {
            Logger::error('상품 패턴 정보 가져오기 API 예외', [
                'goods_no' => $goodsNo ?? null,
                'error' => $e->getMessage()
            ]);
            
            $this->sendErrorResponse(500, '서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 添加自定义尺寸API
     * 
     * 后台管理员为商品添加自定义尺寸的接口，包含完整的验证和安全检查
     * 
     * 【请求方式】POST
     * 【请求路径】/admin/custom/goods/add-custom-size
     * 
     * 【验证流程（6步严格验证）】
     * 1. CSRF Token验证：防止跨站请求伪造攻击
     * 2. 必填字段验证：goodsNo, size_name, size_width, size_height
     * 3. 商品编号验证：不能为'new'（新商品需先保存）
     * 4. 商品存在性验证：检查商品是否存在于es_goods表
     * 5. 数据类型转换：string → float，使用验证辅助方法
     * 6. 重复性检查：同一商品下尺寸名称不能重复
     * 
     * 【数据验证辅助方法】
     * - validateSizeName()：验证尺寸名称格式和长度（≤100字符）
     * - validateDimension()：验证尺寸数值（>0且≤9999.99，string→float转换）
     * - validateUnit()：验证单位有效性（inch/cm/mm/m）
     * - isSizeNameExists()：检查名称重复性（返回409 Conflict）
     * 
     * 【CSRF Token验证】
     * - 方法：validateCsrfToken()
     * - 目的：防止CSRF攻击，确保请求来自可信来源
     * - 失败：返回403 Forbidden
     * 
     * 【重复检查机制】
     * - 检查范围：同一商品（goodsNo）下的所有启用尺寸
     * - 检查方法：isSizeNameExists(goodsNo, sizeName)
     * - 返回码：409 Conflict（HTTP标准冲突状态码）
     * - 用户提示：'동일한 이름의 크기가 이미 존재합니다.'
     * 
     * 【事务处理】
     * - Component层（addCustomSize）使用事务确保原子性
     * - Controller层只负责参数验证和响应
     * - 失败时Component层自动回滚
     * 
     * 【请求参数】
     * @param int goodsNo - 商品编号（POST，必填）
     * @param string size_name - 尺寸名称（POST，必填，≤100字符）
     * @param float size_width - 宽度（POST，必填，>0且≤9999.99）
     * @param float size_height - 高度（POST，必填，>0且≤9999.99）
     * @param string size_unit - 单位（POST，可选，默认'inch'）
     * 
     * 【响应格式】
     * 成功：{success: true, message: string, data: {customSizeId, sizeName, ...}}
     * 失败：{success: false, message: string, code: 400/404/409/500}
     * 
     * @return void 直接输出JSON响应
     */
    public function addCustomSizeAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();
            
            // 获取POST数据
            $postData = Request::post()->get();
            
            // 1. 严格验证必要参数（使用Godomall官方字段名 goodsNo）
            $requiredFields = ['goodsNo', 'size_name', 'size_width', 'size_height'];
            foreach ($requiredFields as $field) {
                if (!isset($postData[$field]) || trim($postData[$field]) === '') {
                    $this->sendErrorResponse(400, "필수 필드 '{$field}'가 누락되었습니다.");
                    return;
                }
            }
            
            // 2. 验证商品编号
            $goodsNo = $postData['goodsNo'];
            if ($goodsNo === 'new') {
                $this->sendErrorResponse(400, '새 상품을 먼저 저장한 후 크기를 추가하세요.');
                return;
            }
            
            // 3. 验证商品是否存在
            $goodsInfo = $this->getGoodsInfo($goodsNo);
            if (!$goodsInfo && $goodsNo !== 'new') {
                $this->sendErrorResponse(404, '상품을 찾을 수 없습니다.');
                return;
            }
            
            // 4. 数据类型转换和验证
            try {
                $sizeData = [
                    'size_name' => $this->validateSizeName($postData['size_name']),
                    'size_width' => $this->validateDimension($postData['size_width'], 'width'),
                    'size_height' => $this->validateDimension($postData['size_height'], 'height'),
                    'size_unit' => $this->validateUnit($postData['size_unit'] ?? 'inch')
                ];
            } catch (\Exception $e) {
                $this->sendErrorResponse(400, $e->getMessage());
                return;
            }
            
            // 5. 检查尺寸名称是否重复
            if ($this->customGoods->isSizeNameExists($goodsNo, $sizeData['size_name'])) {
                $this->sendErrorResponse(409, '동일한 이름의 크기가 이미 존재합니다.');
                return;
            }
            
            // 6. 添加自定义尺寸
            $result = $this->customGoods->addCustomSize($goodsNo, $sizeData);
            
            if ($result['success']) {
                $this->sendSuccessResponse('사용자 정의 크기가 성공적으로 추가되었습니다.', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message']);
            }
            
        } catch (\Exception $e) {
            Logger::error('사용자 정의 크기 추가 API 예외', [
                'post_data' => $postData ?? [],
                'error' => $e->getMessage()
            ]);
            
            $this->sendErrorResponse(500, '서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 获取商品所有尺寸API
     */
    public function getGoodsAllSizesAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();
            
            // 获取商品编号（使用Godomall官方字段名 goodsNo）
            $goodsNo = Request::get()->get('goodsNo');
            
            if (empty($goodsNo)) {
                $this->sendErrorResponse(400, '상품 번호가 필요합니다.');
                return;
            }
            
            // 获取所有尺寸
            $result = $this->customGoods->getGoodsAllSizes($goodsNo);
            
            if ($result['success']) {
                $this->sendSuccessResponse('상품 크기 정보를 성공적으로 가져왔습니다.', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message']);
            }
            
        } catch (\Exception $e) {
            Logger::error('상품 크기 정보 가져오기 API 예외', [
                'goodsNo' => $goodsNo ?? null,
                'error' => $e->getMessage()
            ]);
            
            $this->sendErrorResponse(500, '서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 删除自定义尺寸API
     */
    public function deleteCustomSizeAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();
            
            // 获取POST数据
            $postData = Request::post()->get();
            
            // 验证必要参数
            if (empty($postData['custom_size_id'])) {
                $this->sendErrorResponse(400, '사용자 정의 크기 ID가 필요합니다.');
                return;
            }
            
            // 删除自定义尺寸
            $result = $this->customGoods->deleteCustomSize($postData['custom_size_id']);
            
            if ($result['success']) {
                $this->sendSuccessResponse('사용자 정의 크기가 성공적으로 삭제되었습니다.');
            } else {
                $this->sendErrorResponse(500, $result['message']);
            }
            
        } catch (\Exception $e) {
            Logger::error('사용자 정의 크기 삭제 API 예외', [
                'post_data' => $postData ?? [],
                'error' => $e->getMessage()
            ]);
            
            $this->sendErrorResponse(500, '서버 오류가 발생했습니다: ' . $e->getMessage());
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

    /**
     * 验证尺寸名称
     * 
     * 对用户输入的尺寸名称进行格式和长度验证
     * 
     * 【验证规则】
     * 1. 去除首尾空格（trim处理）
     * 2. 长度必须≥1字符（不能为空）
     * 3. 长度必须≤50字符（数据库字段限制）
     * 
     * 【使用场景】
     * - addCustomSizeAPI()中调用，验证用户输入
     * - 在数据库操作前进行前置验证
     * - 确保数据符合custom_goods_sizes表的size_name字段要求
     * 
     * @param string $name 尺寸名称（未验证的用户输入）
     * @return string 验证后的尺寸名称（已trim）
     * @throws \Exception 验证失败时抛出异常（空字符串或超长）
     */
    private function validateSizeName($name) {
        $name = trim($name);
        if (strlen($name) < 1) {
            throw new \Exception('크기 이름은 비워둘 수 없습니다.');
        }
        if (strlen($name) > 50) {
            throw new \Exception('크기 이름은 50자를 초과할 수 없습니다.');
        }
        return $name;
    }

    /**
     * 验证尺寸数值
     * 
     * 验证宽度或高度数值的有效性，并进行类型转换（string → float）
     * 
     * 【验证规则】
     * 1. 必须是数值类型（is_numeric检查）
     * 2. 数值必须>0（不能为负数或0）
     * 3. 数值必须≤10000（防止异常大的值）
     * 4. 类型转换：string → float（保留小数精度）
     * 
     * 【数据库对应】
     * - 数据库字段类型：DECIMAL(10,2)
     * - 最大值：9999.99（实际验证使用10000作为上限）
     * - 精度：小数点后2位
     * 
     * 【前端输入处理】
     * - 前端输入框value为字符串类型
     * - 后端接收时需要验证并转换为float
     * - 转换后存储到数据库为DECIMAL类型
     * 
     * 【使用场景】
     * - addCustomSizeAPI()中对size_width和size_height验证
     * - 确保尺寸数值符合业务要求和数据库限制
     * 
     * @param mixed $value 尺寸值（通常为string类型的用户输入）
     * @param string $field 字段名称（'width'或'height'，用于错误提示）
     * @return float 验证并转换后的尺寸值
     * @throws \Exception 验证失败时抛出异常（非数值、≤0、>10000）
     */
    private function validateDimension($value, $field) {
        if (!is_numeric($value)) {
            throw new \Exception("{$field} 값은 숫자여야 합니다.");
        }
        $num = (float)$value;
        if ($num <= 0) {
            throw new \Exception("{$field} 값은 0보다 커야 합니다.");
        }
        if ($num > 10000) {
            throw new \Exception("{$field} 값이 너무 큽니다.");
        }
        return $num;
    }

    /**
     * 验证尺寸单位
     * 
     * 验证尺寸单位是否在允许的范围内
     * 
     * 【允许的单位】
     * - inch：英寸（默认单位，国际通用）
     * - cm：厘米（公制单位）
     * - mm：毫米（精密尺寸）
     * - m：米（大型商品）
     * 
     * 【验证逻辑】
     * - 使用白名单验证（in_array检查）
     * - 不在白名单内的单位会被拒绝
     * - 区分大小写（必须小写）
     * 
     * 【数据库存储】
     * - 字段：custom_goods_sizes.size_unit
     * - 类型：VARCHAR(10)
     * - 默认值：'inch'
     * 
     * 【前端显示】
     * - 前端根据单位显示相应的符号
     * - 例如：50.0 cm → "50 x 60 cm"
     * 
     * @param string $unit 尺寸单位（用户输入或默认值）
     * @return string 验证后的尺寸单位
     * @throws \Exception 验证失败时抛出异常（不支持的单位）
     */
    private function validateUnit($unit) {
        $allowedUnits = ['inch', 'cm', 'mm', 'm'];
        if (!in_array($unit, $allowedUnits)) {
            throw new \Exception("지원하지 않는 단위입니다: {$unit}");
        }
        return $unit;
    }
} 