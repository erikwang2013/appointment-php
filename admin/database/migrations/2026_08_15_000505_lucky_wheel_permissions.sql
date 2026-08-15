-- ============================================================
-- 幸运转盘权限种子（R23 积分转盘抽奖）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/LuckyWheelController.php，
-- /admin 组新增 4 条幸运转盘路由：
--   GET  /admin/lucky-wheel                奖品列表
--   POST /admin/lucky-wheel                奖品新增
--   PUT  /admin/lucky-wheel/{id}           奖品编辑
--   DELETE /admin/lucky-wheel/{id}         奖品删除
--   POST /admin/lucky-wheel/{id}/toggle-status 上下架
--   GET  /admin/lucky-wheel/records        抽奖记录
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），一条权限
-- 记录仅对应一个路由模式 slug，故按 401-405 拆分（对应功能分组：
-- 401 列表 / 402 新增编辑 / 403 删除+上下架），另 406 为抽奖记录查看
-- （AdminPermissionTest 要求全部 /admin 路由均有种子权限）。
-- sort 接续 2026_08_15_000406 的 183 之后（184-189）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000505_lucky_wheel_permissions.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000401, 0, '幸运转盘奖品列表', 'get.admin/lucky-wheel', 3, '', '', 184, NOW(), NOW()),
(21000000000000402, 0, '幸运转盘奖品新增', 'post.admin/lucky-wheel', 3, '', '', 185, NOW(), NOW()),
(21000000000000403, 0, '幸运转盘奖品编辑', 'put.admin/lucky-wheel/{id}', 3, '', '', 186, NOW(), NOW()),
(21000000000000404, 0, '幸运转盘奖品删除', 'delete.admin/lucky-wheel/{id}', 3, '', '', 187, NOW(), NOW()),
(21000000000000405, 0, '幸运转盘奖品上下架', 'post.admin/lucky-wheel/{id}/toggle-status', 3, '', '', 188, NOW(), NOW()),
(21000000000000406, 0, '幸运转盘抽奖记录', 'get.admin/lucky-wheel/records', 3, '', '', 189, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
