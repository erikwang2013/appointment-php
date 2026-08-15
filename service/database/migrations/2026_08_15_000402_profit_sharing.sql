-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 微信分账记录表（erik_profit_sharing）
--
-- 微信官方分账（请求单次分账 API）的本地记录：支付成功后按配置比例
-- 向技师分账，记录每次分账请求与结果。未启用/未配置时降级 disabled
-- （不落库），启用时先落 pending 记录再构造微信请求。
-- uk_sharing_no 唯一（分账单号复用订单支付单号，防重复分账），
-- idx_order 支撑按订单查询（querySharing / admin 列表）。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000402_profit_sharing.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_profit_sharing` (
    `id`         BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id`    BIGINT UNSIGNED NOT NULL COMMENT '分账接收方用户ID（技师用户，erik_user.id）',
    `order_id`   BIGINT UNSIGNED NOT NULL COMMENT '订单ID（erik_order.id）',
    `sharing_no` VARCHAR(64)     NOT NULL COMMENT '分账单号（复用支付单号，唯一）',
    `amount`     DECIMAL(10,2)   NOT NULL COMMENT '分账金额（元）',
    `ratio`      DECIMAL(5,4)    NOT NULL COMMENT '分账比例（技师分成，如 0.7000）',
    `status`     VARCHAR(16)     NOT NULL DEFAULT 'pending' COMMENT '状态: pending/success/failed/disabled',
    `response`   JSON            NULL COMMENT '微信分账响应（原始 JSON）',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sharing_no` (`sharing_no`),
    KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='微信分账记录';
