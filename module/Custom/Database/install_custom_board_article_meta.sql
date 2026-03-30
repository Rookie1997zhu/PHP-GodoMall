-- Lookbook 게시판 게시글 메타 확장 테이블
-- 규칙 준수: 공식 게시판 원본 테이블(es_bd_*) 스키마는 변경하지 않음

CREATE TABLE IF NOT EXISTS `custom_board_article_meta` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `bd_id` VARCHAR(40) NOT NULL COMMENT '게시판 ID',
    `article_sno` INT UNSIGNED NOT NULL COMMENT '게시글 번호',
    `sub_title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Lookbook Sub Title',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_board_article_meta` (`bd_id`, `article_sno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='게시판 게시글 메타 확장 테이블';

-- 回滚 SQL
-- DROP TABLE IF EXISTS `custom_board_article_meta`;
