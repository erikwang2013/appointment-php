-- ============================================================
-- 技师排班管理权限种子（S7 排班管理闭环）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/TechnicianScheduleController.php，
-- /admin 组新增 4 条排班管理路由（list/store/destroy/rest）。
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），
-- 本迁移为已部署环境补齐权限条目，并给超级管理员角色授予全部新权限。
-- 全新安装由 seed_permissions.sql（已同步本清单）覆盖。
--
-- id 沿用 21000000000000xxx 系列，接续 2026_08_14_000001 的 354。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
-- 技师排班管理
(21000000000000355, NULL, '排班列表', 'get.admin/schedules', 3, NULL, NULL, 155, NOW(), NOW()),
(21000000000000356, NULL, '创建排班', 'post.admin/schedules', 3, NULL, NULL, 156, NOW(), NOW()),
(21000000000000357, NULL, '删除排班', 'delete.admin/schedules/{id}', 3, NULL, NULL, 157, NOW(), NOW()),
(21000000000000358, NULL, '排班设为休息', 'put.admin/schedules/{id}/rest', 3, NULL, NULL, 158, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
