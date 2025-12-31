<?php

/**
 * 商品定制功能 - 自定义数据库表字段定义类
 * 
 * 本类继承自官方的 DBTableField 类，用于定义商品定制功能所需的所有数据库表字段
 * 
 * 设计原则：
 * - 遵循增量开发原则，不修改官方核心代码
 * - 所有自定义表使用 custom_ 前缀
 * - 字段定义格式遵循官方标准：['val' => '字段名', 'typ' => '类型', 'def' => '默认值']
 * - 类型说明：'s' = string, 'i' = integer, 'd' = decimal, 'dt' = datetime
 * 
 * @package   Custom\Component\Database
 * @author    GodoMall Custom Development Team
 * @copyright Copyright (c) 2025
 * @version   1.0.0
 * @since     2025-01-01
 */

namespace Custom\Component\Database;

use Bundle\Component\Database\DBTableField;

/**
 * 自定义数据库表字段定义类
 * 
 * 继承官方 DBTableField 类，添加商品定制功能相关的表字段定义
 */
class CustomDBTableField extends DBTableField
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * [定制图案] custom_pattern 表字段定义
     * 
     * 用于存储商品定制图案的基本信息
     * 
     * @author GodoMall Custom Team
     * @return array custom_pattern 表字段信息
     */
    public static function tableCustomPattern()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'pattern_id', 'typ' => 'i', 'def' => null], // 图案ID，主键
            ['val' => 'pattern_name', 'typ' => 's', 'def' => null], // 图案名称
            ['val' => 'pattern_url', 'typ' => 's', 'def' => null], // 图案文件URL
            ['val' => 'pattern_thumbnail', 'typ' => 's', 'def' => null], // 缩略图URL
            ['val' => 'category', 'typ' => 's', 'def' => null], // 图案分类
            ['val' => 'pattern_group', 'typ' => 's', 'def' => null], // 图案分组
            ['val' => 'tags', 'typ' => 's', 'def' => null], // 标签
            ['val' => 'description', 'typ' => 's', 'def' => null], // 图案描述
            ['val' => 'uploader', 'typ' => 's', 'def' => null], // 上传者
            ['val' => 'file_size', 'typ' => 'i', 'def' => null], // 文件大小（字节）
            ['val' => 'file_type', 'typ' => 's', 'def' => null], // 文件类型
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序顺序
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：0-禁用，1-启用
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品图案关联] goods_custom_pattern 表字段定义
     * 
     * 用于关联商品与定制图案的多对多关系
     * 
     * @author GodoMall Custom Team
     * @return array goods_custom_pattern 表字段信息
     */
    public static function tableGoodsCustomPattern()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 关联ID，主键
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'pattern_id', 'typ' => 'i', 'def' => null], // 图案ID
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序顺序
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品定制记录] custom_goods_customization 表字段定义
     * 
     * 用于存储用户的商品定制记录和配置数据
     * 
     * @author GodoMall Custom Team
     * @return array custom_goods_customization 表字段信息
     */
    public static function tableCustomGoodsCustomization()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'customization_id', 'typ' => 'i', 'def' => null], // 定制记录ID，主键
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'user_id', 'typ' => 'i', 'def' => null], // 用户ID
            ['val' => 'customization_data', 'typ' => 's', 'def' => null], // 定制数据JSON
            ['val' => 'custom_image_url', 'typ' => 's', 'def' => null], // 定制图片URL
            ['val' => 'selected_color', 'typ' => 's', 'def' => null], // 选择的颜色
            ['val' => 'selected_size', 'typ' => 's', 'def' => null], // 选择的尺寸
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：1-待处理，2-已完成，3-已取消，4-已删除
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [上传图片记录] custom_uploaded_images 表字段定义
     * 
     * 用于存储用户上传的定制图片信息
     * 
     * @author GodoMall Custom Team
     * @return array custom_uploaded_images 表字段信息
     */
    public static function tableCustomUploadedImages()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'image_id', 'typ' => 'i', 'def' => null], // 图片ID，主键
            ['val' => 'filename', 'typ' => 's', 'def' => null], // 文件名
            ['val' => 'file_path', 'typ' => 's', 'def' => null], // 文件路径
            ['val' => 'thumbnail_path', 'typ' => 's', 'def' => null], // 缩略图路径
            ['val' => 'file_size', 'typ' => 'i', 'def' => null], // 文件大小（字节）
            ['val' => 'mime_type', 'typ' => 's', 'def' => null], // MIME类型
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：1-正常，0-已删除
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [图案分类] custom_pattern_category 表字段定义
     * 
     * 用于管理定制图案的分类结构（支持多级分类）
     * 
     * @author GodoMall Custom Team
     * @return array custom_pattern_category 表字段信息
     */
    public static function tableCustomPatternCategory()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'category_id', 'typ' => 'i', 'def' => null], // 分类ID，主键
            ['val' => 'category_name', 'typ' => 's', 'def' => null], // 分类名称
            ['val' => 'category_description', 'typ' => 's', 'def' => null], // 分类描述
            ['val' => 'parent_id', 'typ' => 'i', 'def' => 0], // 父分类ID，0表示顶级分类
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序顺序
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：1-启用，0-禁用
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品颜色] es_goods_colors 表字段定义
     * 
     * 用于存储商品颜色的基本信息（全局颜色库）
     * 
     * @author GodoMall Custom Team
     * @return array es_goods_colors 表字段信息
     */
    public static function tableEsGoodsColors()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'color_id', 'typ' => 'i', 'def' => null], // 颜色ID，主键
            ['val' => 'color_code', 'typ' => 's', 'def' => null], // 颜色代码
            ['val' => 'color_name', 'typ' => 's', 'def' => null], // 颜色名称
            ['val' => 'color_hex', 'typ' => 's', 'def' => null], // 颜色十六进制值
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：1-启用，0-禁用
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品尺寸] es_goods_sizes 表字段定义
     * 
     * 用于存储商品尺寸的基本信息（全局尺寸库）
     * 
     * @author GodoMall Custom Team
     * @return array es_goods_sizes 表字段信息
     */
    public static function tableEsGoodsSizes()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'size_id', 'typ' => 'i', 'def' => null], // 尺寸ID，主键
            ['val' => 'size_code', 'typ' => 's', 'def' => null], // 尺寸代码
            ['val' => 'size_name', 'typ' => 's', 'def' => null], // 尺寸名称
            ['val' => 'size_display', 'typ' => 's', 'def' => null], // 显示名称
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：1-启用，0-禁用
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品颜色关联] es_goods_color_relation 表字段定义
     * 
     * 用于关联商品与颜色的多对多关系
     * 
     * @author GodoMall Custom Team
     * @return array es_goods_color_relation 表字段信息
     */
    public static function tableEsGoodsColorRelation()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 关联ID，主键
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'color_id', 'typ' => 'i', 'def' => null], // 颜色ID
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品尺寸关联] es_goods_size_relation 表字段定义
     * 
     * 用于关联商品与尺寸的多对多关系
     * 
     * @author GodoMall Custom Team
     * @return array es_goods_size_relation 表字段信息
     */
    public static function tableEsGoodsSizeRelation()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 关联ID，主键
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'size_id', 'typ' => 'i', 'def' => null], // 尺寸ID
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品定制颜色] custom_goods_colors 表字段定义
     * 
     * 用于存储特定商品的定制颜色（含SVG图片路径）
     * 
     * @author GodoMall Custom Team
     * @return array custom_goods_colors 表字段信息
     */
    public static function tableCustomGoodsColors()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'color_id', 'typ' => 'i', 'def' => null], // 颜色ID，主键
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'color_name', 'typ' => 's', 'def' => null], // 颜色名称
            ['val' => 'color_code', 'typ' => 's', 'def' => null], // 颜色代码
            ['val' => 'color_hex', 'typ' => 's', 'def' => null], // 颜色HEX值
            ['val' => 'svg_image_path', 'typ' => 's', 'def' => null], // SVG图片路径
            ['val' => 'sort_order', 'typ' => 'i', 'def' => 0], // 排序
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：0-禁用，1-启用
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [自定义商品尺寸] custom_goods_sizes 表字段定义
     * 
     * 用于存储商品的自定义尺寸（支持宽高定义）
     * 
     * @author GodoMall Custom Team
     * @return array custom_goods_sizes 表字段信息
     */
    public static function tableCustomGoodsSizes()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'custom_size_id', 'typ' => 'i', 'def' => null], // 自定义尺寸ID，主键
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'size_name', 'typ' => 's', 'def' => null], // 尺寸名称
            ['val' => 'size_width', 'typ' => 'd', 'def' => null], // 宽度
            ['val' => 'size_height', 'typ' => 'd', 'def' => null], // 高度
            ['val' => 'size_unit', 'typ' => 's', 'def' => 'inch'], // 尺寸单位（inch/cm）
            ['val' => 'is_custom', 'typ' => 'i', 'def' => 1], // 是否为自定义尺寸
            ['val' => 'status', 'typ' => 'i', 'def' => 1], // 状态：0-禁用，1-启用
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [商品定制扩展] custom_goods_extension 表字段定义
     * 
     * 替代对 es_goods 表的字段扩展，独立管理商品定制属性
     * 
     * @author GodoMall Custom Team
     * @return array custom_goods_extension 表字段信息
     */
    public static function tableCustomGoodsExtension()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 扩展ID，主键
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID（关联es_goods.goodsNo）
            ['val' => 'is_customizable', 'typ' => 'i', 'def' => 0], // 是否支持定制：0-不支持，1-支持
            ['val' => 'default_color', 'typ' => 's', 'def' => null], // 默认颜色代码
            ['val' => 'default_size', 'typ' => 's', 'def' => null], // 默认尺寸代码
            ['val' => 'custom_width', 'typ' => 'd', 'def' => null], // 自定义宽度（CM）
            ['val' => 'custom_height', 'typ' => 'd', 'def' => null], // 自定义高度（CM）
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [购物车定制扩展] custom_cart_extension 表字段定义
     * 
     * 替代对 es_cart 表的字段扩展，独立管理购物车定制信息
     * 
     * @author GodoMall Custom Team
     * @return array custom_cart_extension 表字段信息
     */
    public static function tableCustomCartExtension()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 扩展ID，主键
            ['val' => 'sno', 'typ' => 'i', 'def' => null], // 购物车序列号（关联es_cart.sno）
            ['val' => 'customization_data', 'typ' => 's', 'def' => null], // 定制数据JSON
            ['val' => 'selected_color', 'typ' => 's', 'def' => null], // 选择的颜色
            ['val' => 'selected_size', 'typ' => 's', 'def' => null], // 选择的尺寸
            ['val' => 'is_customized', 'typ' => 'i', 'def' => 0], // 是否为定制商品
            ['val' => 'customer_pattern_image', 'typ' => 's', 'def' => null], // 客户上传的原始图案URL
            ['val' => 'customization_image', 'typ' => 's', 'def' => null], // 生成的定制预览图URL
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [订单定制扩展] custom_order_extension 表字段定义
     * 
     * 替代对 es_order 表的字段扩展，独立管理订单定制信息
     * 
     * @author GodoMall Custom Team
     * @return array custom_order_extension 表字段信息
     */
    public static function tableCustomOrderExtension()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 扩展ID，主键
            ['val' => 'order_no', 'typ' => 's', 'def' => null], // 订单号（关联es_order.orderNo）
            ['val' => 'customization_image', 'typ' => 's', 'def' => null], // 定制图片URL
            ['val' => 'customer_pattern_image', 'typ' => 's', 'def' => null], // 客户上传的原始图案URL
            ['val' => 'has_customized_goods', 'typ' => 'i', 'def' => 0], // 是否包含定制商品
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }

    /**
     * [订单商品定制扩展] custom_order_goods_extension 表字段定义
     * 
     * 替代对 es_orderGoods 表的字段扩展，独立管理订单商品定制信息
     * 
     * @author GodoMall Custom Team
     * @return array custom_order_goods_extension 表字段信息
     */
    public static function tableCustomOrderGoodsExtension()
    {
        // @formatter:off
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 扩展ID，主键
            ['val' => 'order_goods_sn', 'typ' => 'i', 'def' => null], // 订单商品序列号（关联es_orderGoods.sno）
            ['val' => 'order_no', 'typ' => 's', 'def' => null], // 订单号（关联es_order.orderNo）
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null], // 商品ID
            ['val' => 'customization_data', 'typ' => 's', 'def' => null], // 定制数据JSON
            ['val' => 'selected_color', 'typ' => 's', 'def' => null], // 选择的颜色
            ['val' => 'selected_size', 'typ' => 's', 'def' => null], // 选择的尺寸
            ['val' => 'is_customized', 'typ' => 'i', 'def' => 0], // 是否为定制商品
            ['val' => 'customer_pattern_image', 'typ' => 's', 'def' => null], // 客户上传的原始图案URL
            ['val' => 'customization_image', 'typ' => 's', 'def' => null], // 生成的定制图URL
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null], // 创建时间
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null], // 更新时间
        ];
        // @formatter:on

        return $arrField;
    }
}

