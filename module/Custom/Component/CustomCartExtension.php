<?php
/**
 * 购物车定制扩展数据组件
 * 
 * 专门负责从custom_cart_extension表获取定制相关字段
 * 遵循增量开发原则，独立于官方购物车组件
 * 
 * 【核心功能】
 * 1. 根据购物车sno获取定制图片和客户图案
 * 2. 批量获取多个购物车项的定制数据
 * 3. 仅提供GET方法，不修改数据
 * 
 * 【数据源】
 * - custom_cart_extension表
 * - 字段：customization_image、customer_pattern_image
 * 
 * 【使用场景】
 * - 购物车列表页面展示定制图片
 * - 订单确认页面展示定制信息
 * 
 * @package Custom\Component
 * @author Custom Development Team
 * @version 1.0.0
 */

namespace Custom\Component;

use Custom\Component\Database\CustomPDO;
use Exception;

/**
 * 购物车定制扩展数据组件类
 */
class CustomCartExtension
{
    /**
     * CustomPDO数据库实例
     * @var CustomPDO
     */
    private $db;

    /**
     * 表名
     * @var string
     */
    private $tableName = 'custom_cart_extension';

    /**
     * 构造函数
     * 
     * 初始化数据库连接
     */
    public function __construct()
    {
        $this->db = new CustomPDO();
    }

    /**
     * 根据购物车sno获取定制图片数据
     * 
     * @param int $cartSno 购物车序列号
     * @return array 包含customization_image和customer_pattern_image的数组
     * 
     * 返回格式：
     * [
     *     'customization_image' => '定制图片URL或null',
     *     'customer_pattern_image' => '客户图案URL或null'
     * ]
     */
    public function getCustomizationImages($cartSno)
    {
        try {
            if (empty($cartSno)) {
                return [
                    'customization_image' => null,
                    'customer_pattern_image' => null
                ];
            }

            $sql = "SELECT 
                        customization_image, 
                        customer_pattern_image 
                    FROM {$this->tableName} 
                    WHERE sno = :sno 
                    LIMIT 1";

            $result = $this->db->fetch($sql, ['sno' => $cartSno]);

            if ($result) {
                return [
                    'customization_image' => $result['customization_image'] ?? null,
                    'customer_pattern_image' => $result['customer_pattern_image'] ?? null
                ];
            }

            return [
                'customization_image' => null,
                'customer_pattern_image' => null
            ];

        } catch (Exception $e) {
            // 记录错误日志但不抛出异常，避免影响购物车主流程
            error_log('CustomCartExtension::getCustomizationImages 失败: ' . $e->getMessage());
            
            return [
                'customization_image' => null,
                'customer_pattern_image' => null
            ];
        }
    }

    /**
     * 批量获取多个购物车项的定制图片数据（含附加费用）
     * 
     * @param array $cartSnoList 购物车序列号数组
     * @return array 以sno为key的关联数组
     * 
     * 返回格式：
     * [
     *     123 => [
     *         'customization_image'   => 'xxx.jpg',
     *         'customer_pattern_image'=> 'yyy.jpg',
     *         'customization_data'    => '{...json...}',
     *         'extra_price_total'     => 1000,
     *     ],
     *     456 => [
     *         'customization_image'   => null,
     *         'customer_pattern_image'=> null,
     *         'customization_data'    => null,
     *         'extra_price_total'     => 0,
     *     ]
     * ]
     */
    public function getBatchCustomizationImages($cartSnoList)
    {
        try {
            if (empty($cartSnoList) || !is_array($cartSnoList)) {
                return [];
            }

            // 过滤并验证sno列表
            $cartSnoList = array_filter($cartSnoList, function($sno) {
                return is_numeric($sno) && $sno > 0;
            });

            if (empty($cartSnoList)) {
                return [];
            }

            // 构建IN查询的占位符
            $placeholders = implode(',', array_fill(0, count($cartSnoList), '?'));
            
            $sql = "SELECT 
                        sno,
                        customization_image, 
                        customer_pattern_image,
                        customization_data,
                        extra_price_total
                    FROM {$this->tableName} 
                    WHERE sno IN ({$placeholders})";

            $results = $this->db->fetchAll($sql, array_values($cartSnoList));

            // 转换为以sno为key的关联数组
            $imageData = [];
            foreach ($results as $row) {
                $imageData[$row['sno']] = [
                    'customization_image'    => $row['customization_image'] ?? null,
                    'customer_pattern_image' => $row['customer_pattern_image'] ?? null,
                    'customization_data'     => $row['customization_data'] ?? null,
                    'extra_price_total'      => (int)($row['extra_price_total'] ?? 0),
                ];
            }

            // 为没有数据的sno补充null值
            foreach ($cartSnoList as $sno) {
                if (!isset($imageData[$sno])) {
                    $imageData[$sno] = [
                        'customization_image'    => null,
                        'customer_pattern_image' => null,
                        'customization_data'     => null,
                        'extra_price_total'      => 0,
                    ];
                }
            }

            return $imageData;

        } catch (Exception $e) {
            error_log('CustomCartExtension::getBatchCustomizationImages 失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 检查购物车项是否有定制数据
     * 
     * @param int $cartSno 购物车序列号
     * @return bool 是否有定制数据
     */
    public function hasCustomizationData($cartSno)
    {
        try {
            if (empty($cartSno)) {
                return false;
            }

            $sql = "SELECT COUNT(*) as cnt 
                    FROM {$this->tableName} 
                    WHERE sno = :sno 
                      AND (customization_image IS NOT NULL 
                           OR customer_pattern_image IS NOT NULL)
                    LIMIT 1";

            $result = $this->db->fetch($sql, ['sno' => $cartSno]);

            return $result && $result['cnt'] > 0;

        } catch (Exception $e) {
            error_log('CustomCartExtension::hasCustomizationData 失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取完整的定制扩展数据（包含所有字段）
     * 
     * @param int $cartSno 购物车序列号
     * @return array|null 完整的定制扩展数据或null
     */
    public function getFullExtensionData($cartSno)
    {
        try {
            if (empty($cartSno)) {
                return null;
            }

            $sql = "SELECT * 
                    FROM {$this->tableName} 
                    WHERE sno = :sno 
                    LIMIT 1";

            $result = $this->db->fetch($sql, ['sno' => $cartSno]);

            return $result ?: null;

        } catch (Exception $e) {
            error_log('CustomCartExtension::getFullExtensionData 失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 将定制数据写入扩展表（内部用，失败抛异常以便事务回滚）
     *
     * @param int $cartSno 购物车序列号（es_cart.sno）
     * @param array $data 定制数据
     * @throws Exception
     */
    private function persistCustomizationData($cartSno, array $data)
    {
        if (empty($cartSno)) {
            throw new Exception('购物车序列号不能为空');
        }
        if (empty($data['customer_pattern_image']) && empty($data['customization_image'])) {
            throw new Exception('客户图案或定制图片不能为空');
        }

        $sql = "INSERT INTO {$this->tableName} 
                (sno, customization_data, selected_color, selected_size, 
                 is_customized, customer_pattern_image, customization_image,
                 extra_price_total, created_at, updated_at)
                VALUES 
                (:sno, :customization_data, :selected_color, :selected_size, 
                 1, :customer_pattern_image, :customization_image,
                 :extra_price_total, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    customization_data = VALUES(customization_data),
                    selected_color = VALUES(selected_color),
                    selected_size = VALUES(selected_size),
                    customer_pattern_image = VALUES(customer_pattern_image),
                    customization_image = VALUES(customization_image),
                    extra_price_total = VALUES(extra_price_total),
                    is_customized = 1,
                    updated_at = NOW()";

        $params = [
            'sno'                   => $cartSno,
            'customization_data'    => $data['customization_data'] ?? null,
            'selected_color'        => $data['selected_color'] ?? null,
            'selected_size'         => $data['selected_size'] ?? null,
            'customer_pattern_image'=> $data['customer_pattern_image'],
            'customization_image'   => $data['customization_image'] ?? null,
            'extra_price_total'     => isset($data['extra_price_total']) ? (int)$data['extra_price_total'] : 0,
        ];

        if (!$this->db->execute($sql, $params)) {
            throw new Exception('扩展表保存失败');
        }
    }

    /**
     * 保存定制数据到购物车扩展表
     * 
     * 用于定制商品加入购物车时，保存定制图片等扩展数据
     * 
     * @param int $cartSno 购物车序列号（es_cart.sno）
     * @param array $data 定制数据数组
     * @return bool 保存是否成功
     * 
     * $data 参数格式：
     * [
     *     'customization_data'   => JSON字符串（定制元素数据）,
     *     'selected_color'       => 选择的颜色,
     *     'selected_size'        => 选择的尺寸,
     *     'customer_pattern_image' => 客户上传的原始图案（Base64）,
     *     'customization_image'  => 生成的定制预览图（Base64）,
     *     'extra_price_total'    => 附加费用总额（int，单位：원）
     * ]
     */
    public function saveCustomizationData($cartSno, $data)
    {
        try {
            $this->persistCustomizationData($cartSno, $data);
            error_log('CustomCartExtension::saveCustomizationData 成功: cartSno=' . $cartSno);
            return true;
        } catch (Exception $e) {
            error_log('CustomCartExtension::saveCustomizationData 失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 同一连接事务内保存扩展表并更新 es_cart 数量（编辑购物车项用）
     *
     * @param int $cartSno 购物车序列号
     * @param array $data 定制数据（同 saveCustomizationData）
     * @param int $quantity 数量；≤0 时仅写扩展表不写 es_cart
     * @return bool
     */
    public function saveCustomizationDataWithCartQuantity($cartSno, array $data, $quantity)
    {
        try {
            $this->db->beginTransaction();
            $this->persistCustomizationData($cartSno, $data);
            if ($quantity > 0) {
                $ok = $this->db->execute(
                    'UPDATE es_cart SET goodsCnt = :cnt WHERE sno = :sno',
                    ['cnt' => (int)$quantity, 'sno' => (int)$cartSno]
                );
                if (!$ok) {
                    throw new Exception('购物车数量更新失败');
                }
            }
            $this->db->commit();
            error_log('CustomCartExtension::saveCustomizationDataWithCartQuantity 成功: cartSno=' . $cartSno);
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('CustomCartExtension::saveCustomizationDataWithCartQuantity 失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除购物车项的定制数据
     * 
     * @param int $cartSno 购物车序列号
     * @return bool 删除是否成功
     */
    public function deleteCustomizationData($cartSno)
    {
        try {
            if (empty($cartSno)) {
                return false;
            }

            $sql = "DELETE FROM {$this->tableName} WHERE sno = :sno";
            $result = $this->db->execute($sql, ['sno' => $cartSno]);

            return $result;

        } catch (Exception $e) {
            error_log('CustomCartExtension::deleteCustomizationData 失败: ' . $e->getMessage());
            return false;
        }
    }
}

