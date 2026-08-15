-- Copyright (c) 2026 erik <erik@erik.xyz — https://erik.xyz

-- ============================================================
-- 账号注销闭环（erik_user 增列）
--
-- close_status: 0=正常 1=待注销（已申请，72h 冷却期）2=已注销
-- close_requested_at: 申请注销时间（确认注销需距此 ≥72 小时）
-- close_at: 实际注销时间
-- 幂等：用 INFORMATION_SCHEMA 探测列，已存在则跳过（可重复执行）。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000403_account_close.sql
-- ============================================================

DROP PROCEDURE IF EXISTS `erik_add_account_close_columns`;

DELIMITER $$
CREATE PROCEDURE `erik_add_account_close_columns`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erik_user' AND COLUMN_NAME = 'close_status'
    ) THEN
        ALTER TABLE `erik_user`
            ADD COLUMN `close_status` TINYINT NOT NULL DEFAULT 0 COMMENT '注销状态: 0=正常 1=待注销 2=已注销' AFTER `status`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erik_user' AND COLUMN_NAME = 'close_requested_at'
    ) THEN
        ALTER TABLE `erik_user`
            ADD COLUMN `close_requested_at` DATETIME NULL COMMENT '注销申请时间（确认需距此满72小时）' AFTER `close_status`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erik_user' AND COLUMN_NAME = 'close_at'
    ) THEN
        ALTER TABLE `erik_user`
            ADD COLUMN `close_at` DATETIME NULL COMMENT '实际注销时间' AFTER `close_requested_at`;
    END IF;
END$$
DELIMITER ;

CALL `erik_add_account_close_columns`();

DROP PROCEDURE IF EXISTS `erik_add_account_close_columns`;
