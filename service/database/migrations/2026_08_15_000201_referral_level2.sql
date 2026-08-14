-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 多级分销：二级返佣
-- 1. erik_referral_level2_reward 二级返佣发放记录表（幂等防重：
--    UNIQUE KEY uk_order_referred (order_id, referred_user_id)）
-- 2. erik_system_config 增加 referral.level2_rate（默认 0.02，
--    非法值（<=0 或 >1）回落默认）
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_referral_level2_reward` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID（被推荐人首单）',
    `referred_user_id` BIGINT UNSIGNED NOT NULL COMMENT '被推荐人用户ID（首单用户）',
    `referrer_id` BIGINT UNSIGNED NOT NULL COMMENT '二级推荐人（上上级）用户ID',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '发放金额',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 1=已发放',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_referred` (`order_id`, `referred_user_id`),
    KEY `idx_referrer_id` (`referrer_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='二级返佣发放记录表';

INSERT INTO `erik_system_config`
    (`id`, `group`, `key`, `value`, `type`, `description`)
VALUES
    (91000000000000024, 'referral', 'level2_rate', '0.02', 'string',
     '二级返佣比例：被推荐人首单完成后发放给上上级推荐人的佣金比例（0-1，非法值回落 0.02）')
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`);
