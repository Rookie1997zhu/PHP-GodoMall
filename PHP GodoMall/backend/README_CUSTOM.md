# Godomall 商品定制功能代码包

## 📦 功能概述

本代码包实现了Godomall电商平台的商品定制功能，包括：
- 用户端商品定制页面
- 管理员图案管理功能
- 商品定制配置功能
- 数据库表结构扩展
- 完整的定制流程支持

## 🗂️ 代码文件目录结构

### 1. 核心组件 (Custom/Component/)

#### 业务逻辑组件
- **CustomGoods.php** - 商品定制业务逻辑
- **CustomOrder.php** - 定制订单业务逻辑  
- **CustomPattern.php** - 定制图案业务逻辑

#### 存储组件
- **Storage/CustomStorage.php** - 自定义文件存储管理

#### 数据库组件
- **Database/CustomTableField.php** - 数据库表结构扩展定义

### 2. 控制器 (Custom/Controller/)

#### 管理员控制器
- **Admin/Custom/CustomGoodsController.php** - 商品定制配置管理
- **Admin/Custom/CustomPatternController.php** - 图案库管理
- **Admin/Custom/DatabaseInstallController.php** - 数据库安装管理

#### 前台控制器
- **Front/CustomGoodsController.php** - 用户端商品定制页面

### 3. 数据库脚本 (Custom/Database/)
- **install_custom_tables.sql** - 自定义数据表创建脚本

### 4. 前端界面 (Asset/)

#### 用户端界面 (Front/default/custom/)
- **goods_custom.html** - 商品定制页面HTML模板
- **goods_custom.css** - 商品定制页面样式文件
- **goods_custom.js** - 商品定制页面交互逻辑

#### 管理员界面 (Admin/custom/)
- **goods_config.html** - 商品定制配置页面
- **pattern_add.html** - 图案添加页面
- **pattern_list.html** - 图案管理列表页面
- **database_install.html** - 数据库安装页面

## 📍 部署到Godomall的目录位置

### 核心代码部署位置
```
[Godomall根目录]/
├── Custom/
│   ├── Component/
│   │   ├── CustomGoods.php
│   │   ├── CustomOrder.php
│   │   ├── CustomPattern.php
│   │   ├── Storage/
│   │   │   └── CustomStorage.php
│   │   └── Database/
│   │       └── CustomTableField.php
│   ├── Controller/
│   │   ├── Admin/
│   │   │   └── Custom/
│   │   │       ├── CustomGoodsController.php
│   │   │       ├── CustomPatternController.php
│   │   │       └── DatabaseInstallController.php
│   │   └── Front/
│   │       └── CustomGoodsController.php
│   └── Database/
│       └── install_custom_tables.sql
```

### 前端界面部署位置
```
[Godomall根目录]/
├── Asset/
│   ├── Front/
│   │   └── default/
│   │       └── custom/
│   │           ├── goods_custom.html
│   │           ├── goods_custom.css
│   │           └── goods_custom.js
│   └── Admin/
│       └── custom/
│           ├── goods_config.html
│           ├── pattern_add.html
│           ├── pattern_list.html
│           └── database_install.html
```

## 🚀 部署步骤

### 1. 上传代码文件
将本代码包中的所有文件按照上述目录结构上传到Godomall服务器

### 2. 安装数据库
1. 登录Godomall管理后台
2. 访问：`/admin/custom/database-install`
3. 点击"安装数据库"按钮执行SQL脚本

### 3. 配置商品定制
1. 访问：`/admin/custom/goods-config`
2. 为需要支持定制的商品启用定制功能
3. 上传并关联定制图案

### 4. 测试用户端功能
1. 访问商品详情页
2. 点击"定制"按钮进入定制页面
3. 测试图案选择、文字添加、图片上传等功能

## 🔧 功能特性

### 用户端功能
- ✅ 商品定制预览（显示实际商品图片）
- ✅ 图案库选择（拖拽到画布）
- ✅ 自定义图片上传
- ✅ 文字添加和编辑
- ✅ 元素拖拽、缩放、旋转
- ✅ 撤销/重做操作
- ✅ 定制图片生成和保存
- ✅ 韩文界面支持

### 管理员功能
- ✅ 图案库管理（增删改查）
- ✅ 商品定制配置
- ✅ 批量图案上传
- ✅ 数据库安装管理
- ✅ 操作日志记录

### 技术特性
- ✅ 基于Fabric.js的Canvas操作
- ✅ 响应式设计（PC/移动端）
- ✅ 安全的文件上传验证
- ✅ 完整的错误处理
- ✅ 遵循Godomall开发规范

## 📋 数据库变更

### 新增数据表
- `custom_pattern` - 定制图案表
- `goods_custom_pattern` - 商品图案关联表
- `custom_goods_customization` - 商品定制记录表
- `custom_uploaded_images` - 用户上传图片表
- `es_goods_colors` - 商品颜色表
- `es_goods_color_relation` - 商品颜色关联表
- `es_goods_sizes` - 商品尺寸表
- `es_goods_size_relation` - 商品尺寸关联表

### 扩展字段
- `es_order.customization_image` - 订单定制图片
- `es_goods.is_customizable` - 商品是否支持定制

## ⚠️ 注意事项

1. **备份重要数据**：部署前请备份现有数据库和代码
2. **权限设置**：确保上传目录有正确的读写权限
3. **图片格式**：支持JPG、PNG、GIF格式，最大5MB
4. **浏览器兼容**：建议使用现代浏览器（Chrome、Firefox、Safari、Edge）
5. **升级兼容**：本代码遵循Godomall规范，与官方补丁兼容

## 🆘 技术支持

如遇到问题，请检查：
1. 数据库是否正确安装
2. 文件权限是否正确设置
3. 服务器错误日志
4. 浏览器控制台错误信息

## 📄 版本信息

- **版本**: 1.0
- **兼容性**: Godomall 5.x
- **PHP版本**: 7.0+
- **数据库**: MySQL 5.7+
- **更新日期**: 2024年8月

---

**注意**: 本代码包严格遵循Godomall增量开发原则，不会修改任何官方核心代码，确保与官方升级兼容。
