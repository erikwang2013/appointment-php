-- ============================================================
-- 技师考勤管理权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：技师考勤功能，管理端新增 2 条路由：
--   GET /admin/attendance       考勤列表
--   GET /admin/attendance/stats 考勤统计
-- AdminPermission 中间件按 method.path 精确匹配，
-- 本迁移补齐权限条目并给超级管理员角色授予新权限，INSERT IGNORE 幂等。
-- 注：id 388 已被工单满意度统计占用、389-391 已被评价审核占用，
-- 本迁移使用 392（考勤列表）与 393（考勤统计）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000408_permission.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000392, 0, '考勤列表', 'get.admin/attendance', 3, '', '', 176, NOW(), NOW()),
(21000000000000393, 0, '考勤统计', 'get.admin/attendance/stats', 3, '', '', 177, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`) VALUES
(10000000000000001, 21000000000000392),
(10000000000000001, 21000000000000393);
