-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 管理后台扩展 — 店长子账号、培训模块、技师等级、提现审批链
-- ============================================================

-- ============================================================
-- 1. erik_admin_user 增加 store_id（店长子账号所属门店，0=平台管理员）
-- ============================================================
ALTER TABLE `erik_admin_user`
ADD COLUMN `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '店长所管门店ID，0=平台管理员'
AFTER `deleted_at`;

ALTER TABLE `erik_admin_user`
ADD INDEX `idx_store_id` (`store_id`);

-- ============================================================
-- 2. 培训课程表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_training_course` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '课程标题',
    `type` VARCHAR(20) NOT NULL DEFAULT 'video' COMMENT '课程类型: video=视频 article=文章',
    `url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '视频/文章链接',
    `content` TEXT COMMENT '课程内容（富文本）',
    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '课程时长（分钟）',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训课程表';

-- ============================================================
-- 3. 培训学习进度表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_training_progress` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `course_id` BIGINT UNSIGNED NOT NULL COMMENT '课程ID',
    `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '学习进度（0-100）',
    `completed_at` DATETIME DEFAULT NULL COMMENT '完成时间',
    `status` VARCHAR(20) NOT NULL DEFAULT 'learning' COMMENT '学习状态: learning=学习中 completed=已完成',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tech_course` (`technician_id`, `course_id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_course_id` (`course_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训学习进度表';

-- ============================================================
-- 4. erik_technician_withdrawal 增加多级审批链字段
-- ============================================================
ALTER TABLE `erik_technician_withdrawal`
ADD COLUMN `store_approved_at` DATETIME DEFAULT NULL COMMENT '店长审批时间'
AFTER `audit_remark`;

ALTER TABLE `erik_technician_withdrawal`
ADD COLUMN `finance_approved_at` DATETIME DEFAULT NULL COMMENT '财务审批时间'
AFTER `store_approved_at`;

ALTER TABLE `erik_technician_withdrawal`
ADD COLUMN `reject_reason` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '驳回原因'
AFTER `finance_approved_at`;

-- ============================================================
-- 5. 技师等级配置表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_technician_tier_config` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '等级名称: junior=初级 senior=高级 expert=专家',
    `slug` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '等级标识: junior/senior/expert',
    `min_orders` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最低接单数',
    `min_rating` DECIMAL(2,1) NOT NULL DEFAULT 0.0 COMMENT '最低评分',
    `commission_rate` DECIMAL(4,2) NOT NULL DEFAULT 0.00 COMMENT '佣金率（百分比，如30.00表示30%）',
    `price_multiplier` DECIMAL(4,2) NOT NULL DEFAULT 1.00 COMMENT '服务价格倍率',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师等级配置表';

-- 默认等级种子数据
INSERT INTO `erik_technician_tier_config` (`id`, `name`, `slug`, `min_orders`, `min_rating`, `commission_rate`, `price_multiplier`, `sort`) VALUES
(80000000000000001, '初级技师', 'junior', 0, 0.0, 30.00, 1.00, 1),
(80000000000000002, '高级技师', 'senior', 100, 4.0, 35.00, 1.20, 2),
(80000000000000003, '专家技师', 'expert', 500, 4.5, 40.00, 1.50, 3);
