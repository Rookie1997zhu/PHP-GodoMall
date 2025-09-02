-- 商品定制功能数据库安装脚本（修复版）
-- 执行前请备份数据库
-- 本脚本与PHP代码中的CustomTableField类定义完全一致
-- 
-- 修复内容：
-- 1. 统一时间字段命名：所有表使用created_at和updated_at（与前端代码一致）
-- 2. 添加购物车表字段扩展：es_cart和es_cart_items表
-- 3. 添加字段结构验证查询：确保所有字段正确创建
-- 4. 修复字段命名不一致问题：避免运行时错误

-- =====================================================
-- 1. 定制图案表 (custom_pattern)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_pattern` (
    `pattern_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '图案ID，主键',
    `pattern_name` varchar(100) NOT NULL COMMENT '图案名称',
    `pattern_url` varchar(255) NOT NULL COMMENT '图案文件URL',
    `pattern_thumbnail` varchar(255) DEFAULT NULL COMMENT '缩略图URL',
    `pattern_category` varchar(50) DEFAULT NULL COMMENT '图案分类',
    `category` varchar(50) DEFAULT NULL COMMENT '分类（简化字段）',
    `pattern_group` varchar(50) DEFAULT NULL COMMENT '图案分组',
    `tags` varchar(255) DEFAULT NULL COMMENT '标签',
    `pattern_description` text DEFAULT NULL COMMENT '图案描述',
    `description` text DEFAULT NULL COMMENT '描述（简化字段）',
    `uploader` varchar(50) DEFAULT NULL COMMENT '上传者',
    `file_size` int(11) DEFAULT NULL COMMENT '文件大小（字节）',
    `file_type` varchar(20) DEFAULT NULL COMMENT '文件类型',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
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
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID，主键',
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `pattern_id` int(11) NOT NULL COMMENT '图案ID',
    `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序顺序',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
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
    `customization_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '定制记录ID',
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
    `customization_data` longtext NOT NULL COMMENT '定制数据JSON',
    `custom_image` longtext DEFAULT NULL COMMENT '定制图片数据',
    `selected_color` varchar(50) DEFAULT NULL COMMENT '选择的颜色',
    `selected_size` varchar(50) DEFAULT NULL COMMENT '选择的尺寸',
    `status` enum('pending','completed','cancelled','deleted') DEFAULT 'pending' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
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
    `image_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '图片ID',
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

-- =====================================================
-- 5. 图案分类表 (custom_pattern_category)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_pattern_category` (
    `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
    `category_name` varchar(100) NOT NULL COMMENT '分类名称',
    `category_description` text COMMENT '分类描述',
    `parent_id` int(11) DEFAULT 0 COMMENT '父分类ID，0表示顶级分类',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
    `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用，0禁用',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`category_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='图案分类表';

-- =====================================================
-- 6. 商品颜色表 (es_goods_colors)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_colors` (
    `color_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '颜色ID',
    `color_code` varchar(50) NOT NULL COMMENT '颜色代码',
    `color_name` varchar(100) NOT NULL COMMENT '颜色名称',
    `color_hex` varchar(7) DEFAULT NULL COMMENT '颜色十六进制值',
    `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`color_id`),
    UNIQUE KEY `uk_color_code` (`color_code`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品颜色表';

-- =====================================================
-- 7. 商品尺寸表 (es_goods_sizes)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_sizes` (
    `size_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '尺寸ID',
    `size_code` varchar(50) NOT NULL COMMENT '尺寸代码',
    `size_name` varchar(100) NOT NULL COMMENT '尺寸名称',
    `size_display` varchar(100) DEFAULT NULL COMMENT '显示名称',
    `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`size_id`),
    UNIQUE KEY `uk_size_code` (`size_code`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品尺寸表';

-- 扩展现有尺寸表，添加自定义尺寸支持字段
ALTER TABLE `es_goods_sizes` 
ADD COLUMN IF NOT EXISTS `is_custom` tinyint(1) DEFAULT 0 COMMENT '是否为自定义尺寸',
ADD COLUMN IF NOT EXISTS `size_width` decimal(10,2) DEFAULT NULL COMMENT '宽度',
ADD COLUMN IF NOT EXISTS `size_height` decimal(10,2) DEFAULT NULL COMMENT '高度',
ADD COLUMN IF NOT EXISTS `size_unit` varchar(20) DEFAULT 'inch' COMMENT '尺寸单位';

-- =====================================================
-- 8. 商品颜色关联表 (es_goods_color_relation)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_color_relation` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID',
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `color_id` int(11) NOT NULL COMMENT '颜色ID',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_color` (`goods_id`, `color_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_color_id` (`color_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品颜色关联表';

-- =====================================================
-- 9. 商品尺寸关联表 (es_goods_size_relation)
-- =====================================================
CREATE TABLE IF NOT EXISTS `es_goods_size_relation` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID',
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `size_id` int(11) NOT NULL COMMENT '尺寸ID',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_size` (`goods_id`, `size_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_size_id` (`size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品尺寸关联表';

-- =====================================================
-- 9.1. 自定义商品尺寸表 (custom_goods_sizes)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_goods_sizes` (
    `custom_size_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自定义尺寸ID',
    `goods_id` int(11) NOT NULL COMMENT '商品ID',
    `size_name` varchar(100) NOT NULL COMMENT '尺寸名称',
    `size_width` decimal(10,2) NOT NULL COMMENT '宽度',
    `size_height` decimal(10,2) NOT NULL COMMENT '高度',
    `size_unit` varchar(20) DEFAULT 'inch' COMMENT '尺寸单位',
    `is_custom` tinyint(1) DEFAULT 1 COMMENT '是否为自定义尺寸',
    `status` tinyint(1) DEFAULT 1 COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`custom_size_id`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_status` (`status`),
    KEY `idx_custom_size_goods` (`goods_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品自定义尺寸表';

-- =====================================================
-- 10. 扩展商品表 (es_goods) - 添加定制相关字段
-- =====================================================
ALTER TABLE `es_goods` 
ADD COLUMN IF NOT EXISTS `is_customizable` tinyint(1) DEFAULT 0 COMMENT '是否支持定制：0-不支持，1-支持',
ADD COLUMN IF NOT EXISTS `default_color` varchar(50) DEFAULT NULL COMMENT '默认颜色代码',
ADD COLUMN IF NOT EXISTS `default_size` varchar(50) DEFAULT NULL COMMENT '默认尺寸代码',
ADD COLUMN IF NOT EXISTS `custom_width` DECIMAL(10,2) DEFAULT NULL COMMENT '自定义宽度（CM）',
ADD COLUMN IF NOT EXISTS `custom_height` DECIMAL(10,2) DEFAULT NULL COMMENT '自定义高度（CM）';

-- =====================================================
-- 11. 扩展购物车表 (es_cart) - 添加定制相关字段
-- =====================================================
ALTER TABLE `es_cart` 
ADD COLUMN IF NOT EXISTS `customization_data` TEXT DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` VARCHAR(50) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` TINYINT(1) DEFAULT 0 COMMENT '是否为定制商品';

-- =====================================================
-- 11.1. 扩展购物车商品表 (es_cart_items) - 添加定制相关字段
-- =====================================================
-- 注意：如果es_cart_items表存在，则添加以下字段
-- 如果表不存在，请根据实际Godomall版本调整表名
ALTER TABLE `es_cart_items` 
ADD COLUMN IF NOT EXISTS `customization_data` TEXT DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` VARCHAR(50) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` TINYINT(1) DEFAULT 0 COMMENT '是否为定制商品';

-- =====================================================
-- 12. 扩展购物车商品表 (es_cart_items) - 添加定制相关字段
-- =====================================================
ALTER TABLE `es_cart_items` 
ADD COLUMN IF NOT EXISTS `customization_data` TEXT DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` VARCHAR(50) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` TINYINT(1) DEFAULT 0 COMMENT '是否为定制商品';

-- =====================================================
-- 13. 扩展订单表 (es_order) - 添加定制图片字段
-- =====================================================
ALTER TABLE `es_order` 
ADD COLUMN IF NOT EXISTS `customization_image` VARCHAR(500) DEFAULT NULL COMMENT '定制图片URL';

-- =====================================================
-- 14. 扩展订单商品表 (es_orderGoods) - 添加定制相关字段
-- =====================================================
ALTER TABLE `es_orderGoods` 
ADD COLUMN IF NOT EXISTS `customization_data` TEXT DEFAULT NULL COMMENT '定制数据JSON',
ADD COLUMN IF NOT EXISTS `selected_color` VARCHAR(50) DEFAULT NULL COMMENT '选择的颜色',
ADD COLUMN IF NOT EXISTS `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
ADD COLUMN IF NOT EXISTS `is_customized` TINYINT(1) DEFAULT 0 COMMENT '是否为定制商品';

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
-- 16. 插入默认颜色数据
-- =====================================================
INSERT IGNORE INTO `es_goods_colors` (`color_code`, `color_name`, `color_hex`, `sort_order`) VALUES
('navy', 'Navy', '#1e3a8a', 1),
('red', 'Red', '#dc2626', 2),
('beige', 'Beige', '#f5f5dc', 3),
('black', 'Black', '#000000', 4),
('pink', 'Pink', '#ec4899', 5),
('white', 'White', '#ffffff', 6),
('green', 'Green', '#16a34a', 7),
('gray', 'Gray', '#6b7280', 8),
('blue', 'Blue', '#2563eb', 9),
('yellow', 'Yellow', '#eab308', 10);

-- =====================================================
-- 17. 插入默认尺寸数据
-- =====================================================
INSERT IGNORE INTO `es_goods_sizes` (`size_code`, `size_name`, `size_display`, `sort_order`) VALUES
('4.7x2.8', '4.7x2.8', '4.7 x 2.8 inch', 1),
('5.0x3.0', '5.0x3.0', '5.0 x 3.0 inch', 2),
('5.5x3.5', '5.5x3.5', '5.5 x 3.5 inch', 3),
('6.0x4.0', '6.0x4.0', '6.0 x 4.0 inch', 4);

-- =====================================================
-- 18. 插入默认图案分类数据
-- =====================================================
INSERT IGNORE INTO `custom_pattern_category` (`category_id`, `category_name`, `category_description`, `parent_id`, `sort_order`, `status`) VALUES
(1, '기본 분류', '시스템 기본 분류', 0, 1, 1),
(2, '축제 테마', '축제 관련 패턴', 0, 2, 1),
(3, '비즈니스 스타일', '비즈니스 오피스 패턴', 0, 3, 1),
(4, '만화 애니메이션', '만화 애니메이션 패턴', 0, 4, 1),
(5, '자연 풍경', '자연 풍경 패턴', 0, 5, 1);

-- =====================================================
-- 19. 插入示例图案数据
-- =====================================================
INSERT IGNORE INTO `custom_pattern` (`pattern_name`, `pattern_url`, `pattern_category`, `pattern_group`, `uploader`, `file_size`, `file_type`, `status`) VALUES
('별', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><polygon points="25,5 30,20 45,20 35,30 40,45 25,35 10,45 15,30 5,20 20,20" fill="%23ffd700"/></svg>', '기본 분류', '기하학적', 'admin', 1024, 'svg', 1),
('하트', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><path d="M25,15 C25,15 20,10 15,15 C10,20 15,30 25,35 C35,30 40,20 35,15 C30,10 25,15 25,15 Z" fill="%23ff6b6b"/></svg>', '기본 분류', '사랑', 'admin', 1024, 'svg', 1),
('원형', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="%234ecdc4"/></svg>', '기본 분류', '기하학적', 'admin', 1024, 'svg', 1),
('사각형', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><rect x="10" y="10" width="30" height="30" fill="%23a8e6cf"/></svg>', '기본 분류', '기하학적', 'admin', 1024, 'svg', 1);

-- =====================================================
-- 20. 创建定制图片目录的说明
-- =====================================================
-- 注意：以下目录需要在文件系统中手动创建，或通过PHP代码自动创建
-- 建议目录路径：/data/custom_images/
-- 目录权限：755
-- 所有者：web服务器用户（如www-data、apache、nginx等）

-- =====================================================
-- 21. 安装完成提示
-- =====================================================
SELECT '상품 정제 기능 데이터베이스 설치 완료!' AS message;

-- =====================================================
-- 22. 验证表结构
-- =====================================================
SELECT 
    TABLE_NAME as '테이블명',
    TABLE_ROWS as '행 수',
    DATA_LENGTH as '데이터 크기(KB)',
    INDEX_LENGTH as '인덱스 크기(KB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN (
    'custom_pattern',
    'goods_custom_pattern', 
    'custom_goods_customization',
    'custom_uploaded_images',
    'custom_pattern_category',
    'custom_goods_sizes',
    'es_goods_colors',
    'es_goods_sizes',
    'es_goods_color_relation',
    'es_goods_size_relation'
)
ORDER BY TABLE_NAME;

-- =====================================================
-- 23. 验证关键字段结构
-- =====================================================
-- 验证goods_custom_pattern表的时间字段
SELECT 
    TABLE_NAME as '테이블명',
    COLUMN_NAME as '컬럼명',
    DATA_TYPE as '데이터타입',
    IS_NULLABLE as 'NULL허용',
    COLUMN_DEFAULT as '기본값',
    COLUMN_COMMENT as '컬럼설명'
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'goods_custom_pattern'
AND COLUMN_NAME IN ('created_at')
ORDER BY ORDINAL_POSITION;

-- 验证custom_goods_sizes表的关键字段
SELECT 
    TABLE_NAME as '테이블명',
    COLUMN_NAME as '컬럼명',
    DATA_TYPE as '데이터타입',
    IS_NULLABLE as 'NULL허용',
    COLUMN_DEFAULT as '기본값',
    COLUMN_COMMENT as '컬럼설명'
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'custom_goods_sizes'
AND COLUMN_NAME IN ('size_width', 'size_height', 'size_unit', 'created_at')
ORDER BY ORDINAL_POSITION;

-- 验证es_order表的定制图片字段
SELECT 
    TABLE_NAME as '테이블명',
    COLUMN_NAME as '컬럼명',
    DATA_TYPE as '데이터타입',
    IS_NULLABLE as 'NULL허용',
    COLUMN_DEFAULT as '기본값',
    COLUMN_COMMENT as '컬럼설명'
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'es_order'
AND COLUMN_NAME = 'customization_image';
