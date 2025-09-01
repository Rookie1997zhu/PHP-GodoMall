<?php

/**
 * 商品定制管理控制器
 * 
 * 本控制器提供后台商品定制功能管理的接口，包括商品定制属性配置、图案关联等功能
 * 严格遵循Godomall开发规范，继承官方Controller
 * 
 * @package Controller\Admin\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Admin\Custom;

use Component\Custom\CustomGoods;
use Component\Custom\CustomPattern;
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

            // 获取可选图案库
            $availablePatterns = $this->customGoods->getAvailablePatterns(['limit' => 100]);
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

            // 获取可选图案库（分页）
            $params = [
                'page' => Request::get()->get('page', 1),
                'limit' => Request::get()->get('limit', 20),
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
     * 
     * @param int $goodsNo 商品编号
     * @return array|false 商品信息或false
     */
    private function getGoodsInfo($goodsNo)
    {
        try {
            $sql = "SELECT goodsNo, goodsNm, goodsDisplayFl, goodsSellFl, regDt 
                    FROM es_goods 
                    WHERE goodsNo = ? AND delFl = 'n'";
            
            $result = $this->db->query($sql, [$goodsNo]);

            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }

            return false;

        } catch (Exception $e) {
            Logger::error('获取商品信息异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return false;
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
     * 设置商品定制图案API接口
     * 
     * @return void
     */
    public function setGoodsPatternsAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取请求参数
            $goodsId = Request::post()->get('goods_id');
            $patternIds = Request::post()->get('pattern_ids');
            $operation = Request::post()->get('operation', 'set');

            // 参数验证
            if (!$goodsId) {
                $this->sendErrorResponse(400, '商品ID不能为空');
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
                    $result = $this->customGoods->setGoodsPatterns($goodsId, $patternIds);
                    break;
                case 'add':
                    $result = $this->customGoods->addGoodsPatterns($goodsId, $patternIds);
                    break;
                case 'remove':
                    $result = $this->customGoods->removeGoodsPatterns($goodsId, $patternIds);
                    break;
                default:
                    $this->sendErrorResponse(400, '无效的操作类型', ['operation' => $operation]);
                    return;
            }

            if ($result['success']) {
                $this->sendSuccessResponse('商品图案关联设置成功', [
                    'goods_id' => $goodsId,
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
                'goods_id' => $goodsId ?? null,
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
     * 获取商品定制图案API接口
     * 
     * @return void
     */
    public function getGoodsPatternsAPI()
    {
        try {
            // 验证请求方法
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => $_SERVER['REQUEST_METHOD']]);
                return;
            }

            // 获取请求参数
            $goodsId = Request::get()->get('goods_id');

            // 参数验证
            if (!$goodsId) {
                $this->sendErrorResponse(400, '商品ID不能为空');
                return;
            }

            // 获取商品图案关联
            $result = $this->customGoods->getGoodsPatterns($goodsId);

            if ($result['success']) {
                $this->sendSuccessResponse('获取商品图案关联成功', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message'], $result['data'] ?? null);
            }

        } catch (Exception $e) {
            Logger::error('获取商品定制图案API异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId ?? null
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