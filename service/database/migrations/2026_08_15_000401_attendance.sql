-- ============================================================
-- 技师考勤表迁移（幂等）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：erik_technician_attendance 表已存在于库中（结构完整，0 行数据），
-- 本迁移仅做两件事：
--   1) CREATE TABLE IF NOT EXISTS —— 保证全新环境可建表（幂等 no-op）
--   2) 补齐 (technician_id, date) 唯一索引 —— 防并发重复打卡，
--      通过 information_schema 守卫，重复执行跳过（幂等）
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000401_attendance.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_technician_attendance` (
  `id` bigint unsigned NOT NULL COMMENT '主键ID，由snowflake生成',
  `technician_id` bigint unsigned NOT NULL COMMENT '技师档案ID',
  `date` date NOT NULL COMMENT '考勤日期',
  `check_in_at` datetime DEFAULT NULL COMMENT '签到时间',
  `check_out_at` datetime DEFAULT NULL COMMENT '签退时间',
  `clean_photo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '卫生照片URL',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '考勤状态: normal=正常 late=迟到 early=早退 absent=缺勤',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_technician_id` (`technician_id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师考勤表';

-- 唯一索引（防并发重复打卡），已存在则跳过
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erik_technician_attendance' AND INDEX_NAME = 'uk_technician_date');
SET @ddl = IF(@idx_exists = 0,
  'ALTER TABLE `erik_technician_attendance` ADD UNIQUE KEY `uk_technician_date` (`technician_id`, `date`)',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
