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
     * @return void
     */
    public function setGoodsPatternsAPI()
    {
        try {
            // 验证请求方法
            if (Request::server()->get('REQUEST_METHOD') !== 'POST') {
                $this->sendErrorResponse(405, '请求方法不允许', ['method' => Request::server()->get('REQUEST_METHOD')]);
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
     * 获取商品图案API
     */
    public function getGoodsPatternsAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();
            
            // 获取商品编号
            $goodsNo = Request::get()->get('goods_no');
            
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
     */
    public function addCustomSizeAPI()
    {
        try {
            // 验证CSRF Token
            $this->validateCsrfToken();
            
            // 获取POST数据
            $postData = Request::post()->get();
            
            // 1. 严格验证必要参数
            $requiredFields = ['goods_id', 'size_name', 'size_width', 'size_height'];
            foreach ($requiredFields as $field) {
                if (!isset($postData[$field]) || trim($postData[$field]) === '') {
                    $this->sendErrorResponse(400, "필수 필드 '{$field}'가 누락되었습니다.");
                    return;
                }
            }
            
            // 2. 验证商品ID
            $goodsId = $postData['goods_id'];
            if ($goodsId === 'new') {
                $this->sendErrorResponse(400, '새 상품을 먼저 저장한 후 크기를 추가하세요.');
                return;
            }
            
            // 3. 验证商品是否存在
            $goodsInfo = $this->getGoodsInfo($goodsId);
            if (!$goodsInfo && $goodsId !== 'new') {
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
            if ($this->customGoods->isSizeNameExists($goodsId, $sizeData['size_name'])) {
                $this->sendErrorResponse(409, '동일한 이름의 크기가 이미 존재합니다.');
                return;
            }
            
            // 6. 添加自定义尺寸
            $result = $this->customGoods->addCustomSize($goodsId, $sizeData);
            
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
            
            // 获取商品ID
            $goodsId = Request::get()->get('goods_id');
            
            if (empty($goodsId)) {
                $this->sendErrorResponse(400, '상품 ID가 필요합니다.');
                return;
            }
            
            // 获取所有尺寸
            $result = $this->customGoods->getGoodsAllSizes($goodsId);
            
            if ($result['success']) {
                $this->sendSuccessResponse('상품 크기 정보를 성공적으로 가져왔습니다.', $result['data']);
            } else {
                $this->sendErrorResponse(500, $result['message']);
            }
            
        } catch (\Exception $e) {
            Logger::error('상품 크기 정보 가져오기 API 예외', [
                'goods_id' => $goodsId ?? null,
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
     * @param string $name 尺寸名称
     * @return string 验证后的尺寸名称
     * @throws \Exception 验证失败时抛出异常
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
     * @param mixed $value 尺寸值
     * @param string $field 字段名称(width/height)
     * @return float 验证后的尺寸值
     * @throws \Exception 验证失败时抛出异常
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
     * @param string $unit 尺寸单位
     * @return string 验证后的尺寸单位
     * @throws \Exception 验证失败时抛出异常
     */
    private function validateUnit($unit) {
        $allowedUnits = ['inch', 'cm', 'mm', 'm'];
        if (!in_array($unit, $allowedUnits)) {
            throw new \Exception("지원하지 않는 단위입니다: {$unit}");
        }
        return $unit;
    }
} 