# 商品定制功能集成指南

## 概述

本指南说明如何将商品定制功能集成到GodoMall系统中，包括数据库安装、后端集成、前端配置等步骤。

## 1. 数据库安装

### 1.1 执行数据库脚本

```bash
# 在MySQL中执行安装脚本
mysql -u username -p database_name < backend/Custom/Database/install_custom_tables.sql
```

### 1.2 验证安装

检查以下表是否创建成功：
- `custom_pattern` - 定制图案表
- `goods_custom_pattern` - 商品定制图案关联表
- `custom_goods_customization` - 商品定制记录表
- `custom_uploaded_images` - 上传图片记录表
- `es_goods_colors` - 商品颜色表
- `es_goods_color_relation` - 商品颜色关联表
- `es_goods_sizes` - 商品尺寸表
- `es_goods_size_relation` - 商品尺寸关联表

## 2. 后端集成

### 2.1 文件结构

确保以下文件已正确放置：

```
backend/
└── Custom/
    ├── Component/
    │   ├── CustomGoods.php
    │   └── CustomPattern.php
    ├── Controller/
    │   └── Front/
    │       └── CustomGoodsController.php
    └── Database/
        └── install_custom_tables.sql
```

### 2.2 路由配置

在GodoMall的路由文件中添加定制页面路由：

```php
// 在相应的路由文件中添加
Route::get('/custom/goods/{goods_id}', 'Custom\Controller\Front\CustomGoodsController@show');
Route::post('/custom/add-to-cart', 'Custom\Controller\Front\CustomGoodsController@addToCart');
Route::post('/custom/save', 'Custom\Controller\Front\CustomGoodsController@saveCustomization');
Route::post('/custom/upload-image', 'Custom\Controller\Front\CustomGoodsController@uploadImage');
Route::get('/custom/patterns/{goods_id}', 'Custom\Controller\Front\CustomGoodsController@getPatterns');
```

### 2.3 权限配置

确保用户有访问定制页面的权限：

```php
// 在权限配置中添加
'custom' => [
    'goods_custom' => '商品定制',
    'pattern_manage' => '图案管理',
    'customization_view' => '定制记录查看'
]
```

## 3. 前端集成

### 3.1 文件部署

将前端文件部署到正确位置：

```
data/skin/front/default/custom/
├── goods_custom.html      # 主定制页面
├── goods_custom.css       # 样式文件
├── goods_custom.js        # 交互脚本
├── test.html             # 测试页面
└── README.md             # 说明文档
```

### 3.2 商品详情页集成

在商品详情页添加"定制"按钮：

```html
<!-- 在商品详情页添加定制按钮 -->
<button class="custom-btn" onclick="goToCustomization({=goods_id})">
    <i class="icon-custom"></i>
    定制商品
</button>

<script>
function goToCustomization(goodsId) {
    // 检查商品是否支持定制
    fetch(`/api/goods/${goodsId}/customizable`)
        .then(response => response.json())
        .then(data => {
            if (data.is_customizable) {
                window.location.href = `/custom/goods/${goodsId}`;
            } else {
                alert('该商品不支持定制功能');
            }
        });
}
</script>
```

### 3.3 模板变量替换

确保HTML模板中的变量能被正确替换：

```php
// 在控制器中准备数据
$templateData = [
    'goods_id' => $goods['goods_id'],
    'goods_name' => $goods['goods_name'],
    'goods_price_display' => $priceDisplay,
    'goods_base_price' => $this->formatPrice($goods['price']),
    'goods_rating' => number_format($goods['rating'], 1),
    'goods_review_count' => $goods['review_count'],
    'selected_color_name' => $goods['default_color_name'],
    'goods_colors' => $colorsHtml,
    'goods_sizes' => $sizesHtml,
    'patterns_data' => json_encode($patternsData),
    'can_customize' => true,
    'max_upload_size' => 5 * 1024 * 1024,
    'allowed_image_types' => json_encode(['jpg', 'jpeg', 'png', 'gif'])
];
```

## 4. 商品配置

### 4.1 设置商品支持定制

在商品管理后台添加定制选项：

```sql
-- 为商品启用定制功能
UPDATE es_goods SET is_customizable = 1 WHERE goods_id = ?;

-- 设置默认颜色和尺寸
UPDATE es_goods SET 
    default_color = 'navy',
    default_size = '4.7x2.8'
WHERE goods_id = ?;
```

### 4.2 为商品添加颜色选项

```sql
-- 为商品添加颜色选项
INSERT INTO es_goods_color_relation (goods_id, color_id) VALUES
(?, (SELECT color_id FROM es_goods_colors WHERE color_code = 'navy')),
(?, (SELECT color_id FROM es_goods_colors WHERE color_code = 'red')),
(?, (SELECT color_id FROM es_goods_colors WHERE color_code = 'black'));
```

### 4.3 为商品添加尺寸选项

```sql
-- 为商品添加尺寸选项
INSERT INTO es_goods_size_relation (goods_id, size_id) VALUES
(?, (SELECT size_id FROM es_goods_sizes WHERE size_code = '4.7x2.8')),
(?, (SELECT size_id FROM es_goods_sizes WHERE size_code = '5.0x3.0'));
```

### 4.4 为商品添加定制图案

```sql
-- 为商品添加图案
INSERT INTO goods_custom_pattern (goods_id, pattern_id) VALUES
(?, (SELECT pattern_id FROM custom_pattern WHERE pattern_name = '星星')),
(?, (SELECT pattern_id FROM custom_pattern WHERE pattern_name = '心形')),
(?, (SELECT pattern_id FROM custom_pattern WHERE pattern_name = '圆形'));
```

## 5. 测试验证

### 5.1 功能测试

1. **访问测试页面**：
   ```
   http://your-domain/data/skin/front/default/custom/test.html
   ```

2. **测试定制功能**：
   - 添加图案到画布
   - 添加文字
   - 上传图片
   - 调整颜色和尺寸
   - 添加到购物车

3. **测试API接口**：
   ```bash
   # 测试获取图案
   curl -X GET "http://your-domain/custom/patterns/1"
   
   # 测试添加到购物车
   curl -X POST "http://your-domain/custom/add-to-cart" \
        -H "Content-Type: application/json" \
        -d '{"goods_id": 1, "quantity": 1, "customization_data": {...}}'
   ```

### 5.2 性能测试

1. **图片上传测试**：
   - 测试不同格式图片（JPG、PNG、GIF）
   - 测试文件大小限制（5MB）
   - 测试并发上传

2. **画布性能测试**：
   - 测试大量图案同时显示
   - 测试撤销/重做功能
   - 测试内存使用情况

## 6. 配置优化

### 6.1 文件上传配置

在PHP配置中调整上传限制：

```ini
; php.ini 配置
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

### 6.2 图片处理配置

确保GD库已安装：

```bash
# 检查GD库
php -m | grep gd

# 如果没有安装，安装GD库
# Ubuntu/Debian
sudo apt-get install php-gd

# CentOS/RHEL
sudo yum install php-gd
```

### 6.3 缓存配置

配置图片缓存：

```apache
# .htaccess 配置
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>
```

## 7. 故障排除

### 7.1 常见问题

1. **Fabric.js加载失败**：
   - 检查CDN连接
   - 考虑本地部署Fabric.js

2. **图片上传失败**：
   - 检查文件权限
   - 检查磁盘空间
   - 检查PHP配置

3. **数据库连接错误**：
   - 检查数据库配置
   - 检查表是否存在
   - 检查用户权限

### 7.2 日志查看

查看错误日志：

```bash
# PHP错误日志
tail -f /var/log/php_errors.log

# Apache/Nginx错误日志
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

## 8. 维护说明

### 8.1 定期维护

1. **清理临时文件**：
   ```bash
   # 清理超过7天的临时图片
   find /path/to/custom_images -name "temp_*" -mtime +7 -delete
   ```

2. **数据库优化**：
   ```sql
   -- 优化表
   OPTIMIZE TABLE custom_goods_customization;
   OPTIMIZE TABLE custom_uploaded_images;
   ```

3. **备份数据**：
   ```bash
   # 备份定制相关表
   mysqldump -u username -p database_name custom_pattern goods_custom_pattern custom_goods_customization > custom_backup.sql
   ```

### 8.2 升级注意事项

1. **备份数据**：升级前务必备份所有定制相关数据
2. **测试环境**：在测试环境验证升级效果
3. **兼容性检查**：确保与现有功能兼容

## 9. 技术支持

如遇到问题，请提供以下信息：
- 错误日志
- 系统环境信息
- 复现步骤
- 期望结果

---

**注意**：本集成指南基于GodoMall开发规范编写，请根据实际项目情况调整配置参数和路径。 