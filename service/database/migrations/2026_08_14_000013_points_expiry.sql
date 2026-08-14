-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 积分过期
-- 1. erik_user_points 增加 expires_at（NULL=永不过期，如签到积分；
--    非空=该笔 earn 积分到期时间，落库时 = 发放时间 + points.expiry_days）
-- 2. erik_system_config 增加 points.expiry_days（默认 365 天；<=0 视为永不过期）
-- 3. 过期扣减：PointsExpiryTimer 进程扫描到期 earn 流水，写 type=expire
--    负值扣减行（source=expiry，order_id 记录原 earn 流水 id 作为幂等依据）
-- ============================================================

ALTER TABLE `erik_user_points`
    ADD COLUMN `expires_at` DATETIME NULL DEFAULT NULL
    COMMENT '积分到期时间（NULL=永不过期；earn 类型落库时=发放时间+points.expiry_days）'
    AFTER `description`;

ALTER TABLE `erik_user_points`
    ADD KEY `idx_expires_at` (`expires_at`);

INSERT INTO `erik_system_config`
    (`id`, `group`, `key`, `value`, `type`, `description`)
VALUES
    (990000000000000021, 'points', 'expiry_days', '365', 'int',
     '积分有效期（天）：新 earn 积分到期时间 = 发放时间 + 该值；<=0 视为永不过期')
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`);
