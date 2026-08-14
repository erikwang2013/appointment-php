-- ============================================================
-- 售后管理权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/AftersaleController.php，
-- /admin 组新增 2 条售后路由（index/review）。
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），
-- 本迁移为已部署环境补齐权限条目，并给超级管理员角色授予全部新权限。
-- id 接续 2026_08_14_000008 会员卡权限的 369，下一个 370。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000370, 0, '售后列表', 'get.admin/aftersales', 3, '', '', 165, NOW(), NOW()),
(21000000000000371, 0, '售后审核', 'post.admin/aftersales/{id}/review', 3, '', '', 166, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
