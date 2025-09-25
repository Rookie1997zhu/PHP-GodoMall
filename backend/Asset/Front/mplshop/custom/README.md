# 商品定制功能 - MplShop集成版

## 功能简介

这是基于Godomall平台开发的商品定制功能，已移动到mplshop目录结构中，完全遵循Godomall开发规范。用户可以通过可视化界面对商品进行个性化定制，包括添加图案、文字、上传图片等功能。

## 文件结构

```
mplshop/
├── custom/
│   ├── goods_custom.html          # 主定制页面
│   └── README.md                  # 本说明文件
├── css/custom/
│   ├── goods_custom.css           # 定制页面样式
│   └── custom_goods_purchase.css  # 购买按钮样式  
└── js/custom/
    ├── goods_custom.js            # 定制功能脚本
    └── custom_goods_purchase.js   # 购买按钮脚本

backend/
└── Custom/
    └── Controller/
        └── Front/
            └── add_to_cart.php    # 定制商品购物车API
```

## 主要特性

- ✅ **遵循Godomall开发规范**：所有代码严格按照官方开发指南编写
- ✅ **可视化定制界面**：基于Fabric.js的强大画布功能
- ✅ **多种定制选项**：支持图案、文字、图片上传等
- ✅ **响应式设计**：适配PC和移动端设备
- ✅ **安全性保障**：防止SQL注入，安全处理用户输入
- ✅ **购物车集成**：定制商品可添加到购物车并结算

## 核心功能

### 1. 商品定制
- **图案库**：预设图案供用户选择
- **文字定制**：添加自定义文字，支持字体、颜色、大小调整
- **图片上传**：用户可上传自己的图片进行定制
- **画布操作**：撤销/重做、删除、翻转、对齐等操作

### 2. 商品选项
- **颜色选择**：支持多种颜色选项
- **尺寸选择**：支持多种尺寸规格
- **数量控制**：灵活的数量选择

### 3. 购买流程
- **定制完成**：生成定制预览图
- **添加购物车**：将定制商品加入购物车
- **数据保存**：保存定制配置用于后续处理

## 技术栈

- **前端**：HTML5, CSS3, JavaScript (ES6+)
- **画布技术**：Fabric.js v5.3.0
- **后端**：PHP (Godomall Framework)
- **数据库**：MySQL
- **样式框架**：自定义CSS，支持响应式设计

## 集成说明

### API路径更新

由于移动到mplshop目录，相关API路径已更新：

**正确的API调用方式**：
```javascript  
// 方式1：直接调用Controller方法
fetch('../../Custom/Controller/Front/add_to_cart.php', {...})

// 方式2：通过专门的API文件
fetch('../../Custom/Controller/Front/CustomGoodsController.php', {
    method: 'POST',
    body: JSON.stringify({mode: 'addToCart', ...})
})
```

**注意**：`add_to_cart.php` 文件已移动到正确的后端Controller目录 `backend/Custom/Controller/Front/` 中，遵循Godomall的MVC架构规范。

### 样式文件引用

**HTML模板中的引用路径**：
```html
<!-- CSS文件 -->
<link rel="stylesheet" href="../css/custom/goods_custom.css">

<!-- JavaScript文件 -->
<script src="../js/custom/goods_custom.js"></script>
```

### 与商品详情页集成

在商品详情页中添加定制按钮：

```html
<!--{ ? goods.is_customizable == '1' }-->
<button class="btn-custom-purchase" onclick="goToCustomization({=goods.goodsNo})">
    🎨 定制商品
</button>
<!--{ / }-->

<script>
function goToCustomization(goodsNo) {
    window.location.href = `/custom/goods_custom.html?goodsNo=${goodsNo}`;
}
</script>
```

## 使用方法

### 1. 页面访问
```
http://yourdomain.com/mplshop/custom/goods_custom.html?goodsNo=商品ID
```

### 2. 参数说明
- `goodsNo`: 商品编号（必需）
- `color`: 默认颜色（可选）
- `size`: 默认尺寸（可选）

### 3. 数据传递

页面通过HTML模板变量接收后端数据：

```html
<script id="pageConfig" type="application/json">
{
    "goods_id": "{=goods_id}",
    "can_customize": {=can_customize},
    "max_upload_size": {=max_upload_size},
    "goods_image_url": "{=goods_image_url}"
}
</script>
```

## 注意事项

### 1. 文件权限
确保上传目录有正确的写入权限：
```bash
chmod 755 data/custom_images/
```

### 2. PHP配置
上传功能需要适当的PHP配置：
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

### 3. CDN依赖
页面依赖Fabric.js CDN，确保网络连接：
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
```

### 4. 浏览器支持
- Chrome 60+
- Firefox 55+  
- Safari 12+
- Edge 79+

## 故障排除

### 常见问题

1. **图片上传失败**
   - 检查文件大小限制
   - 确认文件格式支持
   - 验证目录权限

2. **定制功能无响应**
   - 检查Fabric.js是否加载成功
   - 查看浏览器控制台错误信息
   - 确认API路径正确

3. **样式显示异常**
   - 确认CSS文件路径正确
   - 检查样式冲突
   - 验证响应式设计

### 调试技巧

在浏览器控制台中可以访问定制编辑器实例：
```javascript
// 获取编辑器实例
window.customEditor

// 测试画布状态
customEditor.testCanvasState()

// 获取定制数据
customEditor.customizationData
```

## 性能优化

1. **图片压缩**：上传的图片自动压缩到合适尺寸
2. **延迟加载**：图案库按需加载
3. **缓存策略**：静态资源启用浏览器缓存
4. **代码分割**：核心功能与附加功能分离

## 安全考虑

1. **文件类型验证**：严格验证上传文件类型
2. **大小限制**：限制上传文件大小
3. **路径检查**：防止目录遍历攻击
4. **输入过滤**：对所有用户输入进行清理

## 版本信息

- **当前版本**：1.0.0
- **更新日期**：2024-01-XX
- **兼容性**：Godomall 5.0+

## 联系支持

如有问题或需要技术支持，请参考：
- Godomall官方文档
- 开发团队技术支持
- 社区论坛交流

---

**注意**：本功能基于Godomall开发规范构建，确保与官方补丁兼容。升级前请备份自定义代码。
