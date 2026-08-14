-- ============================================================
-- 评价追评：erik_order_review 补充 append_content/append_images/append_at 列
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：用户可在已完成评价后追加内容/图片（order/v1 ReviewController::append）。
-- 与回复（reply/replied_at）并列，只可追评一次。
-- MySQL 8 无 ADD COLUMN IF NOT EXISTS，用 information_schema 判断保证幂等。
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'erik_order_review'
      AND COLUMN_NAME = 'append_content'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE `erik_order_review` ADD COLUMN `append_content` TEXT NULL COMMENT ''追评内容'' AFTER `replied_at`',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'erik_order_review'
      AND COLUMN_NAME = 'append_images'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE `erik_order_review` ADD COLUMN `append_images` JSON NULL COMMENT ''追评图片列表'' AFTER `append_content`',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'erik_order_review'
      AND COLUMN_NAME = 'append_at'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE `erik_order_review` ADD COLUMN `append_at` DATETIME NULL DEFAULT NULL COMMENT ''追评时间'' AFTER `append_images`',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
