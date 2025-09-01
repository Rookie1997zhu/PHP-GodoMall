# Godomall 商品定制功能部署说明

## 🚀 快速部署指南

### 第一步：准备部署环境
1. 确保Godomall版本为5.x
2. 确保PHP版本为7.0+
3. 确保MySQL版本为5.7+
4. 备份现有数据库和重要代码文件

### 第二步：上传代码文件
将本代码包中的所有文件按照以下结构上传到Godomall服务器：

```
[Godomall根目录]/
├── Custom/                    ← 新建目录
│   ├── Component/            ← 新建目录
│   │   ├── CustomGoods.php
│   │   ├── CustomOrder.php
│   │   ├── CustomPattern.php
│   │   ├── Storage/          ← 新建目录
│   │   │   └── CustomStorage.php
│   │   └── Database/         ← 新建目录
│   │       └── CustomTableField.php
│   ├── Controller/           ← 新建目录
│   │   ├── Admin/            ← 新建目录
│   │   │   └── Custom/       ← 新建目录
│   │   │       ├── CustomGoodsController.php
│   │   │       ├── CustomPatternController.php
│   │   │       └── DatabaseInstallController.php
│   │   └── Front/            ← 新建目录
│   │       └── CustomGoodsController.php
│   └── Database/             ← 新建目录
│       └── install_custom_tables.sql
└── Asset/                    ← 已存在目录
    ├── Front/
    │   └── default/
    │       └── custom/       ← 新建目录
    │           ├── goods_custom.html
    │           ├── goods_custom.css
    │           └── goods_custom.js
    └── Admin/
        └── custom/            ← 新建目录
            ├── goods_config.html
            ├── pattern_add.html
            ├── pattern_list.html
            └── database_install.html
```

### 第三步：设置文件权限
```bash
# 设置目录权限
chmod 755 Custom/
chmod 755 Custom/Component/
chmod 755 Custom/Controller/
chmod 755 Asset/Front/default/custom/
chmod 755 Asset/Admin/custom/

# 设置文件权限
chmod 644 Custom/Component/*.php
chmod 644 Custom/Controller/Admin/Custom/*.php
chmod 644 Custom/Controller/Front/*.php
chmod 644 Asset/Front/default/custom/*
chmod 644 Asset/Admin/custom/*
```

### 第四步：安装数据库
1. 登录Godomall管理后台
2. 访问：`/admin/custom/database-install`
3. 点击"安装数据库"按钮
4. 确认安装成功

### 第五步：配置商品定制
1. 访问：`/admin/custom/goods-config`
2. 选择需要支持定制的商品
3. 启用定制功能
4. 上传并关联定制图案

### 第六步：测试功能
1. 访问商品详情页
2. 点击"定制"按钮
3. 测试各项定制功能

## 📋 详细部署步骤

### 1. 代码文件上传

#### 使用FTP/SFTP上传
```bash
# 连接到Godomall服务器
sftp username@your-godomall-server.com

# 创建目录结构
mkdir -p Custom/Component/Storage
mkdir -p Custom/Component/Database
mkdir -p Custom/Controller/Admin/Custom
mkdir -p Custom/Controller/Front
mkdir -p Custom/Database
mkdir -p Asset/Front/default/custom
mkdir -p Asset/Admin/custom

# 上传文件
put Custom/Component/CustomGoods.php
put Custom/Component/CustomOrder.php
put Custom/Component/CustomPattern.php
put Custom/Component/Storage/CustomStorage.php
put Custom/Component/Database/CustomTableField.php
put Custom/Controller/Admin/Custom/CustomGoodsController.php
put Custom/Controller/Admin/Custom/CustomPatternController.php
put Custom/Controller/Admin/Custom/DatabaseInstallController.php
put Custom/Controller/Front/CustomGoodsController.php
put Custom/Database/install_custom_tables.sql
put Asset/Front/default/custom/goods_custom.html
put Asset/Front/default/custom/goods_custom.css
put Asset/Front/default/custom/goods_custom.js
put Asset/Admin/custom/goods_config.html
put Asset/Admin/custom/pattern_add.html
put Asset/Admin/custom/pattern_list.html
put Asset/Admin/custom/database_install.html
```

#### 使用Web管理界面上传
1. 登录Godomall管理后台
2. 进入"开发源码管理"
3. 选择"工作商城"
4. 上传各个代码文件到对应目录

### 2. 数据库安装

#### 自动安装（推荐）
1. 访问：`/admin/custom/database-install`
2. 点击"安装数据库"按钮
3. 系统自动执行SQL脚本

#### 手动安装
1. 登录phpMyAdmin
2. 选择Godomall数据库
3. 执行`install_custom_tables.sql`脚本

### 3. 目录权限设置

#### Linux/Unix系统
```bash
# 设置目录权限
find Custom/ -type d -exec chmod 755 {} \;
find Asset/Front/default/custom/ -type d -exec chmod 755 {} \;
find Asset/Admin/custom/ -type d -exec chmod 755 {} \;

# 设置文件权限
find Custom/ -type f -name "*.php" -exec chmod 644 {} \;
find Asset/Front/default/custom/ -type f -exec chmod 644 {} \;
find Asset/Admin/custom/ -type f -exec chmod 644 {} \;
```

#### Windows系统
1. 右键点击目录/文件
2. 选择"属性"
3. 设置"安全"权限
4. 确保Web服务器有读写权限

### 4. 配置验证

#### 检查文件是否上传成功
```bash
# 检查核心文件
ls -la Custom/Component/
ls -la Custom/Controller/Admin/Custom/
ls -la Custom/Controller/Front/
ls -la Asset/Front/default/custom/
ls -la Asset/Admin/custom/
```

#### 检查数据库表是否创建成功
```sql
-- 检查新增表
SHOW TABLES LIKE 'custom_%';
SHOW TABLES LIKE 'es_goods_colors';
SHOW TABLES LIKE 'es_goods_sizes';

-- 检查扩展字段
DESCRIBE es_order;
DESCRIBE es_goods;
```

## 🔧 配置说明

### 1. 商品定制配置
- 访问：`/admin/custom/goods-config`
- 选择商品并启用定制功能
- 上传定制图案并关联到商品

### 2. 图案库管理
- 访问：`/admin/custom/pattern-list`
- 添加、编辑、删除定制图案
- 设置图案分类和状态

### 3. 用户端定制页面
- 商品详情页显示"定制"按钮
- 点击进入定制页面
- 支持图案选择、文字添加、图片上传

## ⚠️ 常见问题解决

### 1. 页面显示404错误
- 检查文件是否上传到正确目录
- 检查文件权限是否正确
- 检查Godomall路由配置

### 2. 数据库连接错误
- 检查数据库配置
- 确认数据库表是否创建成功
- 检查数据库用户权限

### 3. 图片上传失败
- 检查上传目录权限
- 检查文件大小限制
- 检查文件格式支持

### 4. 定制功能不显示
- 确认商品已启用定制功能
- 检查是否有可用图案
- 检查前端JavaScript是否加载

## 📞 技术支持

如遇到部署问题，请：
1. 检查服务器错误日志
2. 检查浏览器控制台错误
3. 确认所有文件都已正确上传
4. 验证数据库安装是否成功

## 🎯 部署完成检查清单

- [ ] 所有代码文件已上传到正确目录
- [ ] 文件权限设置正确
- [ ] 数据库表创建成功
- [ ] 管理员页面可以正常访问
- [ ] 商品定制功能正常工作
- [ ] 用户端定制页面显示正常
- [ ] 图案上传和管理功能正常
- [ ] 定制图片保存和订单关联正常

---

**恭喜！** 如果以上所有项目都已勾选，说明您的商品定制功能已经成功部署到Godomall！
