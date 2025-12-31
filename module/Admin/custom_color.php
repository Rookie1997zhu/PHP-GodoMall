<?php
/**
 * 商品定制颜色管理入口文件
 * 
 * 本文件作为CustomColorController的入口点，处理前端API调用
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
        require_once dirname(__FILE__) . '/../../../Custom/Controller/Admin/Custom/CustomColorController.php';
    }
    
    // 获取请求的mode参数（支持POST和GET）
    $mode = $_POST['mode'] ?? $_GET['mode'] ?? '';
    
    // 创建控制器实例
    $controller = new \Custom\Controller\Admin\Custom\CustomColorController();
    
    // 根据mode调用相应的方法（显式列出，便于排查）
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
        case 'updateColorStockAPI':
            $controller->updateColorStockAPI();
            break;
        case 'getCustomizationStatusAPI':
            $controller->getCustomizationStatusAPI();
            break;
        case 'updateCustomizationStatusAPI':
            $controller->updateCustomizationStatusAPI();
            break;
        case 'getCustomSizeAPI':
            $controller->getCustomSizeAPI();
            break;
        case 'updateCustomSizeAPI':
            $controller->updateCustomSizeAPI();
            break;
        case 'getWorkGuideAPI':
            $controller->getWorkGuideAPI();
            break;
        case 'uploadWorkGuideAPI':
            $controller->uploadWorkGuideAPI();
            break;

        // ===== 추가 옵션 관련 API =====
        case 'getCustomTemplateAPI':
            $controller->getCustomTemplateAPI();
            break;
        case 'saveCustomTemplateViewBoxAPI':
            $controller->saveCustomTemplateViewBoxAPI();
            break;
        case 'addClipRegionAPI':
            $controller->addClipRegionAPI();
            break;
        case 'updateClipRegionAPI':
            $controller->updateClipRegionAPI();
            break;
        case 'deleteClipRegionAPI':
            $controller->deleteClipRegionAPI();
            break;
        case 'getExtraOptionsAPI':
            $controller->getExtraOptionsAPI();
            break;
        case 'addExtraOptionAPI':
            $controller->addExtraOptionAPI();
            break;
        case 'updateExtraOptionAPI':
            $controller->updateExtraOptionAPI();
            break;
        case 'deleteExtraOptionAPI':
            $controller->deleteExtraOptionAPI();
            break;
        case 'getExtraOptionColorsAPI':
            $controller->getExtraOptionColorsAPI();
            break;
        case 'addExtraOptionColorAPI':
            $controller->addExtraOptionColorAPI();
            break;
        case 'updateExtraOptionColorAPI':
            $controller->updateExtraOptionColorAPI();
            break;
        case 'deleteExtraOptionColorAPI':
            $controller->deleteExtraOptionColorAPI();
            break;

        default:
            // 如果没有指定或未匹配的mode，统一走 index() 分发
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
