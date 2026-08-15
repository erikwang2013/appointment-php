-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- APP 推送：记录表 + 配置种子
-- 1. erik_push_log APP 推送日志表（推送链路排查用）
-- 2. erik_system_config group=push 配置种子：
--    push.enabled=0（默认关闭，关闭时 pushToUser 静默降级仅记日志）
--    push.provider=''（厂商：jpush/getui/placeholder，空=未配置凭据）
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_push_log` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '目标用户ID',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '推送标题',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '推送内容',
    `payload` JSON NULL COMMENT '自定义字段（JSON）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'sent' COMMENT '状态: sent=已发送/skipped=跳过',
    `provider` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '推送厂商: jpush/getui/placeholder',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='APP推送日志表';

INSERT INTO `erik_system_config`
    (`id`, `group`, `key`, `value`, `type`, `description`)
VALUES
    (91000000000000025, 'push', 'enabled', '0', 'string',
     'APP 推送总开关：1=启用（占位层构造推送结构并写 erik_push_log），0=关闭（静默降级仅记日志）'),
    (91000000000000026, 'push', 'provider', '', 'string',
     'APP 推送厂商：jpush/getui/placeholder，空表示未配置凭据（不实际发送）')
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`);
