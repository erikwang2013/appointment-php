-- ============================================================
-- 微信订阅消息推送标记：erik_notification 增加 push_sent_at
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：预约前提醒的订阅消息发送链路已实现
-- （NotificationReminderService::sendSubscribeReminder →
-- WechatTemplateMessageService::sendSubscribeMessage），推送成功
-- （微信 errcode=0）后写入 push_sent_at 作为"已推送"幂等标记，
-- 防止 60s 定时扫描重复推送；失败不写（下次扫描可重试）。
-- 存量行 NULL 兼容（未推送过，不触发任何行为变化）。
--
-- 幂等：MySQL 8.0 本地不支持 ADD COLUMN IF NOT EXISTS，
-- 先查 information_schema.COLUMNS 再决定是否 ALTER，可重复执行。
-- ============================================================

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'erik_notification'
      AND COLUMN_NAME = 'push_sent_at'
);

SET @ddl := IF(@col_exists = 0,
    'ALTER TABLE `erik_notification` ADD COLUMN `push_sent_at` DATETIME DEFAULT NULL COMMENT ''订阅消息推送成功时间'' AFTER `read_at`',
    'SELECT ''push_sent_at already exists, skip'''
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
