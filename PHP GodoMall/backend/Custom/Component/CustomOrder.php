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
                    'file_size' => filesize($uploadData['file_path']),
                    'upload_time' => date('Y-m-d H:i:s')
                ],
                'message' => '定制图片上传成功'
            ];

        } catch (Exception $e) {
            Logger::error('定制图片上传失败', [
                'error' => $e->getMessage(),
                'upload_data' => $uploadData
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 保存订单定制图片
     * 
     * @param string $orderNo 订单号
     * @param string $customImageUrl 定制图片URL
     * @param array $additionalData 附加数据
     * @return array 保存结果
     */
    public function saveOrderCustomImage($orderNo, $customImageUrl, $additionalData = [])
    {
        try {
            // 验证订单号
            if (empty($orderNo)) {
                throw new AlertBackException('订单号不能为空');
            }

            // 验证图片URL
            if (empty($customImageUrl)) {
                throw new AlertBackException('定制图片URL不能为空');
            }

            // 检查订单是否存在
            if (!$this->isOrderExists($orderNo)) {
                throw new AlertBackException('订单不存在');
            }

            // 检查是否已有定制图片
            $existingImage = $this->getOrderCustomImage($orderNo);
            if ($existingImage && $existingImage['success']) {
                // 更新现有记录
                $result = $this->updateOrderCustomImage($orderNo, $customImageUrl);
            } else {
                // 插入新记录
                $result = $this->insertOrderCustomImage($orderNo, $customImageUrl, $additionalData);
            }

            if ($result) {
                Logger::info('订单定制图片保存成功', [
                    'order_no' => $orderNo,
                    'image_url' => $customImageUrl
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'order_no' => $orderNo,
                        'custom_image_url' => $customImageUrl,
                        'save_time' => date('Y-m-d H:i:s')
                    ],
                    'message' => '订单定制图片保存成功'
                ];
            } else {
                throw new AlertBackException('订单定制图片保存失败');
            }

        } catch (Exception $e) {
            Logger::error('订单定制图片保存失败', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo,
                'image_url' => $customImageUrl
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取订单定制图片
     * 
     * @param string $orderNo 订单号
     * @return array 获取结果
     */
    public function getOrderCustomImage($orderNo)
    {
        try {
            if (empty($orderNo)) {
                throw new AlertBackException('订单号不能为空');
            }

            $sql = "
                SELECT 
                    o.order_no,
                    o.customization_image,
                    o.order_date,
                    o.order_status,
                    o.member_id,
                    o.member_name
                FROM es_order o
                WHERE o.order_no = ? 
                    AND o.customization_image IS NOT NULL 
                    AND o.customization_image != ''
            ";

            $params = [$orderNo];
            $result = $this->db->query($sql, $params);

            if ($result && count($result) > 0) {
                return [
                    'success' => true,
                    'data' => $result[0],
                    'message' => '获取成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '未找到订单定制图片'
                ];
            }

        } catch (Exception $e) {
            Logger::error('获取订单定制图片失败', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 删除订单定制图片
     * 
     * @param string $orderNo 订单号
     * @return array 删除结果
     */
    public function deleteOrderCustomImage($orderNo)
    {
        try {
            if (empty($orderNo)) {
                throw new AlertBackException('订单号不能为空');
            }

            // 获取图片URL用于删除文件
            $imageInfo = $this->getOrderCustomImage($orderNo);
            if (!$imageInfo['success']) {
                throw new AlertBackException('订单定制图片不存在');
            }

            // 删除文件
            if (!empty($imageInfo['data']['customization_image'])) {
                $deleteResult = CustomStorage::deleteCustomPattern($imageInfo['data']['customization_image']);
                if (!$deleteResult) {
                    Logger::warning('定制图片文件删除失败', [
                        'order_no' => $orderNo,
                        'image_url' => $imageInfo['data']['customization_image']
                    ]);
                }
            }

            // 更新数据库
            $sql = "UPDATE es_order SET customization_image = NULL WHERE order_no = ?";
            $params = [$orderNo];
            $result = $this->db->query($sql, $params);

            if ($result) {
                Logger::info('订单定制图片删除成功', [
                    'order_no' => $orderNo
                ]);

                return [
                    'success' => true,
                    'message' => '订单定制图片删除成功'
                ];
            } else {
                throw new AlertBackException('订单定制图片删除失败');
            }

        } catch (Exception $e) {
            Logger::error('订单定制图片删除失败', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 批量获取订单定制图片
     * 
     * @param array $orderNos 订单号数组
     * @return array 获取结果
     */
    public function getBatchOrderCustomImages($orderNos)
    {
        try {
            if (empty($orderNos) || !is_array($orderNos)) {
                throw new AlertBackException('订单号数组不能为空');
            }

            if (count($orderNos) > 100) {
                throw new AlertBackException('单次查询订单数量不能超过100个');
            }

            $placeholders = str_repeat('?,', count($orderNos) - 1) . '?';
            $sql = "
                SELECT 
                    o.order_no,
                    o.customization_image,
                    o.order_date,
                    o.order_status,
                    o.member_id,
                    o.member_name,
                    o.total_price,
                    o.payment_method
                FROM es_order o
                WHERE o.order_no IN ({$placeholders})
                    AND o.customization_image IS NOT NULL 
                    AND o.customization_image != ''
                ORDER BY o.order_date DESC
            ";

            $params = $orderNos;
            $result = $this->db->query($sql, $params);

            if ($result && count($result) > 0) {
                return [
                    'success' => true,
                    'data' => $result,
                    'total' => count($result),
                    'message' => '批量获取成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '未找到相关订单定制图片',
                    'data' => [],
                    'total' => 0
                ];
            }

        } catch (Exception $e) {
            Logger::error('批量获取订单定制图片失败', [
                'error' => $e->getMessage(),
                'order_nos' => $orderNos
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'total' => 0
            ];
        }
    }

    /**
     * 获取包含定制图片的订单列表
     * 
     * @param array $params 查询参数
     * @return array 获取结果
     */
    public function getOrdersWithCustomImages($params = [])
    {
        try {
            $defaultParams = [
                'page' => 1,
                'page_size' => 20,
                'order_status' => '',
                'start_date' => '',
                'end_date' => '',
                'member_id' => '',
                'keyword' => ''
            ];

            $params = array_merge($defaultParams, $params);
            $whereConditions = ['o.customization_image IS NOT NULL', 'o.customization_image != ""'];
            $queryParams = [];

            // 订单状态筛选
            if (!empty($params['order_status'])) {
                $whereConditions[] = 'o.order_status = ?';
                $queryParams[] = $params['order_status'];
            }

            // 日期范围筛选
            if (!empty($params['start_date'])) {
                $whereConditions[] = 'o.order_date >= ?';
                $queryParams[] = $params['start_date'] . ' 00:00:00';
            }

            if (!empty($params['end_date'])) {
                $whereConditions[] = 'o.order_date <= ?';
                $queryParams[] = $params['end_date'] . ' 23:59:59';
            }

            // 会员ID筛选
            if (!empty($params['member_id'])) {
                $whereConditions[] = 'o.member_id = ?';
                $queryParams[] = $params['member_id'];
            }

            // 关键词搜索
            if (!empty($params['keyword'])) {
                $whereConditions[] = '(o.order_no LIKE ? OR o.member_name LIKE ?)';
                $queryParams[] = '%' . $params['keyword'] . '%';
                $queryParams[] = '%' . $params['keyword'] . '%';
            }

            $whereClause = implode(' AND ', $whereConditions);

            // 获取总数
            $countSql = "SELECT COUNT(*) as total FROM es_order o WHERE {$whereClause}";
            $countResult = $this->db->query($countSql, $queryParams);
            $total = $countResult[0]['total'] ?? 0;

            // 分页
            $offset = ($params['page'] - 1) * $params['page_size'];
            $limit = $params['page_size'];

            // 获取订单列表
            $sql = "
                SELECT 
                    o.order_no,
                    o.customization_image,
                    o.order_date,
                    o.order_status,
                    o.member_id,
                    o.member_name,
                    o.total_price,
                    o.payment_method,
                    o.delivery_method
                FROM es_order o
                WHERE {$whereClause}
                ORDER BY o.order_date DESC
                LIMIT {$limit} OFFSET {$offset}
            ";

            $result = $this->db->query($sql, $queryParams);

            return [
                'success' => true,
                'data' => $result ?: [],
                'pagination' => [
                    'current_page' => $params['page'],
                    'page_size' => $params['page_size'],
                    'total' => $total,
                    'total_pages' => ceil($total / $params['page_size'])
                ],
                'message' => '获取成功'
            ];

        } catch (Exception $e) {
            Logger::error('获取包含定制图片的订单列表失败', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'pagination' => []
            ];
        }
    }

    /**
     * 验证商品是否支持定制
     * 
     * @param string $goodsNo 商品编号
     * @return array 验证结果
     */
    public function validateGoodsForCustomization($goodsNo)
    {
        try {
            if (empty($goodsNo)) {
                throw new AlertBackException('商品编号不能为空');
            }

            $sql = "
                SELECT 
                    g.goods_no,
                    g.goods_name,
                    g.is_customizable,
                    g.goods_state,
                    g.stock_cnt
                FROM es_goods g
                WHERE g.goods_no = ?
            ";

            $params = [$goodsNo];
            $result = $this->db->query($sql, $params);

            if (!$result || count($result) === 0) {
                return [
                    'success' => false,
                    'message' => '商品不存在'
                ];
            }

            $goods = $result[0];

            // 检查商品状态
            if ($goods['goods_state'] !== 'Y') {
                return [
                    'success' => false,
                    'message' => '商品已下架'
                ];
            }

            // 检查库存
            if ($goods['stock_cnt'] <= 0) {
                return [
                    'success' => false,
                    'message' => '商品库存不足'
                ];
            }

            // 检查是否支持定制
            if ($goods['is_customizable'] !== 'Y') {
                return [
                    'success' => false,
                    'message' => '该商品不支持定制'
                ];
            }

            return [
                'success' => true,
                'data' => $goods,
                'message' => '商品支持定制'
            ];

        } catch (Exception $e) {
            Logger::error('验证商品定制功能失败', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
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
        $timestamp = time();
        $randomStr = StringUtils::generateRandomString(8);
        $extension = pathinfo($uploadData['file_path'], PATHINFO_EXTENSION);
        
        $prefix = $uploadData['prefix'] ?? 'custom';
        $userId = $uploadData['user_id'] ?? 'guest';
        
        return "{$prefix}_{$userId}_{$timestamp}_{$randomStr}.{$extension}";
    }

    /**
     * 验证上传数据
     * 
     * @param array $uploadData 上传数据
     * @throws AlertBackException
     */
    private function validateUploadData($uploadData)
    {
        if (!is_array($uploadData)) {
            throw new AlertBackException('上传数据格式错误');
        }

        if (empty($uploadData['file_path'])) {
            throw new AlertBackException('文件路径不能为空');
        }

        if (!file_exists($uploadData['file_path'])) {
            throw new AlertBackException('文件不存在');
        }

        if (!is_readable($uploadData['file_path'])) {
            throw new AlertBackException('文件无法读取');
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if (filesize($uploadData['file_path']) > $maxSize) {
            throw new AlertBackException('文件大小超过限制（最大10MB）');
        }
    }

    /**
     * 检查订单是否存在
     * 
     * @param string $orderNo 订单号
     * @return bool 是否存在
     */
    private function isOrderExists($orderNo)
    {
        $sql = "SELECT COUNT(*) as count FROM es_order WHERE order_no = ?";
        $params = [$orderNo];
        $result = $this->db->query($sql, $params);
        
        return $result && $result[0]['count'] > 0;
    }

    /**
     * 插入订单定制图片记录
     * 
     * @param string $orderNo 订单号
     * @param string $customImageUrl 定制图片URL
     * @param array $additionalData 附加数据
     * @return bool 插入结果
     */
    private function insertOrderCustomImage($orderNo, $customImageUrl, $additionalData = [])
    {
        $sql = "
            UPDATE es_order 
            SET customization_image = ?,
                update_date = NOW()
            WHERE order_no = ?
        ";

        $params = [$customImageUrl, $orderNo];
        return $this->db->query($sql, $params);
    }

    /**
     * 更新订单定制图片记录
     * 
     * @param string $orderNo 订单号
     * @param string $customImageUrl 定制图片URL
     * @return bool 更新结果
     */
    private function updateOrderCustomImage($orderNo, $customImageUrl)
    {
        $sql = "
            UPDATE es_order 
            SET customization_image = ?,
                update_date = NOW()
            WHERE order_no = ?
        ";

        $params = [$customImageUrl, $orderNo];
        return $this->db->query($sql, $params);
    }
}
