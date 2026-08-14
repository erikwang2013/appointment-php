-- ============================================================
-- 评价管理权限种子（S7 评价管理闭环）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/ReviewController.php，
-- /admin 组新增 4 条评价管理路由（index/show/audit/destroy）。
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），
-- 本迁移为已部署环境补齐权限条目，并给超级管理员角色授予全部新权限。
--
-- id 沿用 21000000000000xxx 系列，接续 2026_08_14_000003 的 360，下一个 361。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
-- 评价管理
(21000000000000361, NULL, '评价列表', 'get.admin/reviews', 3, NULL, NULL, 161, NOW(), NOW()),
(21000000000000362, NULL, '评价详情', 'get.admin/reviews/{id}', 3, NULL, NULL, 162, NOW(), NOW()),
(21000000000000363, NULL, '评价审核', 'put.admin/reviews/{id}/audit', 3, NULL, NULL, 163, NOW(), NOW()),
(21000000000000364, NULL, '删除评价', 'delete.admin/reviews/{id}', 3, NULL, NULL, 164, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
