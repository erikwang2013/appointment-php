-- ============================================================
-- 满减活动权限种子（R22 满减营销）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/FullReductionController.php，
-- /admin 组新增 5 条满减活动路由（resource: index/store/update/destroy +
-- {id}/toggle-status 上下架）。AdminPermission 中间件按 method.path 精确匹配
-- （{id} 通配），一条权限记录仅对应一个路由模式 slug，故按 396-400 拆分：
--   396 满减列表/查看  get.admin/full-reduction-activities
--   397 满减新增      post.admin/full-reduction-activities
--   398 满减编辑      put.admin/full-reduction-activities/{id}
--   399 满减上下架    post.admin/full-reduction-activities/{id}/toggle-status
--   400 满减删除      delete.admin/full-reduction-activities/{id}
-- sort 接续 2026_08_15_000307 的 178 之后（179-183）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000406_full_reduction_permissions.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000396, 0, '满减活动列表', 'get.admin/full-reduction-activities', 3, '', '', 179, NOW(), NOW()),
(21000000000000397, 0, '满减活动新增', 'post.admin/full-reduction-activities', 3, '', '', 180, NOW(), NOW()),
(21000000000000398, 0, '满减活动编辑', 'put.admin/full-reduction-activities/{id}', 3, '', '', 181, NOW(), NOW()),
(21000000000000399, 0, '满减活动上下架', 'post.admin/full-reduction-activities/{id}/toggle-status', 3, '', '', 182, NOW(), NOW()),
(21000000000000400, 0, '满减活动删除', 'delete.admin/full-reduction-activities/{id}', 3, '', '', 183, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
