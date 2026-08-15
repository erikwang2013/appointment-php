-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 订单状态时间线（erik_order_status_log）
-- 记录订单每次状态变更（pending→paid→confirmed→serving→completed
-- /cancelled/refunding→refunded），供用户端/管理端展示状态轨迹。
-- from_status 首条可为 NULL（订单创建即记 pending）；remark 记录
-- 原因（取消原因/退款原因/自动取消等）；operator 标注操作方：
-- user/technician/admin/system。
-- 幂等：CREATE TABLE IF NOT EXISTS，可重复执行。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000501_order_status_log.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_order_status_log` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `from_status` VARCHAR(20) NULL COMMENT '变更前状态（首条可为 NULL）',
    `to_status` VARCHAR(20) NOT NULL COMMENT '变更后状态',
    `remark` VARCHAR(255) NULL COMMENT '备注（取消/退款原因等）',
    `operator` VARCHAR(20) NOT NULL DEFAULT 'system' COMMENT '操作方: user/technician/admin/system',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单状态变更时间线';
