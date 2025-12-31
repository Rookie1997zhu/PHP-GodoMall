<?php
/**
 * 商品定制颜色管理入口文件 - 简化版本
 * 
 * 直接require控制器文件，不依赖autoloader
 * 用于解决autoloader路径问题
 * 
 * @package Asset\Admin
 * @author Custom Development Team
 * @version 1.0
 */

// 开启错误显示用于调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 直接require控制器文件和相关依赖
    require_once dirname(__FILE__) . '/../../../Custom/Component/CustomColor.php';
    require_once dirname(__FILE__) . '/../../../Custom/Controller/Admin/Custom/CustomColorController.php';
    
    // 获取请求的mode参数（支持POST和GET）
    $mode = $_POST['mode'] ?? $_GET['mode'] ?? '';
    
    // 创建控制器实例
    $controller = new \Custom\Controller\Admin\Custom\CustomColorController();
    
    // 根据mode调用相应的方法
    switch ($mode) {
        case 'getColorsAPI':
            $controller->getColorsAPI();
            break;
        case 'addColorAPI':
            $controller->addColorAPI();
            break;
        case 'updateColorAPI':
            $controller->updateColorAPI();
            break;
        case 'uploadSvgAPI':
            $controller->uploadSvgAPI();
            break;
        case 'deleteColorAPI':
            $controller->deleteColorAPI();
            break;
        default:
            // 如果没有指定mode，调用index方法
            $controller->index();
            break;
    }
    
} catch (Exception $e) {
    // 错误处理
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误：' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'code' => 500
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
