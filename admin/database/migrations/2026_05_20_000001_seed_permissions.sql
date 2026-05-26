-- ============================================================
-- 权限种子数据
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 初始化 RBAC 权限树和角色-权限关联
-- 超级管理员 (super_admin) 自动获得所有权限
-- ============================================================

-- 菜单权限 (type=1)
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000001, NULL, '仪表盘',    'dashboard',     1, 'dashboard', '/dashboard',        1, NOW(), NOW()),
(21000000000000002, NULL, '用户管理',  'user',           1, 'people',    '/admin/user',        2, NOW(), NOW()),
(21000000000000003, NULL, '角色管理',  'role',           1, 'shield',    '/admin/role',        3, NOW(), NOW()),
(21000000000000004, NULL, '权限管理',  'permission',     1, 'lock',      '/admin/permission',  4, NOW(), NOW()),
(21000000000000005, NULL, '系统配置',  'config',         1, 'settings',  '/admin/config',      5, NOW(), NOW()),
(21000000000000006, NULL, '操作日志',  'log',            1, 'article',   '/admin/log',         6, NOW(), NOW());

-- 按钮权限 (type=2)
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000011, 21000000000000002, '批量删除',     'batch.destroy', 2, NULL, NULL, 1, NOW(), NOW()),
(21000000000000012, 21000000000000002, '批量启用/禁用', 'batch.status', 2, NULL, NULL, 2, NOW(), NOW()),
(21000000000000013, 21000000000000002, '导入用户',     'import.users',  2, NULL, NULL, 3, NOW(), NOW()),
(21000000000000014, 21000000000000002, '导出Excel',     'export.excel',  2, NULL, NULL, 4, NOW(), NOW()),
(21000000000000015, 21000000000000002, '导出PDF',       'export.pdf',    2, NULL, NULL, 5, NOW(), NOW()),
(21000000000000016, 21000000000000002, '文件上传',     'upload',         2, NULL, NULL, 6, NOW(), NOW());

-- API 权限 (type=3) — 仪表盘
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000021, 21000000000000001, '查看仪表盘',   'get.admin/dashboard', 3, NULL, NULL, 1, NOW(), NOW());

-- API 权限 — 用户管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000031, 21000000000000002, '查看用户',     'get.admin/user',             3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000032, 21000000000000002, '创建用户',     'post.admin/user',            3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000033, 21000000000000002, '更新用户',     'put.admin/user',             3, NULL, NULL, 3, NOW(), NOW()),
(21000000000000034, 21000000000000002, '删除用户',     'delete.admin/user',          3, NULL, NULL, 4, NOW(), NOW()),
(21000000000000035, 21000000000000002, '批量删除用户', 'post.admin/user/batch/destroy', 3, NULL, NULL, 5, NOW(), NOW()),
(21000000000000036, 21000000000000002, '批量启禁用',   'post.admin/user/batch/status',  3, NULL, NULL, 6, NOW(), NOW());

-- API 权限 — 角色管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000041, 21000000000000003, '查看角色', 'get.admin/role',    3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000042, 21000000000000003, '创建角色', 'post.admin/role',   3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000043, 21000000000000003, '更新角色', 'put.admin/role',    3, NULL, NULL, 3, NOW(), NOW()),
(21000000000000044, 21000000000000003, '删除角色', 'delete.admin/role', 3, NULL, NULL, 4, NOW(), NOW());

-- API 权限 — 权限管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000051, 21000000000000004, '查看权限', 'get.admin/permission',    3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000052, 21000000000000004, '创建权限', 'post.admin/permission',   3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000053, 21000000000000004, '更新权限', 'put.admin/permission',    3, NULL, NULL, 3, NOW(), NOW()),
(21000000000000054, 21000000000000004, '删除权限', 'delete.admin/permission', 3, NULL, NULL, 4, NOW(), NOW());

-- API 权限 — 系统配置
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000061, 21000000000000005, '查看配置', 'get.admin/config',    3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000062, 21000000000000005, '创建配置', 'post.admin/config',   3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000063, 21000000000000005, '更新配置', 'put.admin/config',    3, NULL, NULL, 3, NOW(), NOW()),
(21000000000000064, 21000000000000005, '删除配置', 'delete.admin/config', 3, NULL, NULL, 4, NOW(), NOW());

-- API 权限 — 操作日志
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000071, 21000000000000006, '查看日志', 'get.admin/log', 3, NULL, NULL, 1, NOW(), NOW());

-- API 权限 — 个人中心
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000081, NULL, '个人中心-更新信息', 'put.admin/profile',         3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000082, NULL, '个人中心-修改密码', 'put.admin/profile/password', 3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000083, NULL, '个人中心-登出',     'post.admin/profile/logout',  3, NULL, NULL, 3, NOW(), NOW());

-- API 权限 — 导出
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000091, NULL, '导出Excel', 'post.admin/export/excel', 3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000092, NULL, '导出PDF',   'post.admin/export/pdf',   3, NULL, NULL, 2, NOW(), NOW());

-- API 权限 — 导入
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000093, NULL, '导入用户', 'post.admin/import/users', 3, NULL, NULL, 1, NOW(), NOW());

-- API 权限 — 上传
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000094, NULL, '文件上传', 'post.admin/upload', 3, NULL, NULL, 1, NOW(), NOW());

-- ============================================================
-- 店长子账号菜单 + API 权限
-- ============================================================

-- 菜单: 店长工作台
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000101, NULL, '店长工作台', 'store_manager', 1, 'store', '/store-manager', 7, NOW(), NOW());

-- API 权限: 店长仪表盘
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000102, 21000000000000101, '店长仪表盘', 'get.admin/store-manager/dashboard', 3, NULL, NULL, 1, NOW(), NOW());

-- API 权限: 订单管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000103, 21000000000000101, '订单查询', 'get.admin/appointment-orders', 3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000104, 21000000000000101, '订单更新', 'put.admin/appointment-orders', 3, NULL, NULL, 3, NOW(), NOW());

-- API 权限: 排班管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000105, 21000000000000101, '排班管理', 'get.admin/technicians/schedules', 3, NULL, NULL, 4, NOW(), NOW()),
(21000000000000106, 21000000000000101, '排班设置', 'post.admin/technicians/schedules', 3, NULL, NULL, 5, NOW(), NOW());

-- API 权限: 优惠券管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000107, 21000000000000101, '优惠券查询', 'get.admin/coupons', 3, NULL, NULL, 6, NOW(), NOW()),
(21000000000000108, 21000000000000101, '优惠券创建', 'post.admin/coupons', 3, NULL, NULL, 7, NOW(), NOW());

-- API 权限: Store manager CRUD
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000109, NULL, '店长查询', 'get.admin/store-managers', 3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000110, NULL, '店长创建', 'post.admin/store-managers', 3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000111, NULL, '店长更新', 'put.admin/store-managers', 3, NULL, NULL, 3, NOW(), NOW()),
(21000000000000112, NULL, '店长删除', 'delete.admin/store-managers', 3, NULL, NULL, 4, NOW(), NOW());

-- API 权限: 培训课程
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000113, NULL, '培训课程查询', 'get.admin/training-courses', 3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000114, NULL, '培训课程创建', 'post.admin/training-courses', 3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000115, NULL, '培训课程更新', 'put.admin/training-courses', 3, NULL, NULL, 3, NOW(), NOW()),
(21000000000000116, NULL, '培训课程删除', 'delete.admin/training-courses', 3, NULL, NULL, 4, NOW(), NOW());

-- API 权限: 调度任务
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000117, NULL, '调度任务执行', 'post.admin/scheduled-tasks', 3, NULL, NULL, 1, NOW(), NOW());

-- API 权限: 客户画像
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000118, NULL, '客户画像查询', 'get.admin/customer-profiles', 3, NULL, NULL, 1, NOW(), NOW());

-- API 权限: 批量消息
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000119, NULL, '批量消息发送', 'post.admin/batch-messages', 3, NULL, NULL, 1, NOW(), NOW());

-- API 权限: 退款审批
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000120, NULL, '退款审批列表', 'get.admin/refund-workflows', 3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000121, NULL, '退款审批通过', 'post.admin/refund-workflows/approve', 3, NULL, NULL, 2, NOW(), NOW()),
(21000000000000122, NULL, '退款审批驳回', 'post.admin/refund-workflows/reject', 3, NULL, NULL, 3, NOW(), NOW());

-- API 权限: 技师等级
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000123, NULL, '技师等级查询', 'get.admin/technician-tiers', 3, NULL, NULL, 1, NOW(), NOW()),
(21000000000000124, NULL, '技师等级更新', 'put.admin/technician-tiers', 3, NULL, NULL, 2, NOW(), NOW());

-- ============================================================
-- 超级管理员角色 (ID=10000000000000001) 关联所有权限
-- ============================================================
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
