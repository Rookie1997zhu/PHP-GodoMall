<?php
/**
 * 定制订单图片下载入口文件
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 尝试加载Composer autoload（兼容多种部署路径）
    $autoloadPaths = [
        dirname(__FILE__) . '/../../../Vendor/autoload.php',
        dirname(__FILE__) . '/../../Vendor/autoload.php',
        dirname(__FILE__) . '/../../../../Vendor/autoload.php',
        '/www/hellomrlee1_godomall_com/backend/Vendor/autoload.php',
        '/www/system/src/Vendor/autoload.php',
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
        require_once dirname(__FILE__) . '/../../../Controller/Admin/Custom/CustomOrderImageController.php';
    }

    if (!isset($_POST['xframeFl']) && !isset($_GET['xframeFl'])) {
        $_POST['xframeFl'] = $_GET['xframeFl'] = 'y';
    }

    if (!isset($_REQUEST['xframeFl'])) {
        $_REQUEST['xframeFl'] = 'y';
    }

    $controller = new \Controller\Admin\Custom\CustomOrderImageController();
    $controller->index();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo '定制图片下载失败：' . $e->getMessage();
    exit;
}


