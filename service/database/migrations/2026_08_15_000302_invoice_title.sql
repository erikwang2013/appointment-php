-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 常用发票抬头表（erik_invoice_title）
--
-- 用户维护常用开票抬头，申请开票时通过 title_id 带入
-- invoice_title/tax_no/title_type，无需每次手填。
-- uk_user_title(user_id, title_type, invoice_title) 防同用户同抬头重复；
-- idx_user_default(user_id, is_default) 加速默认抬头查询。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000302_invoice_title.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_invoice_title` (
    `id`            BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id`       BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `title_type`    VARCHAR(20)     NOT NULL COMMENT '抬头类型: personal=个人/company=企业',
    `invoice_title` VARCHAR(255)    NOT NULL COMMENT '发票抬头',
    `tax_no`        VARCHAR(50)     NULL COMMENT '纳税人识别号（company 必填）',
    `is_default`    TINYINT         NOT NULL DEFAULT 0 COMMENT '是否默认抬头: 0=否/1=是',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_title` (`user_id`, `title_type`, `invoice_title`),
    KEY `idx_user_default` (`user_id`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='常用发票抬头';
