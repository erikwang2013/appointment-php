-- ============================================================
-- 工单满意度统计权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：客服工单满意度功能，管理端新增 1 条路由：
--   GET /admin/tickets/satisfaction  工单满意度统计
-- AdminPermission 中间件按 method.path 精确匹配，
-- 本迁移补齐权限条目并给超级管理员角色授予新权限，INSERT IGNORE 幂等。
-- 注：id 382-384 已被发票功能占用、385/387 已被工单功能占用，
-- 本迁移使用 388（工单满意度统计）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000306_ticket_satisfaction_permission.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000388, 0, '工单满意度统计', 'get.admin/tickets/satisfaction', 3, '', '', 174, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`) VALUES
(10000000000000001, 21000000000000388);
