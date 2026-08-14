-- ============================================================
-- 评价图片审核权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/ReviewAuditController.php，
-- /admin 组新增 3 条带图评价审核路由（index / {id}/hide / {id}/restore）。
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），
-- 一条权限记录仅对应一个路由模式 slug，故 hide/restore 拆分为 390/391。
-- id 接续 2026_08_15_000205 的 387/388，本文件用 389/390/391；sort 接续 175 之后。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000307_review_audit_permission.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000389, 0, '评价图片审核列表', 'get.admin/review-audit', 3, '', '', 176, NOW(), NOW()),
(21000000000000390, 0, '评价图片隐藏', 'post.admin/review-audit/{id}/hide', 3, '', '', 177, NOW(), NOW()),
(21000000000000391, 0, '评价图片恢复', 'post.admin/review-audit/{id}/restore', 3, '', '', 178, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
