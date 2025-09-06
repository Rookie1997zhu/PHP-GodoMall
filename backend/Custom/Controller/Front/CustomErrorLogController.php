<?php

/**
 * 自定义错误日志控制器
 * 
 * 处理前端错误日志的接收和存储
 * 严格遵循Godomall开发规范
 * 
 * @package Controller\Front\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Front\Custom;

use Custom\Component\CustomErrorHandler;
use Framework\Debug\Exception\AlertBackException;
use Exception;
use Logger;
use Request;

/**
 * 自定义错误日志控制器
 */
class CustomErrorLogController extends \Controller\Front\Controller
{
    /**
     * 错误处理器
     * @var CustomErrorHandler
     */
    private $errorHandler;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->errorHandler = new CustomErrorHandler();
    }

    /**
     * 接收前端错误日志
     * 
     * @return void
     */
    public function logError()
    {
        try {
            // 只接受POST请求
            if (Request::get()->getMethod() !== 'POST') {
                $this->json([
                    'success' => false,
                    'message' => 'Method not allowed'
                ]);
                return;
            }

            // 获取错误数据
            $errorData = Request::post()->toArray();
            
            // 验证必要字段
            if (empty($errorData['operation']) || empty($errorData['message'])) {
                $this->json([
                    'success' => false,
                    'message' => 'Missing required fields'
                ]);
                return;
            }

            // 记录错误日志
            $this->errorHandler->logError(
                $errorData['operation'],
                $errorData['message'],
                $errorData,
                $errorData['level'] ?? CustomErrorHandler::LEVEL_ERROR,
                $errorData['type'] ?? CustomErrorHandler::TYPE_SYSTEM
            );

            $this->json([
                'success' => true,
                'message' => 'Error logged successfully'
            ]);

        } catch (Exception $e) {
            Logger::error('Error logging failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Internal server error'
            ]);
        }
    }
}
