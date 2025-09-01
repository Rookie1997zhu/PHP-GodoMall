<?php
/**
 * 自定义数据库表字段扩展
 * 继承官方DBTableField类，添加定制相关字段
 */

namespace Custom\Component\Database;

use Bundle\Component\Database\DBTableField as BaseDBTableField;

class DBTableField extends BaseDBTableField
{
    /**
     * 扩展购物车表字段（添加定制相关字段）
     * 
     * @param null $conf
     * @return array
     */
    public static function tableCart($conf = null)
    {
        // 继承父类字段
        $arrField = parent::tableCart($conf);
        
        // 添加定制相关字段
        $customFields = [
            ['val' => 'customization_data', 'typ' => 's', 'def' => null], // 定制数据JSON
            ['val' => 'selected_color', 'typ' => 's', 'def' => null], // 选择的颜色
            ['val' => 'selected_size', 'typ' => 's', 'def' => null], // 选择的尺寸
            ['val' => 'is_customized', 'typ' => 'i', 'def' => 0], // 是否为定制商品
        ];
        
        return array_merge($arrField, $customFields);
    }

    /**
     * 扩展订单商品表字段（添加定制相关字段）
     * 
     * @param null $conf
     * @return array
     */
    public static function tableOrderGoods($conf = null)
    {
        // 继承父类字段
        $arrField = parent::tableOrderGoods($conf);
        
        // 添加定制相关字段
        $customFields = [
            ['val' => 'customization_data', 'typ' => 's', 'def' => null], // 定制数据JSON
            ['val' => 'selected_color', 'typ' => 's', 'def' => null], // 选择的颜色
            ['val' => 'selected_size', 'typ' => 's', 'def' => null], // 选择的尺寸
            ['val' => 'is_customized', 'typ' => 'i', 'def' => 0], // 是否为定制商品
        ];
        
        return array_merge($arrField, $customFields);
    }

    /**
     * 商品颜色表字段
     * 
     * @return array
     */
    public static function tableGoodsColors()
    {
        $arrField = [
            ['val' => 'color_id', 'typ' => 'i', 'def' => null], // 颜色ID
            ['val' => 'color_code', 'typ' => 's', 'def' => null], // 颜色代码
            ['val' => 'color_name', 'typ' => 's', 'def' => null], // 颜色名称
            ['val' => 'color_hex', 'typ' => 's', 'def' => null], // 颜色十六进制值
            ['val' => 'status', 'typ' => 's', 'def' => 'active'], // 状态
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序
            ['val' => 'created_at', 'typ' => 's', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 's', 'def' => null], // 更新时间
        ];
        
        return $arrField;
    }

    /**
     * 商品尺寸表字段
     * 
     * @return array
     */
    public static function tableGoodsSizes()
    {
        $arrField = [
            ['val' => 'size_id', 'typ' => 'i', 'def' => null], // 尺寸ID
            ['val' => 'size_code', 'typ' => 's', 'def' => null], // 尺寸代码
            ['val' => 'size_name', 'typ' => 's', 'def' => null], // 尺寸名称
            ['val' => 'size_display', 'typ' => 's', 'def' => null], // 尺寸显示名
            ['val' => 'status', 'typ' => 's', 'def' => 'active'], // 状态
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序
            ['val' => 'created_at', 'typ' => 's', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 's', 'def' => null], // 更新时间
        ];
        
        return $arrField;
    }

    /**
     * 商品颜色关联表字段
     * 
     * @return array
     */
    public static function tableGoodsColorRelation()
    {
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 关联ID
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'color_id', 'typ' => 'i', 'def' => null], // 颜色ID
            ['val' => 'created_at', 'typ' => 's', 'def' => null], // 创建时间
        ];
        
        return $arrField;
    }

    /**
     * 商品尺寸关联表字段
     * 
     * @return array
     */
    public static function tableGoodsSizeRelation()
    {
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 关联ID
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'size_id', 'typ' => 'i', 'def' => null], // 尺寸ID
            ['val' => 'created_at', 'typ' => 's', 'def' => null], // 创建时间
        ];
        
        return $arrField;
    }

    /**
     * 自定义商品尺寸表字段
     * 
     * @return array
     */
    public static function tableCustomGoodsSizes()
    {
        $arrField = [
            ['val' => 'custom_size_id', 'typ' => 'i', 'def' => null], // 自定义尺寸ID
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'size_name', 'typ' => 's', 'def' => null], // 尺寸名称
            ['val' => 'size_width', 'typ' => 'd', 'def' => null], // 宽度
            ['val' => 'size_height', 'typ' => 'd', 'def' => null], // 高度
            ['val' => 'size_unit', 'typ' => 's', 'def' => 'inch'], // 尺寸单位
            ['val' => 'is_custom', 'typ' => 'i', 'def' => 1], // 是否为自定义尺寸
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态
            ['val' => 'created_at', 'typ' => 's', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 's', 'def' => null], // 更新时间
        ];
        
        return $arrField;
    }
}
