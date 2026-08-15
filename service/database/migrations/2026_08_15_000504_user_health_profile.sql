-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

-- ============================================================
-- 用户健康档案与服务偏好（erik_user_health_profile）
-- 一人一份（uk_user）：记录过敏史/慢性病禁忌、偏好技师、偏好时段、其他备注。
-- 档案用于预约前服务风险评估与技师推荐（下单接口可读取本表做提示/过滤）。
-- 字段说明：
--   allergies              过敏史（如 "花粉、青霉素"，逗号分隔）
--   chronic_diseases       慢性病/禁忌（如 "高血压，不宜剧烈按摩"）
--   preferred_technician_id 偏好技师（erik_user.user_type='technician' 的用户ID）
--   preferred_time          偏好时段（如 "14:00-17:00"）
--   notes                   其他备注
-- 所有文本字段可空：未提供即为空档案（接口返回 set:false）。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000504_user_health_profile.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_user_health_profile` (
    `id`                      BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id`                 BIGINT UNSIGNED NOT NULL COMMENT '用户ID（erik_user.id）',
    `allergies`               VARCHAR(500)    NULL COMMENT '过敏史，逗号分隔，如 "花粉、青霉素"',
    `chronic_diseases`        VARCHAR(500)    NULL COMMENT '慢性病/禁忌，如 "高血压，不宜剧烈按摩"',
    `preferred_technician_id` BIGINT UNSIGNED NULL COMMENT '偏好技师（erik_user.user_type=technician 的用户ID）',
    `preferred_time`          VARCHAR(50)     NULL COMMENT '偏好时段，如 14:00-17:00',
    `notes`                   VARCHAR(500)    NULL COMMENT '其他备注',
    `created_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户健康档案与服务偏好（一人一份）';
