-- ============================================================
-- 用户积分转赠记录表
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：service 端新增用户间积分转赠（POST /api/user/points/transfer）。
-- 转赠产生两条积分流水：发送方 type=consume source=points_transfer（负值）、
-- 接收方 type=earn source=points_transfer（正值）。points 列存正数，
-- balance 列是单次增量快照，真实余额必须 SUM 聚合。
-- 防滥用：
--   - 单日累计转赠限额（10000 积分/日，控制器常量）
--   - Redis NX 锁 points_transfer:{userId}（30s）+ 事务内双方行锁复验
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_user_points_transfer` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `from_user_id` BIGINT UNSIGNED NOT NULL COMMENT '转赠人ID',
    `to_user_id` BIGINT UNSIGNED NOT NULL COMMENT '接收人ID',
    `points` INT UNSIGNED NOT NULL COMMENT '转赠积分（正数）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'completed' COMMENT '状态: completed=已完成',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_from_user_created` (`from_user_id`, `created_at`),
    KEY `idx_to_user_created` (`to_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户积分转赠记录表';
