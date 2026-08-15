-- ============================================================
-- 回头客奖励权限种子（R24：30 天内二次消费奖金）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/ReturnCustomerController.php，
-- /admin 组新增 3 条回头客奖励路由：
--   GET /admin/return-customer/config     配置查看
--   PUT /admin/return-customer/config     配置更新
--   GET /admin/return-customer/rewards    奖励记录列表
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），一条权限
-- 记录仅对应一个路由模式 slug，故按 412-414 拆分。
-- sort 接续 2026_08_15_000505 的 189 之后（190-192）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000607_return_customer_permissions.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000412, 0, '回头客奖励配置查看', 'get.admin/return-customer/config', 3, '', '', 190, NOW(), NOW()),
(21000000000000413, 0, '回头客奖励配置更新', 'put.admin/return-customer/config', 3, '', '', 191, NOW(), NOW()),
(21000000000000414, 0, '回头客奖励记录列表', 'get.admin/return-customer/rewards', 3, '', '', 192, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
