-- ============================================================
-- 优惠券转赠表
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：service 端新增优惠券转赠（/api/marketing/coupons/transfer + /claim）。
-- 转赠即消耗原券：领取时原 erik_user_coupon 置 used，生成新券绑定接收人。
-- 防滥用规则：
--   - 转赠码 code 唯一索引，一次性（claimed 后不可再领）
--   - uk_user_coupon 唯一索引：同一张用户券只能转赠一次
--   - expire_at = 生成时间 + 7 天，过期懒判定（claim 时置 expired 并恢复原券）
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_user_coupon_transfer` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_coupon_id` BIGINT UNSIGNED NOT NULL COMMENT '原用户券ID',
    `coupon_id` BIGINT UNSIGNED NOT NULL COMMENT '券定义ID',
    `from_user_id` BIGINT UNSIGNED NOT NULL COMMENT '转赠人ID',
    `to_user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '领取人ID（领取后填充）',
    `code` VARCHAR(32) NOT NULL COMMENT '转赠码（8位随机串，唯一）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending=待领取 claimed=已领取 expired=已过期',
    `claimed_at` DATETIME DEFAULT NULL COMMENT '领取时间',
    `expire_at` DATETIME NOT NULL COMMENT '过期时间（生成+7天）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    UNIQUE KEY `uk_user_coupon` (`user_coupon_id`),
    KEY `idx_from_user` (`from_user_id`),
    KEY `idx_to_user` (`to_user_id`),
    KEY `idx_status_expire` (`status`, `expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠券转赠记录表';
