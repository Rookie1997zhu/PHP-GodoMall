<?php
/**
 * 商品定制功能 Widget
 * 
 * 本 Widget 用于在商品详情页查询商品是否支持定制功能
 * 严格遵循 Godomall Widget 开发规范
 * 
 * 【使用方式】
 * 在模板中调用：
 * <!--{ set customData = gWidget('Custom\\Widget\\Front\\Goods\\CustomizationWidget', {'goodsNo': goodsView.goodsNo}) }-->
 * 
 * 【返回数据】
 * - is_customizable: 是否启用定制（'0' 或 '1'）
 * - custom_width: 自定义宽度（单位：cm）
 * - custom_height: 自定义高度（单位：cm）
 * - updated_at: 最后更新时间
 * - isCustomizable: 布尔值便利字段
 * - hasCustomSize: 是否设置了自定义尺寸
 * 
 * 【数据来源】
 * custom_goods_extension 表
 * 
 * @package Custom\Widget\Front\Goods
 * @author Custom Development Team
 * @version 1.0
 * @since 2024-10-22
 */

namespace Custom\Widget\Front\Goods;

use Exception;
use Logger;

/**
 * 定制功能状态查询 Widget
 */
class CustomizationWidget extends \Widget\Front\Widget
{
    /**
     * Widget 主方法
     * 
     * 从 custom_goods_extension 表查询商品的定制功能状态
     * 
     * @return void
     */
    public function index()
    {
        try {
            // 从 Widget 数据中获取商品编号
            $goodsNo = $this->getData('goodsNo');
            
            if (empty($goodsNo)) {
                // 如果没有传入商品编号，返回默认值
                $this->setCustomizationData([
                    'is_customizable' => '0',
                    'custom_width' => null,
                    'custom_height' => null,
                    'updated_at' => null
                ]);
                return;
            }
            
            // 查询定制功能状态
            $customData = $this->getCustomizationStatus($goodsNo);
            
            // 设置数据到 Widget
            $this->setCustomizationData($customData);
            
        } catch (Exception $e) {
            // 异常处理：返回默认值（未启用）
            Logger::error('[Widget-商品定制] 查询定制状态失败', [
                'goodsNo' => $goodsNo ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            $this->setCustomizationData([
                'is_customizable' => '0',
                'custom_width' => null,
                'custom_height' => null,
                'updated_at' => null
            ]);
        }
    }
    
    /**
     * 查询商品定制功能状态
     * 
     * 从 custom_goods_extension 表读取定制化配置
     * 
     * @param int $goodsNo 商品编号
     * @return array 定制状态数据
     */
    protected function getCustomizationStatus($goodsNo)
    {
        // 获取数据库实例
        $db = \App::getInstance('DB');
        
        // 查询商品定制化扩展信息
        $query = "
            SELECT 
                is_customizable,
                custom_width,
                custom_height,
                updated_at
            FROM custom_goods_extension 
            WHERE goods_id = ? 
            LIMIT 1
        ";
        
        $result = $db->query_fetch($query, [$goodsNo], false);
        
        if ($result !== false && $result !== null) {
            // 找到定制配置记录
            return [
                'is_customizable' => $result['is_customizable'] ?? '0',
                'custom_width' => $result['custom_width'],
                'custom_height' => $result['custom_height'],
                'updated_at' => $result['updated_at']
            ];
        } else {
            // 未找到记录，返回默认值（未启用）
            return [
                'is_customizable' => '0',
                'custom_width' => null,
                'custom_height' => null,
                'updated_at' => null
            ];
        }
    }
    
    /**
     * 设置定制数据到 Widget
     * 
     * @param array $data 定制数据
     * @return void
     */
    protected function setCustomizationData($data)
    {
        $this->setData('is_customizable', $data['is_customizable']);
        $this->setData('custom_width', $data['custom_width']);
        $this->setData('custom_height', $data['custom_height']);
        $this->setData('updated_at', $data['updated_at']);
        
        // 额外的便利字段
        $this->setData('isCustomizable', ($data['is_customizable'] == '1')); // 布尔值
        $this->setData('hasCustomSize', !empty($data['custom_width']) && !empty($data['custom_height']));
    }
    
    /**
     * 直接获取定制状态（供其他地方调用）
     * 
     * 这个方法可以在控制器中直接调用，不需要渲染 Widget
     * 
     * @param int $goodsNo 商品编号
     * @return array 定制状态数据
     */
    public static function getStatus($goodsNo)
    {
        $widget = new self([]);
        return $widget->getCustomizationStatus($goodsNo);
    }
}

