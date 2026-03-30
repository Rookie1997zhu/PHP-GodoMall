-- Lookbook 게시판 상품분류 확장 테이블
-- 규칙 준수: 공식 게시판 원본 테이블(es_bd_*) 스키마는 변경하지 않음

CREATE TABLE IF NOT EXISTS `custom_board_goods_category` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `bd_id` VARCHAR(40) NOT NULL COMMENT '게시판 ID',
    `article_sno` INT UNSIGNED NOT NULL COMMENT '게시글 번호',
    `goods_cate_cd` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '发布时选择的商品分类代码',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_board_article_category` (`bd_id`, `article_sno`, `goods_cate_cd`),
    KEY `idx_goods_cate_cd` (`goods_cate_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='게시판 상품분류 다대다 확장 테이블';

-- 回滚 SQL
-- DROP TABLE IF EXISTS `custom_board_goods_category`;

-- 旧版单分类表升级为多分类映射表
-- ALTER TABLE `custom_board_goods_category`
-- DROP INDEX `uk_board_article`,
-- ADD UNIQUE KEY `uk_board_article_category` (`bd_id`, `article_sno`, `goods_cate_cd`);

-- 如旧表中存在文章级 `sub_title` 字段，请先迁移到 `custom_board_article_meta` 再删除
-- INSERT INTO `custom_board_article_meta` (`bd_id`, `article_sno`, `sub_title`, `created_at`, `updated_at`)
-- SELECT `bd_id`, `article_sno`, MAX(`sub_title`) AS `sub_title`, NOW(), NOW()
-- FROM `custom_board_goods_category`
-- WHERE `sub_title` IS NOT NULL AND `sub_title` <> ''
-- GROUP BY `bd_id`, `article_sno`
-- ON DUPLICATE KEY UPDATE `sub_title` = VALUES(`sub_title`), `updated_at` = NOW();

-- 回滚 SQL
-- ALTER TABLE `custom_board_goods_category`
-- DROP INDEX `uk_board_article_category`,
-- ADD UNIQUE KEY `uk_board_article` (`bd_id`, `article_sno`);
