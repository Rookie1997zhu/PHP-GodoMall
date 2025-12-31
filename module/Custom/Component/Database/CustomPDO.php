<?php

/**
 * 自定义PDO数据库操作类
 * 
 * 提供基于PDO的数据库操作，用于替代Godomall的数据库组件
 * 严格遵循Godomall开发规范，保持API兼容性
 * 
 * @package Custom\Component\Database
 * @author Custom Development Team
 * @version 1.0
 */

namespace Custom\Component\Database;

use PDO;
use PDOException;
use Exception;

/**
 * 自定义PDO数据库操作类
 */
class CustomPDO
{
    /**
     * PDO连接实例
     * @var PDO
     */
    private $pdo;

    /**
     * 数据库配置
     * @var array
     */
    private $config;

    /**
     * 构造函数
     * 
     * @param array $config 数据库配置（可选，不传则使用独立配置文件）
     */
    public function __construct($config = null)
    {
        $this->config = $config ?: $this->loadCustomDbConfig();
        $this->connect();
    }

    /**
     * 加载自定义数据库配置
     * 
     * @return array 数据库配置
     */
    private function loadCustomDbConfig()
    {
        try {
            // 加载独立的数据库配置文件
            $configPath = __DIR__ . '/../../config/database.php';
            
            if (!file_exists($configPath)) {
                throw new Exception('数据库配置文件不存在: ' . $configPath);
            }
            
            $config = require $configPath;
            
            if (!is_array($config)) {
                throw new Exception('数据库配置文件格式错误');
            }
            
            // 验证必需的配置项
            $requiredKeys = ['host', 'port', 'dbname', 'username', 'password'];
            foreach ($requiredKeys as $key) {
                if (!isset($config[$key])) {
                    throw new Exception("数据库配置缺少必需项: {$key}");
                }
            }
            
            return $config;
            
        } catch (Exception $e) {
            throw new Exception('数据库配置加载失败：' . $e->getMessage());
        }
    }

    /**
     * 建立PDO连接
     */
    private function connect()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname'],
                $this->config['charset']
            );

            // 使用配置中的PDO选项
            $options = $this->config['options'] ?? [];
            
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );

        } catch (PDOException $e) {
            throw new Exception('数据库连接失败：' . $e->getMessage());
        }
    }

    /**
     * 执行查询并返回单行结果
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return array|false 查询结果或false
     */
    public function fetch($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: false;
        } catch (PDOException $e) {
            throw new Exception('查询失败：' . $e->getMessage());
        }
    }

    /**
     * 执行查询并返回多行结果
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return array 查询结果数组
     */
    public function fetchAll($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            return $result ?: [];
        } catch (PDOException $e) {
            throw new Exception('查询失败：' . $e->getMessage());
        }
    }

    /**
     * 执行插入、更新、删除操作
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return bool 执行结果
     */
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception('执行失败：' . $e->getMessage());
        }
    }

    /**
     * 执行插入操作并返回插入的ID
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return int|false 插入的ID或false
     */
    public function insert($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            throw new Exception('插入失败：' . $e->getMessage());
        }
    }

    /**
     * 开始事务
     */
    public function beginTransaction()
    {
        try {
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            throw new Exception('开始事务失败：' . $e->getMessage());
        }
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        try {
            return $this->pdo->commit();
        } catch (PDOException $e) {
            throw new Exception('提交事务失败：' . $e->getMessage());
        }
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        try {
            return $this->pdo->rollback();
        } catch (PDOException $e) {
            throw new Exception('回滚事务失败：' . $e->getMessage());
        }
    }

    /**
     * 获取最后插入的ID
     * 
     * @return string 最后插入的ID
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取影响的行数
     * 
     * @return int 影响的行数
     */
    public function rowCount()
    {
        return $this->pdo->rowCount();
    }

    /**
     * 获取完整的表名（包含前缀）
     * 
     * @param string $tableName 表名
     * @return string 完整表名
     */
    public function getTableName($tableName)
    {
        // 获取配置中的表前缀
        $prefix = $this->config['table_prefix'] ?? '';
        
        // 如果表名已经包含前缀，直接返回
        if ($prefix && strpos($tableName, $prefix) === 0) {
            return $tableName;
        }
        
        // 添加表前缀
        return $prefix . $tableName;
    }

    /**
     * 获取PDO实例（用于高级操作）
     * 
     * @return PDO PDO实例
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * 关闭数据库连接
     */
    public function close()
    {
        $this->pdo = null;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}
