-- ============================================================
-- 用户间余额转账表
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：service 端新增用户间余额转账（/api/wallet/transfer）。
-- 转账为即时完成（无中间状态），status 预留扩展。
-- 金额 DECIMAL(25,2) 与 WalletTxn 一致走 DECIMAL 处理，禁止 float 比较。
-- 索引覆盖「发出+收到」两种分页查询（按 created_at 倒序）。
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_wallet_transfer` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `from_user_id` BIGINT UNSIGNED NOT NULL COMMENT '转出用户ID',
    `to_user_id` BIGINT UNSIGNED NOT NULL COMMENT '接收用户ID',
    `amount` DECIMAL(25,2) NOT NULL DEFAULT '0.00' COMMENT '转账金额（元）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'completed' COMMENT '状态: completed=已完成',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_from_user_created` (`from_user_id`, `created_at`),
    KEY `idx_to_user_created` (`to_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户间余额转账记录表';
