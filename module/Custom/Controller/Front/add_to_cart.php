<?php
/**
 * 定制商品添加到购物车 API 入口
 * 
 * 遵循Godomall开发规范的API处理文件
 * 所有业务逻辑通过Controller和Component处理
 * 
 * @author Custom Development Team
 * @version 1.0
 */

// 设置错误报告级别
error_reporting(E_ALL);
ini_set('display_errors', 0); // 生产环境建议关闭

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 设置CORS头（如果需要）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 引入Godomall框架
    require_once dirname(__FILE__) . '/../../../../init.php';
    
    // 遵循Godomall开发规范，使用官方Request类
    $request = Request::getInstance();
    
    // 验证请求方法
    if ($request->server()->get('REQUEST_METHOD') !== 'POST') {
        throw new \InvalidArgumentException('POST 요청만 지원합니다');
    }
    
    // 获取JSON数据
    $jsonData = file_get_contents('php://input');
    $requestData = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \InvalidArgumentException('잘못된 JSON 데이터 형식입니다');
    }
    
    // 数据验证
    $goodsNo = isset($requestData['goods_no']) ? intval($requestData['goods_no']) : 0;
    $quantity = isset($requestData['quantity']) ? intval($requestData['quantity']) : 1;
    $customizationData = isset($requestData['customization_data']) ? $requestData['customization_data'] : [];
    $selectedColor = isset($requestData['selected_color']) ? trim($requestData['selected_color']) : '';
    $selectedSize = isset($requestData['selected_size']) ? trim($requestData['selected_size']) : '';
    
    // 基本验证
    if ($goodsNo <= 0) {
        throw new \InvalidArgumentException('상품 번호가 유효하지 않습니다');
    }
    
    if ($quantity <= 0) {
        throw new \InvalidArgumentException('상품 수량은 0보다 커야 합니다');
    }
    
    if (empty($customizationData)) {
        throw new \InvalidArgumentException('정제 데이터가 비어있습니다');
    }
    
    // 调用定制商品Controller处理业务逻辑
    $customGoodsController = new \Bundle\Controller\Front\CustomGoodsController();
    
    // 设置请求参数（遵循Godomall规范）
    $params = [
        'goods_no' => $goodsNo,
        'quantity' => $quantity,
        'customization_data' => $customizationData,
        'selected_color' => $selectedColor,
        'selected_size' => $selectedSize
    ];
    
    // 调用添加到购物车方法
    $result = $customGoodsController->addToCart($params);
    
    // 返回JSON响应
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'] ?? '커스텀 상품이 장바구니에 추가되었습니다.',
            'data' => $result['data'] ?? [],
            'redirect_url' => '/order/cart.php'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? '장바구니 추가에 실패했습니다',
            'error_code' => $result['error_code'] ?? 'CART_ADD_FAILED'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (\InvalidArgumentException $e) {
    // 客户端错误
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'INVALID_PARAMETER'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (\Exception $e) {
    // 服务器错误
    error_log('정제 상품 장바구니 추가 실패: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 내부 오류가 발생했습니다. 잠시 후 다시 시도해 주세요',
        'error_code' => 'INTERNAL_SERVER_ERROR'
    ], JSON_UNESCAPED_UNICODE);
}
?>
