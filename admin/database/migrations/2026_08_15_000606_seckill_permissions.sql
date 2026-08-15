-- ============================================================
-- 秒杀管理权限种子（R24 秒杀功能）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/SeckillController.php，
-- /admin 组新增秒杀路由：
--   GET    /admin/seckill                 活动列表
--   POST   /admin/seckill                 活动新增
--   GET    /admin/seckill/{id}            活动详情
--   PUT    /admin/seckill/{id}            活动编辑
--   DELETE /admin/seckill/{id}            活动删除
--   POST   /admin/seckill/{id}/toggle-status 上下架
--   GET    /admin/seckill/{id}/orders     秒杀订单列表
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），一条权限
-- 记录仅对应一个路由模式 slug，故 407-411 + 420 拆分（407 列表 / 408 新增 /
-- 409 编辑 / 410 删除 / 411 上下架 / 420 订单列表）。
-- 注意：412-414 已被「回头客奖励」权限占用（并发迁移），订单列表用 420。
-- 幂等：ON DUPLICATE KEY UPDATE（表无 description 列，回写 name/sort）。
-- sort 接续 193 之后（194-199）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000606_seckill_permissions.sql
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000407, 0, '秒杀活动列表', 'get.admin/seckill', 3, '', '', 194, NOW(), NOW()),
(21000000000000408, 0, '秒杀活动新增', 'post.admin/seckill', 3, '', '', 195, NOW(), NOW()),
(21000000000000409, 0, '秒杀活动编辑', 'put.admin/seckill/{id}', 3, '', '', 196, NOW(), NOW()),
(21000000000000410, 0, '秒杀活动删除', 'delete.admin/seckill/{id}', 3, '', '', 197, NOW(), NOW()),
(21000000000000411, 0, '秒杀活动上下架', 'post.admin/seckill/{id}/toggle-status', 3, '', '', 198, NOW(), NOW()),
(21000000000000420, 0, '秒杀订单列表', 'get.admin/seckill/{id}/orders', 3, '', '', 199, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `sort` = VALUES(`sort`);

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
