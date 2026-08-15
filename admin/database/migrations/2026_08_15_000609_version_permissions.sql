-- ============================================================
-- APP 版本管理权限种子（R24 检测更新）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：新增 admin/app/admin/controller/VersionController.php，
-- /admin 组新增 4 条版本管理路由（Route::resource 按控制器方法注册，
-- 无 show 方法故不含 get.admin/versions/{id}）：
--   GET    /admin/versions         版本列表
--   POST   /admin/versions         版本新增
--   PUT    /admin/versions/{id}    版本编辑
--   DELETE /admin/versions/{id}    版本删除
-- AdminPermission 中间件按 method.path 精确匹配（{id} 通配），
-- 权限 ID 接续 2026_08_15_000505 的 406（407-415 已被并行任务占用），
-- sort 接续 189 之后（190-193）。
-- 应用方式：mysql -uroot -proot appointment < admin/database/migrations/2026_08_15_000609_version_permissions.sql
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000416, 0, 'APP版本列表', 'get.admin/versions', 3, '', '', 190, NOW(), NOW()),
(21000000000000417, 0, 'APP版本新增', 'post.admin/versions', 3, '', '', 191, NOW(), NOW()),
(21000000000000418, 0, 'APP版本编辑', 'put.admin/versions/{id}', 3, '', '', 192, NOW(), NOW()),
(21000000000000419, 0, 'APP版本删除', 'delete.admin/versions/{id}', 3, '', '', 193, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
