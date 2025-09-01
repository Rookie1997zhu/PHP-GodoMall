<?php

/**
 * 商品定制功能数据库安装控制器
 * 
 * 本控制器提供后台安装商品定制功能数据库的接口
 * 严格遵循Godomall开发规范，继承官方Controller
 * 
 * @package Controller\Admin\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Admin\Custom;

use Component\Database\CustomDatabaseInstaller;
use Framework\Debug\Exception\AlertBackException;
use Logger;
use Request;

/**
 * 商品定制功能数据库安装控制器
 */
class DatabaseInstallController extends \Controller\Admin\Controller
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 数据库安装页面
     */
    public function index()
    {
        $this->setData('page_title', '商品定制功能数据库安装');
        $this->setData('install_status', $this->getInstallStatus());
    }

    /**
     * 执行数据库安装
     */
    public function install()
    {
        try {
            // 检查管理员权限
            if (!$this->checkAdminPermission()) {
                throw new AlertBackException('权限不足，无法执行数据库安装操作');
            }

            $installer = new CustomDatabaseInstaller();
            $result = $installer->install();

            if ($result['success']) {
                Logger::info('商品定制功能数据库安装成功', $result);
                $this->setData('install_result', $result);
                $this->setData('success_message', '数据库安装成功！');
            } else {
                Logger::error('商品定制功能数据库安装失败', $result);
                $this->setData('install_result', $result);
                $this->setData('error_message', '数据库安装失败：' . $result['message']);
            }

        } catch (AlertBackException $e) {
            $this->setData('error_message', $e->getMessage());
        } catch (\Exception $e) {
            Logger::error('商品定制功能数据库安装异常', ['error' => $e->getMessage()]);
            $this->setData('error_message', '数据库安装过程中发生异常：' . $e->getMessage());
        }

        $this->setData('page_title', '商品定制功能数据库安装结果');
        $this->setData('install_status', $this->getInstallStatus());
    }

    /**
     * 检查安装状态
     */
    public function checkStatus()
    {
        try {
            $status = $this->getInstallStatus();
            
            $response = [
                'success' => true,
                'data' => $status
            ];

        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => '检查安装状态失败：' . $e->getMessage()
            ];
        }

        $this->setData('response', $response);
    }

    /**
     * 获取安装状态
     * 
     * @return array 安装状态信息
     */
    private function getInstallStatus()
    {
        $status = [
            'order_table_extended' => false,
            'goods_table_extended' => false,
            'custom_pattern_table_exists' => false,
            'goods_custom_pattern_table_exists' => false,
            'all_installed' => false
        ];

        try {
            $db = \App::load('DB');

            // 检查订单表是否已扩展
            $result = $db->query("SHOW COLUMNS FROM es_order LIKE 'customization_image'");
            $status['order_table_extended'] = ($result->num_rows > 0);

            // 检查商品表是否已扩展
            $result = $db->query("SHOW COLUMNS FROM es_goods LIKE 'is_customizable'");
            $status['goods_table_extended'] = ($result->num_rows > 0);

            // 检查定制图案表是否存在
            $result = $db->query("SHOW TABLES LIKE 'custom_pattern'");
            $status['custom_pattern_table_exists'] = ($result->num_rows > 0);

            // 检查商品定制图案关联表是否存在
            $result = $db->query("SHOW TABLES LIKE 'goods_custom_pattern'");
            $status['goods_custom_pattern_table_exists'] = ($result->num_rows > 0);

            // 检查是否全部安装完成
            $status['all_installed'] = (
                $status['order_table_extended'] &&
                $status['goods_table_extended'] &&
                $status['custom_pattern_table_exists'] &&
                $status['goods_custom_pattern_table_exists']
            );

        } catch (\Exception $e) {
            Logger::error('检查数据库安装状态失败', ['error' => $e->getMessage()]);
        }

        return $status;
    }

    /**
     * 检查管理员权限
     * 
     * @return bool
     */
    private function checkAdminPermission()
    {
        // 这里可以根据实际需求检查管理员权限
        // 例如检查是否登录、是否有超级管理员权限等
        return true;
    }
} 