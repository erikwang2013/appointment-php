-- ============================================================
-- 技师回复评价：erik_order_review 补充 replied_at 列
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：技师可回复用户评价（technician/v1 ReviewController::reply）。
-- reply 列已于 2026_05_26_000003 建表时定义，本迁移补齐 replied_at（回复时间）。
-- MySQL 8 无 ADD COLUMN IF NOT EXISTS，用 information_schema 判断保证幂等。
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'erik_order_review'
      AND COLUMN_NAME = 'replied_at'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE `erik_order_review` ADD COLUMN `replied_at` DATETIME NULL DEFAULT NULL COMMENT ''技师回复时间'' AFTER `reply`',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
