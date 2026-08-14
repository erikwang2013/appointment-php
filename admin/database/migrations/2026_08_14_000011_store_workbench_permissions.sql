-- ============================================================
-- 门店工作台权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：StoreController 新增 workbenchOverview（门店工作台概览，
-- 供 admin Flutter 门店工作台视图页调用），/admin 组新增 1 条静态路由。
-- AdminPermission 中间件按 method.path 精确匹配，
-- 本迁移为已部署环境补齐权限条目，并给超级管理员角色授予全部新权限。
-- id 接续 2026_08_14_000010 售后权限的 371，下一个 372。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000372, 0, '门店工作台概览', 'get.admin/stores/workbench-overview', 3, '', '', 167, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
