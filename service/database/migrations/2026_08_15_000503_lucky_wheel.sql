-- ============================================================
-- 积分幸运转盘迁移（R23 积分转盘抽奖）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 积分体系闭环补充：用户消耗积分抽奖，按权重随机中奖。
--   erik_lucky_wheel   转盘奖品（cost_points 单次消耗积分；weight 权重，
--                      0=不可中奖；stock -1=不限量；prize_type:
--                      points 积分返还 / coupon 优惠券 / balance 余额 /
--                      none 谢谢参与）
--   erik_wheel_record  抽奖记录（win=中奖 lose=未中；client_token 幂等令牌，
--                      同用户同令牌唯一，NULL 不参与唯一约束）
-- 幂等：CREATE TABLE IF NOT EXISTS + INSERT IGNORE，重复执行结果一致。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000503_lucky_wheel.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_lucky_wheel` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(100) NOT NULL COMMENT '奖品名称',
    `cost_points` INT NOT NULL DEFAULT 0 COMMENT '单次抽奖消耗积分',
    `prize_type` VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT 'points 积分返还/coupon 优惠券/balance 余额/none 谢谢参与',
    `prize_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'points=返还积分/coupon=优惠券面额/balance=余额',
    `weight` INT NOT NULL DEFAULT 0 COMMENT '权重（0=不可中奖）',
    `stock` INT NOT NULL DEFAULT -1 COMMENT '库存（-1=不限量）',
    `sort` INT NOT NULL DEFAULT 0 COMMENT '排序（小在前）',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='幸运转盘奖品表';

CREATE TABLE IF NOT EXISTS `erik_wheel_record` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `wheel_id` BIGINT UNSIGNED NOT NULL COMMENT '奖品ID（erik_lucky_wheel.id）',
    `prize_type` VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT 'points 积分返还/coupon 优惠券/balance 余额/none 谢谢参与',
    `prize_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '中奖数值（面额/返还积分）',
    `result` VARCHAR(20) NOT NULL DEFAULT 'lose' COMMENT 'win=中奖 lose=未中',
    `client_token` VARCHAR(64) DEFAULT NULL COMMENT '客户端幂等令牌（同用户唯一，NULL 不参与唯一）',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    UNIQUE KEY `idx_user_client` (`user_id`, `client_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='幸运转盘抽奖记录表';

-- 演示奖品种子（幂等）
INSERT IGNORE INTO `erik_lucky_wheel` (`id`, `name`, `cost_points`, `prize_type`, `prize_value`, `weight`, `stock`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(10000000000001001, '谢谢参与', 10, 'none', 0.00, 60, -1, 1, 1, NOW(), NOW()),
(10000000000001002, '100积分返还', 10, 'points', 100.00, 40, -1, 2, 1, NOW(), NOW());
