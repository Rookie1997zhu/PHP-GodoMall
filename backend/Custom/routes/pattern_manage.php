<?php
/**
 * 图案管理路由配置
 * 
 * @package Custom\Routes
 * @author Custom Developer
 * @since 2024-01-01
 */

// 图案管理页面路由
$router->get('/admin/custom/pattern/manage', 'Custom\Controller\Admin\Custom\PatternManageController@index');

// 图案管理API路由
$router->group('/admin/custom/pattern', function($router) {
    // 获取图案列表
    $router->get('/list', 'Custom\Controller\Admin\Custom\PatternManageController@getPatternListAPI');
    
    // 获取图案信息
    $router->get('/get', 'Custom\Controller\Admin\Custom\PatternManageController@getPatternAPI');
    
    // 图案上传
    $router->post('/upload-pattern', 'Custom\Controller\Admin\Custom\PatternManageController@uploadPatternAPI');
    
    // 更新图案
    $router->post('/update', 'Custom\Controller\Admin\Custom\PatternManageController@updatePatternAPI');
    
    // 删除图案
    $router->post('/delete', 'Custom\Controller\Admin\Custom\PatternManageController@deletePatternAPI');
    
    // 批量更新状态
    $router->post('/batch-update-status', 'Custom\Controller\Admin\Custom\PatternManageController@batchUpdatePatternStatusAPI');
    
    // 批量删除
    $router->post('/batch-delete', 'Custom\Controller\Admin\Custom\PatternManageController@batchDeletePatternsAPI');
    
    // 获取分类列表
    $router->get('/categories', 'Custom\Controller\Admin\Custom\PatternManageController@getPatternCategoriesAPI');
});
