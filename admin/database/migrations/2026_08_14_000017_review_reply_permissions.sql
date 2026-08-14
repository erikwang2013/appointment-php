-- ============================================================
-- 评价回复查看权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：技师可回复用户评价，管理端新增 GET /admin/reviews/{id}/reply
-- 查看评价回复详情（静态路由，先于 resource 定义）。
-- AdminPermission 中间件按 method.path 精确匹配，
-- 本迁移为已部署环境补齐权限条目，并给超级管理员角色授予全部新权限。
-- id 接续 2026_08_14_000016 的 380，下一个 381。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000381, 0, '评价回复查看', 'get.admin/reviews/{id}/reply', 3, '', '', 169, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
