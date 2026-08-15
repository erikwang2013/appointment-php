-- ============================================================
-- APP 版本迁移（R24 检测更新）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：用户端 App 启动/进入前台时检查更新，登录前即可访问。
--   erik_app_version 版本表（platform 枚举 android/ios；force_update
--   1=强制更新 0=非强制；status 1=上架 0=下架；演示种子每平台一条，
--   INSERT ... ON DUPLICATE KEY UPDATE 幂等，重复执行结果一致）。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000603_app_version.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_app_version` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `platform` VARCHAR(16) NOT NULL COMMENT '平台: android/ios',
    `version_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '版本号（客户端比对用，如 1.0.0）',
    `version_name` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '版本名称（展示用）',
    `force_update` TINYINT NOT NULL DEFAULT 0 COMMENT '是否强制更新: 0=非强制 1=强制',
    `changelog` TEXT COMMENT '更新日志',
    `download_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '下载地址',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_platform_status` (`platform`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='APP 版本表';

-- 演示版本种子（每平台一条，幂等：重复执行仅刷新演示数据不报错）
INSERT INTO `erik_app_version` (`id`, `platform`, `version_code`, `version_name`, `force_update`, `changelog`, `download_url`, `status`, `created_at`, `updated_at`) VALUES
(10000000000000001, 'android', '1.0.0', 'v1.0.0', 0, '初始版本', '', 1, NOW(), NOW()),
(10000000000000002, 'ios', '1.0.0', 'v1.0.0', 0, '初始版本', '', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `version_code` = VALUES(`version_code`),
    `version_name` = VALUES(`version_name`),
    `force_update` = VALUES(`force_update`),
    `changelog` = VALUES(`changelog`),
    `download_url` = VALUES(`download_url`),
    `status` = VALUES(`status`),
    `updated_at` = NOW();
