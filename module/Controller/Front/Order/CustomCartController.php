<?php
/**
 * 自定义购物车控制器
 * 
 * 继承官方CartController，添加定制数据获取逻辑
 * 遵循增量开发原则，不修改官方代码
 * 
 * 【扩展功能】
 * 1. 在官方购物车数据基础上，补充定制图片和客户图案字段
 * 2. 从custom_cart_extension表批量获取定制数据
 * 3. 将定制数据合并到购物车数据中，供模板使用
 * 
 * 【数据流转】
 * 官方Cart组件 → 获取es_cart数据
 *   ↓
 * parent::index() → 调用父类方法
 *   ↓
 * CustomCartExtension → 获取custom_cart_extension数据
 *   ↓
 * 数据合并 → customization_image + customer_pattern_image
 *   ↓
 * 模板渲染 → cart.html显示定制图片
 * 
 * 【ClassLoader机制】
 * Godomall会优先加载 module/Controller/ 下的类
 * 因此本类会自动替换官方 Bundle/Controller/Front/Order/CartController
 * 
 * @package Controller\Front\Order
 * @author Custom Development Team
 * @version 1.0.0
 */

namespace Controller\Front\Order;

use Custom\Component\CustomCartExtension;
use Exception;

/**
 * 自定义购物车控制器类
 */
class CustomCartController extends \Controller\Front\Controller
{
    /**
     * 购物车页面主方法
     * 
     * 重写父类index方法，在获取购物车数据后补充定制字段
     * 
     * @return void
     */
    public function index()
    {
        // 调用父类方法，获取官方购物车数据
        parent::index();
        
        // 补充定制数据
        $this->appendCustomizationData();
    }

    /**
     * 补充定制数据到购物车数据中
     * 
     * 【数据结构说明】
     * $cartInfo 是三层嵌套数组：
     * [
     *     scmKey => [                    // 供应商key
     *         deliveryKey => [           // 配送方式key
     *             itemKey => [           // 商品项
     *                 'sno' => 123,
     *                 'goodsNo' => 456,
     *                 'goodsNm' => '商品名',
     *                 // ... 其他字段
     *             ]
     *         ]
     *     ]
     * ]
     * 
     * @return void
     */
    private function appendCustomizationData()
    {
        try {
            // 获取已设置的购物车数据
            $cartInfo = $this->getData('cartInfo');
            
            if (empty($cartInfo) || !is_array($cartInfo)) {
                return;
            }
            
            // 提取所有购物车项的sno
            $cartSnoList = $this->extractCartSnoList($cartInfo);
            
            if (empty($cartSnoList)) {
                return;
            }
            
            // 批量获取定制数据
            $customCartExt = \App::load('\\Custom\\Component\\CustomCartExtension');
            $customizationImages = $customCartExt->getBatchCustomizationImages($cartSnoList);
            
            // 将定制数据合并到购物车数据中
            $cartInfo = $this->mergeCustomizationData($cartInfo, $customizationImages);
            
            // 更新模板数据
            $this->setData('cartInfo', $cartInfo);
            
        } catch (Exception $e) {
            // 定制数据获取失败不应影响购物车主流程
            // 仅记录错误日志
            error_log('CustomCartController::appendCustomizationData 失败: ' . $e->getMessage());
        }
    }

    /**
     * 从购物车数据中提取所有sno
     * 
     * @param array $cartInfo 购物车数据
     * @return array sno列表
     */
    private function extractCartSnoList($cartInfo)
    {
        $cartSnoList = [];
        
        // 遍历三层嵌套数组
        foreach ($cartInfo as $scmData) {
            if (!is_array($scmData)) {
                continue;
            }
            
            foreach ($scmData as $deliveryData) {
                if (!is_array($deliveryData)) {
                    continue;
                }
                
                foreach ($deliveryData as $item) {
                    if (isset($item['sno']) && is_numeric($item['sno'])) {
                        $cartSnoList[] = $item['sno'];
                    }
                }
            }
        }
        
        return $cartSnoList;
    }

    /**
     * 将定制数据合并到购物车数据中
     * 
     * @param array $cartInfo 购物车数据
     * @param array $customizationImages 定制图片数据（以sno为key，含附加费用）
     * @return array 合并后的购物车数据
     */
    private function mergeCustomizationData($cartInfo, $customizationImages)
    {
        // 遍历三层嵌套数组，为每个商品项添加定制字段
        foreach ($cartInfo as $scmKey => &$scmData) {
            if (!is_array($scmData)) {
                continue;
            }
            
            foreach ($scmData as $deliveryKey => &$deliveryData) {
                if (!is_array($deliveryData)) {
                    continue;
                }
                
                foreach ($deliveryData as $itemKey => &$item) {
                    $sno = $item['sno'] ?? null;
                    
                    if ($sno && isset($customizationImages[$sno])) {
                        // 添加定制图片字段
                        $item['customization_image'] = $customizationImages[$sno]['customization_image'];
                        $item['customer_pattern_image'] = $customizationImages[$sno]['customer_pattern_image'];
                        // 添加附加费用字段（如果没有则为0）
                        $item['extra_price_total'] = isset($customizationImages[$sno]['extra_price_total'])
                            ? (int)$customizationImages[$sno]['extra_price_total']
                            : 0;
                    } else {
                        // 没有定制数据，设置为null/0
                        $item['customization_image'] = null;
                        $item['customer_pattern_image'] = null;
                        $item['extra_price_total'] = 0;
                    }
                }
            }
        }
        
        return $cartInfo;
    }
}

