-- ============================================================
-- 微信分账记录查看权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：微信官方分账功能，管理端新增 1 条路由：
--   GET /admin/profit-sharing  分账记录列表
-- AdminPermission 中间件按 method.path 精确匹配，
-- 本迁移补齐权限条目并给超级管理员角色授予新权限，INSERT IGNORE 幂等。
-- 注：id 21000000000000388 已被工单满意度统计占用，本迁移使用
-- 21000000000000394（分账记录查看）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000407_permission.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000394, 0, '分账记录查看', 'get.admin/profit-sharing', 3, '', '', 175, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`) VALUES
(10000000000000001, 21000000000000394);
