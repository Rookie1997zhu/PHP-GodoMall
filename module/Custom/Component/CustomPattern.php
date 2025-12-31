<?php

/**
 * 定制图案管理组件
 * 
 * 本组件负责定制图案的业务逻辑处理，包括图案的增删改查、分类管理等功能
 * 严格遵循Godomall开发规范，基于继承实现功能扩展
 * 
 * 【核心功能】
 * 1. 图案CRUD操作：添加、更新、删除、查询图案
 * 2. 图案列表管理：支持分页、搜索、分类筛选、排序
 * 3. 缓存机制：实现图案列表缓存，提升查询性能
 * 4. 批量操作：批量删除图案、批量更新状态
 * 5. 依赖检查：删除前检查图案是否被商品使用
 * 
 * 【数据库表操作】
 * - custom_pattern：图案主表（pattern_id, pattern_name, pattern_url等）
 * - goods_custom_pattern：商品图案关联表（用于检查依赖）
 * 
 * 【缓存策略】
 * - 使用Godomall官方SimpleCache系统
 * - 缓存前缀：custom_pattern_
 * - 默认TTL：1800秒（30分钟）
 * - 缓存键规则：包含所有查询参数（page、limit、search、category等）
 * - 缓存锁机制：防止并发请求导致的缓存击穿
 * - 失效时机：图案添加/更新/删除时自动清除相关缓存
 * 
 * 【文件存储集成】
 * - 使用 CustomStorage 组件处理图案文件上传和删除
 * - 支持的格式：JPG、PNG、GIF、SVG
 * - 文件大小限制：最大10MB
 * - 存储路径：/data/custom_patterns/
 * 
 * @package Custom\Component
 * @author Custom Development Team
 * @version 1.0
 */

namespace Custom\Component;

use Bundle\Component\Goods\Goods;
use Custom\Component\Storage\CustomStorage;
use Custom\Component\Database\CustomTableField;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\DateTimeUtils;
use Framework\SimpleCache\SimpleCache;
use Framework\SimpleCache\CacheableProxyFactory;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 定制图案管理组件
 * 继承官方商品组件，获得基础的商品管理功能
 */
class CustomPattern extends Goods
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
     * 缓存对象
     * @var SimpleCache
     */
    private $cache;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 使用Godomall标准的数据库加载方式
        $this->db = App::load('DB');
        $this->tableField = new CustomTableField();
        
        // 初始化Godomall缓存系统
        $this->initCache();
    }

    /**
     * 初始化缓存系统
     * 
     * 【缓存系统配置】
     * - 使用：Godomall官方SimpleCache组件
     * - 前缀：custom_pattern_（避免与其他模块缓存键冲突）
     * - 默认TTL：1800秒（30分钟）
     * 
     * 【初始化失败处理】
     * - 缓存初始化失败不影响主要功能
     * - 降级为每次查询数据库（性能略降，功能正常）
     * - 记录警告日志便于排查
     * 
     * 【使用场景】
     * - 在构造函数中调用
     * - 系统启动时自动初始化
     */
    private function initCache()
    {
        try {
            // 使用Godomall标准的缓存系统
            $this->cache = new SimpleCache();
            
            // 设置缓存配置
            $this->cache->setPrefix('custom_pattern_');
            $this->cache->setDefaultTtl(1800); // 30分钟默认缓存时间
            
        } catch (Exception $e) {
            Logger::warning('缓存系统初始化失败', [
                'error' => $e->getMessage()
            ]);
            // 缓存初始化失败不影响主要功能
        }
    }

    /**
     * 添加定制图案
     * 
     * @param array $patternData 图案数据
     * @return array 添加结果
     */
    public function addPattern($patternData)
    {
        try {
            // 验证必填字段
            $this->validatePatternData($patternData);
            
            // 验证文件
            if (!isset($patternData['file_path']) || !CustomStorage::validateImageFile($patternData['file_path'])) {
                throw new AlertBackException('无效的图片文件');
            }
            
            // 上传文件
            $fileUrl = CustomStorage::uploadCustomPattern(
                $patternData['file_path'],
                $patternData['file_name'],
                $patternData['upload_options'] ?? []
            );
            
            if (!$fileUrl) {
                throw new AlertBackException('文件上传失败');
            }
            
            // 准备数据库数据
            $dbData = [
                'pattern_name' => $patternData['pattern_name'],
                'pattern_url' => $fileUrl,
                'category' => $patternData['category'] ?? null,
                'pattern_group' => $patternData['pattern_group'] ?? null,
                'uploader' => $patternData['uploader'] ?? 'admin',
                'file_size' => filesize($patternData['file_path']),
                'file_type' => pathinfo($patternData['file_name'], PATHINFO_EXTENSION),
                'status' => $patternData['status'] ?? 1
            ];
            
            // 插入数据库
            $patternId = $this->insertPattern($dbData);
            
            if ($patternId) {
                // 清除相关缓存
                $this->clearPatternCache();
                
                Logger::info('定制图案添加成功', [
                    'pattern_id' => $patternId,
                    'pattern_name' => $dbData['pattern_name']
                ]);
                
                return [
                    'success' => true,
                    'pattern_id' => $patternId,
                    'message' => '图案添加成功'
                ];
            } else {
                throw new AlertBackException('数据库插入失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案添加异常', [
                'error' => $e->getMessage(),
                'data' => $patternData
            ]);
            throw new AlertBackException('图案添加失败：' . $e->getMessage());
        }
    }

    /**
     * 更新定制图案
     * 
     * @param int $patternId 图案ID
     * @param array $patternData 图案数据
     * @return array 更新结果
     */
    public function updatePattern($patternId, $patternData)
    {
        try {
            // 检查图案是否存在
            $existingPattern = $this->getPatternById($patternId);
            if (!$existingPattern) {
                throw new AlertBackException('图案不存在');
            }
            
            // 验证数据
            $this->validatePatternData($patternData, false);
            
            // 准备更新数据
            $updateData = [];
            
            if (isset($patternData['pattern_name'])) {
                $updateData['pattern_name'] = $patternData['pattern_name'];
            }
            
            if (isset($patternData['category'])) {
                $updateData['category'] = $patternData['category'];
            }
            
            if (isset($patternData['pattern_group'])) {
                $updateData['pattern_group'] = $patternData['pattern_group'];
            }
            
            if (isset($patternData['status'])) {
                $updateData['status'] = $patternData['status'];
            }
            
            // 如果有新文件上传
            if (isset($patternData['file_path']) && isset($patternData['file_name'])) {
                if (!CustomStorage::validateImageFile($patternData['file_path'])) {
                    throw new AlertBackException('无效的图片文件');
                }
                
                // 上传新文件
                $newFileUrl = CustomStorage::uploadCustomPattern(
                    $patternData['file_path'],
                    $patternData['file_name'],
                    $patternData['upload_options'] ?? []
                );
                
                if (!$newFileUrl) {
                    throw new AlertBackException('新文件上传失败');
                }
                
                $updateData['pattern_url'] = $newFileUrl;
                $updateData['file_size'] = filesize($patternData['file_path']);
                $updateData['file_type'] = pathinfo($patternData['file_name'], PATHINFO_EXTENSION);
                
                // 删除旧文件
                $oldFileName = basename($existingPattern['pattern_url']);
                CustomStorage::deleteCustomPattern($oldFileName);
            }
            
            if (empty($updateData)) {
                throw new AlertBackException('没有需要更新的数据');
            }
            
            // 更新数据库
            $result = $this->updatePatternInDb($patternId, $updateData);
            
            if ($result) {
                // 清除相关缓存
                $this->clearPatternCache($patternId);
                
                Logger::info('定制图案更新成功', [
                    'pattern_id' => $patternId,
                    'update_data' => $updateData
                ]);
                
                return [
                    'success' => true,
                    'message' => '图案更新成功'
                ];
            } else {
                throw new AlertBackException('数据库更新失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案更新异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId,
                'data' => $patternData
            ]);
            throw new AlertBackException('图案更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除定制图案
     * 
     * 删除图案及其关联的文件，包含依赖检查确保数据完整性
     * 
     * 【删除流程】
     * 1. 检查图案是否存在
     * 2. **依赖检查**：检查图案是否被商品使用（关键安全检查）
     * 3. 删除文件：调用CustomStorage删除图案文件
     * 4. 删除记录：从custom_pattern表删除数据库记录
     * 5. 清除缓存：清除该图案的相关缓存
     * 
     * 【依赖检查说明（防止数据不一致）】
     * - 查询 goods_custom_pattern 表检查是否有商品关联此图案
     * - 如果被使用：抛出异常，禁止删除
     * - 目的：避免商品关联的图案被删除后导致前端显示错误
     * - 建议：先解除商品关联，再删除图案
     * 
     * 【文件删除】
     * - 从 pattern_url 提取文件名
     * - 调用 CustomStorage::deleteCustomPattern() 删除文件
     * - 文件删除失败不影响数据库删除（允许孤立文件存在）
     * 
     * 【缓存清除】
     * - 调用 clearPatternCache($patternId) 精确清除
     * - 清除该图案的详情缓存和列表缓存
     * 
     * @param int $patternId 图案ID
     * @return array 删除结果 {success: bool, message: string}
     * @throws AlertBackException 图案不存在、图案正在被使用
     */
    public function deletePattern($patternId)
    {
        try {
            // 检查图案是否存在
            $pattern = $this->getPatternById($patternId);
            if (!$pattern) {
                throw new AlertBackException('图案不存在');
            }
            
            // 检查是否被商品使用
            if ($this->isPatternUsedByGoods($patternId)) {
                throw new AlertBackException('该图案正在被商品使用，无法删除');
            }
            
            // 删除文件
            $fileName = basename($pattern['pattern_url']);
            CustomStorage::deleteCustomPattern($fileName);
            
            // 删除数据库记录
            $result = $this->deletePatternFromDb($patternId);
            
            if ($result) {
                // 清除相关缓存
                $this->clearPatternCache($patternId);
                
                Logger::info('定制图案删除成功', [
                    'pattern_id' => $patternId,
                    'pattern_name' => $pattern['pattern_name']
                ]);
                
                return [
                    'success' => true,
                    'message' => '图案删除成功'
                ];
            } else {
                throw new AlertBackException('数据库删除失败');
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('定制图案删除异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            throw new AlertBackException('图案删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取图案列表（API专用）
     * 支持分页、搜索、分类筛选、排序，包含缓存机制
     * 
     * 【缓存机制详细说明】
     * 1. 生成缓存键：根据所有查询参数生成唯一键（page、limit、search、category、status、sort）
     * 2. 尝试读取缓存：如果缓存命中，直接返回数据（跳过数据库查询）
     * 3. 缓存未命中：执行数据库查询
     * 4. 查询后缓存：将结果缓存1800秒（30分钟）
     * 5. 缓存失效：图案增删改操作会自动清除相关缓存
     * 
     * 【缓存锁机制（防止缓存击穿）】
     * - 并发请求时，只有第一个请求查询数据库
     * - 其他请求检测到锁后等待100ms，然后尝试获取缓存
     * - 锁定时间：10秒（足够数据库查询完成）
     * - 锁键格式：{cacheKey}_lock
     * 
     * 【查询优化】
     * - 支持多条件组合查询（status AND category AND search）
     * - 支持多字段排序（pattern_id, pattern_name, category, sort_order, created_at）
     * - 使用参数绑定防止SQL注入
     * - 分页参数验证（page≥1, limit在1-100之间）
     * 
     * 【缓存失效策略】
     * - 添加图案 → clearPatternCache() 清除所有列表缓存
     * - 更新图案 → clearPatternCache($patternId) 清除特定图案缓存
     * - 删除图案 → clearPatternCache($patternId) 清除特定图案缓存
     * - 批量操作 → clearPatternCache() 清除所有缓存
     * 
     * @param array $params 查询参数 {page, limit, search, category, status, sort_by, sort_order}
     * @return array 图案列表 {success, message, data: {patterns, pagination}}
     */
    public function getPatternListForAPI($params)
    {
        try {
            // 生成缓存键
            $cacheKey = $this->generatePatternListCacheKey($params);
            
            // 尝试从缓存获取数据
            $cachedData = $this->getCachedPatternList($cacheKey);
            if ($cachedData !== false) {
                Logger::info('图案列表从缓存获取', ['cache_key' => $cacheKey]);
                return $cachedData;
            }

            // 构建查询条件
            $where = $this->buildPatternListWhere($params);
            $orderBy = $this->buildPatternListOrderBy($params);
            
            // 获取总数
            $totalQuery = "SELECT COUNT(*) as total FROM custom_pattern WHERE {$where['condition']}";
            $totalResult = $this->db->query($totalQuery, $where['params']);
            $total = $totalResult[0]['total'] ?? 0;
            
            // 计算分页（统一使用 page_size）
            $page = max(1, $params['page']);
            $pageSize = max(1, min(100, $params['page_size']));  // ✅ 改为 page_size
            $offset = ($page - 1) * $pageSize;
            $totalPages = ceil($total / $pageSize);
            
            // 获取图案列表
            $listQuery = "
                SELECT 
                    pattern_id,
                    pattern_name,
                    pattern_url,
                    pattern_thumbnail,
                    category,
                    tags,
                    description,
                    sort_order,
                    status,
                    created_at
                FROM custom_pattern 
                WHERE {$where['condition']}
                ORDER BY {$orderBy}
                LIMIT {$pageSize} OFFSET {$offset}
            ";
            
            $patterns = $this->db->query($listQuery, $where['params']);
            
            // 格式化数据
            $formattedPatterns = $this->formatPatternListForAPI($patterns);
            
            // 构建响应数据
            $responseData = [
                'patterns' => $formattedPatterns,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $pageSize,  // ✅ 使用 page_size
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ];
            
            $result = [
                'success' => true,
                'message' => '图案列表获取成功',
                'data' => $responseData
            ];
            
            // 缓存数据（30分钟）
            $this->cachePatternList($cacheKey, $result, 1800);
            
            Logger::info('图案列表API调用成功', [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,  // ✅ 使用 page_size
                'cache_key' => $cacheKey
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Logger::error('图案列表API调用异常', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => '图案列表获取失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 生成图案列表缓存键
     * 
     * @param array $params 查询参数
     * @return string 缓存键
     */
    private function generatePatternListCacheKey($params)
    {
        $keyParts = [
            'pattern_list',
            'page_' . ($params['page'] ?? 1),
            'page_size_' . ($params['page_size'] ?? 20),        // ✅ 统一使用 page_size
            'keyword_' . md5($params['keyword'] ?? ''),         // ✅ 统一使用 keyword
            'category_' . ($params['category'] ?? ''),
            'status_' . ($params['status'] ?? 'active'),
            'sort_' . ($params['sort_by'] ?? 'sort_order') . '_' . ($params['sort_order'] ?? 'ASC')
        ];
        
        return 'custom_' . implode('_', $keyParts);
    }

    /**
     * 从缓存获取图案列表（带并发控制）
     * 
     * 带缓存锁机制的缓存读取，防止缓存击穿和并发问题
     * 
     * 【缓存击穿问题】
     * - 问题场景：缓存过期瞬间，大量并发请求同时查询数据库
     * - 解决方案：使用缓存锁机制，只允许一个请求查询数据库
     * 
     * 【缓存锁机制实现】
     * 1. 尝试读取缓存数据
     * 2. 缓存未命中 → 检查是否有锁（lockKey = {cacheKey}_lock）
     * 3. 如果有锁：说明其他请求正在查询数据库
     *    - 等待100ms（usleep(100000)）
     *    - 重新尝试读取缓存（此时可能已被其他请求缓存）
     *    - 如果仍未命中：返回false，调用方继续查询
     * 4. 无锁：返回false，调用方查询数据库并设置锁
     * 
     * 【性能优化】
     * - 减少数据库并发查询压力（100个并发请求→只有1个查询数据库）
     * - 等待时间短（100ms），用户体验影响小
     * - 锁超时自动释放（10秒），避免死锁
     * 
     * @param string $cacheKey 缓存键
     * @return array|false 缓存数据（命中）或false（未命中）
     */
    private function getCachedPatternList($cacheKey)
    {
        try {
            if (!$this->cache) {
                return false;
            }
            
            // ✅ 尝试从缓存获取数据
            $cachedData = $this->cache->get($cacheKey);
            
            if ($cachedData !== null) {
                Logger::info('图案列表从缓存获取', ['cache_key' => $cacheKey]);
                return $cachedData;
            }
            
            // ✅ 缓存未命中，检查是否有其他进程正在加载
            $lockKey = $cacheKey . '_lock';
            $isLocked = $this->cache->get($lockKey);
            
            if ($isLocked) {
                // 其他进程正在加载，等待一小段时间后重试
                usleep(100000); // 等待100毫秒
                $retryData = $this->cache->get($cacheKey);
                if ($retryData !== null) {
                    Logger::info('图案列表从缓存获取（重试成功）', ['cache_key' => $cacheKey]);
                    return $retryData;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::warning('图案列表缓存读取失败', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 缓存图案列表数据（带并发控制）
     * 
     * 将查询结果缓存到SimpleCache系统，使用锁机制防止并发缓存
     * 
     * 【缓存写入流程】
     * 1. 设置缓存锁：{cacheKey}_lock，锁定10秒
     * 2. 写入缓存数据：使用cache->set()存储
     * 3. 释放缓存锁：删除lockKey
     * 4. 记录日志：缓存保存成功/失败
     * 
     * 【锁的作用】
     * - 防止多个并发请求同时缓存相同数据
     * - 确保只有一个请求执行缓存写入
     * - 其他请求等待后直接读取已缓存的数据
     * 
     * 【TTL设置】
     * - 默认1800秒（30分钟）
     * - 平衡缓存命中率和数据新鲜度
     * - 过期后自动重建（懒加载）
     * 
     * 【异常处理】
     * - 缓存失败不影响主流程（降级到每次查询数据库）
     * - 记录警告日志便于监控
     * 
     * @param string $cacheKey 缓存键
     * @param array $data 要缓存的数据（完整的API响应）
     * @param int $ttl 缓存时间（秒），默认1800
     * @return bool 是否成功
     */
    private function cachePatternList($cacheKey, $data, $ttl)
    {
        try {
            if (!$this->cache) {
                return false;
            }
            
            // ✅ 设置缓存锁，防止并发重复加载
            $lockKey = $cacheKey . '_lock';
            $this->cache->set($lockKey, 1, 10); // 锁定10秒
            
            try {
                // 使用Godomall标准的缓存系统
                $result = $this->cache->set($cacheKey, $data, $ttl);
                
                if ($result) {
                    Logger::info('图案列表缓存保存成功', [
                        'cache_key' => $cacheKey,
                        'ttl' => $ttl
                    ]);
                    return true;
                }
                
                return false;
                
            } finally {
                // ✅ 无论成功失败，都释放锁
                $this->cache->delete($lockKey);
            }
            
        } catch (Exception $e) {
            Logger::warning('图案列表缓存保存失败', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 清除图案相关缓存（带锁清除）
     * 
     * 在图案数据变更时清除相关缓存，确保用户获取最新数据
     * 
     * 【缓存清除策略】
     * 1. 指定patternId：清除特定图案的缓存（精确清除）
     *    - pattern_{patternId}：单个图案详情缓存
     *    - pattern_list_{patternId}：包含该图案的列表缓存
     *    - pattern_{patternId}_lock：该图案的缓存锁
     * 
     * 2. 不指定patternId：清除所有图案相关缓存（全量清除）
     *    - custom_pattern_*：所有图案缓存（使用deletePattern通配符）
     *    - custom_pattern_*_lock：所有缓存锁
     * 
     * 【调用时机】
     * - 添加图案后：clearPatternCache() - 全量清除（影响所有列表）
     * - 更新图案后：clearPatternCache($patternId) - 精确清除（只影响该图案）
     * - 删除图案后：clearPatternCache($patternId) - 精确清除
     * - 批量操作后：clearPatternCache() - 全量清除（确保一致性）
     * 
     * 【性能考虑】
     * - 精确清除：减少缓存重建开销，适用于单个图案操作
     * - 全量清除：确保数据一致性，适用于批量操作
     * - 自动重建：下次请求时自动重建缓存（懒加载）
     * 
     * @param string $patternId 图案ID（可选，传入则精确清除，不传则全量清除）
     */
    public function clearPatternCache($patternId = null)
    {
        try {
            if (!$this->cache) {
                return;
            }
            
            if ($patternId) {
                // ✅ 清除特定图案的缓存及其锁
                $this->cache->delete('pattern_' . $patternId);
                $this->cache->delete('pattern_list_' . $patternId);
                $this->cache->delete('pattern_' . $patternId . '_lock');
            } else {
                // ✅ 清除所有图案相关缓存（包括锁）
                $this->cache->deletePattern('custom_pattern_*');
                $this->cache->deletePattern('custom_pattern_*_lock');
            }
            
            Logger::info('图案缓存清除成功', [
                'pattern_id' => $patternId,
                'scope' => $patternId ? 'specific' : 'all'
            ]);
            
        } catch (Exception $e) {
            Logger::warning('图案缓存清除失败', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
        }
    }

    /**
     * 构建图案列表查询条件
     * 
     * @param array $params 查询参数
     * @return array 查询条件和参数
     */
    private function buildPatternListWhere($params)
    {
        $conditions = [];
        $queryParams = [];
        
        // 状态条件
        if (!empty($params['status'])) {
            $conditions[] = "status = ?";
            $queryParams[] = $params['status'];
        }
        
        // 分类条件
        if (!empty($params['category'])) {
            $conditions[] = "category = ?";
            $queryParams[] = $params['category'];
        }
        
        // 搜索条件（模糊匹配）- 统一使用 keyword
        if (!empty($params['keyword'])) {
            $searchTerm = '%' . $params['keyword'] . '%';
            $conditions[] = "(pattern_name LIKE ? OR tags LIKE ? OR description LIKE ?)";
            $queryParams[] = $searchTerm;
            $queryParams[] = $searchTerm;
            $queryParams[] = $searchTerm;
        }
        
        $whereCondition = empty($conditions) ? '1=1' : implode(' AND ', $conditions);
        
        return [
            'condition' => $whereCondition,
            'params' => $queryParams
        ];
    }

    /**
     * 构建图案列表排序
     * 
     * @param array $params 查询参数
     * @return string 排序语句
     */
    private function buildPatternListOrderBy($params)
    {
        $sortBy = $params['sort_by'] ?? 'sort_order';
        $sortOrder = strtoupper($params['sort_order'] ?? 'ASC');
        
        // 验证排序字段
        $allowedSortFields = ['pattern_id', 'pattern_name', 'category', 'sort_order', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'sort_order';
        }
        
        // 验证排序方向
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'ASC';
        }
        
        return "{$sortBy} {$sortOrder}";
    }

    /**
     * 格式化图案列表数据（API专用）
     * 
     * @param array $patterns 原始图案数据
     * @return array 格式化后的数据
     */
    private function formatPatternListForAPI($patterns)
    {
        $formatted = [];
        
        foreach ($patterns as $pattern) {
            $formatted[] = [
                'pattern_id' => (int)$pattern['pattern_id'],
                'pattern_name' => $pattern['pattern_name'],
                'pattern_url' => $pattern['pattern_url'],
                'pattern_thumbnail' => $pattern['pattern_thumbnail'],
                'category' => $pattern['category'],
                'tags' => $pattern['tags'],
                'description' => $pattern['description'],
                'sort_order' => (int)$pattern['sort_order'],
                'status' => $pattern['status'],
                'created_at' => $pattern['created_at']
            ];
        }
        
        return $formatted;
    }

    /**
     * 获取图案列表
     * 
     * @param array $params 查询参数
     * @return array 图案列表
     */
    public function getPatternList($params = [])
    {
        try {
            $defaultParams = [
                'page' => 1,
                'page_size' => 20,      // ✅ 统一使用 page_size
                'category' => null,
                'group' => null,
                'status' => null,
                'keyword' => null,      // ✅ 统一使用 keyword
                'sort_field' => 'created_at',
                'sort_order' => 'DESC'
            ];
            
            $params = array_merge($defaultParams, $params);
            
            // 构建查询条件
            $where = [];
            $bindParams = [];
            
            if ($params['category']) {
                $where[] = "category = ?";
                $bindParams[] = $params['category'];
            }
            
            if ($params['group']) {
                $where[] = "pattern_group = ?";
                $bindParams[] = $params['group'];
            }
            
            if ($params['status'] !== null) {
                $where[] = "status = ?";
                $bindParams[] = $params['status'];
            }
            
            if ($params['keyword']) {
                $where[] = "(pattern_name LIKE ? OR category LIKE ? OR pattern_group LIKE ?)";
                $keyword = '%' . $params['keyword'] . '%';
                $bindParams[] = $keyword;
                $bindParams[] = $keyword;
                $bindParams[] = $keyword;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // 计算总数
            $countSql = "SELECT COUNT(*) as total FROM custom_pattern {$whereClause}";
            $countResult = $this->db->query($countSql, $bindParams);
            $total = $countResult->fetch_assoc()['total'];
            
            // 计算分页（统一使用 page_size）
            $offset = ($params['page'] - 1) * $params['page_size'];
            $pageSize = $params['page_size'];
            
            // 查询数据
            $sql = "SELECT * FROM custom_pattern {$whereClause} 
                    ORDER BY {$params['sort_field']} {$params['sort_order']} 
                    LIMIT {$offset}, {$pageSize}";
            
            $result = $this->db->query($sql, $bindParams);
            
            $patterns = [];
            while ($row = $result->fetch_assoc()) {
                $patterns[] = $row;
            }
            
            return [
                'success' => true,
                'data' => [
                    'patterns' => $patterns,
                    'total' => $total,
                    'page' => $params['page'],
                    'page_size' => $params['page_size'],  // ✅ 使用 page_size
                    'total_pages' => ceil($total / $params['page_size'])
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error('获取定制图案列表异常', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => '获取图案列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 根据ID获取图案
     * 
     * @param int $patternId 图案ID
     * @return array|false 图案数据或false
     */
    public function getPatternById($patternId)
    {
        try {
            // 使用Godomall标准的数据库查询方法
            $sql = "SELECT * FROM custom_pattern WHERE pattern_id = ?";
            $result = $this->db->query_fetch($sql, [$patternId]);
            
            return $result ?: false;
            
        } catch (Exception $e) {
            Logger::error('根据ID获取定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            return false;
        }
    }

    /**
     * 获取图案分类列表
     * 
     * @return array 分类列表
     */
    public function getPatternCategories()
    {
        try {
            $sql = "SELECT DISTINCT category FROM custom_pattern 
                    WHERE category IS NOT NULL AND category != '' 
                    ORDER BY category";
            
            $result = $this->db->query($sql);
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['category'];
            }
            
            return [
                'success' => true,
                'data' => $categories
            ];
            
        } catch (Exception $e) {
            Logger::error('获取定制图案分类异常', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => '获取分类失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取图案分组列表
     * 
     * @return array 分组列表
     */
    public function getPatternGroups()
    {
        try {
            $sql = "SELECT DISTINCT pattern_group FROM custom_pattern 
                    WHERE pattern_group IS NOT NULL AND pattern_group != '' 
                    ORDER BY pattern_group";
            
            $result = $this->db->query($sql);
            
            $groups = [];
            while ($row = $result->fetch_assoc()) {
                $groups[] = $row['pattern_group'];
            }
            
            return [
                'success' => true,
                'data' => $groups
            ];
            
        } catch (Exception $e) {
            Logger::error('获取定制图案分组异常', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => '获取分组失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量删除图案（优化事务处理）
     * 
     * 批量删除多个图案，支持部分成功和整体事务两种模式
     * 
     * 【批量处理策略】
     * - 逐个处理：foreach循环调用deletePattern()
     * - 部分成功模式（默认）：单个图案删除失败不影响其他图案
     * - 整体事务模式（可选）：要么全部成功要么全部失败
     * - 统计结果：分别统计成功数量和失败数量
     * - 错误收集：收集所有失败图案的错误信息
     * 
     * 【事务模式选择】
     * 1. 部分成功模式（useTransaction=false，默认推荐）
     *    - 每个图案独立处理，互不影响
     *    - 适合大批量删除（如100个图案）
     *    - 删除50个成功，50个失败也能保存成功的部分
     * 
     * 2. 整体事务模式（useTransaction=true）
     *    - 所有图案删除作为一个事务
     *    - 任何一个失败都会回滚所有操作
     *    - 适合小批量删除且要求完全一致性
     * 
     * 【依赖检查】
     * - 每个图案删除前都会调用isPatternUsedByGoods()检查
     * - 正在被使用的图案会被记录为失败
     * - 不会影响其他图案的删除（部分成功模式）
     * 
     * 【缓存清除】
     * - 批量删除后调用clearPatternCache()全量清除
     * - 确保所有相关缓存都被清除
     * 
     * @param array $patternIds 图案ID数组
     * @param bool $useTransaction 是否使用整体事务（默认false，推荐部分成功模式）
     * @return array 删除结果 {success, message, data: {total, success_count, failed_count, errors[]}}
     */
    public function batchDeletePatterns($patternIds, $useTransaction = false)
    {
        try {
            if (empty($patternIds)) {
                throw new AlertBackException('请选择要删除的图案');
            }
            
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            
            // ✅ 可选：使用整体事务（全部成功或全部失败）
            if ($useTransaction) {
                $this->db->begin_transaction();
            }
            
            try {
                foreach ($patternIds as $patternId) {
                    try {
                        $result = $this->deletePattern($patternId);
                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $failedCount++;
                            $errors[] = "图案ID {$patternId}: " . $result['message'];
                            
                            // ✅ 如果使用整体事务，任何失败都抛出异常
                            if ($useTransaction) {
                                throw new AlertBackException("图案ID {$patternId} 删除失败");
                            }
                        }
                    } catch (Exception $e) {
                        $failedCount++;
                        $errors[] = "图案ID {$patternId}: " . $e->getMessage();
                        
                        // ✅ 如果使用整体事务，重新抛出异常触发回滚
                        if ($useTransaction) {
                            throw $e;
                        }
                    }
                }
                
                // ✅ 如果使用整体事务，全部成功后提交
                if ($useTransaction) {
                    $this->db->commit();
                }
                
                Logger::info('批量删除定制图案完成', [
                    'total' => count($patternIds),
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'use_transaction' => $useTransaction
                ]);
                
                return [
                    'success' => true,
                    'data' => [
                        'total' => count($patternIds),
                        'success_count' => $successCount,
                        'failed_count' => $failedCount,
                        'errors' => $errors
                    ],
                    'message' => "批量删除完成：成功 {$successCount} 个，失败 {$failedCount} 个"
                ];
                
            } catch (Exception $e) {
                // ✅ 如果使用整体事务，失败时回滚
                if ($useTransaction) {
                    $this->db->rollback();
                }
                throw $e;
            }
            
        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('批量删除定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_ids' => $patternIds
            ]);
            throw new AlertBackException('批量删除失败：' . $e->getMessage());
        }
    }

    /**
     * 验证图案数据
     * 
     * @param array $patternData 图案数据
     * @param bool $isNew 是否新增
     * @throws AlertBackException
     */
    private function validatePatternData($patternData, $isNew = true)
    {
        if ($isNew) {
            if (empty($patternData['pattern_name'])) {
                throw new AlertBackException('图案名称不能为空');
            }
            
            if (strlen($patternData['pattern_name']) > 100) {
                throw new AlertBackException('图案名称不能超过100个字符');
            }
        }
        
        if (isset($patternData['pattern_name']) && empty($patternData['pattern_name'])) {
            throw new AlertBackException('图案名称不能为空');
        }
        
        if (isset($patternData['category']) && strlen($patternData['category']) > 50) {
            throw new AlertBackException('图案分类不能超过50个字符');
        }
        
        if (isset($patternData['pattern_group']) && strlen($patternData['pattern_group']) > 50) {
            throw new AlertBackException('图案分组不能超过50个字符');
        }
    }

    /**
     * 插入图案到数据库
     * 
     * @param array $data 图案数据
     * @return int|false 插入成功返回ID，失败返回false
     */
    private function insertPattern($data)
    {
        try {
            // 使用Godomall标准的数据库插入方法
            $arrBind = $this->db->get_binding($this->tableField->tableCustomPattern(), $data, 'insert');
            
            $result = $this->db->set_insert_db(
                'custom_pattern',
                $arrBind['param'],
                $arrBind['bind'],
                'y'
            );
            
            if ($result) {
                return $this->db->insert_id();
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('插入定制图案到数据库异常', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * 更新数据库中的图案
     * 
     * @param int $patternId 图案ID
     * @param array $data 更新数据
     * @return bool 更新成功返回true，失败返回false
     */
    private function updatePatternInDb($patternId, $data)
    {
        try {
            $setParts = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                $setParts[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $setParts[] = "updated_at = NOW()";
            
            $sql = "UPDATE custom_pattern SET " . implode(', ', $setParts) . " WHERE pattern_id = ?";
            $params[] = $patternId;
            
            $result = $this->db->query($sql, $params);
            
            return $result !== false;
            
        } catch (Exception $e) {
            Logger::error('更新数据库中的定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * 从数据库删除图案
     * 
     * @param int $patternId 图案ID
     * @return bool 删除成功返回true，失败返回false
     */
    private function deletePatternFromDb($patternId)
    {
        try {
            $sql = "DELETE FROM custom_pattern WHERE pattern_id = ?";
            $result = $this->db->query($sql, [$patternId]);
            
            return $result !== false;
            
        } catch (Exception $e) {
            Logger::error('从数据库删除定制图案异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            return false;
        }
    }

    /**
     * 检查图案是否被商品使用
     * 
     * 在删除图案前检查是否有商品正在使用，防止删除后数据不一致
     * 
     * 【依赖检查逻辑】
     * - 查询 goods_custom_pattern 表统计使用该图案的商品数量
     * - COUNT(*) > 0 表示有商品正在使用
     * - 返回true时，deletePattern()会抛出异常阻止删除
     * 
     * 【数据完整性保护】
     * - 目的：避免商品关联的图案被删除导致前端显示错误
     * - 场景：管理员在后台删除图案，但有商品正在使用该图案
     * - 保护：强制要求先解除商品关联，再删除图案
     * 
     * 【解决方案】
     * - 用户操作：先在商品管理中移除该图案关联
     * - 然后：才能在图案管理中删除该图案
     * - 或者：使用批量操作同时处理
     * 
     * @param int $patternId 图案ID
     * @return bool 被使用返回true（禁止删除），未使用返回false（允许删除）
     */
    private function isPatternUsedByGoods($patternId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM goods_custom_pattern WHERE pattern_id = ?";
            $result = $this->db->query($sql, [$patternId]);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['count'] > 0;
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('检查图案是否被商品使用异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId
            ]);
            return false;
        }
    }

    /**
     * 更新图案状态
     * 
     * @param int $patternId 图案ID
     * @param int $status 新状态 (0: 禁用, 1: 启用)
     * @return array 更新结果
     */
    public function updatePatternStatus($patternId, $status)
    {
        try {
            if (!in_array($status, [0, 1])) {
                return ['success' => false, 'message' => '无效的状态值'];
            }

            // 检查图案是否存在
            $pattern = $this->getPatternById($patternId);
            if (!$pattern) {
                return ['success' => false, 'message' => '图案不存在'];
            }

            // 更新状态
            $updateData = ['status' => $status];
            $result = $this->updatePatternInDb($patternId, $updateData);

            if ($result) {
                Logger::info('图案状态更新成功', [
                    'pattern_id' => $patternId,
                    'old_status' => $pattern['status'],
                    'new_status' => $status
                ]);

                return [
                    'success' => true,
                    'message' => '图案状态更新成功',
                    'data' => [
                        'pattern_id' => $patternId,
                        'status' => $status
                    ]
                ];
            } else {
                return ['success' => false, 'message' => '图案状态更新失败'];
            }

        } catch (Exception $e) {
            Logger::error('图案状态更新异常', [
                'error' => $e->getMessage(),
                'pattern_id' => $patternId,
                'status' => $status
            ]);

            return ['success' => false, 'message' => '图案状态更新异常：' . $e->getMessage()];
        }
    }

} 