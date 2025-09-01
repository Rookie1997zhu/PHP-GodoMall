# 商品定制功能 - Backend实现说明

## 🎯 **功能概述**

本backend实现了完整的商品定制功能，包括定制图片的生成、保存、存储和管理。

## ✨ **新增功能特性**

### **1. 定制图片保存功能**
- ✅ **Base64图片转换**: 将前端Canvas生成的Base64图片数据转换为文件
- ✅ **文件系统存储**: 图片保存到 `data/custom_images/` 目录
- ✅ **URL生成**: 自动生成图片访问URL
- ✅ **文件管理**: 支持图片文件的删除和清理

### **2. 数据库扩展**
- ✅ **订单表扩展**: `es_order.customization_image` 字段存储定制图片URL
- ✅ **购物车扩展**: `es_cart_items` 表支持定制数据存储
- ✅ **定制记录表**: `custom_goods_customization` 表记录定制历史

### **3. 图片处理流程**
```
前端Canvas生成图片 → Base64数据 → 后端转换 → 文件保存 → URL生成 → 数据库存储
```

## 🏗️ **目录结构**

```
backend/
├── Custom/
│   ├── Component/
│   │   ├── CustomGoods.php          # 商品定制业务逻辑（新增图片保存功能）
│   │   ├── CustomOrder.php          # 订单定制管理
│   │   ├── CustomPattern.php        # 图案管理
│   │   ├── Database/
│   │   │   └── CustomTableField.php # 数据库字段定义
│   │   └── Storage/
│   │       └── CustomStorage.php    # 文件存储管理
│   ├── Controller/
│   │   ├── Front/
│   │   │   └── CustomGoodsController.php    # 前台定制接口
│   │   └── Admin/
│   │       └── Custom/
│   │           ├── CustomGoodsController.php     # 后台商品管理
│   │           ├── CustomPatternController.php   # 后台图案管理
│   │           └── DatabaseInstallController.php # 数据库安装
│   └── Database/
│       └── install_custom_tables.sql # 数据库安装脚本
├── Asset/
│   ├── Front/default/custom/
│   │   ├── goods_custom.html        # 商品定制页面
│   │   ├── goods_custom.css         # 定制页面样式
│   │   └── goods_custom.js          # 定制页面脚本
│   └── Admin/custom/
│       └── database_install.html    # 数据库安装页面
└── data/
    └── custom_images/               # 定制图片存储目录
```

## 🔧 **核心方法说明**

### **CustomGoods.php - 新增方法**

#### **1. saveCustomImageFile()**
```php
/**
 * 保存定制图片文件
 * @param string $base64Data Base64图片数据
 * @param int $userId 用户ID
 * @param string $goodsNo 商品编号
 * @return array 保存结果
 */
public function saveCustomImageFile($base64Data, $userId, $goodsNo = '')
```

**功能**:
- 验证Base64图片数据格式
- 解码Base64数据为二进制
- 生成唯一文件名
- 保存到文件系统
- 生成访问URL

#### **2. saveImageToFile()**
```php
/**
 * 将图片数据保存到文件
 * @param string $imageData 图片二进制数据
 * @param string $fileName 文件名
 * @return string|false 文件路径或false
 */
private function saveImageToFile($imageData, $fileName)
```

**功能**:
- 创建定制图片目录
- 写入图片文件
- 返回文件路径

#### **3. deleteCustomImageFile()**
```php
/**
 * 删除定制图片文件
 * @param string $imageUrl 图片URL
 * @return bool 删除结果
 */
public function deleteCustomImageFile($imageUrl)
```

**功能**:
- 从URL提取文件路径
- 删除物理文件
- 记录操作日志

### **修改的方法**

#### **addCustomGoodsToCart()**
- 新增图片处理逻辑
- 自动保存定制图片
- 替换Base64数据为文件URL
- 优化数据库存储

## 📊 **数据库变更**

### **新增字段**
```sql
-- 订单表扩展
ALTER TABLE `es_order` 
ADD COLUMN IF NOT EXISTS `customization_image` VARCHAR(500) DEFAULT NULL COMMENT '定制图片URL';

-- 创建索引
CREATE INDEX IF NOT EXISTS `idx_order_customization_image` ON `es_order` (`customization_image`);
```

### **新增表**
- `custom_goods_customization`: 商品定制记录表
- `custom_uploaded_images`: 上传图片记录表
- `custom_pattern`: 定制图案表
- `goods_custom_pattern`: 商品图案关联表

## 🚀 **使用流程**

### **1. 前端定制流程**
```
用户进入定制页 → 选择图案/文字 → 拖拽调整 → 预览效果 → 点击添加到购物车
```

### **2. 后端处理流程**
```
接收定制数据 → 验证数据 → 保存图片文件 → 生成URL → 存储到数据库 → 返回结果
```

### **3. 图片存储流程**
```
Base64数据 → 解码验证 → 文件保存 → 目录管理 → URL生成 → 数据库关联
```

## ⚠️ **注意事项**

### **1. 文件权限**
- 确保 `data/custom_images/` 目录有写权限
- 建议权限设置为 755
- 所有者设置为web服务器用户

### **2. 存储空间**
- 定期清理无用的定制图片
- 监控存储空间使用情况
- 考虑图片压缩和优化

### **3. 安全考虑**
- 验证图片格式和大小
- 防止恶意文件上传
- 限制文件访问权限

## 🔍 **测试验证**

### **1. 功能测试**
- 测试图片生成和保存
- 验证文件URL访问
- 检查数据库记录

### **2. 性能测试**
- 测试大量图片同时上传
- 验证文件系统性能
- 检查内存使用情况

### **3. 错误处理**
- 测试无效数据输入
- 验证异常情况处理
- 检查日志记录

## 📝 **更新日志**

### **2025-08-12**
- ✅ 新增定制图片保存功能
- ✅ 实现Base64图片转换
- ✅ 添加文件系统存储
- ✅ 扩展数据库结构
- ✅ 完善错误处理和日志

## 🎉 **总结**

本backend实现完全符合流程图要求，支持：
1. **图片生成**: 前端Canvas生成定制图片
2. **图片上传**: 自动保存Base64图片为文件
3. **URL获取**: 生成图片访问URL
4. **订单关联**: 图片URL写入订单表
5. **完整流程**: 从定制到下单的完整数据流

所有功能均已实现并经过测试，可以直接部署使用！
