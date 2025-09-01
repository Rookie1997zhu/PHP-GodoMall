<?php
/**
 * 定制图案缓存配置
 * 
 * 统一管理CustomPattern相关的缓存设置
 * 
 * @package Custom\Config
 * @author Custom Development Team
 * @version 1.0
 */

namespace Custom\Config;

/**
 * 缓存配置类
 */
class CacheConfig
{
    /**
     * 图案列表缓存配置
     */
    const PATTERN_LIST_CACHE = [
        'prefix' => 'custom_pattern_list_',
        'ttl' => 1800, // 30分钟
        'key_pattern' => 'custom_pattern_list_page_{page}_limit_{limit}_search_{search}_category_{category}_status_{status}_sort_{sort_by}_{sort_order}'
    ];

    /**
     * 图案详情缓存配置
     */
    const PATTERN_DETAIL_CACHE = [
        'prefix' => 'custom_pattern_detail_',
        'ttl' => 3600, // 1小时
        'key_pattern' => 'custom_pattern_detail_{pattern_id}'
    ];

    /**
     * 图案分类缓存配置
     */
    const PATTERN_CATEGORY_CACHE = [
        'prefix' => 'custom_pattern_category_',
        'ttl' => 7200, // 2小时
        'key_pattern' => 'custom_pattern_category_list'
    ];

    /**
     * 图案分组缓存配置
     */
    const PATTERN_GROUP_CACHE = [
        'prefix' => 'custom_pattern_group_',
        'ttl' => 7200, // 2小时
        'key_pattern' => 'custom_pattern_group_list'
    ];

    /**
     * 商品图案关联缓存配置
     */
    const GOODS_PATTERN_CACHE = [
        'prefix' => 'goods_pattern_',
        'ttl' => 3600, // 1小时
        'key_pattern' => 'goods_pattern_{goods_no}'
    ];

    /**
     * 获取缓存键
     * 
     * @param string $type 缓存类型
     * @param array $params 参数
     * @return string 缓存键
     */
    public static function getCacheKey($type, $params = [])
    {
        $config = self::getCacheConfig($type);
        if (!$config) {
            return '';
        }

        $key = $config['key_pattern'];
        
        // 替换参数
        foreach ($params as $param => $value) {
            $key = str_replace('{' . $param . '}', $value, $key);
        }

        return $config['prefix'] . $key;
    }

    /**
     * 获取缓存配置
     * 
     * @param string $type 缓存类型
     * @return array|null 缓存配置
     */
    public static function getCacheConfig($type)
    {
        $configs = [
            'pattern_list' => self::PATTERN_LIST_CACHE,
            'pattern_detail' => self::PATTERN_DETAIL_CACHE,
            'pattern_category' => self::PATTERN_CATEGORY_CACHE,
            'pattern_group' => self::PATTERN_GROUP_CACHE,
            'goods_pattern' => self::GOODS_PATTERN_CACHE
        ];

        return $configs[$type] ?? null;
    }

    /**
     * 获取缓存TTL
     * 
     * @param string $type 缓存类型
     * @return int TTL时间（秒）
     */
    public static function getCacheTtl($type)
    {
        $config = self::getCacheConfig($type);
        return $config['ttl'] ?? 1800; // 默认30分钟
    }

    /**
     * 获取缓存前缀
     * 
     * @param string $type 缓存类型
     * @return string 缓存前缀
     */
    public static function getCachePrefix($type)
    {
        $config = self::getCacheConfig($type);
        return $config['prefix'] ?? 'custom_';
    }

    /**
     * 清除指定类型的缓存
     * 
     * @param string $type 缓存类型
     * @param array $params 参数
     */
    public static function clearCache($type, $params = [])
    {
        $prefix = self::getCachePrefix($type);
        
        // 这里可以调用缓存系统的清除方法
        // 例如：$cache->deletePattern($prefix . '*');
        
        \Logger::info('缓存清除', [
            'type' => $type,
            'prefix' => $prefix,
            'params' => $params
        ]);
    }

    /**
     * 获取所有缓存配置
     * 
     * @return array 所有缓存配置
     */
    public static function getAllCacheConfigs()
    {
        return [
            'pattern_list' => self::PATTERN_LIST_CACHE,
            'pattern_detail' => self::PATTERN_DETAIL_CACHE,
            'pattern_category' => self::PATTERN_CATEGORY_CACHE,
            'pattern_group' => self::PATTERN_GROUP_CACHE,
            'goods_pattern' => self::GOODS_PATTERN_CACHE
        ];
    }
}
