<?php
/**
 * 图案分类管理入口文件
 * 
 * 本文件作为CustomPatternCategoryController的入口点，处理前端API调用
 * 遵循Godomall的URL访问模式
 * 
 * @package Asset\Admin
 * @author Custom Development Team
 * @version 1.0
 */

// 开启错误显示用于调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // ✅ 引入Composer autoloader（解决500错误的关键）
    // 尝试多个可能的路径
    $autoloadPaths = [
        dirname(__FILE__) . '/../../../Vendor/autoload.php',  // 原路径
        dirname(__FILE__) . '/../../Vendor/autoload.php',     // admin/Vendor/
        dirname(__FILE__) . '/../../../../Vendor/autoload.php', // 上一级目录
        '/www/hellomrlee1_godomall_com/backend/Vendor/autoload.php', // 绝对路径
        '/www/system/src/Vendor/autoload.php' // 系统autoloader
    ];
    
    $autoloadLoaded = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $autoloadLoaded = true;
            break;
        }
    }
    
    if (!$autoloadLoaded) {
        // 如果找不到autoloader，直接require控制器文件
        require_once dirname(__FILE__) . '/../../../Custom/Controller/Admin/Custom/CustomPatternCategoryController.php';
    }
    
    // 获取请求的mode参数（支持POST和GET）
    $mode = $_POST['mode'] ?? $_GET['mode'] ?? '';
    
    // 创建控制器实例
    $controller = new \Custom\Controller\Admin\Custom\CustomPatternCategoryController();
    
    // 根据mode调用相应的方法
    switch ($mode) {
        case 'getCategoryListAPI':
            $controller->getCategoryListAPI();
            break;
        case 'addCategoryAPI':
            $controller->addCategoryAPI();
            break;
        case 'updateCategoryAPI':
            $controller->updateCategoryAPI();
            break;
        case 'deleteCategoryAPI':
            $controller->deleteCategoryAPI();
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
        'code' => 500
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
