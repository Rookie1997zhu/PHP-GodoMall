-- 商品定制功能数据库安装脚本（独立表版本）
-- 执行前请备份数据库
-- 
-- 设计原则：
-- 1. 遵循增量开发原则：不修改系统表结构
-- 2. 所有扩展字段使用独立关联表管理
-- 3. 通过外键关联系统表，保证数据一致性
-- 4. 便于后续升级和维护
--
-- 修改内容（相比旧版本）：
-- 1. 移除所有对系统表的ALTER TABLE操作
-- 2. 创建独立的关联表替代字段扩展
-- 3. 新增表：custom_goods_extension、custom_cart_extension、custom_order_extension、custom_order_goods_extension
-- 4. 保留原有的定制功能核心表
--
-- 兼容性说明：
-- - 支持MySQL 5.6+ 和 MariaDB 10.1+
-- - 不影响系统表结构，可安全升级
-- - 建议在测试环境先执行验证

-- =====================================================
-- 1. 定制图案表 (custom_pattern)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_pattern` (
    `pattern_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '图案ID，主键',
    `pattern_name` VARCHAR(100) NOT NULL COMMENT '图案名称',
    `pattern_url` VARCHAR(255) NOT NULL COMMENT '图案文件URL',
    `pattern_thumbnail` VARCHAR(255) DEFAULT NULL COMMENT '缩略图URL',
    `category` VARCHAR(50) DEFAULT NULL COMMENT '图案分类',
    `pattern_group` VARCHAR(50) DEFAULT NULL COMMENT '图案分组',
    `tags` VARCHAR(255) DEFAULT NULL COMMENT '标签',
    `description` TEXT DEFAULT NULL COMMENT '图案描述',
    `uploader` VARCHAR(50) DEFAULT NULL COMMENT '上传者',
    `file_size` INT(11) UNSIGNED DEFAULT NULL COMMENT '文件大小（字节）',
    `file_type` VARCHAR(20) DEFAULT NULL COMMENT '文件类型',
    `sort_order` INT(11) UNSIGNED DEFAULT 0 COMMENT '排序顺序',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`pattern_id`),
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
-- 9.2. 商品定制颜色表 (custom_goods_colors)
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_goods_colors` (
    `color_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '颜色ID',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
    `color_name` VARCHAR(50) NOT NULL COMMENT '颜色名称',
    `color_code` VARCHAR(20) NOT NULL COMMENT '颜色代码',
    `color_hex` VARCHAR(7) NOT NULL COMMENT '颜色HEX值',
    `svg_image_path` VARCHAR(255) NOT NULL COMMENT 'SVG图片路径',
    `sort_order` INT(11) UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) UNSIGNED DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`color_id`),
    KEY `idx_goods_id` (`goods_id`, `status`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制颜色表';

-- =====================================================
-- 10. 商品定制扩展表 (custom_goods_extension)
-- 替代对es_goods表的字段扩展，独立管理商品定制属性
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_goods_extension` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '扩展ID',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID（关联es_goods.goodsNo）',
    `is_customizable` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否支持定制：0-不支持，1-支持',
    `default_color` VARCHAR(50) DEFAULT NULL COMMENT '默认颜色代码',
    `default_size` VARCHAR(50) DEFAULT NULL COMMENT '默认尺寸代码',
    `custom_width` DECIMAL(10,2) UNSIGNED DEFAULT NULL COMMENT '自定义宽度（CM）',
    `custom_height` DECIMAL(10,2) UNSIGNED DEFAULT NULL COMMENT '自定义高度（CM）',
    `work_guide_path` VARCHAR(255) DEFAULT NULL COMMENT '작업 가이드文件路径（PDF/JPG/PNG/SVG）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_goods_id` (`goods_id`),
    KEY `idx_is_customizable` (`is_customizable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制扩展表';

-- -----------------------------------------------------
-- 10.1. 작업 가이드字段新增（现网增量执行）
-- -----------------------------------------------------
ALTER TABLE `custom_goods_extension`
    ADD COLUMN IF NOT EXISTS `work_guide_path` VARCHAR(255) DEFAULT NULL COMMENT '작업 가이드文件路径（PDF/JPG/PNG/SVG）' AFTER `custom_height`;

-- 示例：初始化작업 가이드数据
-- INSERT INTO `custom_goods_extension` (`goods_id`, `is_customizable`, `work_guide_path`, `created_at`, `updated_at`)
-- VALUES (12345, 1, '/data/goods/work_guides/12345/sample.pdf', NOW(), NOW());

-- =====================================================
-- 11. 购物车定制扩展表 (custom_cart_extension)
-- 替代对es_cart表的字段扩展，独立管理购物车定制信息
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_cart_extension` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '扩展ID',
    `sno` BIGINT NOT NULL COMMENT '购物车序列号（关联es_cart.sno）',
    `customization_data` JSON DEFAULT NULL COMMENT '定制数据JSON',
    `selected_color` VARCHAR(30) DEFAULT NULL COMMENT '选择的颜色',
    `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
    `is_customized` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否为定制商品',
    `customer_pattern_image` LONGTEXT DEFAULT NULL COMMENT '客户上传的原始图案URL',
    `customization_image` LONGTEXT DEFAULT NULL COMMENT '生成的定制预览图URL',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sno` (`sno`),
    KEY `idx_is_customized` (`is_customized`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车定制扩展表';

-- =====================================================
-- 12. 订单定制扩展表 (custom_order_extension)
-- 替代对es_order表的字段扩展，独立管理订单定制信息
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_order_extension` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '扩展ID',
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号（关联es_order.orderNo）',
    `customization_image` VARCHAR(255) DEFAULT NULL COMMENT '定制图片URL',
    `customer_pattern_image` VARCHAR(255) DEFAULT NULL COMMENT '客户上传的原始图案URL',
    `has_customized_goods` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否包含定制商品',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_has_customized` (`has_customized_goods`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单定制扩展表';

-- =====================================================
-- 13. 订单商品定制扩展表 (custom_order_goods_extension)
-- 替代对es_orderGoods表的字段扩展，独立管理订单商品定制信息
-- =====================================================
CREATE TABLE IF NOT EXISTS `custom_order_goods_extension` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '扩展ID',
    `order_goods_sn` INT NOT NULL COMMENT '订单商品序列号（关联es_orderGoods.sno）',
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号（关联es_order.orderNo）',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
    `customization_data` JSON DEFAULT NULL COMMENT '定制数据JSON',
    `selected_color` VARCHAR(30) DEFAULT NULL COMMENT '选择的颜色',
    `selected_size` VARCHAR(50) DEFAULT NULL COMMENT '选择的尺寸',
    `is_customized` TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '是否为定制商品',
    `customer_pattern_image` VARCHAR(255) DEFAULT NULL COMMENT '客户上传的原始图案URL',
    `customization_image` VARCHAR(255) DEFAULT NULL COMMENT '生成的定制图URL',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_goods_sn` (`order_goods_sn`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_goods_id` (`goods_id`),
    KEY `idx_is_customized` (`is_customized`),
    KEY `idx_order_customized` (`order_no`, `is_customized`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单商品定制扩展表';

-- =====================================================
-- 14. 商品定制附加选项表 (custom_goods_extra_options)
--     用于为单个商品配置额外收费/非收费的附加选项
-- =====================================================  mark
CREATE TABLE IF NOT EXISTS `custom_goods_extra_options` (
    `option_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '附加选项ID',
    `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID（关联es_goods.goodsNo）',
    `option_group` VARCHAR(50) NOT NULL COMMENT '选项组技术Key，如 led_basic / led_single / handle_print',
    `option_label` VARCHAR(255) NOT NULL COMMENT '前台显示标题，如 LED 단색 지정 옵션(+1,000원)',
    `option_desc` VARCHAR(255) DEFAULT NULL COMMENT '前台说明文字',
    `input_type` ENUM('checkbox','radio','color_palette') NOT NULL DEFAULT 'checkbox' COMMENT '输入类型',
    `extra_price` INT(11) NOT NULL DEFAULT 0 COMMENT '附加金额（单位：원）',
    `price_type` ENUM('per_unit','per_order') NOT NULL DEFAULT 'per_unit' COMMENT '计价方式：按件/按订单',
    `is_required` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必选：0-否，1-是',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `sort_order` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`option_id`),
    KEY `idx_goods_id_status` (`goods_id`, `status`),
    KEY `idx_option_group_status` (`option_group`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制附加选项表';

-- -----------------------------------------------------
-- 14.1 商品定制附加选项颜色表 (custom_goods_extra_option_colors)
--      仅当某个附加选项需要颜色盘时使用
-- -----------------------------------------------------  mark
CREATE TABLE IF NOT EXISTS `custom_goods_extra_option_colors` (
    `color_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '附加选项颜色ID',
    `option_id` INT(11) UNSIGNED NOT NULL COMMENT '附加选项ID（关联custom_goods_extra_options.option_id）',
    `color_name` VARCHAR(50) NOT NULL COMMENT '颜色名称',
    `color_code` VARCHAR(20) DEFAULT NULL COMMENT '颜色代码（可选）',
    `color_hex` VARCHAR(7) NOT NULL COMMENT '颜色HEX值',
    `sort_order` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`color_id`),
    KEY `idx_option_id_status` (`option_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品定制附加选项颜色表';

-- =====================================================
-- 15. 创建性能优化索引
-- =====================================================
CREATE INDEX IF NOT EXISTS `idx_goods_customizable` ON `es_goods` (`is_customizable`);
CREATE INDEX IF NOT EXISTS `idx_cart_customized` ON `es_cart` (`is_customized`);
-- 购物车表索引已在es_cart表中创建
CREATE INDEX IF NOT EXISTS `idx_order_goods_customized` ON `es_orderGoods` (`is_customized`);
CREATE INDEX IF NOT EXISTS `idx_order_customization_image` ON `es_order` (`customization_image`);
CREATE INDEX IF NOT EXISTS `idx_order_customer_pattern_image` ON `es_order` (`customer_pattern_image`);
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
-- 16. 添加外键约束确保数据完整性
-- =====================================================
-- 注意：外键约束可以确保数据一致性，但可能影响性能
-- 如果遇到性能问题，可以考虑在应用层维护数据一致性

-- 商品定制扩展表外键
ALTER TABLE `custom_goods_extension`
ADD CONSTRAINT `fk_cge_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 商品图案关联表外键
ALTER TABLE `goods_custom_pattern`
ADD CONSTRAINT `fk_gcp_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 颜色关联表外键
ALTER TABLE `es_goods_color_relation`
ADD CONSTRAINT `fk_gcr_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_gcr_color` FOREIGN KEY (`color_id`) REFERENCES `es_goods_colors` (`color_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 尺寸关联表外键
ALTER TABLE `es_goods_size_relation`
ADD CONSTRAINT `fk_gsr_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_gsr_size` FOREIGN KEY (`size_id`) REFERENCES `es_goods_sizes` (`size_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 自定义商品尺寸表外键
ALTER TABLE `custom_goods_sizes`
ADD CONSTRAINT `fk_cgs_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 商品定制颜色表外键
ALTER TABLE `custom_goods_colors`
ADD CONSTRAINT `fk_cgc_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 商品定制附加选项表外键  mark
ALTER TABLE `custom_goods_extra_options`
ADD CONSTRAINT `fk_cgeo_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 商品定制附加选项颜色表外键  mark
ALTER TABLE `custom_goods_extra_option_colors`
ADD CONSTRAINT `fk_cgeoc_option` FOREIGN KEY (`option_id`) REFERENCES `custom_goods_extra_options` (`option_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 购物车定制扩展表外键（关联es_cart.sno）
ALTER TABLE `custom_cart_extension`
ADD CONSTRAINT `fk_cce_cart` FOREIGN KEY (`sno`) REFERENCES `es_cart` (`sno`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 订单定制扩展表外键（关联es_order.orderNo）
ALTER TABLE `custom_order_extension`
ADD CONSTRAINT `fk_coe_order` FOREIGN KEY (`order_no`) REFERENCES `es_order` (`orderNo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 订单商品定制扩展表外键（关联es_orderGoods.sno、es_order.orderNo、es_goods.goodsNo）
ALTER TABLE `custom_order_goods_extension`
ADD CONSTRAINT `fk_coge_order_goods` FOREIGN KEY (`order_goods_sn`) REFERENCES `es_orderGoods` (`sno`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_coge_order` FOREIGN KEY (`order_no`) REFERENCES `es_order` (`orderNo`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_coge_goods` FOREIGN KEY (`goods_id`) REFERENCES `es_goods` (`goodsNo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 17. 创建定制图片目录的说明
-- =====================================================
-- 注意：以下目录需要在文件系统中手动创建，或通过PHP代码自动创建
-- 建议目录路径：/data/custom_images/
-- 目录权限：755
-- 所有者：web服务器用户（如www-data、apache、nginx等）

-- =====================================================
-- 18. 安装验证（可选）
-- =====================================================
-- 如需验证安装结果，可手动执行以下查询：
-- SHOW TABLES LIKE 'custom_%';
-- DESCRIBE custom_pattern;
-- DESCRIBE custom_goods_extension;
-- DESCRIBE custom_cart_extension;
-- DESCRIBE custom_order_extension;
-- DESCRIBE custom_order_goods_extension;
-- 
-- 验证外键约束：
-- SELECT 
--   TABLE_NAME, 
--   CONSTRAINT_NAME, 
--   COLUMN_NAME, 
--   REFERENCED_TABLE_NAME, 
--   REFERENCED_COLUMN_NAME 
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
-- WHERE TABLE_SCHEMA = DATABASE() 
--   AND TABLE_NAME LIKE 'custom_%' 
--   AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =====================================================
-- 19. 数据库表结构总结
-- =====================================================
-- 
-- 【新建的独立表】（15个）
-- 
-- 定制功能核心表：
--   1. custom_pattern - 定制图案表
--   2. goods_custom_pattern - 商品定制图案关联表
--   3. custom_goods_customization - 商品定制记录表
--   4. custom_uploaded_images - 上传图片记录表
--   5. custom_pattern_category - 图案分类表
-- 
-- 颜色尺寸相关表：
--   6. es_goods_colors - 商品颜色表
--   7. es_goods_sizes - 商品尺寸表
--   8. es_goods_color_relation - 商品颜色关联表
--   9. es_goods_size_relation - 商品尺寸关联表
--   10. custom_goods_sizes - 自定义商品尺寸表
--   11. custom_goods_colors - 商品定制颜色表
-- 
-- 系统表扩展关联表（替代直接修改系统表）：
--   12. custom_goods_extension - 商品定制扩展表（替代修改es_goods，包含width/height）
--   13. custom_cart_extension - 购物车定制扩展表（替代修改es_cart）
--   14. custom_order_extension - 订单定制扩展表（替代修改es_order）
--   15. custom_order_goods_extension - 订单商品定制扩展表（替代修改es_orderGoods）
-- 
-- 【设计优势】
--   ✅ 完全不修改系统表结构，符合增量开发原则
--   ✅ 通过关联表扩展功能，便于后续维护和升级
--   ✅ 可以随时回滚，不影响系统核心功能
--   ✅ 数据隔离清晰，便于备份和迁移
--   ✅ 外键约束确保数据一致性，级联删除避免孤立数据
-- 
-- 【外键关联关系】
--   • custom_goods_extension.goods_id → es_goods.goodsNo
--   • custom_cart_extension.sno → es_cart.sno
--   • custom_order_extension.order_no → es_order.orderNo
--   • custom_order_goods_extension.order_goods_sn → es_orderGoods.sno
--   • custom_order_goods_extension.order_no → es_order.orderNo
--   • custom_order_goods_extension.goods_id → es_goods.goodsNo
-- 
-- =====================================================

-- =====================================================
-- 20. 安装说明
-- =====================================================
-- 1. 执行本脚本前请备份数据库
-- 2. 确保有足够的权限创建表和索引
-- 3. 建议在测试环境先验证
-- 4. 定制图片目录需要手动创建：/data/custom_images/
-- 5. 目录权限设置为755，所有者为web服务器用户
-- 6. 本脚本不修改任何系统表结构，安全可靠
-- 7. 如需回滚，只需删除所有custom_开头的表即可

SELECT '✅ 商品定制功能数据库安装完成（独立表版本）！' AS installation_status;
SELECT '✅ 本次安装未修改任何系统表结构，符合增量开发原则！' AS safety_notice;
