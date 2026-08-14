-- ============================================================
-- 电子发票管理权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/InvoiceController.php，
-- /admin 组新增 3 条发票路由（index / {id}/issue / {id}/reject）。
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），
-- 一条权限记录仅对应一个路由模式 slug，故开票/驳回拆分为 383/384
-- （任务规划为 383 覆盖开票+驳回，per-slug 模型下不可行）。
-- id 接续 2026_08_14_000017 的 381，下一个 382。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000205_permission.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000382, 0, '发票列表', 'get.admin/invoices', 3, '', '', 170, NOW(), NOW()),
(21000000000000383, 0, '发票开票', 'post.admin/invoices/{id}/issue', 3, '', '', 171, NOW(), NOW()),
(21000000000000384, 0, '发票驳回', 'post.admin/invoices/{id}/reject', 3, '', '', 172, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
