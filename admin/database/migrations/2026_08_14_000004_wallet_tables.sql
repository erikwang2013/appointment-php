-- ============================================================
-- 储值支付（余额体系）三表
-- 2026_08_14_000004_wallet_tables.sql
-- 与 docs/install.sql 中同名表语义一致（本地 MySQL 已执行）
-- ============================================================

-- 用户钱包表
CREATE TABLE IF NOT EXISTS `erik_user_wallet` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '可用余额（元）',
    `total_recharge` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计充值金额（元）',
    `total_consume` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计消费金额（元）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户钱包表';

-- 充值单表
CREATE TABLE IF NOT EXISTS `erik_wallet_recharge` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '充值单号（R + 时间戳 + 4位随机数，与订单号体系区分）',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额（元）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending=待支付 paid=已支付 refunded=已退款 failed=失败',
    `pay_channel` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '支付渠道: wechat=微信支付',
    `paid_at` DATETIME DEFAULT NULL COMMENT '支付完成时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='钱包充值单表';

-- 钱包流水表
CREATE TABLE IF NOT EXISTS `erik_wallet_txn` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'recharge' COMMENT '流水类型: recharge=充值 consume=消费 refund=退款（金额一律正数，方向由 type 表达）',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动金额（元，正数）',
    `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动后余额（元）',
    `order_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联订单ID（消费/退款）',
    `recharge_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联充值单ID（充值）',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='钱包流水表';
