-- ============================================================
-- 客服工单权限种子
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：客服工单功能，管理端新增 2 条路由：
--   GET  /admin/tickets             工单列表
--   POST /admin/tickets/{id}/reply  工单回复
-- AdminPermission 中间件按 method.path 精确匹配，
-- 本迁移补齐权限条目并给超级管理员角色授予新权限，INSERT IGNORE 幂等。
-- 注：id 384 已被发票功能占用、386 已被分销功能占用，
-- 本迁移实际使用 385（工单回复）/387（工单列表）。
-- ============================================================

INSERT IGNORE INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000385, 0, '工单回复', 'post.admin/tickets/{id}/reply', 3, '', '', 173, NOW(), NOW()),
(21000000000000387, 0, '工单列表查看', 'get.admin/tickets', 3, '', '', 172, NOW(), NOW());

-- 超级管理员角色关联新增权限（幂等）
INSERT IGNORE INTO `erik_admin_role_permission` (`role_id`, `permission_id`) VALUES
(10000000000000001, 21000000000000385),
(10000000000000001, 21000000000000387);
