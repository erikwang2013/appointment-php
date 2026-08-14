-- ============================================================
-- 二级返佣记录权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 ReferralLevel2Controller（二级返佣记录只读列表），/admin 组新增
-- 1 条路由 GET /admin/referral-level2。AdminPermission 中间件按 method.path
-- 精确匹配，本迁移补齐权限条目，并给超级管理员角色授予全部新权限。
-- id 386（接续现有 381 之后），sort 175（接续现有 174）。
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000386, 0, '二级返佣记录列表', 'get.admin/referral-level2', 3, '', '', 175, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
