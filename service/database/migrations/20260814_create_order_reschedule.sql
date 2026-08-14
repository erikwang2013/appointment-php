-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 订单改期记录表：每次改期一条记录（old → new 服务时间/技师），保留审计轨迹
-- 应用方式：mysql -u root -p appointment < service/database/migrations/20260814_create_order_reschedule.sql
CREATE TABLE IF NOT EXISTS `erik_order_reschedule` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `old_service_time` DATETIME NOT NULL COMMENT '原服务时间',
    `new_service_time` DATETIME NOT NULL COMMENT '改期后服务时间',
    `old_technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '原技师档案ID',
    `new_technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '改期后技师档案ID（当前改期仅支持同技师换时间）',
    `reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '改期原因',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_new_service_time` (`new_service_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单改期记录表';
