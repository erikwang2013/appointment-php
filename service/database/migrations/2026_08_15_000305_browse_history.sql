-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 用户浏览足迹表（erik_browse_history）
--
-- 用户浏览服务详情（/api/service/detail/{id}）时记录足迹，
-- 用于"最近浏览"列表。同一用户浏览同一服务只保留一条：
-- uk_user_item 唯一键防重（业务层 updateOrCreate，DB 层兜底），
-- 重复浏览仅刷新 viewed_at 不重复插入。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000305_browse_history.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_browse_history` (
    `id`         BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id`    BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `item_id`    BIGINT UNSIGNED NOT NULL COMMENT '服务项目ID（erik_service.id）',
    `viewed_at`  DATETIME        NOT NULL COMMENT '最近浏览时间',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_item` (`user_id`, `item_id`),
    KEY `idx_user_viewed` (`user_id`, `viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户浏览足迹';
