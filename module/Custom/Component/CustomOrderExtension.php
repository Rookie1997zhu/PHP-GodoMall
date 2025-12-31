<?php

namespace Custom\Component;

use Custom\Component\Database\CustomPDO;
use Exception;

/**
 * Class CustomOrderExtension
 *
 * @package Custom\Component
 */
class CustomOrderExtension
{
    /**
     * @var CustomPDO
     */
    private $db;

    /**
     * @var string
     */
    private $tableName = 'custom_order_extension';

    public function __construct()
    {
        $this->db = new CustomPDO();
    }

    /**
     * 保存订单定制扩展数据（整单维度）
     *
     * @param string $orderNo
     * @param array  $data [
     *     'customization_image'    => '', // 定制预览图（Base64 或 URL）
     *     'customer_pattern_image' => '', // 客户上传图案（单图或多图JSON）
     *     'customization_data'     => '', // 整单定制数据JSON（可选）
     * ]
     */
    public function saveOrderImages($orderNo, array $data)
    {
        if (empty($orderNo)) {
            throw new Exception('orderNo不能为空');
        }

        $sql = "INSERT INTO {$this->tableName}
                (order_no, customization_image, customer_pattern_image, customization_data, has_customized_goods, created_at, updated_at)
                VALUES
                (:order_no, :customization_image, :customer_pattern_image, :customization_data, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    customization_image = VALUES(customization_image),
                    customer_pattern_image = VALUES(customer_pattern_image),
                    customization_data = VALUES(customization_data),
                    has_customized_goods = 1,
                    updated_at = NOW()";

        $params = [
            'order_no' => $orderNo,
            'customization_image' => $data['customization_image'] ?? null,
            'customer_pattern_image' => $data['customer_pattern_image'] ?? null,
            'customization_data' => $data['customization_data'] ?? null,
        ];

        $this->db->execute($sql, $params);
    }

    /**
     * 批量获取订单定制扩展数据
     *
     * @param array $orderNos
     * @return array [orderNo => ['customization_image' => '', 'customer_pattern_image' => '', 'customization_data' => '']]
     */
    public function getImagesByOrderNos(array $orderNos)
    {
        if (empty($orderNos)) {
            return [];
        }

        $orderNos = array_filter($orderNos);
        if (empty($orderNos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderNos), '?'));
        $sql = "SELECT order_no, customization_image, customer_pattern_image, customization_data
                FROM {$this->tableName}
                WHERE order_no IN ({$placeholders})
                ORDER BY order_no DESC";

        $rows = $this->db->fetchAll($sql, array_values($orderNos));

        $result = [];
        foreach ($rows as $row) {
            $result[$row['order_no']] = [
                'customization_image' => $row['customization_image'] ?? null,
                'customer_pattern_image' => $row['customer_pattern_image'] ?? null,
                'customization_data' => $row['customization_data'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * 获取指定订单定制图片数量
     *
     * @param array $orderNos
     * @return int
     */
    public function getImagesCountByOrderNos(array $orderNos)
    {
        if (empty($orderNos)) {
            return 0;
        }

        $orderNos = array_filter($orderNos);
        if (empty($orderNos)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($orderNos), '?'));
        $sql = "SELECT COUNT(*) AS cnt
                FROM {$this->tableName}
                WHERE order_no IN ({$placeholders})";

        $row = $this->db->fetch($sql, array_values($orderNos));

        return (int)($row['cnt'] ?? 0);
    }

    /**
     * 分页获取订单定制扩展数据
     *
     * @param array $orderNos
     * @param int   $page
     * @param int   $pageSize
     *
     * @return array
     */
    public function getImagesByOrderNosWithPagination(array $orderNos, $page = 1, $pageSize = 5)
    {
        if (empty($orderNos)) {
            return [];
        }

        $orderNos = array_filter($orderNos);
        if (empty($orderNos)) {
            return [];
        }

        $page = max(1, (int)$page);
        $pageSize = max(1, (int)$pageSize);
        $offset = ($page - 1) * $pageSize;

        $placeholders = implode(',', array_fill(0, count($orderNos), '?'));
        $sql = "SELECT order_no, customization_image, customer_pattern_image, customization_data
                FROM {$this->tableName}
                WHERE order_no IN ({$placeholders})
                ORDER BY order_no DESC
                LIMIT {$pageSize} OFFSET {$offset}";

        $rows = $this->db->fetchAll($sql, array_values($orderNos));

        $result = [];
        foreach ($rows as $row) {
            $result[$row['order_no']] = [
                'customization_image' => $row['customization_image'] ?? null,
                'customer_pattern_image' => $row['customer_pattern_image'] ?? null,
                'customization_data' => $row['customization_data'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * 按订单号获取定制扩展数据
     *
     * @param string $orderNo
     * @return array|null
     */
    public function getImageByOrderNo($orderNo)
    {
        if (empty($orderNo)) {
            return null;
        }

        $sql = "SELECT order_no, customization_image, customer_pattern_image, customization_data
                FROM {$this->tableName}
                WHERE order_no = :order_no
                LIMIT 1";

        $row = $this->db->fetch($sql, ['order_no' => $orderNo]);

        if (!$row) {
            return null;
        }

        return [
            'order_no' => $row['order_no'],
            'customization_image' => $row['customization_image'] ?? null,
            'customer_pattern_image' => $row['customer_pattern_image'] ?? null,
            'customization_data' => $row['customization_data'] ?? null,
        ];
    }

    /**
     * 获取全部订单定制扩展数据
     *
     * @return array [orderNo => ['customization_image' => '', 'customer_pattern_image' => '']]
     */
    public function getAllImages()
    {
        $sql = "SELECT order_no, customization_image, customer_pattern_image, customization_data
                FROM {$this->tableName}
                WHERE has_customized_goods = 1";

        $rows = $this->db->fetchAll($sql);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['order_no']] = [
                'customization_image' => $row['customization_image'] ?? null,
                'customer_pattern_image' => $row['customer_pattern_image'] ?? null,
                'customization_data' => $row['customization_data'] ?? null,
            ];
        }

        return $result;
    }
}

