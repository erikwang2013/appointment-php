-- ============================================================
-- 秒杀订单关联列（R24 秒杀功能）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 为 erik_order 增加 seckill_id 列 + 索引，用于 admin 秒杀订单列表
-- 与用户端已售量统计。MySQL 8 无 ADD COLUMN IF NOT EXISTS，
-- 经 information_schema 判断列是否存在，幂等可重复执行。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000602_seckill_order_column.sql
-- ============================================================

SET @seckill_col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erik_order' AND COLUMN_NAME = 'seckill_id'
);

SET @seckill_ddl = IF(@seckill_col_exists = 0,
    'ALTER TABLE `erik_order` ADD COLUMN `seckill_id` BIGINT NULL DEFAULT NULL COMMENT ''秒杀活动ID'' AFTER `participant_id`, ADD KEY `idx_seckill_id` (`seckill_id`)',
    'SELECT 1');

PREPARE seckill_stmt FROM @seckill_ddl;
EXECUTE seckill_stmt;
DEALLOCATE PREPARE seckill_stmt;
