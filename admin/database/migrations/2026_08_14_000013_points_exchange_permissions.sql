-- ============================================================
-- 积分兑换商城权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 PointsExchangeGoodsController（商品 CRUD + 上下架 + 兑换记录
-- 列表），/admin 组新增 4 条路由。AdminPermission 中间件按 method.path
-- 精确匹配，本迁移补齐权限条目，并给超级管理员角色授予全部新权限。
-- id 接续 2026_08_14_000011 门店工作台的 372，下一个 373~378。
-- ============================================================

INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000373, 0, '兑换商品列表', 'get.admin/points-exchange-goods', 3, '', '', 168, NOW(), NOW()),
(21000000000000374, 0, '新增兑换商品', 'post.admin/points-exchange-goods', 3, '', '', 169, NOW(), NOW()),
(21000000000000375, 0, '更新兑换商品', 'put.admin/points-exchange-goods/{id}', 3, '', '', 170, NOW(), NOW()),
(21000000000000376, 0, '删除兑换商品', 'delete.admin/points-exchange-goods/{id}', 3, '', '', 171, NOW(), NOW()),
(21000000000000377, 0, '兑换商品上下架', 'post.admin/points-exchange-goods/{id}/toggle-status', 3, '', '', 172, NOW(), NOW()),
(21000000000000378, 0, '兑换记录列表', 'get.admin/points-exchange-goods/{id}/exchanges', 3, '', '', 173, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
