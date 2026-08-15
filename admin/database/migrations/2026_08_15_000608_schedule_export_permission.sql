-- ============================================================
-- 排班导出权限种子（R24 排班 Excel 导出）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：TechnicianScheduleController::export 新增排班 CSV 导出端点，
-- /admin 组新增 1 条路由：
--   GET /admin/technician-schedule/export  排班导出
-- AdminPermission 中间件按 method.path 精确匹配，一条权限记录对应
-- 一个路由 slug，故新增 415（AdminPermissionTest 要求全部 /admin 路由
-- 均有种子权限）。sort 接续 2026_08_15_000505 的 189 之后（190）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000608_schedule_export_permission.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000415, 0, '排班导出', 'get.admin/technician-schedule/export', 3, '', '', 190, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
