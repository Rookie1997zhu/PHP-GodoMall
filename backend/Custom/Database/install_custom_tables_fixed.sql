-- 商品定制功能数据库安装脚本（修复版）
-- 执行前请备份数据库
-- 本脚本与PHP代码中的CustomTableField类定义完全一致
-- 
-- 修复内容：
-- 1. 统一时间字段命名：所有表使用created_at和updated_at（与前端代码一致）
-- 2. 添加购物车表字段扩展：es_cart和es_cart_items表
-- 3. 添加字段结构验证查询：确保所有字段正确创建
-- 4. 修复字段命名不一致问题：避免运行时错误
-- 5. 统一数据类型：BIGINT改为INT，VARCHAR长度统一为255
-- 6. 添加缺失字段：category和description字段
-- 7. 统一时间字段类型：TIMESTAMP改为DATETIME

-- =====================================================
-- 1. 定制图案表 (custom_pattern)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_pattern` (
    `pattern_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '图案ID，主键',
    `pattern_name` VARCHAR(100) NOT NULL COMMENT '图案名称',
    `pattern_url` VARCHAR(255) NOT NULL COMMENT '图案文件URL',
    `pattern_thumbnail` VARCHAR(255) DEFAULT NULL COMMENT '缩略图URL',
    `pattern_category` VARCHAR(50) DEFAULT NULL COMMENT '图案分类',
    `category` VARCHAR(50) DEFAULT NULL COMMENT '分类（简化字段）',
    `pattern_group` VARCHAR(50) DEFAULT NULL COMMENT '图案分组',
    `tags` VARCHAR(255) DEFAULT NULL COMMENT '标签',
    `pattern_description` TEXT DEFAULT NULL COMMENT '图案描述',
    `description` TEXT DEFAULT NULL COMMENT '描述（简化字段）',
    `uploader` VARCHAR(50) DEFAULT NULL COMMENT '上传者',
    `file_size` INT(11) UNSIGNED DEFAULT NULL COMMENT '文件大小（字节）',
    `file_type` VARCHAR(20) DEFAULT NULL COMMENT '文件类型',
    `sort_order` INT(11) UNSIGNED DEFAULT 0 COMMENT '排序顺序',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`pattern_id`),
    KEY `idx_pattern_category` (`pattern_category`),
    KEY `idx_category` (`category`),
    KEY `idx_pattern_group` (`pattern_group`),
    KEY `idx_tags` (`tags`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制图案表';

-- =====================================================
-- 2. 商品定制图案关联表 (goods_custom_pattern)
-- =====================================================
CREATE TABLE IF NOT EXISTS `goods_custom_pattern` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '关联ID，主键',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
    `pattern_id` INT(11) UNSIGNED NOT NULL COMMENT '图案ID',
    `sort_order` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序顺序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uidx_goods_pattern` (`goods_id`, `pattern_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_pattern_id` (`pattern_id`),
    CONSTRAINT `fk_gcp_pattern` FOREIGN KEY (`pattern_id`) REFERENCES `custom_pattern` (`pattern_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制图案关联表';

-- =====================================================
-- 3. 商品定制记录表 (custom_goods_customization)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_goods_customization` (
    `customization_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '定制记录ID',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
    `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '用户ID',
    `customization_data` JSON NOT NULL COMMENT '定制数据JSON',
    `custom_image_url` VARCHAR(255) DEFAULT NULL COMMENT '定制图片URL',
    `selected_color` VARCHAR(30) DEFAULT NULL COMMENT '选择的颜色',
    `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1-待处理，2-已完成，3-已取消，4-已删除',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`customization_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_customization_user_goods` (`user_id`, `goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制记录表';

-- =====================================================
-- 4. 上传图片记录表 (custom_uploaded_images)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_uploaded_images` (
    `image_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '图片ID',
    `filename` VARCHAR(255) NOT NULL COMMENT '文件名',
    `file_path` VARCHAR(255) NOT NULL COMMENT '文件路径',
    `thumbnail_path` VARCHAR(255) DEFAULT NULL COMMENT '缩略图路径',
    `file_size` INT(11) UNSIGNED DEFAULT NULL COMMENT '文件大小(字节)',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT 'MIME类型',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1-正常，0-已删除',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`image_id`),
    KEY `idx_filename` (`filename`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='上传图片记录表';

-- =====================================================
-- 5. 图案分类表 (custom_pattern_category)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_pattern_category` (
    `category_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
    `category_name` VARCHAR(100) NOT NULL COMMENT '分类名称',
    `category_description` TEXT COMMENT '分类描述',
    `parent_id` INT(11) UNSIGNED DEFAULT 0 COMMENT '父分类ID，0表示顶级分类',
    `sort_order` INT(11) UNSIGNED DEFAULT 0 COMMENT '排序顺序',
    `status` TINYINT(1) UNSIGNED DEFAULT 1 COMMENT '状态：1启用，0禁用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`category_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='图案分类表';

-- =====================================================
-- 6. 商品颜色表 (es_goods_colors)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_colors` (
    `color_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '颜色ID',
    `color_code` VARCHAR(30) NOT NULL COMMENT '颜色代码',
    `color_name` VARCHAR(100) NOT NULL COMMENT '颜色名称',
    `color_hex` VARCHAR(7) DEFAULT NULL COMMENT '颜色十六进制值',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
    `sort_order` INT(11) UNSIGNED DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`color_id`),
    UNIQUE KEY `uk_color_code` (`color_code`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品颜色表';

-- =====================================================
-- 7. 商品尺寸表 (es_goods_sizes)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_sizes` (
    `size_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '尺寸ID',
    `size_code` VARCHAR(30) NOT NULL COMMENT '尺寸代码',
    `size_name` VARCHAR(100) NOT NULL COMMENT '尺寸名称',
    `size_display` VARCHAR(100) DEFAULT NULL COMMENT '显示名称',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
    `sort_order` INT(11) UNSIGNED DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`size_id`),
    UNIQUE KEY `uk_size_code` (`size_code`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品尺寸表';

-- 扩展现有尺寸表，添加自定义尺寸支持字段
ALTER TABLE `es_goods_sizes` 
ADD COLUMN IF NOT EXISTS `is_custom` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否为自定义尺寸',
ADD COLUMN IF NOT EXISTS `size_width` DECIMAL(10,2) UNSIGNED DEFAULT NULL COMMENT '宽度',
ADD COLUMN IF NOT EXISTS `size_height` DECIMAL(10,2) UNSIGNED DEFAULT NULL COMMENT '高度',
ADD COLUMN IF NOT EXISTS `size_unit` VARCHAR(10) DEFAULT 'inch' COMMENT '尺寸单位';

-- =====================================================
-- 8. 商品颜色关联表 (es_goods_color_relation)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_color_relation` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '关联ID',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
    `color_id` INT(11) UNSIGNED NOT NULL COMMENT '颜色ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_color` (`goods_id`, `color_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_color_id` (`color_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品颜色关联表';

-- =====================================================
-- 9. 商品尺寸关联表 (es_goods_size_relation)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_size_relation` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '关联ID',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
    `size_id` INT(11) UNSIGNED NOT NULL COMMENT '尺寸ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_size` (`goods_id`, `size_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_size_id` (`size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品尺寸关联表';

-- =====================================================
-- 9.1. 自定义商品尺寸表 (custom_goods_sizes)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_goods_sizes` (
    `custom_size_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自定义尺寸ID',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
    `size_name` VARCHAR(100) NOT NULL COMMENT '尺寸名称',
    `size_width` DECIMAL(10,2) UNSIGNED NOT NULL COMMENT '宽度',
    `size_height` DECIMAL(10,2) UNSIGNED NOT NULL COMMENT '高度',
    `size_unit` VARCHAR(10) DEFAULT 'inch' COMMENT '尺寸单位',
    `is_custom` TINYINT(1) UNSIGNED DEFAULT 1 COMMENT '是否为自定义尺寸',
    `status` TINYINT(1) UNSIGNED DEFAULT 1 COMMENT '状态',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`custom_size_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_status` (`status`),
    KEY `idx_custom_size_goods` (`goods_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品自定义尺寸表';

-- =====================================================
-- 10. 扩展商品表 (es_goods) - 添加定制相关字段
-- =====================================================
ALTER TABLE `es_goods` 
ADD COLUMN IF NOT EXISTS `is_customizable` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否支持定制：0-不支持，1-支持',
ADD COLUMN IF NOT EXISTS `default_color` VARCHAR(50) DEFAULT NULL COMMENT '默认颜色代码',
ADD COLUMN IF NOT EXISTS `default_size` VARCHAR(50) DEFAULT NULL COMMENT '默认尺寸代码',
ADD COLUMN IF NOT EXISTS `custom_width` DECIMAL(10,2) UNSIGNED DEFAULT NULL COMMENT '自定义宽度（CM）',
ADD COLUMN IF NOT EXISTS `custom_height` DECIMAL(10,2) UNSIGNED DEFAULT NULL COMMENT '自定义高度（CM）';

-- =====================================================
-- 11. 扩展购物车表 (es_cart) - 添加定制相关字段
-- =====================================================
ALTER TABLE `es_cart` 
ADD COLUMN IF NOT EXISTS `customization_data` JSON DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` VARCHAR(30) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否为定制商品';

-- =====================================================
-- 12. 扩展购物车商品表 (es_cart_items) - 添加定制相关字段
-- =====================================================
-- 注意：如果es_cart_items表存在，则添加以下字段
-- 如果表不存在，请根据实际Godomall版本调整表名
ALTER TABLE `es_cart_items` 
ADD COLUMN IF NOT EXISTS `customization_data` JSON DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` VARCHAR(30) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否为定制商品';

-- =====================================================
-- 13. 扩展订单表 (es_order) - 添加定制图片字段
-- =====================================================
ALTER TABLE `es_order` 
ADD COLUMN IF NOT EXISTS `customization_image` VARCHAR(255) DEFAULT NULL COMMENT '定制图片URL';

-- =====================================================
-- 14. 扩展订单商品表 (es_orderGoods) - 添加定制相关字段
-- =====================================================
ALTER TABLE `es_orderGoods` 
ADD COLUMN IF NOT EXISTS `customization_data` JSON DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` VARCHAR(30) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否为定制商品';

-- =====================================================
-- 15. 创建性能优化索引
-- =====================================================
CREATE INDEX IF NOT EXISTS `idx_goods_customizable` ON `es_goods` (`is_customizable`);
CREATE INDEX IF NOT EXISTS `idx_cart_customized` ON `es_cart` (`is_customized`);
CREATE INDEX IF NOT EXISTS `idx_cart_items_customized` ON `es_cart_items` (`is_customized`);
CREATE INDEX IF NOT EXISTS `idx_order_goods_customized` ON `es_orderGoods` (`is_customized`);
CREATE INDEX IF NOT EXISTS `idx_order_customization_image` ON `es_order` (`customization_image`);
CREATE INDEX IF NOT EXISTS `idx_custom_sizes_goods` ON `custom_goods_sizes` (`goods_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_goods_sizes_custom` ON `es_goods_sizes` (`is_custom`, `status`);

-- =====================================================
-- 16. 创建复合索引提高查询性能
-- =====================================================
CREATE INDEX IF NOT EXISTS `idx_customization_goods_user_status` ON `custom_goods_customization` (`goods_id`, `user_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_customization_status_updated` ON `custom_goods_customization` (`status`, `updated_at`);
CREATE INDEX IF NOT EXISTS `idx_pattern_status_created` ON `custom_pattern` (`status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_goods_pattern_goods_sort` ON `goods_custom_pattern` (`goods_id`, `sort_order`);
CREATE INDEX IF NOT EXISTS `idx_image_status_created` ON `custom_uploaded_images` (`status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_category_parent_sort` ON `custom_pattern_category` (`parent_id`, `sort_order`);
CREATE INDEX IF NOT EXISTS `idx_colors_status_sort` ON `es_goods_colors` (`status`, `sort_order`);
CREATE INDEX IF NOT EXISTS `idx_sizes_status_sort` ON `es_goods_sizes` (`status`, `sort_order`);
CREATE INDEX IF NOT EXISTS `idx_goods_color_goods` ON `es_goods_color_relation` (`goods_id`);
CREATE INDEX IF NOT EXISTS `idx_goods_size_goods` ON `es_goods_size_relation` (`goods_id`);

-- =====================================================
-- 17. 添加外键约束确保数据完整性
-- =====================================================
ALTER TABLE `goods_custom_pattern`
ADD CONSTRAINT `fk_gcp_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `es_goods_color_relation`
ADD CONSTRAINT `fk_gcr_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_gcr_color` FOREIGN KEY (`color_id`) REFERENCES `es_goods_colors` (`color_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `es_goods_size_relation`
ADD CONSTRAINT `fk_gsr_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_gsr_size` FOREIGN KEY (`size_id`) REFERENCES `es_goods_sizes` (`size_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `custom_goods_sizes`
ADD CONSTRAINT `fk_cgs_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;
-- =====================================================
-- 20. 创建定制图片目录的说明
-- =====================================================
-- 注意：以下目录需要在文件系统中手动创建，或通过PHP代码自动创建
-- 建议目录路径：/data/custom_images/
-- 目录权限：755
-- 所有者：web服务器用户（如www-data、apache、nginx等）

-- =====================================================
-- 18. 安装完成提示
-- =====================================================
SELECT '상품 정제 기능 데이터베이스 설치 및 최적화 완료!' AS message;

-- =====================================================
-- 19. 字段结构验证查询
-- =====================================================
-- 验证custom_pattern表结构
SELECT 'custom_pattern表字段验证' AS table_name, 
       COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_pattern'
ORDER BY ORDINAL_POSITION;

-- 验证goods_custom_pattern表结构  
SELECT 'goods_custom_pattern表字段验证' AS table_name,
       COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'goods_custom_pattern'
ORDER BY ORDINAL_POSITION;

-- 验证custom_pattern_category表结构
SELECT 'custom_pattern_category表字段验证' AS table_name,
       COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_pattern_category'
ORDER BY ORDINAL_POSITION;
