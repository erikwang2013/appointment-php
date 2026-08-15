-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 钱包支付密码（erik_user_wallet 增列）
--
-- pay_password: 支付密码哈希（password_hash 存储），NULL/空 = 未设置
-- pay_password_set_at: 最近一次设置/修改时间
-- 幂等：用 INFORMATION_SCHEMA 探测列，已存在则跳过（可重复执行）。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000502_wallet_pay_password.sql
-- ============================================================

DROP PROCEDURE IF EXISTS `erik_add_wallet_pay_password_columns`;

DELIMITER $$
CREATE PROCEDURE `erik_add_wallet_pay_password_columns`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erik_user_wallet' AND COLUMN_NAME = 'pay_password'
    ) THEN
        ALTER TABLE `erik_user_wallet`
            ADD COLUMN `pay_password` VARCHAR(255) NULL COMMENT '支付密码哈希（password_hash 存储，空=未设置）' AFTER `total_consume`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erik_user_wallet' AND COLUMN_NAME = 'pay_password_set_at'
    ) THEN
        ALTER TABLE `erik_user_wallet`
            ADD COLUMN `pay_password_set_at` DATETIME NULL COMMENT '支付密码最近设置时间' AFTER `pay_password`;
    END IF;
END$$
DELIMITER ;

CALL `erik_add_wallet_pay_password_columns`();

DROP PROCEDURE IF EXISTS `erik_add_wallet_pay_password_columns`;
