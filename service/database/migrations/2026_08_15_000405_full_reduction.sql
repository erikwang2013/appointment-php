-- ============================================================
-- 满减活动表迁移（R22 满减营销）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 满 X 减 Y 营销活动：标准订单在券/次卡优惠后、等级折扣前应用。
-- threshold 为满减门槛（满多少元），reduction 为减免金额（减多少元），
-- status=1 且 start_at <= now <= end_at 时活动生效（取 reduction 最大者）。
-- 幂等：CREATE TABLE IF NOT EXISTS，重复执行结果一致。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000405_full_reduction.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_full_reduction_activity` (
    `id` VARCHAR(32) NOT NULL COMMENT '主键ID，由snowflake生成',
    `title` VARCHAR(100) NOT NULL COMMENT '活动标题',
    `threshold` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '满减门槛（满多少元）',
    `reduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '减免金额（减多少元）',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0=下架 1=上架',
    `start_at` DATETIME DEFAULT NULL COMMENT '生效开始时间',
    `end_at` DATETIME DEFAULT NULL COMMENT '生效结束时间',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status_status_time` (`status`, `start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='满减活动表';
