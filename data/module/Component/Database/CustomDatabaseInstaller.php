<?php

/**
 * 商品定制功能数据库安装脚本
 * 
 * 本类负责执行商品定制功能相关的数据库表创建和字段扩展操作
 * 严格遵循Godomall开发规范，通过官方方法进行数据库操作
 * 
 * @package Component\Database
 * @author Custom Development Team
 * @version 1.0
 */

namespace Component\Database;

use Component\Database\CustomTableField;
use Exception;
use Logger;

/**
 * 商品定制功能数据库安装脚本
 */
class CustomDatabaseInstaller
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
     * 构造函数
     */
    public function __construct()
    {
        $this->db = \App::load('DB');
        $this->tableField = new CustomTableField();
    }

    /**
     * 执行完整的数据库安装
     * 
     * @return array 安装结果
     */
    public function install()
    {
        $result = [
            'success' => true,
            'message' => '数据库安装完成',
            'details' => []
        ];

        try {
            // 1. 扩展订单表
            $result['details']['extend_order'] = $this->extendOrderTable();
            
            // 2. 扩展商品表
            $result['details']['extend_goods'] = $this->extendGoodsTable();
            
            // 3. 创建定制图案表
            $result['details']['create_pattern'] = $this->createCustomPatternTable();
            
            // 4. 创建商品定制图案关联表
            $result['details']['create_goods_pattern'] = $this->createGoodsCustomPatternTable();

            Logger::info('商品定制功能数据库安装完成', $result);
            
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = '数据库安装失败：' . $e->getMessage();
            Logger::error('商品定制功能数据库安装失败', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * 扩展订单表 - 添加定制图片字段
     * 
     * @return array 执行结果
     */
    private function extendOrderTable()
    {
        try {
            $fields = $this->tableField->extendOrderTable();
            
            foreach ($fields as $fieldName => $fieldDef) {
                $this->addColumnToTable('es_order', $fieldName, $fieldDef);
            }

            return [
                'success' => true,
                'message' => '订单表扩展成功'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '订单表扩展失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 扩展商品表 - 添加是否支持定制字段
     * 
     * @return array 执行结果
     */
    private function extendGoodsTable()
    {
        try {
            $fields = $this->tableField->extendGoodsTable();
            
            foreach ($fields as $fieldName => $fieldDef) {
                $this->addColumnToTable('es_goods', $fieldName, $fieldDef);
            }

            return [
                'success' => true,
                'message' => '商品表扩展成功'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '商品表扩展失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 创建定制图案表
     * 
     * @return array 执行结果
     */
    private function createCustomPatternTable()
    {
        try {
            $tableDef = $this->tableField->createCustomPatternTable();
            $this->createTable($tableDef);

            return [
                'success' => true,
                'message' => '定制图案表创建成功'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '定制图案表创建失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 创建商品定制图案关联表
     * 
     * @return array 执行结果
     */
    private function createGoodsCustomPatternTable()
    {
        try {
            $tableDef = $this->tableField->createGoodsCustomPatternTable();
            $this->createTable($tableDef);

            return [
                'success' => true,
                'message' => '商品定制图案关联表创建成功'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '商品定制图案关联表创建失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 向表中添加字段
     * 
     * @param string $tableName 表名
     * @param string $columnName 字段名
     * @param array $columnDef 字段定义
     * @throws Exception
     */
    private function addColumnToTable($tableName, $columnName, $columnDef)
    {
        // 检查字段是否已存在
        if ($this->columnExists($tableName, $columnName)) {
            Logger::info("字段 {$tableName}.{$columnName} 已存在，跳过添加");
            return;
        }

        $sql = $this->buildAddColumnSQL($tableName, $columnName, $columnDef);
        
        $this->db->query($sql);
        
        Logger::info("成功添加字段 {$tableName}.{$columnName}");
    }

    /**
     * 创建表
     * 
     * @param array $tableDef 表定义
     * @throws Exception
     */
    private function createTable($tableDef)
    {
        $tableName = $tableDef['table_name'];
        
        // 检查表是否已存在
        if ($this->tableExists($tableName)) {
            Logger::info("表 {$tableName} 已存在，跳过创建");
            return;
        }

        $sql = $this->buildCreateTableSQL($tableDef);
        
        $this->db->query($sql);
        
        Logger::info("成功创建表 {$tableName}");
    }

    /**
     * 检查字段是否存在
     * 
     * @param string $tableName 表名
     * @param string $columnName 字段名
     * @return bool
     */
    private function columnExists($tableName, $columnName)
    {
        $sql = "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'";
        $result = $this->db->query($sql);
        
        return $result->num_rows > 0;
    }

    /**
     * 检查表是否存在
     * 
     * @param string $tableName 表名
     * @return bool
     */
    private function tableExists($tableName)
    {
        $sql = "SHOW TABLES LIKE '{$tableName}'";
        $result = $this->db->query($sql);
        
        return $result->num_rows > 0;
    }

    /**
     * 构建添加字段的SQL语句
     * 
     * @param string $tableName 表名
     * @param string $columnName 字段名
     * @param array $columnDef 字段定义
     * @return string SQL语句
     */
    private function buildAddColumnSQL($tableName, $columnName, $columnDef)
    {
        $sql = "ALTER TABLE {$tableName} ADD COLUMN {$columnName} ";
        
        $sql .= $columnDef['type'];
        
        if (isset($columnDef['length'])) {
            $sql .= "({$columnDef['length']})";
        }
        
        if (isset($columnDef['null']) && $columnDef['null'] === false) {
            $sql .= " NOT NULL";
        }
        
        if (isset($columnDef['default'])) {
            if ($columnDef['default'] === null) {
                $sql .= " DEFAULT NULL";
            } else {
                $sql .= " DEFAULT '{$columnDef['default']}'";
            }
        }
        
        if (isset($columnDef['comment'])) {
            $sql .= " COMMENT '{$columnDef['comment']}'";
        }
        
        return $sql;
    }

    /**
     * 构建创建表的SQL语句
     * 
     * @param array $tableDef 表定义
     * @return string SQL语句
     */
    private function buildCreateTableSQL($tableDef)
    {
        $sql = "CREATE TABLE {$tableDef['table_name']} (\n";
        
        $fieldSqls = [];
        foreach ($tableDef['fields'] as $fieldName => $fieldDef) {
            $fieldSql = "    {$fieldName} ";
            $fieldSql .= $fieldDef['type'];
            
            if (isset($fieldDef['length'])) {
                $fieldSql .= "({$fieldDef['length']})";
            }
            
            if (isset($fieldDef['null']) && $fieldDef['null'] === false) {
                $fieldSql .= " NOT NULL";
            }
            
            if (isset($fieldDef['auto_increment']) && $fieldDef['auto_increment']) {
                $fieldSql .= " AUTO_INCREMENT";
            }
            
            if (isset($fieldDef['default'])) {
                if ($fieldDef['default'] === null) {
                    $fieldSql .= " DEFAULT NULL";
                } else {
                    $fieldSql .= " DEFAULT '{$fieldDef['default']}'";
                }
            }
            
            if (isset($fieldDef['comment'])) {
                $fieldSql .= " COMMENT '{$fieldDef['comment']}'";
            }
            
            $fieldSqls[] = $fieldSql;
        }
        
        // 添加索引
        if (isset($tableDef['indexes'])) {
            foreach ($tableDef['indexes'] as $indexName => $indexDef) {
                if ($indexDef['type'] === 'PRIMARY') {
                    $fieldSqls[] = "    PRIMARY KEY (" . implode(', ', $indexDef['fields']) . ")";
                } elseif ($indexDef['type'] === 'UNIQUE') {
                    $fieldSqls[] = "    UNIQUE KEY {$indexName} (" . implode(', ', $indexDef['fields']) . ")";
                } else {
                    $fieldSqls[] = "    KEY {$indexName} (" . implode(', ', $indexDef['fields']) . ")";
                }
            }
        }
        
        $sql .= implode(",\n", $fieldSqls);
        $sql .= "\n) ENGINE={$tableDef['engine']}";
        $sql .= " DEFAULT CHARSET={$tableDef['charset']}";
        $sql .= " COLLATE={$tableDef['collation']}";
        
        if (isset($tableDef['comment'])) {
            $sql .= " COMMENT='{$tableDef['comment']}'";
        }
        
        return $sql;
    }
} 