-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 消息偏好设置
-- 用户可配置的通知类型开关（erik_user_notify_setting）
-- 类型: service_reminder 服务提醒 / card_expiry 到期提醒(会员卡/优惠券) /
--       points_expiry 积分过期 / marketing 营销(预留) / system 系统(不可关)
-- 约定: 未插入行 = 默认开(switch=1)；switch=0 时发送方（定时进程/订单事件，
--       见 NotificationReminderService::notifySettingEnabled）不写站内通知，
--       订阅消息一并跳过。system 类型强制开启。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_14_000102_notify_setting.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_user_notify_setting` (
    `id`         BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id`    BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `type`       VARCHAR(32)     NOT NULL COMMENT '通知类型: service_reminder/card_expiry/points_expiry/marketing/system',
    `switch`     TINYINT         NOT NULL DEFAULT 1 COMMENT '开关: 1=开 0=关（system 恒为 1 不可关闭）',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_type` (`user_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户消息偏好设置';
