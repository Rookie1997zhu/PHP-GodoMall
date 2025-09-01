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

namespace Custom\Component\Database;

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
            ],
            'default_color' => [
                'type' => 'VARCHAR',
                'length' => 50,
                'null' => true,
                'default' => null,
                'comment' => '默认颜色代码'
            ],
            'default_size' => [
                'type' => 'VARCHAR',
                'length' => 50,
                'null' => true,
                'default' => null,
                'comment' => '默认尺寸代码'
            ],
            'custom_width' => [
                'type' => 'DECIMAL',
                'length' => '10,2',
                'null' => true,
                'default' => null,
                'comment' => '自定义宽度（CM）'
            ],
            'custom_height' => [
                'type' => 'DECIMAL',
                'length' => '10,2',
                'null' => true,
                'default' => null,
                'comment' => '自定义高度（CM）'
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
                'pattern_description' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                    'comment' => '图案描述'
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
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => '创建时间'
                ],
                'updated_at' => [
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
                'idx_created_at' => [
                    'type' => 'INDEX',
                    'fields' => ['created_at']
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
                'created_at' => [
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

    /**
     * 获取custom_pattern表结构
     * 
     * @return array 表结构定义
     */
    public function tableCustomPattern()
    {
        return [
            'pattern_id' => [
                'type' => 'int',
                'length' => 11,
                'null' => false,
                'default' => null,
                'auto_increment' => true,
                'comment' => '图案ID，主键'
            ],
            'pattern_name' => [
                'type' => 'varchar',
                'length' => 100,
                'null' => false,
                'default' => '',
                'comment' => '图案名称'
            ],
            'pattern_url' => [
                'type' => 'varchar',
                'length' => 255,
                'null' => false,
                'default' => '',
                'comment' => '图案文件URL'
            ],
            'pattern_category' => [
                'type' => 'varchar',
                'length' => 50,
                'null' => true,
                'default' => null,
                'comment' => '图案分类'
            ],
            'pattern_group' => [
                'type' => 'varchar',
                'length' => 50,
                'null' => true,
                'default' => null,
                'comment' => '图案分组'
            ],
            'pattern_description' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'comment' => '图案描述'
            ],
            'uploader' => [
                'type' => 'varchar',
                'length' => 50,
                'null' => true,
                'default' => null,
                'comment' => '上传者'
            ],
            'file_size' => [
                'type' => 'int',
                'length' => 11,
                'null' => true,
                'default' => null,
                'comment' => '文件大小（字节）'
            ],
            'file_type' => [
                'type' => 'varchar',
                'length' => 20,
                'null' => true,
                'default' => null,
                'comment' => '文件类型'
            ],
            'status' => [
                'type' => 'tinyint',
                'length' => 1,
                'null' => false,
                'default' => 1,
                'comment' => '状态：0-禁用，1-启用'
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => '创建时间'
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
                'comment' => '更新时间'
            ]
        ];
    }

    /**
     * 获取custom_pattern_category表结构
     * 
     * @return array 表结构定义
     */
    public function tableCustomPatternCategory()
    {
        return [
            'category_id' => [
                'type' => 'int',
                'length' => 11,
                'null' => false,
                'default' => null,
                'auto_increment' => true,
                'comment' => '分类ID，主键'
            ],
            'category_name' => [
                'type' => 'varchar',
                'length' => 100,
                'null' => false,
                'default' => '',
                'comment' => '分类名称'
            ],
            'category_description' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'comment' => '分类描述'
            ],
            'parent_id' => [
                'type' => 'int',
                'length' => 11,
                'null' => false,
                'default' => 0,
                'comment' => '父分类ID，0表示顶级分类'
            ],
            'sort_order' => [
                'type' => 'int',
                'length' => 11,
                'null' => false,
                'default' => 0,
                'comment' => '排序顺序'
            ],
            'status' => [
                'type' => 'tinyint',
                'length' => 1,
                'null' => false,
                'default' => 1,
                'comment' => '状态：0-禁用，1-启用'
            ],
            'created_time' => [
                'type' => 'datetime',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => '创建时间'
            ],
            'updated_time' => [
                'type' => 'datetime',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
                'comment' => '更新时间'
            ]
        ];
    }

    /**
     * 创建custom_pattern_category表
     * 
     * @return array 表结构定义
     */
    public function createCustomPatternCategoryTable()
    {
        return [
            'table_name' => 'custom_pattern_category',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '图案分类管理表',
            'fields' => [
                'category_id' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'auto_increment' => true,
                    'comment' => '分类ID，主键'
                ],
                'category_name' => [
                    'type' => 'VARCHAR',
                    'length' => 100,
                    'null' => false,
                    'comment' => '分类名称'
                ],
                'category_description' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                    'comment' => '分类描述'
                ],
                'parent_id' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'default' => 0,
                    'comment' => '父分类ID，0表示顶级分类'
                ],
                'sort_order' => [
                    'type' => 'INT',
                    'length' => 11,
                    'null' => false,
                    'default' => 0,
                    'comment' => '排序顺序'
                ],
                'status' => [
                    'type' => 'TINYINT',
                    'length' => 1,
                    'null' => false,
                    'default' => 1,
                    'comment' => '状态：0-禁用，1-启用'
                ],
                'created_time' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => '创建时间'
                ],
                'updated_time' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                    'comment' => '更新时间'
                ]
            ],
            'indexes' => [
                'PRIMARY' => [
                    'type' => 'PRIMARY',
                    'fields' => ['category_id']
                ],
                'idx_parent_id' => [
                    'type' => 'INDEX',
                    'fields' => ['parent_id']
                ],
                'idx_sort_order' => [
                    'type' => 'INDEX',
                    'fields' => ['sort_order']
                ],
                'idx_status' => [
                    'type' => 'INDEX',
                    'fields' => ['status']
                ],
                'idx_category_name' => [
                    'type' => 'INDEX',
                    'fields' => ['category_name']
                ],
                'idx_created_time' => [
                    'type' => 'INDEX',
                    'fields' => ['created_time']
                ]
            ]
        ];
    }
} 