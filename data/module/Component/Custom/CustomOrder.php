<?php

/**
 * 订单定制组件
 * 
 * 本组件负责定制订单的业务逻辑处理，包括定制图片上传、订单关联等功能
 * 严格遵循Godomall开发规范，基于继承实现功能扩展
 * 
 * @package Component\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Component\Custom;

use Component\Storage\CustomStorage;
use Component\Database\CustomTableField;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\DateTimeUtils;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 订单定制组件
 */
class CustomOrder extends \Component\AbstractComponent
{
    /**
     * 数据库连接对象
     * @var object
     */
    private $db;

    /**
     * 表结构扩展对象
     * @var CustomTableField
     */
    private $tableField;

    /**
     * 定制存储组件
     * @var CustomStorage
     */
    private $customStorage;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = \App::load('DB');
        $this->tableField = new CustomTableField();
        $this->customStorage = new CustomStorage();
    }

    /**
     * 上传定制图片
     * 
     * @param array $uploadData 上传数据
     * @return array 上传结果
     */
    public function uploadCustomImage($uploadData)
    {
        try {
            // 验证上传数据
            $this->validateUploadData($uploadData);

            // 验证文件
            if (!isset($uploadData['file_path']) || !CustomStorage::validateImageFile($uploadData['file_path'])) {
                throw new AlertBackException('无效的图片文件');
            }

            // 生成文件名
            $fileName = $this->generateCustomImageFileName($uploadData);

            // 上传文件
            $fileUrl = CustomStorage::uploadCustomPattern(
                $uploadData['file_path'],
                $fileName,
                $uploadData['upload_options'] ?? []
            );

            if (!$fileUrl) {
                throw new AlertBackException('文件上传失败');
            }

            Logger::info('定制图片上传成功', [
                'file_name' => $fileName,
                'file_url' => $fileUrl,
                'user_id' => $uploadData['user_id'] ?? null
            ]);

            return [
                'success' => true,
                'data' => [
                    'file_name' => $fileName,
                    'file_url' => $fileUrl,
                    'file_size' => filesize($uploadData['file_path'])
                ],
                'message' => '定制图片上传成功'
            ];

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图片上传异常', [
                'error' => $e->getMessage(),
                'data' => $uploadData
            ]);
            throw new AlertBackException('定制图片上传失败：' . $e->getMessage());
        }
    }

    /**
     * 保存订单定制图片
     * 
     * @param int $orderNo 订单号
     * @param string $customImageUrl 定制图片URL
     * @param array $additionalData 附加数据
     * @return array 保存结果
     */
    public function saveOrderCustomImage($orderNo, $customImageUrl, $additionalData = [])
    {
        try {
            // 验证订单是否存在
            if (!$this->isOrderExists($orderNo)) {
                throw new AlertBackException('订单不存在');
            }

            // 验证图片URL
            if (empty($customImageUrl)) {
                throw new AlertBackException('定制图片URL不能为空');
            }

            // 更新订单表
            $result = $this->updateOrderCustomImage($orderNo, $customImageUrl);

            if ($result) {
                Logger::info('订单定制图片保存成功', [
                    'order_no' => $orderNo,
                    'custom_image_url' => $customImageUrl
                ]);

                return [
                    'success' => true,
                    'message' => '订单定制图片保存成功',
                    'data' => [
                        'order_no' => $orderNo,
                        'custom_image_url' => $customImageUrl
                    ]
                ];
            } else {
                throw new AlertBackException('数据库更新失败');
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('保存订单定制图片异常', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo,
                'custom_image_url' => $customImageUrl
            ]);
            throw new AlertBackException('保存订单定制图片失败：' . $e->getMessage());
        }
    }

    /**
     * 获取订单定制图片
     * 
     * @param int $orderNo 订单号
     * @return array|false 定制图片信息或false
     */
    public function getOrderCustomImage($orderNo)
    {
        try {
            $sql = "SELECT customization_image FROM es_order WHERE orderNo = ?";
            $result = $this->db->query($sql, [$orderNo]);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (!empty($row['customization_image'])) {
                    return [
                        'order_no' => $orderNo,
                        'customization_image' => $row['customization_image']
                    ];
                }
            }

            return false;

        } catch (Exception $e) {
            Logger::error('获取订单定制图片异常', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo
            ]);
            return false;
        }
    }

    /**
     * 删除订单定制图片
     * 
     * @param int $orderNo 订单号
     * @return array 删除结果
     */
    public function deleteOrderCustomImage($orderNo)
    {
        try {
            // 获取当前定制图片
            $currentImage = $this->getOrderCustomImage($orderNo);
            
            if (!$currentImage) {
                return [
                    'success' => true,
                    'message' => '订单没有定制图片'
                ];
            }

            // 删除文件
            $fileName = basename($currentImage['customization_image']);
            CustomStorage::deleteCustomPattern($fileName);

            // 清空数据库字段
            $result = $this->updateOrderCustomImage($orderNo, null);

            if ($result) {
                Logger::info('订单定制图片删除成功', [
                    'order_no' => $orderNo,
                    'file_name' => $fileName
                ]);

                return [
                    'success' => true,
                    'message' => '订单定制图片删除成功'
                ];
            } else {
                throw new AlertBackException('数据库更新失败');
            }

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('删除订单定制图片异常', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo
            ]);
            throw new AlertBackException('删除订单定制图片失败：' . $e->getMessage());
        }
    }

    /**
     * 批量获取订单定制图片
     * 
     * @param array $orderNos 订单号数组
     * @return array 定制图片列表
     */
    public function getBatchOrderCustomImages($orderNos)
    {
        try {
            if (empty($orderNos) || !is_array($orderNos)) {
                return [
                    'success' => true,
                    'data' => []
                ];
            }

            $placeholders = str_repeat('?,', count($orderNos) - 1) . '?';
            $sql = "SELECT orderNo, customization_image FROM es_order 
                    WHERE orderNo IN ({$placeholders}) AND customization_image IS NOT NULL AND customization_image != ''";

            $result = $this->db->query($sql, $orderNos);

            $customImages = [];
            while ($row = $result->fetch_assoc()) {
                $customImages[] = [
                    'order_no' => $row['orderNo'],
                    'customization_image' => $row['customization_image']
                ];
            }

            return [
                'success' => true,
                'data' => $customImages
            ];

        } catch (Exception $e) {
            Logger::error('批量获取订单定制图片异常', [
                'error' => $e->getMessage(),
                'order_nos' => $orderNos
            ]);

            return [
                'success' => false,
                'message' => '批量获取订单定制图片失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取有定制图片的订单列表
     * 
     * @param array $params 查询参数
     * @return array 订单列表
     */
    public function getOrdersWithCustomImages($params = [])
    {
        try {
            $defaultParams = [
                'page' => 1,
                'limit' => 20,
                'start_date' => null,
                'end_date' => null,
                'sort_field' => 'regDt',
                'sort_order' => 'DESC'
            ];

            $params = array_merge($defaultParams, $params);

            // 构建查询条件
            $where = ['o.customization_image IS NOT NULL', 'o.customization_image != ""'];
            $bindParams = [];

            if ($params['start_date']) {
                $where[] = "o.regDt >= ?";
                $bindParams[] = $params['start_date'] . ' 00:00:00';
            }

            if ($params['end_date']) {
                $where[] = "o.regDt <= ?";
                $bindParams[] = $params['end_date'] . ' 23:59:59';
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            // 计算总数
            $countSql = "SELECT COUNT(*) as total FROM es_order o {$whereClause}";
            $countResult = $this->db->query($countSql, $bindParams);
            $total = $countResult->fetch_assoc()['total'];

            // 计算分页
            $offset = ($params['page'] - 1) * $params['limit'];
            $limit = $params['limit'];

            // 查询数据
            $sql = "SELECT o.orderNo, o.orderNm, o.customization_image, o.regDt, o.orderStatus, 
                           m.memberId, m.memberNm 
                    FROM es_order o 
                    LEFT JOIN es_member m ON o.memberSno = m.memberSno 
                    {$whereClause} 
                    ORDER BY o.{$params['sort_field']} {$params['sort_order']} 
                    LIMIT {$offset}, {$limit}";

            $result = $this->db->query($sql, $bindParams);

            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }

            return [
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'total' => $total,
                    'page' => $params['page'],
                    'limit' => $params['limit'],
                    'total_pages' => ceil($total / $params['limit'])
                ]
            ];

        } catch (Exception $e) {
            Logger::error('获取有定制图片的订单列表异常', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);

            return [
                'success' => false,
                'message' => '获取订单列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 验证商品是否支持定制（订单前检查）
     * 
     * @param int $goodsNo 商品编号
     * @return array 验证结果
     */
    public function validateGoodsForCustomization($goodsNo)
    {
        try {
            $customGoods = new CustomGoods();
            $customInfo = $customGoods->getGoodsCustomInfo($goodsNo);

            if (!$customInfo['success']) {
                return [
                    'success' => false,
                    'message' => $customInfo['message']
                ];
            }

            if (empty($customInfo['data']['patterns'])) {
                return [
                    'success' => false,
                    'message' => '该商品没有可用的定制图案'
                ];
            }

            return [
                'success' => true,
                'data' => $customInfo['data']
            ];

        } catch (Exception $e) {
            Logger::error('验证商品定制支持异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);

            return [
                'success' => false,
                'message' => '验证商品定制支持失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 生成定制图片文件名
     * 
     * @param array $uploadData 上传数据
     * @return string 文件名
     */
    private function generateCustomImageFileName($uploadData)
    {
        $originalName = $uploadData['file_name'] ?? 'custom_image';
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        
        // 清理文件名
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        
        // 生成时间戳和随机数
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        $userId = $uploadData['user_id'] ?? 'guest';
        
        return "custom_{$userId}_{$name}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * 验证上传数据
     * 
     * @param array $uploadData 上传数据
     * @throws AlertBackException
     */
    private function validateUploadData($uploadData)
    {
        if (!isset($uploadData['file_path']) || !file_exists($uploadData['file_path'])) {
            throw new AlertBackException('上传文件不存在');
        }

        if (!isset($uploadData['file_name']) || empty($uploadData['file_name'])) {
            throw new AlertBackException('文件名不能为空');
        }

        // 检查文件大小（最大20MB）
        if (filesize($uploadData['file_path']) > 20 * 1024 * 1024) {
            throw new AlertBackException('文件大小不能超过20MB');
        }
    }

    /**
     * 检查订单是否存在
     * 
     * @param int $orderNo 订单号
     * @return bool 存在返回true，不存在返回false
     */
    private function isOrderExists($orderNo)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM es_order WHERE orderNo = ?";
            $result = $this->db->query($sql, [$orderNo]);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['count'] > 0;
            }

            return false;

        } catch (Exception $e) {
            Logger::error('检查订单存在性异常', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo
            ]);
            return false;
        }
    }

    /**
     * 更新订单定制图片字段
     * 
     * @param int $orderNo 订单号
     * @param string|null $customImageUrl 定制图片URL
     * @return bool 更新成功返回true，失败返回false
     */
    private function updateOrderCustomImage($orderNo, $customImageUrl)
    {
        try {
            $sql = "UPDATE es_order SET customization_image = ? WHERE orderNo = ?";
            $result = $this->db->query($sql, [$customImageUrl, $orderNo]);

            return $result !== false;

        } catch (Exception $e) {
            Logger::error('更新订单定制图片字段异常', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo,
                'custom_image_url' => $customImageUrl
            ]);
            return false;
        }
    }
} 