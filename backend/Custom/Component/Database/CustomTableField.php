<?php

/**
 * 商品定制功能数据库表结构扩展类
 * 
 * 本类通过继承DBTableField类来扩展数据库表结构，实现商品定制功能所需的表字段
 * 严格遵循Godomall开发规范，不直接修改官方表结构
 * 
 * @package Component\Database
 * @author Custom Development Team
 * @version 1.0
 */

namespace Component\Database;

use Component\Database\DBTableField;

/**
 * 商品定制功能数据库表结构扩展类
 */
class CustomTableField extends DBTableField
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 扩展订单表 - 添加定制图片字段
     * 
     * @return array 字段定义数组
     */
    public function extendOrderTable()
    {
        return [
            'customization_image' => [
                'type' => 'VARCHAR',
                'length' => 500,
                'null' => true,
                'default' => null,
                'comment' => '商品定制图片URL'
            ]
        ];
    }

    /**
     * 扩展商品表 - 添加是否支持定制字段
     * 
     * @return array 字段定义数组
     */
    public function extendGoodsTable()
    {
        return [
            'is_customizable' => [
                'type' => 'TINYINT',
                'length' => 1,
                'null' => false,
                'default' => 0,
                'comment' => '是否支持定制：0-不支持，1-支持'
            ]
        ];
    }

    /**
     * 创建定制图案表
     * 
     * @return array 表结构定义
     */
    public function createCustomPatternTable()
    {
        return [
            'table_name' => 'custom_pattern',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '商品定制图案表',
            'fields' => [
                'pattern_id' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'auto_increment' => true,
                    'comment' => '图案ID，主键'
                ],
                'pattern_name' => [
                    'type' => 'VARCHAR',
                    'length' => 100,
                    'null' => false,
                    'comment' => '图案名称'
                ],
                'pattern_url' => [
                    'type' => 'VARCHAR',
                    'length' => 255,
                    'null' => false,
                    'comment' => '图案文件URL'
                ],
                'pattern_category' => [
                    'type' => 'VARCHAR',
                    'length' => 50,
                    'null' => true,
                    'default' => null,
                    'comment' => '图案分类'
                ],
                'pattern_group' => [
                    'type' => 'VARCHAR',
                    'length' => 50,
                    'null' => true,
                    'default' => null,
                    'comment' => '图案分组'
                ],
                'uploader' => [
                    'type' => 'VARCHAR',
                    'length' => 50,
                    'null' => true,
                    'default' => null,
                    'comment' => '上传者'
                ],
                'file_size' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => true,
                    'default' => null,
                    'comment' => '文件大小（字节）'
                ],
                'file_type' => [
                    'type' => 'VARCHAR',
                    'length' => 20,
                    'null' => true,
                    'default' => null,
                    'comment' => '文件类型'
                ],
                'status' => [
                    'type' => 'TINYINT',
                    'length' => 1,
                    'null' => false,
                    'default' => 1,
                    'comment' => '状态：0-禁用，1-启用'
                ],
                'create_time' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => '创建时间'
                ],
                'update_time' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                    'comment' => '更新时间'
                ]
            ],
            'indexes' => [
                'PRIMARY' => [
                    'type' => 'PRIMARY',
                    'fields' => ['pattern_id']
                ],
                'idx_pattern_category' => [
                    'type' => 'INDEX',
                    'fields' => ['pattern_category']
                ],
                'idx_pattern_group' => [
                    'type' => 'INDEX',
                    'fields' => ['pattern_group']
                ],
                'idx_status' => [
                    'type' => 'INDEX',
                    'fields' => ['status']
                ],
                'idx_create_time' => [
                    'type' => 'INDEX',
                    'fields' => ['create_time']
                ]
            ]
        ];
    }

    /**
     * 创建商品定制图案关联表
     * 
     * @return array 表结构定义
     */
    public function createGoodsCustomPatternTable()
    {
        return [
            'table_name' => 'goods_custom_pattern',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '商品定制图案关联表',
            'fields' => [
                'id' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'auto_increment' => true,
                    'comment' => '关联ID，主键'
                ],
                'goods_id' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'comment' => '商品ID'
                ],
                'pattern_id' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'comment' => '图案ID'
                ],
                'sort_order' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'default' => 0,
                    'comment' => '排序顺序'
                ],
                'create_time' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => '创建时间'
                ]
            ],
            'indexes' => [
                'PRIMARY' => [
                    'type' => 'PRIMARY',
                    'fields' => ['id']
                ],
                'idx_goods_id' => [
                    'type' => 'INDEX',
                    'fields' => ['goods_id']
                ],
                'idx_pattern_id' => [
                    'type' => 'INDEX',
                    'fields' => ['pattern_id']
                ],
                'uidx_goods_pattern' => [
                    'type' => 'UNIQUE',
                    'fields' => ['goods_id', 'pattern_id']
                ]
            ]
        ];
    }
} 