<?php

/**
 * 自定义数据库配置文件
 * 
 * 完全独立于Godomall的数据库配置
 * 专门用于Custom组件的PDO数据库连接
 * 
 * @package Custom\Config
 * @author Custom Development Team
 * @version 1.0
 */

return [
    // 数据库连接配置
    'host' => '10.1.0.189',
    'port' => 3306,
    'dbname' => 'hellomrlee1_godomall_com',
    'charset' => 'utf8mb4',
    'username' => 'hellomister2',
    'password' => 'Gomaz847!!',
    
    // PDO选项配置
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_PERSISTENT => false, // 不使用持久连接
        PDO::ATTR_TIMEOUT => 30, // 连接超时30秒
    ],
    
    // 数据库表前缀（为空表示不使用前缀）
    'table_prefix' => '',
    
    // 连接池配置（可选）
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 10,
        'connection_timeout' => 30,
    ],
    
    // 日志配置
    'logging' => [
        'enabled' => true,
        'log_level' => 'info', // debug, info, warning, error
        'log_sql' => true, // 是否记录SQL语句
    ],
    
    // 缓存配置（可选）
    'cache' => [
        'enabled' => false,
        'cache_time' => 300, // 缓存时间（秒）
    ]
];
