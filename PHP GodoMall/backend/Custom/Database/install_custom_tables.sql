-- 商品定制功能数据库安装脚本
-- 执行前请备份数据库

-- 1. 定制图案表
CREATE TABLE IF NOT EXISTS `custom_pattern` (
    `pattern_id` int(11) NOT NULL AUTO_INCREMENT,
    `pattern_name` varchar(255) NOT NULL COMMENT '图案名称',
    `pattern_url` varchar(500) NOT NULL COMMENT '图案文件URL',
    `pattern_thumbnail` varchar(500) DEFAULT NULL COMMENT '缩略图URL',
    `category` varchar(100) DEFAULT 'default' COMMENT '图案分类',
    `tags` varchar(500) DEFAULT NULL COMMENT '标签，逗号分隔',
    `description` text DEFAULT NULL COMMENT '图案描述',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序',
    `status` enum('active','inactive','deleted') DEFAULT 'active' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`pattern_id`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='定制图案表';

-- 2. 商品定制图案关联表
CREATE TABLE IF NOT EXISTS `goods_custom_pattern` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `pattern_id` int(11) NOT NULL COMMENT '图案ID',
    `status` enum('active','inactive','deleted') DEFAULT 'active' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_pattern` (`goods_id`, `pattern_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_pattern_id` (`pattern_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`pattern_id`) REFERENCES `custom_pattern` (`pattern_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制图案关联表';

-- 3. 商品定制记录表
CREATE TABLE IF NOT EXISTS `custom_goods_customization` (
    `customization_id` int(11) NOT NULL AUTO_INCREMENT,
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
    `customization_data` longtext NOT NULL COMMENT '定制数据JSON',
    `custom_image` longtext DEFAULT NULL COMMENT '定制图片数据',
    `status` enum('pending','completed','cancelled','deleted') DEFAULT 'pending' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`customization_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制记录表';

-- 4. 上传图片记录表
CREATE TABLE IF NOT EXISTS `custom_uploaded_images` (
    `image_id` int(11) NOT NULL AUTO_INCREMENT,
    `filename` varchar(255) NOT NULL COMMENT '文件名',
    `file_path` varchar(500) NOT NULL COMMENT '文件路径',
    `thumbnail_path` varchar(500) DEFAULT NULL COMMENT '缩略图路径',
    `file_size` int(11) DEFAULT NULL COMMENT '文件大小(字节)',
    `mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME类型',
    `status` enum('active','deleted') DEFAULT 'active' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`image_id`),
    KEY `idx_filename` (`filename`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='上传图片记录表';

-- 5. 商品颜色表
CREATE TABLE IF NOT EXISTS `es_goods_colors` (
    `color_id` int(11) NOT NULL AUTO_INCREMENT,
    `color_code` varchar(50) NOT NULL COMMENT '颜色代码',
    `color_name` varchar(100) NOT NULL COMMENT '颜色名称',
    `color_hex` varchar(7) NOT NULL COMMENT '颜色十六进制值',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序',
    `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`color_id`),
    UNIQUE KEY `uk_color_code` (`color_code`),
    KEY `idx_status` (`status`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品颜色表';

-- 6. 商品颜色关联表
CREATE TABLE IF NOT EXISTS `es_goods_color_relation` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `color_id` int(11) NOT NULL COMMENT '颜色ID',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_color` (`goods_id`, `color_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_color_id` (`color_id`),
    FOREIGN KEY (`color_id`) REFERENCES `es_goods_colors` (`color_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品颜色关联表';

-- 7. 商品尺寸表
CREATE TABLE IF NOT EXISTS `es_goods_sizes` (
    `size_id` int(11) NOT NULL AUTO_INCREMENT,
    `size_code` varchar(50) NOT NULL COMMENT '尺寸代码',
    `size_name` varchar(100) NOT NULL COMMENT '尺寸名称',
    `size_display` varchar(100) NOT NULL COMMENT '尺寸显示名称',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序',
    `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`size_id`),
    UNIQUE KEY `uk_size_code` (`size_code`),
    KEY `idx_status` (`status`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品尺寸表';

-- 8. 商品尺寸关联表
CREATE TABLE IF NOT EXISTS `es_goods_size_relation` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `size_id` int(11) NOT NULL COMMENT '尺寸ID',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_size` (`goods_id`, `size_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_size_id` (`size_id`),
    FOREIGN KEY (`size_id`) REFERENCES `es_goods_sizes` (`size_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品尺寸关联表';

-- 9. 扩展商品表（添加定制相关字段）
ALTER TABLE `es_goods` 
ADD COLUMN IF NOT EXISTS `is_customizable` tinyint(1) DEFAULT 0 COMMENT '是否支持定制',
ADD COLUMN IF NOT EXISTS `default_color` varchar(50) DEFAULT NULL COMMENT '默认颜色代码',
ADD COLUMN IF NOT EXISTS `default_size` varchar(50) DEFAULT NULL COMMENT '默认尺寸代码',
ADD COLUMN IF NOT EXISTS `rating` decimal(3,2) DEFAULT 0.00 COMMENT '评分',
ADD COLUMN IF NOT EXISTS `review_count` int(11) DEFAULT 0 COMMENT '评论数量',
ADD COLUMN IF NOT EXISTS `goods_currency` varchar(10) DEFAULT 'USD' COMMENT '商品货币';

-- 10. 扩展购物车表（添加定制相关字段）
ALTER TABLE `es_cart_items` 
ADD COLUMN IF NOT EXISTS `customization_data` longtext DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` varchar(50) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` varchar(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` tinyint(1) DEFAULT 0 COMMENT '是否定制商品';

-- 插入默认颜色数据
INSERT IGNORE INTO `es_goods_colors` (`color_code`, `color_name`, `color_hex`, `sort_order`) VALUES
('navy', 'Navy', '#1e3a8a', 1),
('red', 'Red', '#dc2626', 2),
('beige', 'Beige', '#f5f5dc', 3),
('black', 'Black', '#000000', 4),
('pink', 'Pink', '#ec4899', 5),
('white', 'White', '#ffffff', 6),
('green', 'Green', '#16a34a', 7);

-- 插入默认尺寸数据
INSERT IGNORE INTO `es_goods_sizes` (`size_code`, `size_name`, `size_display`, `sort_order`) VALUES
('4.7x2.8', '4.7x2.8', '4.7 x 2.8 inch', 1),
('5.0x3.0', '5.0x3.0', '5.0 x 3.0 inch', 2),
('5.5x3.5', '5.5x3.5', '5.5 x 3.5 inch', 3);

-- 插入示例图案数据
INSERT IGNORE INTO `custom_pattern` (`pattern_name`, `pattern_url`, `pattern_thumbnail`, `category`, `tags`, `description`, `sort_order`) VALUES
('星星', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><polygon points="25,5 30,20 45,20 35,30 40,45 25,35 10,45 15,30 5,20 20,20" fill="%23ffd700"/></svg>', NULL, '几何', 'star,几何,金色', '金色星星图案', 1),
('心形', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><path d="M25,15 C25,15 20,10 15,15 C10,20 15,30 25,35 C35,30 40,20 35,15 C30,10 25,15 25,15 Z" fill="%23ff6b6b"/></svg>', NULL, '爱心', 'heart,爱心,红色', '红色爱心图案', 2),
('圆形', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="%234ecdc4"/></svg>', NULL, '几何', 'circle,几何,青色', '青色圆形图案', 3),
('方形', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><rect x="10" y="10" width="30" height="30" fill="%23a8e6cf"/></svg>', NULL, '几何', 'square,几何,绿色', '绿色方形图案', 4);

-- 创建索引优化查询性能
CREATE INDEX IF NOT EXISTS `idx_goods_customizable` ON `es_goods` (`is_customizable`);
CREATE INDEX IF NOT EXISTS `idx_cart_customized` ON `es_cart_items` (`is_customized`);
CREATE INDEX IF NOT EXISTS `idx_customization_user_goods` ON `custom_goods_customization` (`user_id`, `goods_id`);

-- 添加外键约束（如果表存在）
-- 注意：这些外键约束可能需要根据实际的表结构调整
-- ALTER TABLE `es_goods_color_relation` ADD CONSTRAINT `fk_goods_color_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goods_id`) ON DELETE CASCADE;
-- ALTER TABLE `es_goods_size_relation` ADD CONSTRAINT `fk_goods_size_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goods_id`) ON DELETE CASCADE;
-- ALTER TABLE `custom_goods_customization` ADD CONSTRAINT `fk_customization_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goods_id`) ON DELETE CASCADE; 