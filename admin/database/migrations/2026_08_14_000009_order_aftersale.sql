-- ============================================================
-- 售后（退换货）表迁移
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 售后申请记录：用户对 paid/completed 订单发起 refund(仅退款)/exchange(换货)，
-- 状态流：pending(待审核) → approved(已通过)/rejected(已驳回) → completed(已完成)。
-- 管理端审核通过后仅状态流转，退款走既有 /api/orders/{id}/refund 由商家另行操作。
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_order_aftersale` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `aftersale_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '售后单号',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '申请用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'refund' COMMENT '售后类型: refund=仅退款 exchange=换货',
    `reason` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '申请原因',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '售后状态: pending=待审核 approved=已通过 rejected=已驳回 completed=已完成',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额（元），仅退款申请时取订单实付',
    `review_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '审核备注',
    `reviewed_at` DATETIME DEFAULT NULL COMMENT '审核时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_aftersale_no` (`aftersale_no`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='售后（退换货）申请表';

