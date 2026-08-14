-- ============================================================
-- 客服工单满意度评分迁移
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 用户关闭工单时可选打分（1-5 星），记录评分与评分时间。
-- rating 为 NULL 表示未评分（兼容旧客户端关闭无评分）。
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000303_ticket_rating.sql
-- ============================================================

ALTER TABLE `erik_ticket`
    ADD COLUMN `rating` TINYINT UNSIGNED DEFAULT NULL COMMENT '满意度评分 1-5，NULL 表示未评分' AFTER `replied_at`,
    ADD COLUMN `rated_at` DATETIME DEFAULT NULL COMMENT '评分时间' AFTER `rating`;
