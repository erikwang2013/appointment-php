-- ============================================================
-- 数据报表权限种子（S7 数据报表闭环）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/ReportController.php，
-- /admin 组新增 3 条报表路由（orders/technicians/distribution）。
-- AdminPermission 中间件按 method.path 精确匹配，
-- 本迁移为已部署环境补齐权限条目，并给超级管理员角色授予全部新权限。
-- 全新安装由 seed_permissions.sql（已同步本清单）覆盖。
--
-- id 沿用 21000000000000xxx 系列，接续 2026_08_14_000002 的 360。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
-- 数据报表
(21000000000000361, NULL, '订单统计报表', 'get.admin/reports/orders', 3, NULL, NULL, 161, NOW(), NOW()),
(21000000000000362, NULL, '技师绩效报表', 'get.admin/reports/technicians', 3, NULL, NULL, 162, NOW(), NOW()),
(21000000000000363, NULL, '分布报表', 'get.admin/reports/distribution', 3, NULL, NULL, 163, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
