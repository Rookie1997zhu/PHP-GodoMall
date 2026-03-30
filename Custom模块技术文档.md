# PHP GodoMall 商品定制模块 (Custom Module) 技术文档

> **版本**: 1.0  
> **创建日期**: 2025年  
> **适用项目**: PHP GodoMall 电商系统  
> **文档类型**: 技术参考文档

---

## 📋 目录索引

1. [整体架构与设计原则](#1-整体架构与设计原则)
2. [目录结构与模块组织](#2-目录结构与模块组织)
3. [数据库设计理念](#3-数据库设计理念)
4. [核心组件层(Component)](#4-核心组件层component)
5. [控制器层(Controller)](#5-控制器层controller)
6. [数据访问层(Database)](#6-数据访问层database)
7. [存储层(Storage)](#7-存储层storage)
8. [编码规范与命名约定](#8-编码规范与命名约定)
9. [API接口设计](#9-api接口设计)
10. [错误处理与日志](#10-错误处理与日志)

---

## 1. 整体架构与设计原则

### 1.1 架构概览

本模块采用经典的 **MVC分层架构 + 组件化设计**,完全遵循Godomall官方开发规范:

```
┌─────────────────────────────────────┐
│  Controller层 (请求处理、参数验证)   │
├─────────────────────────────────────┤
│  Component层 (业务逻辑、数据封装)    │
├─────────────────────────────────────┤
│  Database层 (数据访问、事务管理)     │
├─────────────────────────────────────┤
│  Storage层 (文件上传、存储管理)      │
└─────────────────────────────────────┘
```

### 1.2 核心设计原则

#### ✅ **增量开发原则** (最重要)
- **不修改任何官方系统表结构** (es_goods, es_cart, es_order等)
- 使用独立的扩展表 (custom_*_extension) 关联系统表
- 通过外键约束保证数据一致性
- 可随时回滚,不影响官方系统

#### ✅ **继承扩展原则（以当前项目为准）**
- **并非所有 Component 都采用继承扩展**：
  - `CustomGoods` 当前实现为“独立组件”，不 `extends` 官方 Goods，但会内部调用 `\App::load('\\Component\\Goods\\Goods')` 读取商品基础信息。
  - 控制器仍复用官方基类：`extends \Controller\Admin\Controller` / `extends \Controller\Front\Controller`
  - Storage 仍复用官方能力：`CustomStorage` 负责上传/删除/验证等（以当前实现为准）
- 设计动机：降低对官方父类/上下文的强依赖，减少 500 风险，便于独立排查与迭代。

#### ✅ **独立数据库连接**
- 使用独立的 `CustomPDO` 类管理数据库连接
- 独立的配置文件 `config/database.php`
- 避免与官方DB组件冲突
- 支持事务、参数绑定、预处理语句

#### ✅ **模块化隔离原则**
- 主要自定义业务代码放在 `module/Custom/`
- 但本项目还依赖 **Godomall Controller 覆盖机制**：`module/Controller/` 下的类会优先加载，用于在不改官方文件的前提下扩展行为
- 前端模板/静态资源以 `module/skin/front/mplshop/` 为准（当前项目已删除 `data/` 目录）
- 表名统一前缀：`custom_*`
- 便于迁移、测试、版本管理

---

## 2. 目录结构与模块组织

### 2.1 完整目录树

```
module/Custom/
├── Bootstrap/                          # 启动引导（预留）
├── Component/                          # 业务组件层 ⭐核心⭐
│   ├── CustomGoods.php                 # 定制页视图数据组装（独立组件，内部调用官方Goods）
│   ├── CustomColor.php                 # 商品颜色 + 定制开关/尺寸/作业指南等扩展
│   ├── CustomSize.php                  # 自定义尺寸（custom_goods_sizes）
│   ├── CustomExtraOption.php           # 附加选项（custom_goods_extra_options + colors）
│   ├── CustomExtraImage.php            # 附加图片（custom_goods_extra_images）
│   ├── CustomPattern.php               # 图案库
│   ├── CustomPatternCategory.php       # 图案分类
│   ├── CustomCartExtension.php         # 购物车扩展（custom_cart_extension）
│   ├── CustomOrderExtension.php        # 订单扩展（custom_order_extension）
│   ├── Database/
│   │   ├── CustomPDO.php               # PDO封装
│   │   └── CustomDBTableField.php      # 表字段定义/工具
│   └── Storage/
│       └── CustomStorage.php           # 文件上传/删除/验证
├── Controller/
│   ├── Admin/Custom/
│   │   ├── CustomColorController.php
│   │   ├── CustomGoodsController.php
│   │   ├── CustomPatternController.php
│   │   ├── CustomPatternCategoryController.php
│   │   └── PatternManageController.php
│   └── Front/
│       ├── add_to_cart.php
│       └── CustomErrorLogController.php
├── Database/
│   └── migrations/                     # 预留（当前为空）
└── config/
    └── database.php

module/Controller/                      # ✅ 覆盖/替换官方控制器（Godomall优先加载）
├── Front/Custom/
│   ├── GoodsCustomController.php       # /custom/goods_custom.php（定制页 + addToCart等AJAX）
│   └── CustomGoodsMailController.php   # /custom/custom_goods_mail.php（外部订单邮件发送）
├── Front/Order/
│   └── CustomCartController.php        # 购物车页注入定制字段（customization_image等）
└── Admin/Custom/
    └── CustomOrderImageController.php  # 后台下载定制图（Base64解码后下载）

module/Admin/                           # ✅ 后台入口文件（通过 mode 分发到 Controller）
├── custom_color.php
├── custom_pattern.php
├── custom_pattern_category.php
└── custom_color_simple.php

module/skin/front/mplshop/              # ✅ 定制页模板/资源（当前以 module/skin 为准）
├── custom/goods_custom.html
├── js/custom/goods_custom.js
├── js/custom/goods_custom_for_naver.js
└── css/custom/goods_custom.css
```

### 2.2 核心文件说明

| 文件路径 | 职责 | 依赖 |
|---------|------|------|
| `module/Controller/Front/Custom/GoodsCustomController.php` | 前台定制页入口 + addToCart/orderImage/cartExtraPrice/getCanvasData/deleteCustomData | CustomGoods / Cart |
| `module/Custom/Component/CustomGoods.php` | 组装定制页 customGoodsView（颜色/尺寸/附加选项/官方选项/模板区域） | CustomPDO + 官方Goods组件 |
| `module/Controller/Front/Order/CustomCartController.php` | 购物车页合并 custom_cart_extension 字段到 cartInfo | CustomCartExtension |
| `module/Controller/Admin/Custom/CustomOrderImageController.php` | 后台下载订单定制图（custom/customer） | CustomOrderExtension |
| `module/Admin/custom_color.php` | 后台颜色/尺寸/附加选项/模板区域等 API 入口（mode 分发） | CustomColorController |
| `module/Admin/custom_pattern.php` | 后台图案 API 入口（mode 分发） | CustomPatternController |
| `module/Admin/custom_pattern_category.php` | 后台图案分类 API 入口（mode 分发） | CustomPatternCategoryController |
| `module/skin/front/mplshop/custom/goods_custom.html` | 定制页面模板（SSR） | goods_custom.js/css |

---

## 3. 数据库设计理念

### 3.1 扩展表设计策略

本模块遵循 **增量开发**：不直接修改官方业务逻辑文件，通过 `custom_*` 相关表与扩展表实现定制能力。

> 说明：线上数据库字段/表结构已根据业务需要手工补齐；本文档不再包含 SQL 脚本与 DDL，仅描述“当前项目实际使用”的表与字段约定。

### 3.2 核心数据表结构（以当前项目代码为准）

> 下表为“代码中直接读写”的核心表。字段以当前组件/控制器实际使用为准（线上已补齐）。

#### **A. 定制页面展示与商品配置**

| 表名 | 用途 | 代码关键字段（示例） |
|------|------|----------------------|
| `custom_goods_extension` | 商品定制开关/默认值/作业指南等 | is_customizable, default_color, default_size, custom_width, custom_height, work_guide_path |
| `custom_goods_colors` | 商品颜色（含 SVG） | color_name, color_code, color_hex, svg_image_path, stock_status, sort_order, status |
| `custom_goods_sizes` | 自定义尺寸（多尺寸） | size_name, size_width, size_height, size_unit, status |
| `custom_goods_extra_options` | 定制附加选项（可加价） | option_group, option_label, input_type, extra_price, price_type, is_required, sort_order, status |
| `custom_goods_extra_option_colors` | 附加选项颜色盘 | option_id, color_name, color_hex, sort_order, status |
| `custom_goods_extra_images` | 附加图片（包装/附属品等） | image_type, image_name, image_path, status |
| `custom_template` | 模板信息（viewBox 等） | svg_path, viewbox_width, viewbox_height, use_yn |
| `custom_template_clip_region` | 定制区域裁剪/限制 | template_id, type, x, y, w, h, d, sort |

#### **B. 图案库（若启用）**

| 表名 | 用途 | 代码关键字段（示例） |
|------|------|----------------------|
| `custom_pattern` | 图案库 | pattern_name, pattern_url, pattern_thumbnail, category_id/category, pattern_group, tags, status |
| `custom_pattern_category` | 图案分类 | category_name, parent_id, sort_order, status |
| `goods_custom_pattern` | 商品与图案关联 | goods_id, pattern_id, sort_order |

#### **C. 购物车与订单扩展（定制数据落库）**

| 表名 | 用途 | 代码关键字段（示例） |
|------|------|----------------------|
| `custom_cart_extension` | 购物车扩展（每行） | sno, customization_data, selected_color, selected_size, customer_pattern_image, customization_image, extra_price_total |
| `custom_order_extension` | 订单扩展（整单） | order_no, customization_image, customer_pattern_image, customization_data, has_customized_goods |
| `custom_order_goods_extension` | 订单商品扩展（逐商品） | order_goods_sn, order_no, goods_id, customization_data, selected_color, selected_size, is_customized |

### 3.3 关系说明（简化版）

- `custom_goods_extension.goods_id` ↔ `es_goods.goodsNo`
- `custom_cart_extension.sno` ↔ `es_cart.sno`
- `custom_order_extension.order_no` ↔ `es_order.orderNo`
- `custom_order_goods_extension.order_goods_sn` ↔ `es_orderGoods.sno`

### 3.4 数据库操作规范

#### **事务处理**
```php
// 使用PDO事务确保数据一致性
$this->pdo->beginTransaction();
try {
    $this->pdo->insert($sql1, $params1);
    $this->pdo->update($sql2, $params2);
    $this->pdo->commit();
} catch (Exception $e) {
    $this->pdo->rollback();
    throw $e;
}
```

#### **参数绑定（防SQL注入）**
```php
// ✅ 使用占位符
$sql = "SELECT * FROM custom_pattern WHERE pattern_id = ?";
$result = $this->pdo->fetch($sql, [$patternId]);

// ❌ 不要拼接SQL
$sql = "SELECT * FROM custom_pattern WHERE pattern_id = $patternId"; // 危险！
```

---

## 4. 核心组件层 (Component)

### 4.1 组件设计模式

所有Component类遵循统一模式:

```php
class CustomXXX [extends ParentClass]
{
    private $pdo;           // 数据库连接
    private $cache;         // 缓存实例（可选）
    private $storage;       // 存储实例（可选）
    
    public function __construct() { }
    
    // CRUD操作
    public function getXXX($id) { }
    public function addXXX($data) { }
    public function updateXXX($id, $data) { }
    public function deleteXXX($id) { }
    
    // 业务逻辑
    private function validateXXX($data) { }
    private function processXXX($data) { }
}
```

### 4.2 CustomColor.php - 商品颜色管理

#### **核心功能**
- 每个商品拥有独立的颜色配置（不是全局共享）
- 支持颜色名称、颜色代码、HEX值、SVG图片路径
- 软删除机制（status=0）

#### **关键方法**

| 方法 | 功能 | 返回值 |
|------|------|-------|
| `getGoodsColors($goodsId)` | 获取商品所有颜色 | `['success'=>bool, 'data'=>[], 'message'=>'']` |
| `addGoodsColor($colorData)` | 添加颜色（含唯一性验证） | `['success'=>bool, 'data'=>['color_id'=>X]]` |
| `updateGoodsColor($colorId, $data)` | 更新颜色（动态字段） | `['success'=>bool, 'message'=>'']` |
| `deleteGoodsColor($colorId)` | 软删除颜色（status=0） | `['success'=>bool, 'message'=>'']` |
| `getCustomizationStatus($goodsId)` | 获取商品定制化状态 | int (0/1) |
| `updateCustomizationStatus($goodsId, $status)` | 更新定制化开关 | bool |
| `getCustomSize($goodsId)` | 获取商品自定义尺寸 | `['custom_width'=>float, 'custom_height'=>float]` |
| `updateCustomSize($goodsId, $width, $height)` | 更新商品尺寸（cm） | bool |

#### **数据验证逻辑**
```php
private function validateColorNameUnique($goodsId, $colorName)
{
    // 同一商品不能有同名颜色
    $sql = "SELECT COUNT(*) as cnt FROM custom_goods_colors 
            WHERE goods_id = ? AND color_name = ? AND status = 1";
    $result = $this->pdo->fetch($sql, [$goodsId, $colorName]);
    if ($result['cnt'] > 0) {
        throw new Exception('该商品已存在同名颜色');
    }
}
```

#### **事务处理示例**
```php
public function addGoodsColor($colorData)
{
    $this->pdo->beginTransaction();
    try {
        $this->validateColorNameUnique($colorData['goods_id'], $colorData['color_name']);
        $colorId = $this->pdo->insert($sql, $params);
        $this->pdo->commit();
        return ['success' => true, 'data' => ['color_id' => $colorId]];
    } catch (Exception $e) {
        $this->pdo->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

### 4.3 CustomPattern.php - 定制图案管理⭐

这是最复杂的组件，包含**缓存机制、批量操作、依赖检查**等高级功能。

#### **继承关系**
```php
class CustomPattern extends Goods  // 继承官方商品组件
{
    private $db;         // Godomall标准DB
    private $cache;      // SimpleCache缓存
    private $tableField; // 表字段定义
}
```

#### **核心功能矩阵**

| 功能分类 | 方法名 | 说明 |
|---------|--------|------|
| **基础CRUD** | `addPattern()` | 添加图案（含文件上传） |
|  | `updatePattern()` | 更新图案（支持换图） |
|  | `deletePattern()` | 删除图案（含依赖检查） |
|  | `getPatternById()` | 根据ID获取 |
| **列表查询** | `getPatternList()` | 后台列表（分页） |
|  | `getPatternListForAPI()` | API列表（含缓存） |
| **批量操作** | `batchDeletePatterns()` | 批量删除（支持部分成功） |
|  | `updatePatternStatus()` | 批量更新状态 |
| **辅助功能** | `getPatternCategories()` | 获取分类列表 |
|  | `getPatternGroups()` | 获取分组列表 |
| **缓存管理** | `clearPatternCache()` | 清除缓存 |
|  | `initCache()` | 初始化缓存 |

#### **缓存机制详解（重要！）**

本模块实现了完整的缓存机制，是学习要点：

```php
// 1. 缓存键生成（包含所有查询参数）
private function generatePatternListCacheKey($params)
{
    return 'custom_pattern_list_' 
        . 'page_' . $params['page']
        . '_page_size_' . $params['page_size']
        . '_keyword_' . md5($params['keyword'])
        . '_category_' . $params['category']
        . '_status_' . $params['status']
        . '_sort_' . $params['sort_by'] . '_' . $params['sort_order'];
}

// 2. 缓存读取（带锁机制防止缓存击穿）
private function getCachedPatternList($cacheKey)
{
    $cachedData = $this->cache->get($cacheKey);
    if ($cachedData !== null) {
        return $cachedData; // 缓存命中
    }
    
    // 检查是否有其他进程正在加载
    $lockKey = $cacheKey . '_lock';
    if ($this->cache->get($lockKey)) {
        usleep(100000); // 等待100ms
        return $this->cache->get($cacheKey); // 重试
    }
    
    return false; // 未命中
}

// 3. 缓存写入（带锁）
private function cachePatternList($cacheKey, $data, $ttl)
{
    $lockKey = $cacheKey . '_lock';
    $this->cache->set($lockKey, 1, 10); // 锁定10秒
    
    try {
        $this->cache->set($cacheKey, $data, $ttl);
    } finally {
        $this->cache->delete($lockKey); // 释放锁
    }
}

// 4. 缓存清除策略
public function clearPatternCache($patternId = null)
{
    if ($patternId) {
        // 精确清除：只清除该图案相关缓存
        $this->cache->delete('pattern_' . $patternId);
        $this->cache->delete('pattern_list_' . $patternId);
    } else {
        // 全量清除：使用通配符清除所有图案缓存
        $this->cache->deletePattern('custom_pattern_*');
    }
}
```

#### **依赖检查机制（删除前检查）**

```php
private function isPatternUsedByGoods($patternId)
{
    // 检查是否有商品正在使用该图案
    $sql = "SELECT COUNT(*) as count 
            FROM goods_custom_pattern 
            WHERE pattern_id = ?";
    $result = $this->db->query($sql, [$patternId]);
    return $result['count'] > 0;
}

public function deletePattern($patternId)
{
    // 先检查依赖
    if ($this->isPatternUsedByGoods($patternId)) {
        throw new AlertBackException('该图案正在被商品使用，无法删除');
    }
    
    // 删除文件
    $fileName = basename($pattern['pattern_url']);
    CustomStorage::deleteCustomPattern($fileName);
    
    // 删除数据库记录
    $this->deletePatternFromDb($patternId);
    
    // 清除缓存
    $this->clearPatternCache($patternId);
}
```

#### **批量操作的两种模式**

```php
public function batchDeletePatterns($patternIds, $useTransaction = false)
{
    $successCount = 0;
    $failedCount = 0;
    $errors = [];
    
    // 模式1：部分成功模式（默认，推荐）
    if (!$useTransaction) {
        foreach ($patternIds as $patternId) {
            try {
                $result = $this->deletePattern($patternId);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $errors[] = "图案ID {$patternId}: " . $result['message'];
                }
            } catch (Exception $e) {
                $failedCount++;
                $errors[] = "图案ID {$patternId}: " . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'total' => count($patternIds),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ]
        ];
    }
    
    // 模式2：整体事务模式（要么全部成功要么全部失败）
    $this->db->begin_transaction();
    try {
        foreach ($patternIds as $patternId) {
            $result = $this->deletePattern($patternId);
            if (!$result['success']) {
                throw new AlertBackException("图案ID {$patternId} 删除失败");
            }
            $successCount++;
        }
        $this->db->commit();
        
        return [
            'success' => true,
            'data' => [
                'total' => count($patternIds),
                'success_count' => $successCount
            ]
        ];
    } catch (Exception $e) {
        $this->db->rollback();
        throw $e;
    }
}
```

### 4.4 CustomGoods.php - 商品定制视图

#### **设计特点**
- **当前实现为独立组件，不继承官方 Goods 父类**
- 这是 `CustomGoods` 组件的实现选择，不代表所有 Component / Controller 都采用“不继承”策略
- 通过内部调用官方 Goods 组件，避免直接绑定到官方父类继承链
- 使用CustomPDO直接查询数据库
- 结合官方Goods组件获取基础信息

#### **核心方法**

```php
public function getCustomGoodsView($goodsNo)
{
    // 1. 通过官方Goods组件读取基础信息
    $goodsComponent = \App::load('\\Component\\Goods\\Goods');
    $goodsData = $goodsComponent->getGoodsInfo($goodsNo);
    
    // 2. 查询 custom_goods_extension
    $extensionSql = "SELECT is_customizable, custom_width, custom_height
                     FROM custom_goods_extension
                     WHERE goods_id = ?";
    $extensionData = $this->pdo->fetch($extensionSql, [$goodsNo]);
    
    // 3. 查询 custom_goods_colors
    $colorsSql = "SELECT color_id, color_name, color_hex, svg_image_path
                  FROM custom_goods_colors
                  WHERE goods_id = ? AND status = 1
                  ORDER BY sort_order ASC";
    $colorsData = $this->pdo->fetchAll($colorsSql, [$goodsNo]);
    
    // 4. 合并返回数据
    return [
        'goodsNo' => $goodsData['goodsNo'],
        'goodsNm' => $goodsData['goodsNm'],
        'goodsPrice' => $goodsData['goodsPrice'],
        'is_customizable' => $extensionData['is_customizable'] ?? 0,
        'custom_width' => $extensionData['custom_width'],
        'custom_height' => $extensionData['custom_height'],
        'colors' => $colorsData,
        'color_count' => count($colorsData)
    ];
}
```

### 4.5 CustomCartExtension.php - 购物车扩展

#### **核心功能**
- 根据购物车sno获取定制图片（Base64格式）
- 批量获取多个购物车项的定制数据
- 仅提供GET方法，不修改数据（遵循增量开发）

#### **关键方法**

```php
// 获取单个购物车项的定制图片
public function getCustomizationImages($cartSno)
{
    $sql = "SELECT customization_image, customer_pattern_image 
            FROM custom_cart_extension 
            WHERE sno = ? LIMIT 1";
    
    $result = $this->db->fetch($sql, ['sno' => $cartSno]);
    
    return [
        'customization_image' => $result['customization_image'] ?? null,
        'customer_pattern_image' => $result['customer_pattern_image'] ?? null
    ];
}

// 批量获取（性能优化）
public function getBatchCustomizationImages($cartSnoList)
{
    $placeholders = implode(',', array_fill(0, count($cartSnoList), '?'));
    
    $sql = "SELECT sno, customization_image, customer_pattern_image 
            FROM custom_cart_extension 
            WHERE sno IN ({$placeholders})";
    
    $results = $this->db->fetchAll($sql, array_values($cartSnoList));
    
    // 转换为以sno为key的关联数组
    $imageData = [];
    foreach ($results as $row) {
        $imageData[$row['sno']] = [
            'customization_image' => $row['customization_image'],
            'customer_pattern_image' => $row['customer_pattern_image']
        ];
    }
    
    return $imageData;
}
```

### 4.6 CustomOrderExtension.php - 订单扩展

#### **功能说明**
- 保存订单定制图片（从购物车复制到订单）
- 批量获取订单定制图片

#### **关键方法**

```php
public function saveOrderImages($orderNo, array $data)
{
    $sql = "INSERT INTO custom_order_extension
            (order_no, customization_image, customer_pattern_image, 
             has_customized_goods, created_at, updated_at)
            VALUES (:order_no, :customization_image, :customer_pattern_image, 
                    1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                customization_image = VALUES(customization_image),
                customer_pattern_image = VALUES(customer_pattern_image),
                has_customized_goods = 1,
                updated_at = NOW()";
    
    $params = [
        'order_no' => $orderNo,
        'customization_image' => $data['customization_image'] ?? null,
        'customer_pattern_image' => $data['customer_pattern_image'] ?? null
    ];
    
    $this->db->execute($sql, $params);
}
```

---

## 📝 使用说明

### 如何使用本文档

1. **新手入门**: 先阅读第1-2章，理解整体架构
2. **数据库开发**: 重点学习第3章数据库设计
3. **业务开发**: 深入学习第4章Component层
4. **接口开发**: 参考第5章Controller层
5. **文件处理**: 查阅第7章Storage层

### 后续章节预告

- **第5章**: 控制器层详解（API接口、请求验证、响应格式）
- **第6章**: 数据库层详解（CustomPDO、事务、字段定义）
- **第7章**: 存储层详解（文件上传、验证、删除）
- **第8章**: 编码规范（命名、注释、日志）
- **第9章**: API接口文档（参数、响应、示例）
- **第10章**: 错误处理与调试技巧

---

---

## 5. 控制器层 (Controller)

### 5.1 控制器设计原则

当前项目的 Controller **不应简单理解为“全部同一种写法”**，而是按控制器类型区分：

- **后台定制控制器**：优先 `extends \Controller\Admin\Controller`
- **前台独立页面控制器**：优先 `extends \Controller\Front\Controller`
- **前台覆写官方页面控制器**：优先 `extends \Bundle\Controller\Front\...`
- **构造函数策略**：默认先尝试 `parent::__construct()`；若独立入口场景下父类初始化异常，再考虑 `try/catch` 包裹，只有实测仍不稳定时才评估非继承方案
- **响应策略**：是否使用 `$this->json()` 或 `sendJsonResponse()`，以当前文件已验证模式为准，不做全局一刀切

典型模式示例如下：

```php
// 1) 后台定制控制器
class XxxAdminController extends \Controller\Admin\Controller
{
    private $component;  // 业务组件实例
    
    public function __construct()
    {
        parent::__construct();
        $this->component = new Component();
    }

    public function index()
    {
        $action = Request::request()->get('action');
        switch ($action) {
            case 'xxxAPI': $this->xxxAPI(); break;
        }
    }

    private function sendJsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2) 前台独立页面控制器
class XxxFrontController extends \Controller\Front\Controller
{
    public function __construct()
    {
        parent::__construct();
    }
}

// 3) 前台覆写官方页面控制器
class XxxOverrideController extends \Bundle\Controller\Front\Board\ListController
{
    public function index()
    {
        parent::index();
        $this->appendCustomData();
    }
}

// 4) 独立入口兼容场景
class XxxSafeAdminController extends \Controller\Admin\Controller
{
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (Exception $e) {
            error_log('Godomall init failed: ' . $e->getMessage());
        }
    }
}
```

> 决策顺序：先参考当前仓库同类型旧文件，再决定是否继承、是否保留 `parent::index()`、是否使用自定义 JSON 输出；不要把某个文件里的临时兼容写法扩展成全局规则。

### 5.2 CustomColorController.php - 颜色管理控制器

#### **路由规则**

```
请求URL: `/admin/custom_color.php?mode=xxxAPI`
请求方法: GET/POST
```

#### **API接口列表**

| Action | 方法 | 功能 | 必需参数 |
|--------|------|------|---------|
| `getColorsAPI` | GET | 获取商品颜色列表 | goods_id |
| `addColorAPI` | POST | 添加颜色 | goods_id, color_name, color_code, color_hex |
| `updateColorAPI` | POST | 更新颜色 | color_id, goods_id |
| `deleteColorAPI` | POST | 删除颜色 | color_id, goods_id |
| `uploadSvgAPI` | POST | 上传SVG图片 | goods_id, svg_file |
| `getCustomizationStatusAPI` | GET | 获取定制化状态 | goods_id |
| `updateCustomizationStatusAPI` | POST | 更新定制化状态 | goods_id, is_customizable |
| `getCustomSizeAPI` | GET | 获取自定义尺寸 | goods_id |
| `updateCustomSizeAPI` | POST | 更新自定义尺寸 | goods_id, custom_width, custom_height |

#### **接口示例：添加颜色**

```php
public function addColorAPI()
{
    try {
        // 1. 获取参数
        $goodsId = Request::post()->get('goods_id');
        $colorName = Request::post()->get('color_name');
        $colorCode = Request::post()->get('color_code');
        $colorHex = Request::post()->get('color_hex');
        $svgImagePath = Request::post()->get('svg_image_path');
        $sortOrder = Request::post()->get('sort_order', 0);
        
        // 2. 参数验证
        if (empty($goodsId)) {
            throw new Exception('商品ID不能为空');
        }
        if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
            throw new Exception('商品ID必须是有效的正整数');
        }
        if (empty($colorName)) {
            throw new Exception('颜色名称不能为空');
        }
        
        // 3. 组装数据
        $colorData = [
            'goods_id' => (int)$goodsId,
            'color_name' => $colorName,
            'color_code' => $colorCode,
            'color_hex' => $colorHex,
            'svg_image_path' => $svgImagePath ?? '',
            'sort_order' => (int)$sortOrder
        ];
        
        // 4. 调用组件
        $result = $this->customColor->addGoodsColor($colorData);
        
        // 5. 返回结果
        $this->sendJsonResponse($result);
        
    } catch (Exception $e) {
        $this->sendJsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
```

#### **接口示例：上传SVG文件**

```php
public function uploadSvgAPI()
{
    try {
        // 1. 获取上传文件（Godomall框架方式）
        $uploadedFile = Request::files()->get('svg_file');
        
        if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('请选择要上传的SVG文件');
        }
        
        // 2. 获取文件信息
        $tmpPath = $uploadedFile['tmp_name'];
        $originalName = $uploadedFile['name'];
        $mimeType = $uploadedFile['type'];
        $fileSize = $uploadedFile['size'];
        
        $goodsId = Request::post()->get('goods_id');
        
        // 3. 构建存储路径
        $targetPath = "colors/{$goodsId}/" . basename($originalName);
        
        // 4. 文件验证
        $validation = CustomStorage::validateCustomerPatternFile($tmpPath, $originalName, $mimeType);
        if (!$validation['valid']) {
            throw new Exception('文件验证失败: ' . $validation['message']);
        }
        
        if ($validation['file_info']['extension'] !== 'svg') {
            throw new Exception('仅支持SVG格式');
        }
        
        // 5. 上传文件
        $uploadResult = CustomStorage::uploadCustomPattern($tmpPath, $targetPath);
        
        if ($uploadResult) {
            $this->sendJsonResponse([
                'success' => true,
                'data' => ['svg_url' => $uploadResult],
                'message' => 'SVG上传成功'
            ]);
        } else {
            throw new Exception('文件上传失败');
        }
        
    } catch (Exception $e) {
        $this->sendJsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
```

### 5.3 CustomPatternController.php - 图案管理控制器

#### **API接口列表**

| Action | 方法 | 功能 | 说明 |
|--------|------|------|------|
| `getPatternListAPI` | GET | 获取图案列表 | 支持分页、搜索、筛选 |
| `uploadPatternAPI` | POST | 上传图案 | 支持JPG/PNG/GIF/SVG |
| `updatePatternAPI` | POST | 更新图案信息 | 不含文件 |
| `deletePatternAPI` | POST | 删除图案 | 含依赖检查 |
| `updatePatternStatusAPI` | POST | 更新状态 | 启用/禁用 |
| `getPatternInfoAPI` | GET | 获取图案详情 | 单个图案 |
| `batchUpdatePatternStatusAPI` | POST | 批量更新状态 | 多个图案 |
| `batchDeletePatternsAPI` | POST | 批量删除 | 多个图案 |

#### **接口示例：获取图案列表（含缓存）**

```php
public function getPatternListAPI()
{
    try {
        // 1. 获取请求参数（统一命名规范）
        $params = [
            'page' => (int)Request::get()->get('page', 1),
            'page_size' => (int)Request::get()->get('page_size', 20),
            'keyword' => Request::get()->get('keyword', ''),
            'category' => Request::get()->get('category', ''),
            'status' => Request::get()->get('status', 'active'),
            'sort_by' => Request::get()->get('sort_by', 'sort_order'),
            'sort_order' => Request::get()->get('sort_order', 'ASC')
        ];

        // 2. 参数验证
        $validation = $this->validatePatternListParams($params);
        if (!$validation['valid']) {
            $this->sendErrorResponse(400, $validation['message'], $validation['details']);
            return;
        }

        // 3. 调用业务组件（自动处理缓存）
        $result = $this->customPattern->getPatternListForAPI($params);
        
        // 4. 返回结果
        if ($result['success']) {
            $this->sendSuccessResponse('图案库获取成功', $result['data']);
        } else {
            $this->sendErrorResponse(500, $result['message'], $result['data']);
        }

    } catch (Exception $e) {
        Logger::error('图案库列表API异常', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'params' => $params ?? []
        ]);
        
        $this->sendErrorResponse(500, '服务器内部错误', [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]);
    }
}
```

#### **参数验证方法**

```php
private function validatePatternListParams($params)
{
    $errors = [];

    // 页码验证
    if ($params['page'] < 1) {
        $errors[] = '页码必须大于0';
    }

    // 每页数量验证
    if ($params['page_size'] < 1 || $params['page_size'] > 100) {
        $errors[] = '每页数量必须在1-100之间';
    }

    // 排序字段验证
    $allowedSortFields = ['pattern_id', 'pattern_name', 'category', 'sort_order', 'created_at'];
    if (!in_array($params['sort_by'], $allowedSortFields)) {
        $errors[] = '排序字段无效';
    }

    // 排序方向验证
    if (!in_array(strtoupper($params['sort_order']), ['ASC', 'DESC'])) {
        $errors[] = '排序方向无效';
    }

    if (empty($errors)) {
        return ['valid' => true, 'message' => '', 'details' => []];
    }

    return [
        'valid' => false, 
        'message' => '参数验证失败', 
        'details' => $errors
    ];
}
```

#### **统一响应格式**

```php
// 成功响应
private function sendSuccessResponse($message, $data)
{
    $response = [
        'success' => true,
        'code' => 200,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 错误响应
private function sendErrorResponse($code, $message, $details = null)
{
    $response = [
        'success' => false,
        'code' => $code,
        'message' => $message,
        'data' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
```

### 5.4 CSRF Token验证

所有POST请求都需要CSRF验证：

```php
private function validateCsrfToken()
{
    $token = Request::post()->get('csrf_token');
    $sessionToken = Session::get('csrf_token');
    
    if (!$token || !$sessionToken || $token !== $sessionToken) {
        throw new AlertBackException('安全验证失败，请重新提交');
    }
}

// 在需要CSRF保护的方法中调用
public function uploadPatternAPI()
{
    try {
        $this->validateCsrfToken(); // ← CSRF验证
        // ... 业务逻辑
    } catch (Exception $e) {
        $this->sendErrorResponse(400, $e->getMessage());
    }
}
```

---

## 6. 数据访问层 (Database)

### 6.1 CustomPDO.php - PDO数据库封装

#### **设计目标**
- 提供简洁的PDO操作接口
- 完全独立于Godomall官方DB组件
- 支持事务、预处理语句、参数绑定
- 使用独立配置文件

#### **核心方法**

| 方法 | 功能 | 返回值 |
|------|------|-------|
| `fetch($sql, $params)` | 查询单行 | array/false |
| `fetchAll($sql, $params)` | 查询多行 | array |
| `execute($sql, $params)` | 执行SQL | bool |
| `insert($sql, $params)` | 插入并返回ID | int/false |
| `beginTransaction()` | 开始事务 | bool |
| `commit()` | 提交事务 | bool |
| `rollback()` | 回滚事务 | bool |
| `lastInsertId()` | 获取最后插入ID | string |

#### **构造函数与配置加载**

```php
class CustomPDO
{
    private $pdo;
    private $config;
    
    public function __construct($config = null)
    {
        // 加载配置文件
        $this->config = $config ?: $this->loadCustomDbConfig();
        $this->connect();
    }
    
    private function loadCustomDbConfig()
    {
        $configPath = __DIR__ . '/../../config/database.php';
        
        if (!file_exists($configPath)) {
            throw new Exception('数据库配置文件不存在');
        }
        
        $config = require $configPath;
        
        // 验证必需配置项
        $requiredKeys = ['host', 'port', 'dbname', 'username', 'password'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                throw new Exception("数据库配置缺少必需项: {$key}");
            }
        }
        
        return $config;
    }
    
    private function connect()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['dbname'],
            $this->config['charset']
        );

        $this->pdo = new PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $this->config['options'] ?? []
        );
    }
}
```

#### **查询方法实现**

```php
// 查询单行
public function fetch($sql, $params = [])
{
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: false;
    } catch (PDOException $e) {
        throw new Exception('查询失败：' . $e->getMessage());
    }
}

// 查询多行
public function fetchAll($sql, $params = [])
{
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $result ?: [];
    } catch (PDOException $e) {
        throw new Exception('查询失败：' . $e->getMessage());
    }
}

// 执行SQL
public function execute($sql, $params = [])
{
    try {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        throw new Exception('执行失败：' . $e->getMessage());
    }
}

// 插入并返回ID
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
```

#### **事务支持**

```php
// 开始事务
public function beginTransaction()
{
    try {
        return $this->pdo->beginTransaction();
    } catch (PDOException $e) {
        throw new Exception('开始事务失败：' . $e->getMessage());
    }
}

// 提交事务
public function commit()
{
    try {
        return $this->pdo->commit();
    } catch (PDOException $e) {
        throw new Exception('提交事务失败：' . $e->getMessage());
    }
}

// 回滚事务
public function rollback()
{
    try {
        return $this->pdo->rollback();
    } catch (PDOException $e) {
        throw new Exception('回滚事务失败：' . $e->getMessage());
    }
}
```

### 6.2 config/database.php - 数据库配置

```php
<?php
return [
    // 数据库连接配置
    'host' => '10.1.0.189',
    'port' => 3306,
    'dbname' => 'your_database',
    'charset' => 'utf8mb4',
    'username' => 'your_username',
    'password' => 'your_password',
    
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
    
    // 日志配置
    'logging' => [
        'enabled' => true,
        'log_level' => 'info',
        'log_sql' => true,
    ]
];
```

### 6.3 CustomDBTableField.php - 表字段定义

#### **设计目的**
- 继承官方DBTableField类
- 定义所有自定义表的字段结构
- 配合Godomall官方的数据绑定机制

#### **字段定义格式**

```php
class CustomDBTableField extends DBTableField
{
    // 字段格式：['val' => '字段名', 'typ' => '类型', 'def' => '默认值']
    // 类型说明：'s' = string, 'i' = integer, 'd' = decimal, 'dt' = datetime
    
    public static function tableCustomPattern()
    {
        $arrField = [
            ['val' => 'pattern_id', 'typ' => 'i', 'def' => null],
            ['val' => 'pattern_name', 'typ' => 's', 'def' => null],
            ['val' => 'pattern_url', 'typ' => 's', 'def' => null],
            ['val' => 'category', 'typ' => 's', 'def' => null],
            ['val' => 'status', 'typ' => 'i', 'def' => 1],
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null],
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null],
        ];
        
        return $arrField;
    }
    
    public static function tableCustomGoodsExtension()
    {
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null],
            ['val' => 'goods_id', 'typ' => 'i', 'def' => null],
            ['val' => 'is_customizable', 'typ' => 'i', 'def' => 0],
            ['val' => 'custom_width', 'typ' => 'd', 'def' => null],
            ['val' => 'custom_height', 'typ' => 'd', 'def' => null],
            ['val' => 'created_at', 'typ' => 'dt', 'def' => null],
            ['val' => 'updated_at', 'typ' => 'dt', 'def' => null],
        ];
        
        return $arrField;
    }
}
```

---

## 7. 存储层 (Storage)

### 7.1 文件存储管理策略

#### **设计演变历史**

本模块的文件存储经历了以下演变：

1. **v1.0 - CustomStorage组件（已废弃）**
   - 继承Godomall官方Storage类
   - 使用Flysystem抽象层
   - 遇到`fopen('r+')`权限问题

2. **v2.0 - 纯PHP原生文件操作（当前方案）✅**
   - 直接使用PHP原生函数（`copy()`, `mkdir()`, `chmod()`）
   - 避免复杂的抽象层，提高稳定性
   - 使用Godomall官方`UserFilePath::data()`获取路径

#### **当前实现方式**

文件上传功能**直接在Controller层实现**，不使用单独的Storage组件：

| 实现位置 | 说明 |
|---------|------|
| `CustomColorController::uploadSvgAPI()` | SVG颜色图片上传（纯PHP原生） |
| `CustomPatternController::uploadPatternAPI()` | 图案文件上传（可使用官方Storage） |

#### **核心方法（PHP原生实现）**

| PHP函数 | 用途 | 说明 |
|---------|------|------|
| `Request::files()->get()` | 获取上传文件 | Godomall框架标准方法 |
| `UserFilePath::data('goods')` | 获取存储基础路径 | 返回`data/goods`绝对路径 |
| `mkdir($dir, 0777, true)` | 创建目录 | 递归创建多级目录 |
| `copy($source, $target)` | 复制文件 | 从临时目录复制到目标位置 |
| `chmod($file, 0644)` | 设置文件权限 | 确保文件可读 |
| `file_exists($path)` | 检查文件是否存在 | 验证上传是否成功 |
| `filesize($path)` | 获取文件大小 | 用于验证和日志记录 |

### 7.2 SVG文件上传实现（完整示例）

以下是`CustomColorController::uploadSvgAPI()`的完整实现：

```php
public function uploadSvgAPI()
{
    try {
        // 1. 获取上传文件（Godomall框架标准方式）
        $uploadedFile = Request::files()->get('svg_file');
        
        if (!$uploadedFile) {
            throw new Exception('请选择要上传的SVG文件');
        }
        
        // 2. 检查上传错误
        if ((int)$uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('文件上传失败 (错误代码: ' . $uploadedFile['error'] . ')');
        }
        
        // 3. 获取文件信息（数组格式）
        $tmpPath = $uploadedFile['tmp_name'];
        $originalName = $uploadedFile['name'];
        $mimeType = $uploadedFile['type'];
        $fileSize = $uploadedFile['size'];
        
        $goodsId = Request::post()->get('goods_id');
        if (empty($goodsId)) {
            throw new Exception('商品ID不能为空');
        }
        
        // 4. 验证商品ID必须是正整数
        if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
            throw new Exception('商品ID必须是有效的正整数');
        }
        
        Logger::info('颜色SVG上传开始', [
            'goods_id' => $goodsId,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'tmp_path' => $tmpPath,
            'tmp_exists' => file_exists($tmpPath)
        ]);
        
        // 5. 基本验证
        if ($fileSize > 20 * 1024 * 1024) {
            throw new Exception('文件大小超过20MB限制');
        }
        
        if ($fileSize === 0) {
            throw new Exception('文件大小为0');
        }
        
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'svg') {
            throw new Exception('仅支持SVG格式图片，当前格式: ' . $extension);
        }
        
        // 6. 生成唯一文件名
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueFileName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.svg';
        
        // 7. 构建目标路径（使用Godomall官方方法）
        $goodsDataPath = UserFilePath::data('goods');  // 获取 data/goods 绝对路径
        $targetDir = (string)$goodsDataPath . '/colors/' . $goodsId;
        $targetFile = $targetDir . '/' . $uniqueFileName;
        $httpPath = '/data/goods/colors/' . $goodsId . '/' . $uniqueFileName;
        
        Logger::info('目标路径信息', [
            'goods_data_path' => (string)$goodsDataPath,
            'target_dir' => $targetDir,
            'target_file' => $targetFile,
            'http_path' => $httpPath,
            'target_dir_exists' => is_dir($targetDir)
        ]);
        
        // 8. 确保目录存在
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0777, true)) {
                $error = error_get_last();
                Logger::error('无法创建目标目录', [
                    'dir' => $targetDir,
                    'error' => $error ? $error['message'] : 'unknown'
                ]);
                throw new Exception('无法创建目标目录');
            }
            Logger::info('创建目标目录成功', ['dir' => $targetDir]);
        }
        
        // 9. 复制文件到目标位置
        if (!@copy($tmpPath, $targetFile)) {
            $error = error_get_last();
            Logger::error('文件复制失败', [
                'source' => $tmpPath,
                'target' => $targetFile,
                'source_exists' => file_exists($tmpPath),
                'source_readable' => is_readable($tmpPath),
                'target_dir_writable' => is_writable($targetDir),
                'error' => $error ? $error['message'] : 'unknown'
            ]);
            throw new Exception('文件复制失败');
        }
        
        // 10. 设置文件权限
        @chmod($targetFile, 0644);
        
        // 11. 验证文件已成功写入
        if (!file_exists($targetFile)) {
            Logger::error('文件写入后不存在', ['target' => $targetFile]);
            throw new Exception('文件写入失败');
        }
        
        $finalSize = filesize($targetFile);
        
        Logger::info('颜色SVG上传成功', [
            'goods_id' => $goodsId,
            'original_name' => $originalName,
            'target_file' => $targetFile,
            'http_path' => $httpPath,
            'file_size' => $finalSize,
            'original_size' => $fileSize
        ]);
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => ['svg_url' => $httpPath],
            'message' => 'SVG上传成功'
        ]);
        
    } catch (Exception $e) {
        Logger::error('颜色SVG上传异常', [
            'error' => $e->getMessage(),
            'goods_id' => $goodsId ?? null,
            'original_name' => $originalName ?? null,
            'trace' => $e->getTraceAsString()
        ]);
        $this->sendJsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
```

#### **关键要点说明**

1. **路径获取**：使用`UserFilePath::data('goods')`而非`_ROOT_PATH_`常量
2. **目录创建**：使用`mkdir($dir, 0777, true)`递归创建多级目录
3. **文件复制**：使用`copy()`直接复制，避免`fopen`权限问题
4. **权限设置**：使用`chmod($file, 0644)`确保文件可读
5. **错误抑制**：使用`@`抑制错误，通过`error_get_last()`获取错误信息

### 7.3 文件名生成策略

```php
// 在Controller中直接生成唯一文件名
$baseName = pathinfo($originalName, PATHINFO_FILENAME);
$uniqueFileName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.svg';
```

**文件名组成**：
- 原始文件名（去除扩展名）
- 时间戳（`time()`）
- 4位随机数（`mt_rand(1000, 9999)`）
- 原始扩展名

**示例**：
```
原始文件名: red_color.svg
生成文件名: red_color_1704124800_5678.svg
```

### 7.4 文件验证机制

#### **文件大小验证**

```php
// 检查文件大小（最大20MB）
if ($fileSize > 20 * 1024 * 1024) {
    throw new Exception('文件大小超过20MB限制');
}

if ($fileSize === 0) {
    throw new Exception('文件大小为0');
}
```

#### **文件扩展名验证**

```php
    // 检查文件扩展名
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if ($extension !== 'svg') {
    throw new Exception('仅支持SVG格式图片，当前格式: ' . $extension);
}
```

#### **文件存在性验证**

```php
// 验证临时文件存在
if (!file_exists($tmpPath)) {
    throw new Exception('临时文件不存在');
}

// 验证上传后文件存在
if (!file_exists($targetFile)) {
    throw new Exception('文件写入失败');
}
```

### 7.5 存储路径规范

#### **目录结构**

```
项目根目录/
└── data/
    └── goods/
        └── colors/
            └── {商品ID}/
                ├── red_color_1704124800_1234.svg
                ├── blue_color_1704124801_5678.svg
                └── green_color_1704124802_9012.svg
```

#### **路径获取方法**

```php
// ✅ 正确：使用Godomall官方方法
$goodsDataPath = UserFilePath::data('goods');  // 返回绝对路径
$targetDir = (string)$goodsDataPath . '/colors/' . $goodsId;

// ❌ 错误：使用未定义的常量
$targetDir = _ROOT_PATH_ . '/data/goods/colors/' . $goodsId;

// ❌ 错误：硬编码绝对路径
$targetDir = 'C:/xampp/htdocs/project/data/goods/colors/' . $goodsId;
```

#### **HTTP访问路径**

```php
// 数据库存储的路径（相对路径）
$httpPath = '/data/goods/colors/' . $goodsId . '/' . $uniqueFileName;

// 前端访问URL
// https://yourdomain.com/data/goods/colors/123/red_color_1704124800_1234.svg
```

### 7.6 文件删除（待实现）

当前版本暂未实现文件删除功能，未来可参考以下实现：

```php
public function deleteSvgAPI()
{
    try {
        $svgPath = Request::post()->get('svg_path');
        
        // 构建完整文件路径
        $goodsDataPath = UserFilePath::data('goods');
        $fullPath = (string)$goodsDataPath . str_replace('/data/goods', '', $svgPath);
        
        // 安全检查：确保文件在允许的目录内
        $allowedDir = (string)$goodsDataPath . '/colors/';
        if (strpos($fullPath, $allowedDir) !== 0) {
            throw new Exception('非法的文件路径');
        }
        
        // 删除文件
        if (file_exists($fullPath)) {
            if (@unlink($fullPath)) {
                Logger::info('SVG文件删除成功', ['path' => $fullPath]);
                return ['success' => true, 'message' => '文件删除成功'];
            } else {
                throw new Exception('文件删除失败');
            }
        } else {
            return ['success' => true, 'message' => '文件不存在'];
        }
        
    } catch (Exception $e) {
        Logger::error('SVG文件删除异常', ['error' => $e->getMessage()]);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

### 7.7 存储层最佳实践

#### **✅ 推荐做法**

1. **使用官方路径API**
```php
   UserFilePath::data('goods')  // 获取data/goods目录
   UserFilePath::data('upload') // 获取data/upload目录
   ```

2. **递归创建目录**
   ```php
   mkdir($targetDir, 0777, true)  // true参数递归创建
   ```

3. **设置正确的文件权限**
   ```php
   chmod($targetFile, 0644)  // 文件可读不可写
   chmod($targetDir, 0755)   // 目录可进入可读取
   ```

4. **详细的日志记录**
   ```php
   Logger::info('操作成功', ['关键参数']);
   Logger::error('操作失败', ['错误信息', '堆栈跟踪']);
   ```

5. **完整的错误处理**
   ```php
   if (!@copy($source, $target)) {
       $error = error_get_last();
       throw new Exception('复制失败: ' . $error['message']);
   }
   ```

#### **❌ 避免做法**

1. **不要使用未定义的常量**
   ```php
   // ❌ 错误
   $path = _ROOT_PATH_ . '/data/goods';
   ```

2. **不要硬编码路径**
   ```php
   // ❌ 错误
   $path = 'C:/xampp/htdocs/project/data/goods';
   ```

3. **不要忽略权限设置**
   ```php
   // ❌ 错误：可能导致无法访问
   mkdir($dir, 0000);
   ```

4. **不要跳过文件验证**
   ```php
   // ❌ 错误：存在安全隐患
   copy($_FILES['file']['tmp_name'], $target);  // 未验证文件类型和大小
   ```

### 7.8 常见问题与解决方案

#### **问题1：文件上传失败，提示"无法创建目录"**

**原因**：父目录权限不足

**解决方案**：
```bash
# Linux/Mac
chmod -R 755 data/goods

# 或在PHP中
chmod('/path/to/data/goods', 0755);
```

#### **问题2：文件复制成功但无法访问**

**原因**：文件权限设置不当

**解决方案**：
```php
// 确保设置正确的文件权限
chmod($targetFile, 0644);  // 所有者可读写，其他用户只读
```

#### **问题3：路径中包含中文导致错误**

**原因**：文件系统编码问题

**解决方案**：
```php
// 清理文件名，只保留安全字符
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $baseName);
$uniqueFileName = $safeName . '_' . time() . '_' . mt_rand(1000, 9999) . '.svg';
```

#### **问题4：Windows系统路径分隔符错误**

**原因**：Windows使用`\`，Linux使用`/`

**解决方案**：
```php
// PHP会自动处理路径分隔符，但建议统一使用 /
$path = str_replace('\\', '/', $path);
```

---

## 8. 编码规范与命名约定

### 8.1 PHP编码规范

#### **基本规范（遵循PSR-2）**

```php
<?php
// ✅ 正确的类定义
namespace Custom\Component;

use Exception;
use Logger;

class CustomPattern extends Goods
{
    private $pdo;  // 属性使用驼峰命名
    
    public function __construct()
    {
        parent::__construct();
        $this->pdo = new CustomPDO();
    }
    
    // 方法使用驼峰命名，首字母小写
    public function getPatternList($params)
    {
        // 方法体
    }
}
```

#### **代码风格要点**

| 规则 | 说明 | 示例 |
|------|------|------|
| **缩进** | 4个空格，不使用Tab | `    $var = 'value';` |
| **行长度** | 建议不超过120字符 | - |
| **花括号** | 类/方法花括号独占一行 | `class Xxx\n{\n}` |
| **命名空间** | 每个文件只有一个命名空间 | `namespace Custom\Component;` |
| **一行一条语句** | 不在同一行写多条语句 | `$a = 1; $b = 2;` ❌ |

#### **注释规范**

```php
/**
 * 定制图案管理组件
 * 
 * 本组件负责定制图案的业务逻辑处理，包括图案的增删改查、分类管理等功能
 * 
 * @package Custom\Component
 * @author Custom Development Team
 * @version 1.0
 */
class CustomPattern extends Goods
{
    /**
     * 获取图案列表
     * 
     * @param array $params 查询参数 {page, page_size, keyword, category}
     * @return array 图案列表 {success, data, message}
     * @throws Exception 查询失败时抛出异常
     */
    public function getPatternList($params)
    {
        // 单行注释使用 //
        // 多行注释使用 /* */
        
        /* 
         * 复杂逻辑的多行注释
         * 需要详细说明时使用
         */
    }
}
```

### 8.2 命名约定

#### **表命名规范**

| 类型 | 格式 | 示例 |
|------|------|------|
| **核心表** | `custom_` + 模块名 | `custom_pattern` |
| **扩展表** | `custom_` + 系统表名 + `_extension` | `custom_goods_extension` |
| **关联表** | 主表 + `_` + 从表（复数） | `goods_custom_pattern` |
| **字段** | 小写蛇形命名 | `pattern_id`, `created_at` |

```sql
-- ✅ 正确的表命名
CREATE TABLE custom_pattern (
    pattern_id INT,
    pattern_name VARCHAR(100),
    created_at DATETIME
);

-- ✅ 扩展表命名
CREATE TABLE custom_goods_extension (
    goods_id INT,
    is_customizable TINYINT
);

-- ❌ 错误的命名（避免）
CREATE TABLE Pattern (  -- 首字母大写
    PatternId INT,      -- 驼峰命名
    pattern-name VARCHAR(100)  -- 使用连字符
);
```

#### **PHP类命名规范**

```php
// ✅ 类名：大写驼峰（PascalCase）
class CustomPattern { }
class CustomColorController { }
class CustomPDO { }

// ✅ 接口名：以Interface结尾
interface CustomPatternInterface { }

// ✅ Trait名：以Trait结尾
trait CustomValidationTrait { }

// ✅ 抽象类：以Abstract开头
abstract class AbstractCustomComponent { }
```

#### **变量命名规范**

```php
// ✅ 变量：小写驼峰（camelCase）
$patternId = 123;
$goodsData = [];
$isCustomizable = true;

// ✅ 常量：全大写蛇形命名
const PATH_CODE_CUSTOM_PATTERN = 'custom_pattern';
const MAX_FILE_SIZE = 10485760;
const BASE_DIRECTORY = 'upload/goods';

// ✅ 私有属性：小写驼峰，可选前缀_
private $pdo;
private $_cacheInstance;  // 可选下划线前缀

// ✅ 数组键：小写蛇形
$data = [
    'pattern_id' => 1,
    'pattern_name' => 'test',
    'created_at' => '2025-01-01'
];

// ❌ 避免的命名
$PatternId;     // 大写开头
$pattern_id;    // 蛇形命名（应用驼峰）
$p;             // 单字母（除循环变量外）
$temp, $tmp;    // 模糊的名称
```

#### **方法命名规范**

```php
class CustomPattern
{
    // ✅ 获取器：get + 名词
    public function getPatternList() { }
    public function getPatternById($id) { }
    
    // ✅ 设置器：set + 名词
    public function setPatternName($name) { }
    
    // ✅ 布尔判断：is/has/can + 形容词/名词
    public function isPatternUsedByGoods($id) { }
    public function hasCustomizationData($id) { }
    public function canDeletePattern($id) { }
    
    // ✅ 动作：动词 + 名词
    public function addPattern($data) { }
    public function updatePattern($id, $data) { }
    public function deletePattern($id) { }
    public function validatePatternData($data) { }
    public function processPatternUpload($file) { }
    
    // ✅ 批量操作：batch + 动词 + 名词（复数）
    public function batchDeletePatterns($ids) { }
    public function batchUpdatePatternStatus($ids, $status) { }
    
    // ✅ 私有方法：private + 小写驼峰
    private function validateColorNameUnique($goodsId, $colorName) { }
    private function buildPatternListWhere($params) { }
}
```

### 8.3 文件组织规范

#### **目录结构**

```
module/Custom/
├── Component/              # 业务组件（核心逻辑）
│   ├── CustomColor.php            # 单一职责：颜色管理
│   ├── CustomPattern.php          # 单一职责：图案管理
│   └── Database/                  # 数据库子模块
│       └── CustomPDO.php
├── Controller/            # 控制器（请求处理）
│   ├── Admin/                     # 后台控制器
│   │   └── Custom/
│   └── Front/                     # 前台控制器
├── config/                # 配置文件
│   └── database.php
└── Database/              # 数据库SQL
    └── install_custom_tables_fixed.sql
```

#### **文件命名规则**

| 类型 | 命名格式 | 示例 |
|------|---------|------|
| **类文件** | PascalCase.php | `CustomPattern.php` |
| **配置文件** | lowercase.php | `database.php` |
| **SQL文件** | lowercase_with_underscore.sql | `install_custom_tables.sql` |
| **临时文件** | 前缀temp_ | `temp_import_data.php` |

### 8.4 API响应格式规范

#### **成功响应格式**

```json
{
    "success": true,
    "code": 200,
    "message": "操作成功",
    "data": {
        "pattern_id": 123,
        "pattern_name": "测试图案"
    },
    "timestamp": "2025-01-01 12:00:00"
}
```

#### **错误响应格式**

```json
{
    "success": false,
    "code": 400,
    "message": "参数验证失败",
    "data": {
        "errors": [
            "商品ID不能为空",
            "颜色名称长度不能超过50个字符"
        ]
    },
    "timestamp": "2025-01-01 12:00:00"
}
```

#### **HTTP状态码使用规范**

| 状态码 | 含义 | 使用场景 |
|--------|------|---------|
| 200 | 成功 | 操作成功完成 |
| 400 | 请求错误 | 参数验证失败、业务逻辑错误 |
| 401 | 未授权 | 未登录或Token失效 |
| 403 | 禁止访问 | 权限不足 |
| 404 | 未找到 | 资源不存在 |
| 500 | 服务器错误 | 系统异常、数据库错误 |

### 8.5 日志记录规范

#### **日志级别使用**

```php
// ✅ INFO：正常业务操作
Logger::info('添加商品颜色成功', [
    'goods_id' => $goodsId,
    'color_name' => $colorName
]);

// ✅ WARNING：警告（不影响主流程）
Logger::warning('缓存系统初始化失败', [
    'error' => $e->getMessage()
]);

// ✅ ERROR：错误（影响业务）
Logger::error('添加商品颜色异常', [
    'error' => $e->getMessage(),
    'goods_id' => $goodsId,
    'trace' => $e->getTraceAsString()
]);

// ✅ DEBUG：调试信息（开发环境）
Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 查询商品颜色', [
    'goods_id' => $goodsId,
    'sql' => $sql
]);
```

#### **日志内容规范**

```php
// ✅ 好的日志
Logger::info('图案上传成功', [
    'pattern_id' => 123,
    'original_name' => 'test.jpg',
    'saved_path' => '/upload/goods/test_20250101_1234.jpg',
    'file_size' => 102400,
    'user_id' => 1
]);

// ❌ 不好的日志
Logger::info('成功');  // 信息不足
Logger::info('上传图案', ['data' => $allData]);  // 信息过多
```

### 8.6 异常处理规范

#### **异常分类**

```php
// ✅ 业务异常：AlertBackException（显示给用户）
throw new AlertBackException('该图案正在被商品使用，无法删除');

// ✅ 系统异常：Exception（记录日志）
throw new Exception('数据库连接失败');

// ✅ 参数异常：InvalidArgumentException
throw new InvalidArgumentException('商品ID必须是正整数');
```

#### **异常处理模式**

```php
public function addPattern($patternData)
{
    try {
        // 1. 参数验证
        $this->validatePatternData($patternData);
        
        // 2. 业务逻辑
        $result = $this->processPattern($patternData);
        
        // 3. 返回结果
        return ['success' => true, 'data' => $result];
        
    } catch (AlertBackException $e) {
        // 业务异常：直接返回错误信息
        return ['success' => false, 'message' => $e->getMessage()];
        
    } catch (Exception $e) {
        // 系统异常：记录日志后返回通用错误
        Logger::error('添加图案异常', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return ['success' => false, 'message' => '系统错误，请稍后重试'];
    }
}
```

### 8.7 SQL查询规范

#### **参数绑定（防SQL注入）**

```php
// ✅ 使用占位符
$sql = "SELECT * FROM custom_pattern WHERE pattern_id = ? AND status = ?";
$result = $this->pdo->fetch($sql, [$patternId, 1]);

// ✅ 命名占位符
$sql = "SELECT * FROM custom_pattern WHERE pattern_id = :id AND status = :status";
$result = $this->pdo->fetch($sql, [
    'id' => $patternId,
    'status' => 1
]);

// ❌ 字符串拼接（危险！）
$sql = "SELECT * FROM custom_pattern WHERE pattern_id = $patternId";
```

#### **SQL格式化规范**

```php
// ✅ 多行SQL：关键字大写，缩进清晰
$sql = "SELECT 
            pattern_id,
            pattern_name,
            pattern_url,
            created_at
        FROM custom_pattern 
        WHERE goods_id = ? 
          AND status = 1 
        ORDER BY sort_order ASC, pattern_id DESC
        LIMIT ? OFFSET ?";

// ✅ 简单SQL：单行
$sql = "SELECT COUNT(*) as cnt FROM custom_pattern WHERE goods_id = ?";
```

### 8.8 数组操作规范

#### **数组定义**

```php
// ✅ 短数组语法（PHP 5.4+）
$colors = ['red', 'green', 'blue'];
$data = [
    'pattern_id' => 1,
    'pattern_name' => 'test',
    'status' => 1
];

// ❌ 旧语法（避免）
$colors = array('red', 'green', 'blue');
```

#### **数组键引号规范**

```php
// ✅ 字符串键使用引号
$data = [
    'pattern_id' => 1,    // 字符串键
    'pattern_name' => 'test'
];

// ✅ 访问数组元素使用引号
$id = $data['pattern_id'];

// ❌ 避免（会产生Notice）
$id = $data[pattern_id];  // 未定义常量
```

### 8.9 条件判断规范

#### **布尔比较**

```php
// ✅ 直接判断布尔值
if ($isCustomizable) { }
if (!$isDeleted) { }

// ❌ 冗余比较
if ($isCustomizable == true) { }
if ($isDeleted === false) { }

// ✅ 类型严格比较
if ($status === 1) { }      // 类型和值都相等
if ($result !== false) { }  // 类型和值都不等

// ❌ 弱类型比较（可能导致错误）
if ($status == 1) { }   // '1' == 1 为true
```

#### **空值检查**

```php
// ✅ 检查null
if ($data === null) { }
if ($data !== null) { }

// ✅ 检查空数组
if (empty($colors)) { }
if (!empty($colors)) { }

// ✅ 检查数组键是否存在
if (isset($data['pattern_id'])) { }
if (array_key_exists('pattern_id', $data)) { }

// ✅ 三元运算符
$name = $data['name'] ?? 'default';  // PHP 7+
$name = isset($data['name']) ? $data['name'] : 'default';
```

### 8.10 函数参数规范

#### **参数类型声明（PHP 7+）**

```php
// ✅ 使用类型声明
public function addPattern(array $patternData): array
{
    return ['success' => true];
}

public function getPatternById(int $patternId): ?array
{
    return $this->pdo->fetch($sql, [$patternId]);
}

// ✅ 可空类型（PHP 7.1+）
public function updatePattern(?int $patternId, array $data): bool
{
    if ($patternId === null) {
        return false;
    }
    // ...
}
```

#### **默认参数**

```php
// ✅ 默认参数放在最后
public function getPatternList(int $page = 1, int $pageSize = 20, string $keyword = ''): array
{
    // ...
}

// ❌ 错误的参数顺序
public function getPatternList(int $page = 1, int $pageSize, string $keyword = ''): array
{
    // 语法错误！
}
```

### 8.11 代码复用原则

#### **DRY原则（Don't Repeat Yourself）**

```php
// ❌ 代码重复
public function addColor($data) {
    if (empty($data['color_name'])) {
        throw new Exception('颜色名称不能为空');
    }
    if (strlen($data['color_name']) > 50) {
        throw new Exception('颜色名称不能超过50个字符');
    }
}

public function updateColor($id, $data) {
    if (empty($data['color_name'])) {
        throw new Exception('颜色名称不能为空');
    }
    if (strlen($data['color_name']) > 50) {
        throw new Exception('颜色名称不能超过50个字符');
    }
}

// ✅ 提取公共方法
private function validateColorName($colorName) {
    if (empty($colorName)) {
        throw new Exception('颜色名称不能为空');
    }
    if (strlen($colorName) > 50) {
        throw new Exception('颜色名称不能超过50个字符');
    }
}

public function addColor($data) {
    $this->validateColorName($data['color_name']);
    // ...
}

public function updateColor($id, $data) {
    $this->validateColorName($data['color_name']);
    // ...
}
```

### 8.12 性能优化建议

#### **数据库查询优化**

```php
// ✅ 只查询需要的字段
$sql = "SELECT pattern_id, pattern_name FROM custom_pattern WHERE goods_id = ?";

// ❌ 避免SELECT *
$sql = "SELECT * FROM custom_pattern WHERE goods_id = ?";

// ✅ 使用索引字段作为查询条件
$sql = "SELECT * FROM custom_pattern WHERE pattern_id = ?";  // pattern_id有索引

// ❌ 避免在非索引字段上使用LIKE
$sql = "SELECT * FROM custom_pattern WHERE pattern_name LIKE ?";

// ✅ 批量查询替代循环查询
$sql = "SELECT * FROM custom_pattern WHERE pattern_id IN (?, ?, ?)";
$results = $this->pdo->fetchAll($sql, [1, 2, 3]);

// ❌ 循环查询（N+1问题）
foreach ($ids as $id) {
    $result = $this->pdo->fetch("SELECT * FROM custom_pattern WHERE pattern_id = ?", [$id]);
}
```

#### **缓存使用建议**

```php
// ✅ 缓存列表数据
$cacheKey = "pattern_list_page_{$page}_size_{$size}";
$cachedData = $this->cache->get($cacheKey);
if ($cachedData !== null) {
    return $cachedData;
}
$data = $this->fetchFromDatabase();
$this->cache->set($cacheKey, $data, 1800);

// ✅ 操作后清除缓存
public function addPattern($data) {
    $result = $this->insertPattern($data);
    $this->clearPatternCache();  // 清除缓存
    return $result;
}
```

---

## 9. API接口完整文档

### 9.1 颜色管理API

#### **9.1.1 获取商品颜色列表**

**接口地址**: `/admin/custom_color.php?mode=getColorsAPI`  
**请求方法**: `GET`  
**权限要求**: 管理员登录

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |

**请求示例**:
```
GET /admin/custom_color.php?mode=getColorsAPI&goods_id=123
```

**响应示例**:
```json
{
    "success": true,
    "data": [
        {
            "color_id": 1,
            "goods_id": 123,
            "color_name": "红色",
            "color_code": "RED",
            "color_hex": "#FF0000",
            "svg_image_path": "/upload/goods/colors/123/red.svg",
            "sort_order": 0,
            "status": 1,
            "created_at": "2025-01-01 12:00:00"
        }
    ],
    "message": "获取颜色列表成功"
}
```

---

#### **9.1.2 添加商品颜色**

**接口地址**: `/admin/custom_color.php?mode=addColorAPI`  
**请求方法**: `POST`  
**权限要求**: 管理员登录

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |
| color_name | string | 是 | 颜色名称，最长50字符 |
| color_code | string | 是 | 颜色代码，唯一标识 |
| color_hex | string | 是 | 颜色HEX值，如#FF0000 |
| svg_image_path | string | 否 | SVG图片路径 |
| sort_order | int | 否 | 排序顺序，默认0 |

**请求示例**:
```json
POST /admin/custom_color.php?mode=addColorAPI
Content-Type: application/x-www-form-urlencoded

goods_id=123&color_name=红色&color_code=RED&color_hex=%23FF0000&sort_order=0
```

**响应示例**:
```json
{
    "success": true,
    "data": {
        "color_id": 1
    },
    "message": "颜色添加成功"
}
```

**错误响应示例**:
```json
{
    "success": false,
    "data": null,
    "message": "该商品已存在同名颜色：\"红色\""
}
```

---

#### **9.1.3 更新商品颜色**

**接口地址**: `/admin/custom_color.php?mode=updateColorAPI`  
**请求方法**: `POST`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| color_id | int | 是 | 颜色ID |
| goods_id | int | 是 | 商品ID |
| color_name | string | 否 | 颜色名称 |
| color_code | string | 否 | 颜色代码 |
| color_hex | string | 否 | 颜色HEX值 |
| svg_image_path | string | 否 | SVG图片路径 |
| sort_order | int | 否 | 排序顺序 |

**响应示例**:
```json
{
    "success": true,
    "message": "颜色更新成功"
}
```

---

#### **9.1.4 删除商品颜色**

**接口地址**: `/admin/custom_color.php?mode=deleteColorAPI`  
**请求方法**: `POST`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| color_id | int | 是 | 颜色ID |
| goods_id | int | 是 | 商品ID |

**响应示例**:
```json
{
    "success": true,
    "message": "颜色删除成功"
}
```

---

#### **9.1.5 上传颜色SVG图片**

**接口地址**: `/admin/custom_color.php?mode=uploadSvgAPI`  
**请求方法**: `POST`  
**Content-Type**: `multipart/form-data`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |
| svg_file | file | 是 | SVG文件 |

**响应示例**:
```json
{
    "success": true,
    "data": {
        "svg_url": "/upload/goods/colors/123/red_20250101120000_1234.svg"
    },
    "message": "SVG上传成功"
}
```

---

### 9.2 图案管理API

#### **9.2.1 获取图案列表**

**接口地址**: `/admin/custom_pattern.php?mode=getPatternListAPI`  
**请求方法**: `GET`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 | 默认值 |
|--------|------|------|------|--------|
| page | int | 否 | 页码，从1开始 | 1 |
| page_size | int | 否 | 每页数量，1-100 | 20 |
| keyword | string | 否 | 搜索关键词 | '' |
| category | string | 否 | 分类筛选 | '' |
| status | string | 否 | 状态：active/inactive | 'active' |
| sort_by | string | 否 | 排序字段 | 'sort_order' |
| sort_order | string | 否 | 排序方向：ASC/DESC | 'ASC' |

**请求示例**:
```
GET /admin/custom_pattern.php?mode=getPatternListAPI&page=1&page_size=20&keyword=测试&category=动物&status=active&sort_by=created_at&sort_order=DESC
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案库获取成功",
    "data": {
        "patterns": [
            {
                "pattern_id": 1,
                "pattern_name": "测试图案",
                "pattern_url": "/upload/goods/pattern_20250101_1234.jpg",
                "pattern_thumbnail": "/upload/goods/thumb_pattern_20250101_1234.jpg",
                "category": "动物",
                "tags": "猫,宠物",
                "description": "可爱的小猫图案",
                "sort_order": 0,
                "status": "active",
                "created_at": "2025-01-01 12:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 100,
            "total_pages": 5,
            "has_next": true,
            "has_prev": false
        }
    },
    "timestamp": "2025-01-01 12:00:00"
}
```

---

#### **9.2.2 上传图案**

**接口地址**: `/admin/custom_pattern.php?mode=uploadPatternAPI`  
**请求方法**: `POST`  
**Content-Type**: `multipart/form-data`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_file | file | 是 | 图案文件（JPG/PNG/GIF/SVG） |
| pattern_name | string | 是 | 图案名称 |
| category | string | 否 | 分类 |
| pattern_group | string | 否 | 分组 |
| pattern_tags | string | 否 | 标签，逗号分隔 |
| description | string | 否 | 描述 |
| status | int | 否 | 状态：0-禁用，1-启用 |
| csrf_token | string | 是 | CSRF Token |

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案上传成功",
    "data": {
        "pattern_id": 123,
        "pattern_name": "测试图案",
        "pattern_url": "/upload/goods/test_20250101120000_1234.jpg",
        "file_size": 102400
    },
    "timestamp": "2025-01-01 12:00:00"
}
```

**错误响应示例**:
```json
{
    "success": false,
    "code": 400,
    "message": "不支持的文件类型，仅支持JPEG、PNG、GIF、SVG格式",
    "data": null,
    "timestamp": "2025-01-01 12:00:00"
}
```

---

#### **9.2.3 更新图案信息**

**接口地址**: `/admin/custom_pattern.php?mode=updatePatternAPI`  
**请求方法**: `POST`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_id | int | 是 | 图案ID |
| pattern_name | string | 是 | 图案名称，最长100字符 |
| category | string | 否 | 分类 |
| pattern_group | string | 否 | 分组，最长50字符 |
| description | string | 否 | 描述，最长500字符 |
| status | string | 是 | 状态：0/1 |
| csrf_token | string | 是 | CSRF Token |

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案更新成功",
    "data": {
        "pattern_id": 123,
        "pattern_name": "更新后的名称",
        "category": "动物",
        "status": "1"
    },
    "timestamp": "2025-01-01 12:00:00"
}
```

---

#### **9.2.4 删除图案**

**接口地址**: `/admin/custom_pattern.php?mode=deletePatternAPI`  
**请求方法**: `POST`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_id | int | 是 | 图案ID |
| csrf_token | string | 是 | CSRF Token |

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案删除成功",
    "data": {
        "pattern_id": 123,
        "deleted_files": [
            "/upload/goods/test_20250101_1234.jpg"
        ]
    },
    "timestamp": "2025-01-01 12:00:00"
}
```

**错误响应示例**:
```json
{
    "success": false,
    "code": 400,
    "message": "该图案已被商品引用，无法删除",
    "data": null,
    "timestamp": "2025-01-01 12:00:00"
}
```

---

#### **9.2.5 批量删除图案**

**接口地址**: `/admin/custom_pattern.php?mode=batchDeletePatternsAPI`  
**请求方法**: `POST`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_ids | array | 是 | 图案ID数组 |
| csrf_token | string | 是 | CSRF Token |

**请求示例**:
```json
POST /admin/custom_pattern.php?mode=batchDeletePatternsAPI
Content-Type: application/json

{
    "pattern_ids": [1, 2, 3, 4, 5],
    "csrf_token": "abc123..."
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "批量删除成功",
    "data": {
        "deleted_count": 5,
        "pattern_ids": [1, 2, 3, 4, 5],
        "deleted_files": [
            "/upload/goods/pattern1.jpg",
            "/upload/goods/pattern2.jpg"
        ]
    },
    "timestamp": "2025-01-01 12:00:00"
}
```

---

### 9.3 商品定制状态API

#### **9.3.1 获取商品定制化状态**

**接口地址**: `/admin/custom_color.php?mode=getCustomizationStatusAPI`  
**请求方法**: `GET`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |

**响应示例**:
```json
{
    "success": true,
    "data": {
        "goods_id": 123,
        "is_customizable": 1
    },
    "message": "获取成功"
}
```

---

#### **9.3.2 更新商品定制化状态**

**接口地址**: `/admin/custom_color.php?mode=updateCustomizationStatusAPI`  
**请求方法**: `POST`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |
| is_customizable | int | 是 | 定制化状态：0-不支持，1-支持 |

**响应示例**:
```json
{
    "success": true,
    "data": {
        "goods_id": 123,
        "is_customizable": 1
    },
    "message": "更新成功"
}
```

---

### 9.4 商品自定义尺寸API

#### **9.4.1 获取商品自定义尺寸**

**接口地址**: `/admin/custom_color.php?mode=getCustomSizeAPI`  
**请求方法**: `GET`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |

**响应示例**:
```json
{
    "success": true,
    "data": {
        "custom_width": 10.5,
        "custom_height": 15.0
    },
    "message": "获取成功"
}
```

---

#### **9.4.2 更新商品自定义尺寸**

**接口地址**: `/admin/custom_color.php?mode=updateCustomSizeAPI`
**请求方法**: `POST`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |
| custom_width | float | 否 | 宽度（cm），0-999.99 |
| custom_height | float | 否 | 高度（cm），0-999.99 |

**响应示例**:
```json
{
    "success": true,
    "data": {
        "goods_id": 123,
        "custom_width": 10.5,
        "custom_height": 15.0
    },
    "message": "更新成功"
}
```

---

### 9.5 错误码说明

| 错误码 | 说明 | 解决方案 |
|--------|------|---------|
| 200 | 成功 | - |
| 400 | 请求参数错误 | 检查参数格式和必填项 |
| 401 | 未授权 | 重新登录 |
| 403 | 权限不足 | 联系管理员授权 |
| 404 | 资源不存在 | 检查资源ID是否正确 |
| 500 | 服务器错误 | 查看日志，联系技术支持 |

---

## 10. 错误处理与调试技巧

### 10.1 常见错误类型

#### **10.1.1 数据库连接错误**

**错误信息**:
```
数据库连接失败：SQLSTATE[HY000] [1045] Access denied for user
```

**原因分析**:
1. 数据库配置错误（用户名/密码/主机/端口）
2. 数据库服务未启动
3. 防火墙阻止连接

**解决方案**:
```php
// 1. 检查配置文件 config/database.php
return [
    'host' => '10.1.0.189',      // 检查主机地址
    'port' => 3306,               // 检查端口
    'dbname' => 'your_database',  // 检查数据库名
    'username' => 'your_user',    // 检查用户名
    'password' => 'your_pass',    // 检查密码
];

// 2. 测试数据库连接
try {
    $pdo = new PDO("mysql:host=10.1.0.189;dbname=test", "user", "pass");
    echo "连接成功";
} catch (PDOException $e) {
    echo "连接失败: " . $e->getMessage();
}
```

---

#### **10.1.2 文件上传错误**

**错误信息**:
```
文件上传失败 (错误代码: 1)
```

**PHP上传错误码对照表**:

| 错误码 | 常量 | 说明 |
|--------|------|------|
| 0 | UPLOAD_ERR_OK | 没有错误，上传成功 |
| 1 | UPLOAD_ERR_INI_SIZE | 文件大小超过php.ini的upload_max_filesize |
| 2 | UPLOAD_ERR_FORM_SIZE | 文件大小超过表单的MAX_FILE_SIZE |
| 3 | UPLOAD_ERR_PARTIAL | 文件只有部分被上传 |
| 4 | UPLOAD_ERR_NO_FILE | 没有文件被上传 |
| 6 | UPLOAD_ERR_NO_TMP_DIR | 找不到临时文件夹 |
| 7 | UPLOAD_ERR_CANT_WRITE | 文件写入失败 |

**解决方案**:
```php
// 检查 php.ini 配置
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('memory_limit', '256M');

// 检查上传目录权限
$uploadDir = '/path/to/upload';
if (!is_writable($uploadDir)) {
    chmod($uploadDir, 0755);
}
```

---

#### **10.1.3 缓存相关错误**

**错误信息**:
```
缓存系统初始化失败
```

**解决方案**:
```php
// 检查缓存目录权限
$cacheDir = '/path/to/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// 降级处理：缓存失败不影响主流程
try {
    $this->cache = new SimpleCache();
} catch (Exception $e) {
    Logger::warning('缓存初始化失败，使用无缓存模式', [
        'error' => $e->getMessage()
    ]);
    $this->cache = null;  // 设为null，代码中判断使用
}
```

---

### 10.2 调试技巧

#### **10.2.1 开启详细日志**

```php
// 在Component中添加调试日志
public function addPattern($patternData)
{
    Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 开始添加图案', [
        'pattern_data' => $patternData
    ]);
    
    try {
        $result = $this->processPattern($patternData);
        
        Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 处理成功', [
            'result' => $result
        ]);
        
        return $result;
    } catch (Exception $e) {
        Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 处理失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}
```

---

#### **10.2.2 SQL调试**

```php
// 在CustomPDO中添加SQL日志
public function fetch($sql, $params = [])
{
    Logger::channel('userLog')->debug('SQL执行', [
        'sql' => $sql,
        'params' => $params
    ]);
    
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        Logger::channel('userLog')->debug('SQL结果', [
            'result' => $result
        ]);
        
        return $result ?: false;
    } catch (PDOException $e) {
        Logger::channel('userLog')->debug('SQL错误', [
            'sql' => $sql,
            'params' => $params,
            'error' => $e->getMessage()
        ]);
        throw new Exception('查询失败：' . $e->getMessage());
    }
}
```

---

#### **10.2.3 API请求调试**

```php
// 在Controller中记录完整请求
public function index()
{
    Logger::channel('userLog')->debug('API请求开始', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'get' => $_GET,
        'post' => $_POST,
        'files' => $_FILES
    ]);
    
    $action = Request::request()->get('action');
    
    // ... 处理逻辑
    
    Logger::channel('userLog')->debug('API请求结束', [
        'action' => $action
    ]);
}
```

---

### 10.3 性能分析

#### **10.3.1 慢查询分析**

```php
// 添加查询时间统计
public function fetch($sql, $params = [])
{
    $startTime = microtime(true);
    
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // 转换为毫秒
        
        // 记录慢查询（超过100ms）
        if ($executionTime > 100) {
            Logger::warning('慢查询检测', [
                'sql' => $sql,
                'params' => $params,
                'execution_time' => $executionTime . 'ms'
            ]);
        }
        
        return $result ?: false;
    } catch (PDOException $e) {
        throw new Exception('查询失败：' . $e->getMessage());
    }
}
```

---

#### **10.3.2 缓存命中率分析**

```php
// 统计缓存命中率
private $cacheHitCount = 0;
private $cacheMissCount = 0;

private function getCachedPatternList($cacheKey)
{
    $cachedData = $this->cache->get($cacheKey);
    
    if ($cachedData !== null) {
        $this->cacheHitCount++;
        Logger::info('缓存命中', [
            'cache_key' => $cacheKey,
            'hit_rate' => $this->getCacheHitRate()
        ]);
        return $cachedData;
    }
    
    $this->cacheMissCount++;
    Logger::info('缓存未命中', [
        'cache_key' => $cacheKey,
        'hit_rate' => $this->getCacheHitRate()
    ]);
    
    return false;
}

private function getCacheHitRate()
{
    $total = $this->cacheHitCount + $this->cacheMissCount;
    return $total > 0 ? ($this->cacheHitCount / $total * 100) . '%' : '0%';
}
```

---

### 10.4 故障排查清单

#### **API接口不响应**

- [ ] 检查路由配置是否正确
- [ ] 检查Controller类名和命名空间
- [ ] 检查action参数是否正确
- [ ] 检查权限验证是否通过
- [ ] 查看错误日志

#### **数据查询异常**

- [ ] 检查数据库连接配置
- [ ] 检查表和字段是否存在
- [ ] 检查SQL语句语法
- [ ] 检查参数绑定是否正确
- [ ] 查看数据库错误日志

#### **文件上传失败**

- [ ] 检查php.ini上传限制
- [ ] 检查目录权限（755）
- [ ] 检查磁盘空间
- [ ] 检查文件类型验证
- [ ] 查看上传错误码

#### **缓存不生效**

- [ ] 检查缓存目录权限
- [ ] 检查缓存配置
- [ ] 检查缓存键生成逻辑
- [ ] 检查TTL设置
- [ ] 检查缓存清除时机

---

### 10.5 日志查看命令

```bash
# 查看实时日志
tail -f /path/to/logs/custom.log

# 查看错误日志
tail -f /path/to/logs/error.log

# 搜索特定关键词
grep "图案上传" /path/to/logs/custom.log

# 统计错误次数
grep "ERROR" /path/to/logs/custom.log | wc -l

# 查看最近100行
tail -n 100 /path/to/logs/custom.log
```

---

**文档版本**: v1.0  
**最后更新**: 2025年  
**维护团队**: Custom Development Team  
**技术支持**: [support@example.com](mailto:support@example.com)

---

## 📚 附录

### A. 快速参考索引

**核心类清单**:
- CustomPattern - 图案管理组件
- CustomColor - 颜色管理组件
- CustomGoods - 商品定制组件
- CustomPDO - 数据库操作类
- CustomStorage - 文件存储类

**数据库表清单**:
- custom_pattern - 图案主表
- custom_goods_extension - 商品扩展表
- custom_goods_colors - 商品颜色表
- custom_cart_extension - 购物车扩展表
- custom_order_extension - 订单扩展表

**配置文件清单**:
- config/database.php - 数据库配置
- Database/install_custom_tables_fixed.sql - 表结构SQL

### B. 版本历史

| 版本 | 日期 | 更新内容 |
|------|------|---------|
| 1.0 | 2025-01-01 | 初始版本发布 |

---

**📝 完**

