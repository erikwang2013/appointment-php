-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 操作日志详情表 — 记录操作前后的数据快照与响应
-- ============================================================

-- ============================================================
-- 1. erik_operation_log_detail 操作日志详情表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_operation_log_detail` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `log_id` BIGINT UNSIGNED NOT NULL COMMENT '关联操作日志ID',
    `snapshot_before` TEXT COMMENT '操作前数据快照（JSON）',
    `snapshot_after` TEXT COMMENT '操作后数据快照（JSON）',
    `response_body` TEXT COMMENT '响应内容（JSON）',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_log_id` (`log_id`),
    KEY `idx_log_id` (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志详情表';
