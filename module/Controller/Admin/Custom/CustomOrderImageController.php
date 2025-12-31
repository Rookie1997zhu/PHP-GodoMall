<?php

namespace Controller\Admin\Custom;

use Custom\Component\CustomOrderExtension;
use Exception;
use Request;

class CustomOrderImageController extends \Controller\Admin\Controller
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (Exception $e) {
            error_log('Godomall框架初始化失败: ' . $e->getMessage());
        }
    }

    /**
     * 定制图片下载
     */
    public function index()
    {
        $this->processDownload();
    }

    public function processDownload()
    {
        try {
            $orderNo = Request::get()->get('orderNo');
            $type = Request::get()->get('type');

            if (!$orderNo) {
                throw new Exception('缺少订单号');
            }

            if (!in_array($type, ['custom', 'customer'], true)) {
                throw new Exception('类型参数无效');
            }

            $extension = new CustomOrderExtension();
            $data = $extension->getImageByOrderNo($orderNo);

            if (!$data) {
                throw new Exception('未找到对应的定制图片');
            }

            $base64 = $type === 'custom' ? $data['customization_image'] : $data['customer_pattern_image'];

            if (!$base64) {
                throw new Exception('图片数据为空');
            }

            // 默认值
            $mimeType = 'application/octet-stream';
            $extension = 'png';

            // 从 Base64 数据头提取 MIME 类型
            if (strpos($base64, 'base64,') !== false) {
                $parts = explode('base64,', $base64, 2);
                $header = $parts[0]; // 如 "data:image/svg+xml;"
                
                // 提取 MIME 类型
                if (preg_match('/data:(.*?);/', $header, $matches)) {
                    $mimeType = $matches[1];
                    
                    // 根据 MIME 类型推导文件扩展名
                    if (strpos($mimeType, 'svg') !== false) {
                        $extension = 'svg';
                    } elseif (strpos($mimeType, 'png') !== false) {
                        $extension = 'png';
                    } elseif (strpos($mimeType, 'jpeg') !== false || strpos($mimeType, 'jpg') !== false) {
                        $extension = 'jpg';
                    } elseif (strpos($mimeType, 'gif') !== false) {
                        $extension = 'gif';
                    } elseif (strpos($mimeType, 'webp') !== false) {
                        $extension = 'webp';
                    }
                }
                
                $base64 = $parts[1];
            }

            // 生成文件名，使用识别出的扩展名
            $filename = $type === 'custom' 
                ? "customization_{$orderNo}.{$extension}" 
                : "customer_pattern_{$orderNo}.{$extension}";

            $binary = base64_decode($base64);

            if ($binary === false) {
                throw new Exception('图片解码失败');
            }

            header("Content-Type: {$mimeType}");
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Content-Length: ' . strlen($binary));

            echo $binary;
            exit;
        } catch (Exception $e) {
            header('Content-Type: text/plain; charset=UTF-8');
            echo '下载失败：' . $e->getMessage();
            exit;
        }
    }
}


