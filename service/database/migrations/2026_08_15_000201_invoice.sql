-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 电子发票表（erik_invoice）
--
-- 用户对已完成订单（service）/ 已支付充值（recharge）申请开票，
-- 管理端审核开票（issued）/ 驳回（rejected）。
-- 一单（order_id + order_type）仅可申请一次：uk_order_type 唯一键防重
-- （并发提交时 DB 层兜底，业务层先查后插 + 捕获 1062 返回 422）。
-- 金额由服务端自动带出（服务订单=paid_amount，充值=充值金额），不接受客户端传值。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000201_invoice.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_invoice` (
    `id`            BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id`       BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_id`      BIGINT UNSIGNED NOT NULL COMMENT '业务单ID（服务订单/充值单）',
    `order_type`    VARCHAR(20)     NOT NULL COMMENT '业务单类型: service=服务订单/recharge=充值',
    `title_type`    VARCHAR(20)     NOT NULL COMMENT '抬头类型: personal=个人/company=企业',
    `invoice_title` VARCHAR(255)    NOT NULL COMMENT '发票抬头',
    `tax_no`        VARCHAR(50)     NULL COMMENT '纳税人识别号（company 必填）',
    `amount`        DECIMAL(25,2)   NOT NULL COMMENT '开票金额（服务订单=实付金额，充值=充值金额）',
    `email`         VARCHAR(100)    NULL COMMENT '接收邮箱（可选）',
    `status`        VARCHAR(20)     NOT NULL DEFAULT 'pending' COMMENT '状态: pending=待开票/issued=已开票/rejected=已驳回',
    `issued_no`     VARCHAR(50)     NULL COMMENT '发票号码（开票后写入）',
    `issued_at`     DATETIME        NULL COMMENT '开票时间',
    `remark`        VARCHAR(255)    NULL COMMENT '备注（驳回原因）',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_type` (`order_id`, `order_type`),
    KEY `idx_user_created` (`user_id`, `created_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='电子发票';
