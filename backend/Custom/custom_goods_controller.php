<?php
/**
 * 定制商品控制器路由文件
 * 处理前端AJAX请求并路由到正确的Controller方法
 */

// 引入Godomall框架
require_once '../route.php';

use Custom\Controller\Front\CustomGoodsController;
use Request;

try {
    // 获取请求模式
    $mode = Request::get()->get('mode') ?: Request::post()->get('mode');
    
    if (empty($mode)) {
        throw new Exception('缺少mode参数');
    }

    // 创建控制器实例
    $controller = new CustomGoodsController();
    
    // 根据mode参数路由到对应方法
    switch ($mode) {
        case 'getPatterns':
            $controller->getPatterns();
            break;
            
        case 'saveCustomization':
            $controller->saveCustomization();
            break;
            
        case 'addToCart':
            $controller->addToCart();
            break;
            
        case 'uploadImage':
            $controller->uploadImage();
            break;
            
        default:
            throw new Exception('无效的mode参数: ' . $mode);
    }

} catch (Exception $e) {
    // 统一错误响应格式
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
