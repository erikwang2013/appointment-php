-- ============================================================
-- 客服工单表迁移
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 用户提交客服工单，管理端处理回复，状态流：
-- pending(待处理) → processing(处理中) → resolved(已解决)/closed(已关闭)。
-- 用户端可对 pending/processing 工单自行关闭。
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_ticket` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '提交用户ID',
    `category` VARCHAR(20) NOT NULL DEFAULT 'other' COMMENT '工单分类: service=服务类 refund=退款类 technician=技师类 other=其他',
    `description` TEXT NOT NULL COMMENT '问题描述',
    `images` JSON DEFAULT NULL COMMENT '图片数组',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '工单状态: pending=待处理 processing=处理中 resolved=已解决 closed=已关闭',
    `admin_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '处理管理员ID',
    `reply_content` TEXT DEFAULT NULL COMMENT '回复内容',
    `replied_at` DATETIME DEFAULT NULL COMMENT '回复时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_created` (`user_id`, `created_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客服工单表';
