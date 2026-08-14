-- ============================================================
-- 用户成长等级体系（第 20 轮任务 #4）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：service 端新增独立成长体系（与会员卡 member_level 无耦合）。
-- 成长值来源：签到(signin +10)、评价(review +20)、消费(consume 每实付 1 元 1 点)。
-- value 列存单次增量（正数），balance 列是单次增量快照，真实累计必须 SUM(value) 聚合
-- （与 erik_user_points 同模式）。
--
-- erik_growth_level：等级档位表，benefits 存 JSON 权益（如折扣率/积分倍率）。
-- 种子 5 档：青铜 0 / 白银 100 / 黄金 500 / 铂金 2000 / 钻石 5000。
-- 幂等：CREATE TABLE IF NOT EXISTS + INSERT IGNORE（uk_level 唯一键兜底）。
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_user_growth` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '成长值类型: consume=消费/signin=签到/review=评价',
    `value` INT UNSIGNED NOT NULL COMMENT '本次成长值增量（正数）',
    `balance` INT UNSIGNED NOT NULL COMMENT '单次增量快照（真实累计=SUM(value)）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户成长值流水表';

CREATE TABLE IF NOT EXISTS `erik_growth_level` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID',
    `level` INT UNSIGNED NOT NULL COMMENT '等级序号（1 起）',
    `name` VARCHAR(50) NOT NULL COMMENT '等级名称',
    `min_growth` INT UNSIGNED NOT NULL COMMENT '达到该等级所需最小成长值',
    `benefits` JSON NOT NULL COMMENT '等级权益（JSON，如 {"discount_rate":0.95,"points_multiplier":1.2}）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成长等级档位表';

INSERT IGNORE INTO `erik_growth_level` (`id`, `level`, `name`, `min_growth`, `benefits`) VALUES
(1, 1, '青铜', 0, JSON_OBJECT('discount_rate', 1.0, 'points_multiplier', 1.0)),
(2, 2, '白银', 100, JSON_OBJECT('discount_rate', 0.98, 'points_multiplier', 1.1)),
(3, 3, '黄金', 500, JSON_OBJECT('discount_rate', 0.95, 'points_multiplier', 1.2)),
(4, 4, '铂金', 2000, JSON_OBJECT('discount_rate', 0.92, 'points_multiplier', 1.3)),
(5, 5, '钻石', 5000, JSON_OBJECT('discount_rate', 0.9, 'points_multiplier', 1.5));
