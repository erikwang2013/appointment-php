-- ============================================================
-- 扫码核销业务：新增核销记录管理权限
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 /admin/order-verifications（列表 + 详情）只读接口，
-- AdminPermission 中间件按 method.path 精确匹配（{id} 支持通配），
-- 本迁移为已部署环境补齐权限条目，并给超级管理员角色授予全部新权限。
--
-- 已执行过 2026_08_14_000001_sec_fix_permission_gaps.sql 的环境需执行本迁移；
-- 全新安装可直接执行（幂等：角色关联使用 NOT IN 防重）。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000359, NULL, '核销记录列表', 'get.admin/order-verifications', 3, NULL, NULL, 155, NOW(), NOW()),
(21000000000000360, NULL, '核销记录详情', 'get.admin/order-verifications/{id}', 3, NULL, NULL, 156, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
