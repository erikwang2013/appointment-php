-- ============================================================
-- 预约服务系统 — 统一数据库安装脚本
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 包含: 管理后台 + 业务服务 全部表结构、权限数据、演示数据
-- 表前缀: erik_
-- 主键: BIGINT 非自增，由 snowflake-php 应用层生成
-- 字符集: utf8mb4 / utf8mb4_unicode_ci
-- 存储引擎: InnoDB
-- ============================================================



-- ============================================================
-- [2026_05_16_000000_init_tables.sql]
-- ============================================================
-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 初始化管理后台核心数据表
-- 注意: 主键 id 使用 BIGINT 非自增，由 snowflake-php 在应用层生成
-- ============================================================

-- ============================================================
-- 管理用户表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_admin_user` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码（bcrypt哈希）',
    `real_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
    `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像URL',
    `email` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '邮箱（加密存储）',
    `phone` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '手机号（加密存储）',
    `id_card` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '身份证号（加密存储）',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
    `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` DATETIME DEFAULT NULL COMMENT '软删除标记',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    KEY `idx_status` (`status`),
    KEY `idx_deleted_at` (`deleted_at`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理用户表';

-- ============================================================
-- 角色表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_admin_role` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `slug` VARCHAR(50) NOT NULL COMMENT '角色标识，用于权限判断',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '角色描述',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- ============================================================
-- 权限表（菜单/按钮/接口）
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_admin_permission` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级权限ID，0表示顶级',
    `name` VARCHAR(50) NOT NULL COMMENT '权限名称',
    `slug` VARCHAR(100) NOT NULL COMMENT '权限标识，格式: 模块.操作（如 user.create）',
    `type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型: 1=菜单 2=按钮 3=API接口',
    `icon` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '菜单图标（仅type=1时使用）',
    `path` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '前端路由路径（仅type=1时使用）',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_sort` (`sort`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';

-- ============================================================
-- 用户角色关联表（多对多中间表）
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_admin_user_role` (
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`user_id`, `role_id`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- ============================================================
-- 角色权限关联表（多对多中间表）
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_admin_role_permission` (
    `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
    `permission_id` BIGINT UNSIGNED NOT NULL COMMENT '权限ID',    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

-- ============================================================
-- 系统配置表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_system_config` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `group` VARCHAR(50) NOT NULL DEFAULT 'default' COMMENT '配置分组标识',
    `key` VARCHAR(100) NOT NULL COMMENT '配置键名',
    `value` TEXT COMMENT '配置值',
    `type` VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '值类型: string|int|bool|json|array',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '配置项说明',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_key` (`group`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ============================================================
-- 操作日志表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_operation_log` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作用户ID',
    `action` VARCHAR(100) NOT NULL COMMENT '操作动作，如 admin.user.store',
    `method` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '请求方法: GET|POST|PUT|DELETE',
    `path` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '请求路径',
    `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作IP',
    `input` TEXT COMMENT '请求参数（敏感字段已脱敏）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- ============================================================
-- 插入默认管理员角色
-- ============================================================
INSERT INTO `erik_admin_role` (`id`, `name`, `slug`, `description`, `status`) VALUES
(10000000000000001, '超级管理员', 'super_admin', '系统超级管理员，拥有所有权限', 1);


-- ============================================================
-- [2026_05_20_000001_seed_permissions.sql]
-- ============================================================
-- ============================================================
-- 权限种子数据
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 初始化 RBAC 权限树和角色-权限关联
-- 超级管理员 (super_admin) 自动获得所有权限
-- ============================================================

-- 菜单权限 (type=1)
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000001, 0, '仪表盘',    'dashboard',     1, 'dashboard', '/dashboard',        1, NOW(), NOW()),
(21000000000000002, 0, '用户管理',  'user',           1, 'people',    '/admin/user',        2, NOW(), NOW()),
(21000000000000003, 0, '角色管理',  'role',           1, 'shield',    '/admin/role',        3, NOW(), NOW()),
(21000000000000004, 0, '权限管理',  'permission',     1, 'lock',      '/admin/permission',  4, NOW(), NOW()),
(21000000000000005, 0, '系统配置',  'config',         1, 'settings',  '/admin/config',      5, NOW(), NOW()),
(21000000000000006, 0, '操作日志',  'log',            1, 'article',   '/admin/log',         6, NOW(), NOW());

-- 按钮权限 (type=2)
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000011, 21000000000000002, '批量删除',     'batch.destroy', 2, '', '', 1, NOW(), NOW()),
(21000000000000012, 21000000000000002, '批量启用/禁用', 'batch.status', 2, '', '', 2, NOW(), NOW()),
(21000000000000013, 21000000000000002, '导入用户',     'import.users',  2, '', '', 3, NOW(), NOW()),
(21000000000000014, 21000000000000002, '导出Excel',     'export.excel',  2, '', '', 4, NOW(), NOW()),
(21000000000000015, 21000000000000002, '导出PDF',       'export.pdf',    2, '', '', 5, NOW(), NOW()),
(21000000000000016, 21000000000000002, '文件上传',     'upload',         2, '', '', 6, NOW(), NOW());

-- API 权限 (type=3) — 仪表盘
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000021, 21000000000000001, '查看仪表盘',   'get.admin/dashboard', 3, '', '', 1, NOW(), NOW());

-- API 权限 — 用户管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000031, 21000000000000002, '查看用户',     'get.admin/user',             3, '', '', 1, NOW(), NOW()),
(21000000000000032, 21000000000000002, '创建用户',     'post.admin/user',            3, '', '', 2, NOW(), NOW()),
(21000000000000033, 21000000000000002, '更新用户',     'put.admin/user',             3, '', '', 3, NOW(), NOW()),
(21000000000000034, 21000000000000002, '删除用户',     'delete.admin/user',          3, '', '', 4, NOW(), NOW()),
(21000000000000035, 21000000000000002, '批量删除用户', 'post.admin/user/batch/destroy', 3, '', '', 5, NOW(), NOW()),
(21000000000000036, 21000000000000002, '批量启禁用',   'post.admin/user/batch/status',  3, '', '', 6, NOW(), NOW());

-- API 权限 — 角色管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000041, 21000000000000003, '查看角色', 'get.admin/role',    3, '', '', 1, NOW(), NOW()),
(21000000000000042, 21000000000000003, '创建角色', 'post.admin/role',   3, '', '', 2, NOW(), NOW()),
(21000000000000043, 21000000000000003, '更新角色', 'put.admin/role',    3, '', '', 3, NOW(), NOW()),
(21000000000000044, 21000000000000003, '删除角色', 'delete.admin/role', 3, '', '', 4, NOW(), NOW());

-- API 权限 — 权限管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000051, 21000000000000004, '查看权限', 'get.admin/permission',    3, '', '', 1, NOW(), NOW()),
(21000000000000052, 21000000000000004, '创建权限', 'post.admin/permission',   3, '', '', 2, NOW(), NOW()),
(21000000000000053, 21000000000000004, '更新权限', 'put.admin/permission',    3, '', '', 3, NOW(), NOW()),
(21000000000000054, 21000000000000004, '删除权限', 'delete.admin/permission', 3, '', '', 4, NOW(), NOW());

-- API 权限 — 系统配置
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000061, 21000000000000005, '查看配置', 'get.admin/config',    3, '', '', 1, NOW(), NOW()),
(21000000000000062, 21000000000000005, '创建配置', 'post.admin/config',   3, '', '', 2, NOW(), NOW()),
(21000000000000063, 21000000000000005, '更新配置', 'put.admin/config',    3, '', '', 3, NOW(), NOW()),
(21000000000000064, 21000000000000005, '删除配置', 'delete.admin/config', 3, '', '', 4, NOW(), NOW());

-- API 权限 — 操作日志
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000071, 21000000000000006, '查看日志', 'get.admin/log', 3, '', '', 1, NOW(), NOW());

-- API 权限 — 个人中心
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000081, 0, '个人中心-更新信息', 'put.admin/profile',         3, '', '', 1, NOW(), NOW()),
(21000000000000082, 0, '个人中心-修改密码', 'put.admin/profile/password', 3, '', '', 2, NOW(), NOW()),
(21000000000000083, 0, '个人中心-登出',     'post.admin/profile/logout',  3, '', '', 3, NOW(), NOW());

-- API 权限 — 导出
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000091, 0, '导出Excel', 'post.admin/export/excel', 3, '', '', 1, NOW(), NOW()),
(21000000000000092, 0, '导出PDF',   'post.admin/export/pdf',   3, '', '', 2, NOW(), NOW());

-- API 权限 — 导入
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000093, 0, '导入用户', 'post.admin/import/users', 3, '', '', 1, NOW(), NOW());

-- API 权限 — 上传
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000094, 0, '文件上传', 'post.admin/upload', 3, '', '', 1, NOW(), NOW());

-- ============================================================
-- 店长子账号菜单 + API 权限
-- ============================================================

-- 菜单: 店长工作台
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000101, 0, '店长工作台', 'store_manager', 1, 'store', '/store-manager', 7, NOW(), NOW());

-- API 权限: 店长仪表盘
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000102, 21000000000000101, '店长仪表盘', 'get.admin/store-manager/dashboard', 3, '', '', 1, NOW(), NOW());

-- API 权限: 订单管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000103, 21000000000000101, '订单查询', 'get.admin/appointment-orders', 3, '', '', 2, NOW(), NOW()),
(21000000000000104, 21000000000000101, '订单更新', 'put.admin/appointment-orders', 3, '', '', 3, NOW(), NOW());

-- API 权限: 排班管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000105, 21000000000000101, '排班管理', 'get.admin/technicians/schedules', 3, '', '', 4, NOW(), NOW()),
(21000000000000106, 21000000000000101, '排班设置', 'post.admin/technicians/schedules', 3, '', '', 5, NOW(), NOW());

-- API 权限: 优惠券管理
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000107, 21000000000000101, '优惠券查询', 'get.admin/coupons', 3, '', '', 6, NOW(), NOW()),
(21000000000000108, 21000000000000101, '优惠券创建', 'post.admin/coupons', 3, '', '', 7, NOW(), NOW());

-- API 权限: Store manager CRUD
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000109, 0, '店长查询', 'get.admin/store-managers', 3, '', '', 1, NOW(), NOW()),
(21000000000000110, 0, '店长创建', 'post.admin/store-managers', 3, '', '', 2, NOW(), NOW()),
(21000000000000111, 0, '店长更新', 'put.admin/store-managers', 3, '', '', 3, NOW(), NOW()),
(21000000000000112, 0, '店长删除', 'delete.admin/store-managers', 3, '', '', 4, NOW(), NOW());

-- API 权限: 培训课程
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000113, 0, '培训课程查询', 'get.admin/training-courses', 3, '', '', 1, NOW(), NOW()),
(21000000000000114, 0, '培训课程创建', 'post.admin/training-courses', 3, '', '', 2, NOW(), NOW()),
(21000000000000115, 0, '培训课程更新', 'put.admin/training-courses', 3, '', '', 3, NOW(), NOW()),
(21000000000000116, 0, '培训课程删除', 'delete.admin/training-courses', 3, '', '', 4, NOW(), NOW());

-- API 权限: 调度任务
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000117, 0, '调度任务执行', 'post.admin/scheduled-tasks', 3, '', '', 1, NOW(), NOW());

-- API 权限: 客户画像
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000118, 0, '客户画像查询', 'get.admin/customer-profiles', 3, '', '', 1, NOW(), NOW());

-- API 权限: 批量消息
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000119, 0, '批量消息发送', 'post.admin/batch-messages', 3, '', '', 1, NOW(), NOW());

-- API 权限: 退款审批
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000120, 0, '退款审批列表', 'get.admin/refund-workflows', 3, '', '', 1, NOW(), NOW()),
(21000000000000121, 0, '退款审批通过', 'post.admin/refund-workflows/approve', 3, '', '', 2, NOW(), NOW()),
(21000000000000122, 0, '退款审批驳回', 'post.admin/refund-workflows/reject', 3, '', '', 3, NOW(), NOW());

-- API 权限: 技师等级
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000123, 0, '技师等级查询', 'get.admin/technician-tiers', 3, '', '', 1, NOW(), NOW()),
(21000000000000124, 0, '技师等级更新', 'put.admin/technician-tiers', 3, '', '', 2, NOW(), NOW());

-- ============================================================
-- 超级管理员角色 (ID=10000000000000001) 关联所有权限
-- ============================================================
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);


-- ============================================================
-- [2026_05_21_000002_add_source_to_operation_log.sql]
-- ============================================================
-- ============================================================
-- 操作日志表增加操作来源端字段
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- ============================================================

ALTER TABLE `erik_operation_log`
ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT 'web' COMMENT '操作来源端: ipados|macos|windows|linux|ios|android|harmonyos|web' AFTER `ip`;

ALTER TABLE `erik_operation_log`
ADD KEY `idx_source` (`source`);


-- ============================================================
-- [2026_05_26_000003_appointment_business_tables.sql]
-- ============================================================
-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 预约服务系统业务数据表
-- 注意: 主键 id 使用 BIGINT 非自增，由 erikwang2013/snowflake-php 在应用层生成
-- 敏感字段使用 erikwang2013/encryptable trait 自动加解密
-- 表前缀: erik_
-- ============================================================

-- ============================================================
-- 1. 用户与身份域
-- ============================================================

-- 统一用户表
CREATE TABLE IF NOT EXISTS `erik_user` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `phone` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '手机号（明文存储）',
    `password` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '密码（bcrypt哈希）',
    `wx_openid` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '微信OpenID（明文存储）',
    `wx_unionid` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '微信UnionID（加密存储）',
    `avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '头像URL',
    `nickname` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '用户昵称',
    `real_name` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '真实姓名（加密存储）',
    `gender` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别: 0=未知 1=男 2=女',
    `user_type` VARCHAR(20) NOT NULL DEFAULT 'customer' COMMENT '用户类型: customer=客户 technician=技师。技师同时拥有客户功能',
    `active_role` VARCHAR(20) NOT NULL DEFAULT 'customer' COMMENT '当前活跃身份: customer=客户 technician=技师',
    `referral_code` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '个人推荐码',
    `referrer_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '推荐人用户ID',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
    `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` DATETIME DEFAULT NULL COMMENT '软删除标记',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_phone` (`phone`),
    KEY `idx_wx_openid` (`wx_openid`(191)),
    KEY `idx_wx_unionid` (`wx_unionid`(191)),
    KEY `idx_user_type` (`user_type`),
    KEY `idx_status` (`status`),
    KEY `idx_referrer_id` (`referrer_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='统一用户表';

-- 用户收货地址表
CREATE TABLE IF NOT EXISTS `erik_user_address` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `contact_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_phone` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '联系人电话（加密存储）',
    `province` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '省份',
    `city` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '城市',
    `district` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '区/县',
    `detail` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '详细地址',
    `lat` DECIMAL(10,7) DEFAULT NULL COMMENT '纬度',
    `lng` DECIMAL(10,7) DEFAULT NULL COMMENT '经度',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认地址: 0=否 1=是',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户收货地址表';

-- 用户收藏表
CREATE TABLE IF NOT EXISTS `erik_user_favorite` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `target_type` VARCHAR(20) NOT NULL DEFAULT 'service' COMMENT '收藏类型: service=服务 technician=技师',
    `target_id` BIGINT UNSIGNED NOT NULL COMMENT '收藏目标ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_target` (`user_id`, `target_type`, `target_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户收藏表';

-- ============================================================
-- 2. 技师域
-- ============================================================

-- 技师档案表
CREATE TABLE IF NOT EXISTS `erik_technician_profile` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '关联用户ID',
    `real_name` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '真实姓名（加密存储）',
    `gender` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别: 0=未知 1=男 2=女',
    `id_card` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '身份证号码（加密存储）',
    `id_card_front` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '身份证正面照片URL',
    `id_card_back` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '身份证反面照片URL',
    `avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '技师头像URL',
    `intro` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '个人简介',
    `cover_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图URL',
    `video_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '个人视频URL',
    `certificates` JSON COMMENT '资质证书照片URL列表',
    `rating` DECIMAL(2,1) NOT NULL DEFAULT 5.0 COMMENT '评价星级（1.0-5.0）',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计服务订单数',
    `favorite_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '被收藏数',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '审核状态: pending=待审核 approved=已通过 rejected=已驳回',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `audited_at` DATETIME DEFAULT NULL COMMENT '审核时间',
    `deleted_at` DATETIME DEFAULT NULL COMMENT '软删除标记',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_rating` (`rating`),
    KEY `idx_order_count` (`order_count`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师档案表';

-- 技师排班表
CREATE TABLE IF NOT EXISTS `erik_technician_schedule` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `date` DATE NOT NULL COMMENT '排班日期',
    `time_slots` JSON NOT NULL COMMENT '时间段设置，如[{"start":"09:00","end":"12:00"},{"start":"14:00","end":"18:00"}]',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=休息 1=可预约',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tech_date` (`technician_id`, `date`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师排班表';

-- 技师可服务项目关联表
CREATE TABLE IF NOT EXISTS `erik_technician_service` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `service_id` BIGINT UNSIGNED NOT NULL COMMENT '服务项目ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tech_service` (`technician_id`, `service_id`),
    KEY `idx_service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师可服务项目关联表';

-- 技师收益流水表
CREATE TABLE IF NOT EXISTS `erik_technician_earnings` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `type` VARCHAR(30) NOT NULL DEFAULT 'commission' COMMENT '收益类型: commission=服务提成 bonus=奖金 penalty=罚款 subsidy=补贴 attendance=考勤奖励',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '金额（元）',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '收益说明',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending=待结算 settled=已结算 withdrawn=已提现',
    `settled_at` DATETIME DEFAULT NULL COMMENT '结算时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师收益流水表';

-- 技师提现记录表
CREATE TABLE IF NOT EXISTS `erik_technician_withdrawal` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `withdrawal_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '提现单号',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '提现金额（元）',
    `actual_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际到账金额（元）',
    `commission_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '手续费（元）',
    `account_type` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '提现账户类型: wechat=微信零钱',
    `account_name` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '账户名（加密存储）',
    `account_no` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '账号（加密存储）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending=待审核 approved=已通过 rejected=已驳回 completed=已完成',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `audited_at` DATETIME DEFAULT NULL COMMENT '审核时间',
    `completed_at` DATETIME DEFAULT NULL COMMENT '到账时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_withdrawal_no` (`withdrawal_no`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_tech_status` (`technician_id`, `status`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师提现记录表';

-- 技师考勤表
CREATE TABLE IF NOT EXISTS `erik_technician_attendance` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `date` DATE NOT NULL COMMENT '考勤日期',
    `check_in_at` DATETIME DEFAULT NULL COMMENT '签到时间',
    `check_out_at` DATETIME DEFAULT NULL COMMENT '签退时间',
    `clean_photo` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '卫生照片URL',
    `status` VARCHAR(20) NOT NULL DEFAULT 'normal' COMMENT '考勤状态: normal=正常 late=迟到 early=早退 absent=缺勤',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师考勤表';

-- 技师会员档案表（技师对顾客的记录）
CREATE TABLE IF NOT EXISTS `erik_technician_member_note` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '顾客用户ID',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `content` TEXT NOT NULL COMMENT '档案内容（加密存储）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师会员档案表';

-- ============================================================
-- 3. 服务与产品域
-- ============================================================

-- 服务分类表
CREATE TABLE IF NOT EXISTS `erik_service_category` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分类名称',
    `icon` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分类图标URL',
    `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级分类ID，0为顶级分类',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='服务分类表';

-- 服务项目表
CREATE TABLE IF NOT EXISTS `erik_service` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `category_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '服务名称',
    `description` TEXT COMMENT '服务描述',
    `cover_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图URL',
    `images` JSON COMMENT '服务图片列表',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '销售价（元）',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价（元）',
    `duration` INT UNSIGNED NOT NULL DEFAULT 60 COMMENT '服务时长（分钟）',
    `specs` JSON COMMENT '规格配置，如[{"name":"标准","price":100,"duration":60}]',
    `sales_volume` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '销量',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` DATETIME DEFAULT NULL COMMENT '软删除标记',
    PRIMARY KEY (`id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`),
    KEY `idx_sales_volume` (`sales_volume`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='服务项目表';

-- 产品表
CREATE TABLE IF NOT EXISTS `erik_product` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `category_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '产品分类ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '产品名称',
    `cover_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图URL',
    `images` JSON COMMENT '产品图片列表',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '销售价（元）',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价（元）',
    `stock` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '库存数量',
    `sales_volume` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '销量',
    `type` VARCHAR(20) NOT NULL DEFAULT 'physical' COMMENT '产品类型: physical=实物 virtual=虚拟卡券',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` DATETIME DEFAULT NULL COMMENT '软删除标记',
    PRIMARY KEY (`id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`),
    KEY `idx_type` (`type`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='产品表';

-- 门店表
CREATE TABLE IF NOT EXISTS `erik_store` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '门店名称',
    `address` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '门店地址',
    `lat` DECIMAL(10,7) NOT NULL DEFAULT 0.0000000 COMMENT '纬度',
    `lng` DECIMAL(10,7) NOT NULL DEFAULT 0.0000000 COMMENT '经度',
    `phone` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '联系电话（加密存储）',
    `business_hours` JSON COMMENT '营业时间，如{"mon":{"start":"09:00","end":"21:00"}}',
    `images` JSON COMMENT '门店图片列表',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=关闭 1=营业中',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` DATETIME DEFAULT NULL COMMENT '软删除标记',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_lat_lng` (`lat`, `lng`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='门店表';

-- ============================================================
-- 4. 订单域
-- ============================================================

-- 订单主表
CREATE TABLE IF NOT EXISTS `erik_order` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '订单编号（展示用）',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '技师档案ID',
    `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '门店ID',
    `order_type` VARCHAR(20) NOT NULL DEFAULT 'appointment' COMMENT '订单类型: appointment=预约服务 product=产品购买',
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单总金额（元）',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额（元）',
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额（元）',
    `coupon_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用的优惠券ID，0 表示未使用',
    `user_coupon_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户优惠券记录ID，0 表示未使用',
    `member_card_usage_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '次卡使用记录ID，0 表示未使用',
    `service_time` DATETIME DEFAULT NULL COMMENT '预约服务时间',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '订单状态: pending=待支付 paid=已支付 confirmed=已确认 serving=服务中 completed=已完成 cancelled=已取消 refunding=退款中 refunded=已退款',
    `cancel_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '取消原因',
    `cancel_at` DATETIME DEFAULT NULL COMMENT '取消时间',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用户备注',
    `service_start_at` DATETIME DEFAULT NULL COMMENT '服务开始时间',
    `service_end_at` DATETIME DEFAULT NULL COMMENT '服务结束时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_store_id` (`store_id`),
    KEY `idx_status` (`status`),
    KEY `idx_order_type` (`order_type`),
    KEY `idx_service_time` (`service_time`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_status` (`user_id`, `status`),
    KEY `idx_tech_status` (`technician_id`, `status`),
    KEY `idx_tech_service_time` (`technician_id`, `service_time`),
    KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单主表';

-- 订单明细表
CREATE TABLE IF NOT EXISTS `erik_order_item` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `target_type` VARCHAR(20) NOT NULL DEFAULT 'service' COMMENT '项目类型: service=服务 product=产品',
    `target_id` BIGINT UNSIGNED NOT NULL COMMENT '项目ID（服务ID或产品ID）',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '项目名称（冗余，防止改名后历史订单显示异常）',
    `cover_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图（冗余）',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价（元）',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `spec_info` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '规格信息',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单明细表';

-- 支付记录表
CREATE TABLE IF NOT EXISTS `erik_order_payment` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `payment_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '支付单号',
    `pay_type` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '支付方式: wechat=微信支付',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方交易号（微信支付流水号）',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '支付金额（元）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '支付状态: pending=待支付 success=成功 failed=失败 closed=已关闭',
    `paid_at` DATETIME DEFAULT NULL COMMENT '支付完成时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payment_no` (`payment_no`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付记录表';

-- 退款记录表
CREATE TABLE IF NOT EXISTS `erik_order_refund` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `payment_id` BIGINT UNSIGNED NOT NULL COMMENT '支付记录ID',
    `refund_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '退款单号',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额（元）',
    `ratio` DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT '退款比例（如0.90表示退90%）',
    `reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '退款状态: pending=处理中 success=成功 failed=失败',
    `refunded_at` DATETIME DEFAULT NULL COMMENT '退款完成时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_refund_no` (`refund_no`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='退款记录表';

-- 用户钱包表（储值支付）
CREATE TABLE IF NOT EXISTS `erik_user_wallet` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '可用余额（元）',
    `total_recharge` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计充值金额（元）',
    `total_consume` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计消费金额（元）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户钱包表';

-- 钱包充值单表
CREATE TABLE IF NOT EXISTS `erik_wallet_recharge` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '充值单号（R + 时间戳 + 4位随机数，与订单号体系区分）',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额（元）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending=待支付 paid=已支付 refunded=已退款 failed=失败',
    `pay_channel` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '支付渠道: wechat=微信支付',
    `paid_at` DATETIME DEFAULT NULL COMMENT '支付完成时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='钱包充值单表';

-- 钱包流水表
CREATE TABLE IF NOT EXISTS `erik_wallet_txn` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'recharge' COMMENT '流水类型: recharge=充值 consume=消费 refund=退款（金额一律正数，方向由 type 表达）',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动金额（元，正数）',
    `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动后余额（元）',
    `order_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联订单ID（消费/退款）',
    `recharge_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联充值单ID（充值）',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='钱包流水表';

-- 服务评价表
CREATE TABLE IF NOT EXISTS `erik_order_review` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `service_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '被评价服务ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '评价用户ID',
    `technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '被评价技师ID',
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '评分（1-5星）',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '评价内容',
    `images` JSON COMMENT '评价图片列表',
    `reply` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '技师回复',
    `replied_at` DATETIME DEFAULT NULL COMMENT '回复时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=隐藏 1=显示',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_id` (`order_id`),
    KEY `idx_service_id` (`service_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='服务评价表';

-- 核销记录表
CREATE TABLE IF NOT EXISTS `erik_order_verification` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `code` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '核销二维码值',
    `verified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '核销人ID（技师或用户自核销）',
    `verify_type` VARCHAR(20) NOT NULL DEFAULT 'scan' COMMENT '核销方式: scan=扫码 self=自行核销',
    `location` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '核销地点',
    `verified_at` DATETIME DEFAULT NULL COMMENT '核销时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='核销记录表';

-- ============================================================
-- 5. 营销域
-- ============================================================

-- 优惠券定义表
CREATE TABLE IF NOT EXISTS `erik_coupon` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '优惠券名称',
    `type` VARCHAR(20) NOT NULL DEFAULT 'fixed' COMMENT '优惠类型: fixed=固定金额 percent=百分比',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额/折扣率',
    `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低消费金额（元），0=无门槛',
    `total_qty` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放总数',
    `remain_qty` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '剩余数量',
    `start_at` DATETIME DEFAULT NULL COMMENT '有效期开始',
    `end_at` DATETIME DEFAULT NULL COMMENT '有效期结束',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
    `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建管理员ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_start_end` (`start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠券定义表';

-- 用户优惠券表
CREATE TABLE IF NOT EXISTS `erik_user_coupon` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `coupon_id` BIGINT UNSIGNED NOT NULL COMMENT '优惠券ID',
    `status` VARCHAR(20) NOT NULL DEFAULT 'available' COMMENT '状态: available=可用 used=已使用 expired=已过期',
    `used_at` DATETIME DEFAULT NULL COMMENT '使用时间',
    `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '领取时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_coupon` (`user_id`, `coupon_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_coupon_id` (`coupon_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户优惠券表';

-- 会员卡定义表
CREATE TABLE IF NOT EXISTS `erik_member_card` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '会员卡名称',
    `type` VARCHAR(20) NOT NULL DEFAULT 'month' COMMENT '会员卡类型: month=普通月卡 vip=VIP年卡 times=次卡',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '售价（元）',
    `duration_days` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效天数',
    `total_times` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总次数（次卡使用）',
    `services` JSON COMMENT '次卡包含的服务项目，如[{"service_id":1,"times":3}]',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员卡定义表';

-- 用户会员卡表
CREATE TABLE IF NOT EXISTS `erik_user_member_card` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `card_id` BIGINT UNSIGNED NOT NULL COMMENT '会员卡定义ID',
    `start_at` DATETIME NOT NULL COMMENT '生效时间',
    `end_at` DATETIME DEFAULT NULL COMMENT '到期时间',
    `total_times` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总次数',
    `used_times` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已使用次数',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT '状态: active=有效 expired=已过期 used_up=次数用完',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_card_id` (`card_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户会员卡表';

-- 次卡使用记录表
CREATE TABLE IF NOT EXISTS `erik_member_card_usage` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_card_id` BIGINT UNSIGNED NOT NULL COMMENT '用户会员卡ID',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `service_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用的服务项目ID',
    `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '使用时间',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT '状态: active=有效 cancelled=已撤销（退款/取消归还）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_user_card_id` (`user_card_id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='次卡使用记录表';

-- 积分流水表
CREATE TABLE IF NOT EXISTS `erik_user_points` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'earn' COMMENT '类型: earn=获取 use=使用 expire=过期',
    `points` INT NOT NULL DEFAULT 0 COMMENT '积分数量（正数获取，负数使用）',
    `balance` INT NOT NULL DEFAULT 0 COMMENT '积分余额',
    `source` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '来源: order=消费 referral=推荐 gift_card=礼品卡兑换 admin=后台调整',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '说明',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分流水表';

-- 礼品卡表
CREATE TABLE IF NOT EXISTS `erik_gift_card` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '礼品卡兑换码',
    `type` VARCHAR(20) NOT NULL DEFAULT 'cash' COMMENT '类型: cash=现金礼品卡 gift=实物礼品',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '现金金额（元）或礼品价值',
    `gift_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '礼品名称（type=gift时有效）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'unused' COMMENT '状态: unused=未使用 used=已使用 expired=已过期',
    `used_by` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用用户ID',
    `used_at` DATETIME DEFAULT NULL COMMENT '使用时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='礼品卡表';

-- 用户推广记录表
CREATE TABLE IF NOT EXISTS `erik_user_referral` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `referrer_id` BIGINT UNSIGNED NOT NULL COMMENT '推荐人用户ID',
    `referred_user_id` BIGINT UNSIGNED NOT NULL COMMENT '被推荐用户ID',
    `reward_type` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '奖励类型: coupon=优惠券 points=积分',
    `reward_amount` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '奖励详情',
    `rewarded_at` DATETIME DEFAULT NULL COMMENT '发放奖励时间',
    `registered_at` DATETIME NOT NULL COMMENT '被推荐人注册时间',
    `first_order_at` DATETIME DEFAULT NULL COMMENT '被推荐人首单时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_referrer_id` (`referrer_id`),
    KEY `idx_referred_user_id` (`referred_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户推广记录表';

-- ============================================================
-- 6. 内容与通知域
-- ============================================================

-- 轮播图表
CREATE TABLE IF NOT EXISTS `erik_banner` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `position` VARCHAR(50) NOT NULL DEFAULT 'home' COMMENT '展示位置: home=首页',
    `image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '轮播图URL',
    `jump_type` VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT '跳转类型: url=网页 detail=详情页 none=无操作',
    `jump_value` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '跳转目标值（URL或服务ID）',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_position` (`position`),
    KEY `idx_sort` (`sort`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='轮播图表';

-- 公告表
CREATE TABLE IF NOT EXISTS `erik_announcement` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '公告标题',
    `content` TEXT NOT NULL COMMENT '公告内容',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=隐藏 1=显示',
    `published_at` DATETIME DEFAULT NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';

-- 平台协议表
CREATE TABLE IF NOT EXISTS `erik_platform_agreement` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '协议类型: user_agreement=用户协议 privacy_policy=隐私协议 service_agreement=服务协议',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '协议标题',
    `content` LONGTEXT NOT NULL COMMENT '协议内容（富文本）',
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0' COMMENT '版本号',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=草稿 1=发布',
    `published_at` DATETIME DEFAULT NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='平台协议表';

-- 常见问题表
CREATE TABLE IF NOT EXISTS `erik_faq` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '问题标题',
    `content` TEXT NOT NULL COMMENT '问题答案',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=隐藏 1=显示',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_sort` (`sort`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='常见问题表';

-- 意见反馈表
CREATE TABLE IF NOT EXISTS `erik_feedback` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `content` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT '反馈内容',
    `images` JSON COMMENT '反馈图片列表',
    `handler_reply` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT '客服回复内容',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '处理状态: pending=待处理 handled=已处理',
    `handled_by` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理管理员ID',
    `handled_at` DATETIME DEFAULT NULL COMMENT '处理时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='意见反馈表';

-- 朋友圈动态表
CREATE TABLE IF NOT EXISTS `erik_moment` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `content` TEXT NOT NULL COMMENT '动态内容',
    `images` JSON COMMENT '动态图片列表',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核状态: 0=待审核 1=已发布 2=已驳回',
    `published_at` DATETIME DEFAULT NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='朋友圈动态表';

-- 消息通知表
CREATE TABLE IF NOT EXISTS `erik_notification` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '接收用户ID，0=全部用户',
    `type` VARCHAR(20) NOT NULL DEFAULT 'system' COMMENT '通知类型: system=系统通知 order=订单通知',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '通知标题',
    `content` TEXT NOT NULL COMMENT '通知内容',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `is_read` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已读: 0=未读 1=已读',
    `read_at` DATETIME DEFAULT NULL COMMENT '阅读时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_user_read` (`user_id`, `is_read`),
    KEY `idx_type` (`type`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息通知表';

-- ============================================================
-- 7. 财务域（管理后台使用）
-- ============================================================

-- 收支流水表
CREATE TABLE IF NOT EXISTS `erik_finance_transaction` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `finance_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '财务单号',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '财务类型: order_payment=订单支付 order_refund=订单退款 technician_commission=技师佣金 technician_withdrawal=技师提现 platform_revenue=平台收入',
    `direction` VARCHAR(10) NOT NULL DEFAULT 'income' COMMENT '收支方向: income=收入 expense=支出',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '提交金额（元）',
    `actual_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际落地金额（元）',
    `commission` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '佣金（元）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '交易状态: pending=处理中 success=成功 failed=失败',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_finance_no` (`finance_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_type` (`type`),
    KEY `idx_direction` (`direction`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收支流水表';

-- 技师佣金配置表
CREATE TABLE IF NOT EXISTS `erik_technician_commission_config` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `commission_rate` DECIMAL(4,2) NOT NULL DEFAULT 0.00 COMMENT '佣金率（百分比，如30.00表示30%）',
    `settlement_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly' COMMENT '结算周期: monthly=每月',
    `penalty_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '罚款金额（元）',
    `bonus_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '奖金金额（元）',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师佣金配置表';

-- 提现账号表
CREATE TABLE IF NOT EXISTS `erik_withdrawal_account` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '账号类型: wechat=微信零钱',
    `account_name` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '账户名（加密存储）',
    `account_no` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '账号（加密存储）',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现账号表';

-- 提现限制配置表
CREATE TABLE IF NOT EXISTS `erik_withdrawal_config` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 10.00 COMMENT '最低提现金额（元）',
    `reserve_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低保留金额（元）',
    `round_to_hundred` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否只能整百提现: 0=否 1=是',
    `withdrawal_day` TINYINT UNSIGNED NOT NULL DEFAULT 20 COMMENT '每月可提现日（1-28）',
    `arrival_days` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '到账工作日天数（T+N）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现限制配置表';

-- ============================================================
-- 初始数据: 提现默认配置
-- ============================================================

INSERT INTO `erik_withdrawal_config` (`id`, `min_amount`, `reserve_amount`, `round_to_hundred`, `withdrawal_day`, `arrival_days`) VALUES
(10000000000000001, 10.00, 0.00, 1, 20, 1);


-- ============================================================
-- [2026_05_26_000004_admin_extensions.sql]
-- ============================================================
-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 管理后台扩展 — 店长子账号、培训模块、技师等级、提现审批链
-- ============================================================

-- ============================================================
-- 1. erik_admin_user 增加 store_id（店长子账号所属门店，0=平台管理员）
-- ============================================================
ALTER TABLE `erik_admin_user`
ADD COLUMN `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '店长所管门店ID，0=平台管理员'
AFTER `deleted_at`;

ALTER TABLE `erik_admin_user`
ADD INDEX `idx_store_id` (`store_id`);

-- ============================================================
-- 2. 培训课程表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_training_course` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '课程标题',
    `type` VARCHAR(20) NOT NULL DEFAULT 'video' COMMENT '课程类型: video=视频 article=文章',
    `url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '视频/文章链接',
    `content` TEXT COMMENT '课程内容（富文本）',
    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '课程时长（分钟）',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训课程表';

-- ============================================================
-- 3. 培训学习进度表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_training_progress` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师档案ID',
    `course_id` BIGINT UNSIGNED NOT NULL COMMENT '课程ID',
    `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '学习进度（0-100）',
    `completed_at` DATETIME DEFAULT NULL COMMENT '完成时间',
    `status` VARCHAR(20) NOT NULL DEFAULT 'learning' COMMENT '学习状态: learning=学习中 completed=已完成',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tech_course` (`technician_id`, `course_id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_course_id` (`course_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训学习进度表';

-- ============================================================
-- 4. erik_technician_withdrawal 增加多级审批链字段
-- ============================================================
ALTER TABLE `erik_technician_withdrawal`
ADD COLUMN `store_approved_at` DATETIME DEFAULT NULL COMMENT '店长审批时间'
AFTER `audit_remark`;

ALTER TABLE `erik_technician_withdrawal`
ADD COLUMN `finance_approved_at` DATETIME DEFAULT NULL COMMENT '财务审批时间'
AFTER `store_approved_at`;

ALTER TABLE `erik_technician_withdrawal`
ADD COLUMN `reject_reason` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '驳回原因'
AFTER `finance_approved_at`;

-- ============================================================
-- 5. 技师等级配置表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_technician_tier_config` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '等级名称: junior=初级 senior=高级 expert=专家',
    `slug` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '等级标识: junior/senior/expert',
    `min_orders` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最低接单数',
    `min_rating` DECIMAL(2,1) NOT NULL DEFAULT 0.0 COMMENT '最低评分',
    `commission_rate` DECIMAL(4,2) NOT NULL DEFAULT 0.00 COMMENT '佣金率（百分比，如30.00表示30%）',
    `price_multiplier` DECIMAL(4,2) NOT NULL DEFAULT 1.00 COMMENT '服务价格倍率',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师等级配置表';

-- 默认等级种子数据
INSERT INTO `erik_technician_tier_config` (`id`, `name`, `slug`, `min_orders`, `min_rating`, `commission_rate`, `price_multiplier`, `sort`) VALUES
(80000000000000001, '初级技师', 'junior', 0, 0.0, 30.00, 1.00, 1),
(80000000000000002, '高级技师', 'senior', 100, 4.0, 35.00, 1.20, 2),
(80000000000000003, '专家技师', 'expert', 500, 4.5, 40.00, 1.50, 3);


-- ============================================================
-- [2026_05_26_000005_operation_log_detail.sql]
-- ============================================================
-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 操作日志详情表 — 记录操作前后的数据快照与响应
-- ============================================================

-- ============================================================
-- 1. erik_operation_log_detail 操作日志详情表
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_operation_log_detail` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `log_id` BIGINT UNSIGNED NOT NULL COMMENT '关联操作日志ID',
    `snapshot_before` TEXT COMMENT '操作前数据快照（JSON）',
    `snapshot_after` TEXT COMMENT '操作后数据快照（JSON）',
    `response_body` TEXT COMMENT '响应内容（JSON）',    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_log_id` (`log_id`),
    KEY `idx_log_id` (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志详情表';


-- ============================================================
-- [2026_05_26_000005_third_party_config_seed.sql]
-- ============================================================
-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 第三方服务集成 — 微信支付、短信、地图、模板消息、对象存储配置种子数据
-- ============================================================

-- 微信支付配置 (wechat_pay)
INSERT IGNORE INTO `erik_system_config` (`id`, `group`, `key`, `value`, `type`, `description`) VALUES
(91000000000000001, 'wechat_pay', 'app_id', '', 'string', '微信支付 AppID'),
(91000000000000002, 'wechat_pay', 'mch_id', '', 'string', '微信支付商户号'),
(91000000000000003, 'wechat_pay', 'api_key', '', 'string', '微信支付 API 密钥'),
(91000000000000004, 'wechat_pay', 'notify_url', '', 'string', '微信支付回调通知地址'),
(91000000000000005, 'wechat_pay', 'cert_path', '', 'string', '微信支付 API 证书路径（apiclient_cert.pem）'),
(91000000000000006, 'wechat_pay', 'key_path', '', 'string', '微信支付 API 证书密钥路径（apiclient_key.pem）');

-- 短信配置 (sms)
INSERT IGNORE INTO `erik_system_config` (`id`, `group`, `key`, `value`, `type`, `description`) VALUES
(91000000000000007, 'sms', 'provider', 'aliyun', 'string', '短信服务商: aliyun=阿里云 tencent=腾讯云'),
(91000000000000008, 'sms', 'access_key', '', 'string', 'AccessKey（阿里云）/ SmsSdkAppId（腾讯云）'),
(91000000000000009, 'sms', 'secret_key', '', 'string', 'SecretKey'),
(91000000000000010, 'sms', 'sign_name', '', 'string', '短信签名'),
(91000000000000011, 'sms', 'template_code', '', 'string', '默认验证码模板ID');

-- 地图服务配置 (map_service)
INSERT IGNORE INTO `erik_system_config` (`id`, `group`, `key`, `value`, `type`, `description`) VALUES
(91000000000000012, 'map_service', 'provider', 'amap', 'string', '地图服务商: amap=高德 tencent=腾讯'),
(91000000000000013, 'map_service', 'api_key', '', 'string', '地图 API Key');

-- 微信应用配置 (wechat_app) — 用于模板消息、小程序登录等
INSERT IGNORE INTO `erik_system_config` (`id`, `group`, `key`, `value`, `type`, `description`) VALUES
(91000000000000014, 'wechat_app', 'app_id', '', 'string', '微信公众号/小程序 AppID'),
(91000000000000015, 'wechat_app', 'app_secret', '', 'string', '微信公众号/小程序 AppSecret'),
(91000000000000016, 'wechat_app', 'template_ids', '{}', 'json', '微信模板消息 ID 映射（JSON 格式，keys: order_confirm, service_reminder, refund_notify, technician_assigned）');

-- 对象存储配置 (storage)
INSERT IGNORE INTO `erik_system_config` (`id`, `group`, `key`, `value`, `type`, `description`) VALUES
(91000000000000017, 'storage', 'provider', 'local', 'string', '存储服务商: local=本地 oss=阿里云OSS cos=腾讯云COS'),
(91000000000000018, 'storage', 'access_key', '', 'string', 'AccessKey（OSS）/ SecretId（COS）'),
(91000000000000019, 'storage', 'secret_key', '', 'string', 'SecretKey'),
(91000000000000020, 'storage', 'bucket', '', 'string', '存储桶名称'),
(91000000000000021, 'storage', 'endpoint', '', 'string', '访问端点（如 oss-cn-hangzhou.aliyuncs.com）'),
(91000000000000022, 'storage', 'cdn_domain', '', 'string', 'CDN 加速域名（如 cdn.example.com）');


-- ============================================================
-- [2026_05_27_000006_demo_seeds.sql]
-- ============================================================
-- ============================================================
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
-- 迁移: 演示数据种子
-- 为预约服务系统填充真实可用的演示数据，便于开发测试和产品演示
-- ============================================================

-- ============================================================
-- 1. 服务分类
-- ============================================================
INSERT IGNORE INTO `erik_service_category` (`id`, `name`, `icon`, `sort`, `status`) VALUES
(10000000000001001, '推拿按摩', '/images/icons/massage.png', 1, 1),
(10000000000001002, '美容护肤', '/images/icons/skincare.png', 2, 1),
(10000000000001003, '足疗保健', '/images/icons/foot.png', 3, 1),
(10000000000001004, '中医调理', '/images/icons/tcm.png', 4, 1),
(10000000000001005, '运动康复', '/images/icons/sport.png', 5, 1);

-- ============================================================
-- 2. 服务项目
-- ============================================================
INSERT IGNORE INTO `erik_service` (`id`, `category_id`, `name`, `description`, `cover_image`, `price`, `original_price`, `duration`, `sort`, `status`) VALUES
-- 推拿按摩
(10000000000002001, 10000000000001001, '全身经络推拿', '疏通经络、缓解疲劳，60分钟深度按摩，适合长期久坐办公人群', '/images/services/fullbody.jpg', 198.00, 298.00, 60, 1, 1),
(10000000000002002, 10000000000001001, '肩颈专项调理', '针对肩颈酸痛、僵硬问题，采用推拿配合热敷，30分钟快速见效', '/images/services/shoulder.jpg', 128.00, 188.00, 30, 2, 1),
-- 美容护肤
(10000000000002003, 10000000000001002, '深层清洁面部护理', '进口产品深层清洁毛孔，配合面部穴位按摩，让肌肤焕发光彩', '/images/services/facial.jpg', 168.00, 258.00, 45, 3, 1),
(10000000000002004, 10000000000001002, '玻尿酸补水护理', '高浓度玻尿酸精华导入，深层补水锁水，改善干燥粗糙肌肤', '/images/services/hydra.jpg', 238.00, 358.00, 60, 4, 1),
-- 足疗保健
(10000000000002005, 10000000000001003, '中药泡脚+足底按摩', '选用上等中药材泡脚，配合专业足底反射区按摩，缓解脚部疲劳', '/images/services/footmassage.jpg', 98.00, 158.00, 45, 5, 1),
(10000000000002006, 10000000000001003, '泰式足部舒缓', '泰式手法足部按摩，疏通足部经络，改善睡眠质量', '/images/services/thai.jpg', 128.00, 188.00, 60, 6, 1),
-- 中医调理
(10000000000002007, 10000000000001004, '中医体质调理', '中医体质辨识+个性化调理方案，涵盖艾灸、拔罐、刮痧等传统疗法', '/images/services/tcm_body.jpg', 268.00, 398.00, 90, 7, 1),
-- 运动康复
(10000000000002008, 10000000000001005, '运动损伤康复', '针对运动损伤的专业康复治疗，结合筋膜松解和功能训练', '/images/services/sport_rehab.jpg', 298.00, 428.00, 60, 8, 1);

-- ============================================================
-- 3. 门店
-- ============================================================
INSERT IGNORE INTO `erik_store` (`id`, `name`, `address`, `lat`, `lng`, `phone`, `business_hours`, `status`) VALUES
(10000000000003001, '康悦养生·旗舰店', '广东省深圳市南山区科技园南区高新南一道3号', 22.5362000, 113.9526000, '0755-88888801', '{"mon":{"start":"09:00","end":"22:00"},"tue":{"start":"09:00","end":"22:00"},"wed":{"start":"09:00","end":"22:00"},"thu":{"start":"09:00","end":"22:00"},"fri":{"start":"09:00","end":"22:00"},"sat":{"start":"09:00","end":"22:00"},"sun":{"start":"10:00","end":"20:00"}}', 1),
(10000000000003002, '康悦养生·福田店', '广东省深圳市福田区中心区福华三路88号', 22.5429000, 114.0596000, '0755-88888802', '{"mon":{"start":"09:00","end":"21:00"},"tue":{"start":"09:00","end":"21:00"},"wed":{"start":"09:00","end":"21:00"},"thu":{"start":"09:00","end":"21:00"},"fri":{"start":"09:00","end":"21:00"},"sat":{"start":"09:00","end":"21:00"},"sun":{"start":"10:00","end":"19:00"}}', 1),
(10000000000003003, '康悦养生·宝安店', '广东省深圳市宝安区新安街道宝民一路168号', 22.5683000, 113.8830000, '0755-88888803', '{"mon":{"start":"10:00","end":"21:00"},"tue":{"start":"10:00","end":"21:00"},"wed":{"start":"10:00","end":"21:00"},"thu":{"start":"10:00","end":"21:00"},"fri":{"start":"10:00","end":"21:00"},"sat":{"start":"10:00","end":"21:00"},"sun":{"start":"10:00","end":"20:00"}}', 1);

-- ============================================================
-- 4. 演示用户（技师账号）
-- ============================================================
INSERT IGNORE INTO `erik_user` (`id`, `phone`, `password`, `nickname`, `avatar`, `user_type`, `active_role`, `status`) VALUES
(10000000000004001, '13800138001', '$2y$10$dummy_hash_placeholder_tech1', '张师傅', '/images/avatars/tech1.jpg', 'technician', 'technician', 1),
(10000000000004002, '13800138002', '$2y$10$dummy_hash_placeholder_tech2', '李师傅', '/images/avatars/tech2.jpg', 'technician', 'technician', 1);

-- ============================================================
-- 5. 技师档案
-- ============================================================
INSERT IGNORE INTO `erik_technician_profile` (`id`, `user_id`, `real_name`, `gender`, `avatar`, `intro`, `rating`, `order_count`, `favorite_count`, `cover_image`, `video_url`, `certificates`, `status`) VALUES
(10000000000005001, 10000000000004001, '张伟', 1, '/images/avatars/tech1.jpg', '从业8年，国家高级按摩师，擅长经络调理和运动康复，服务细致耐心，深受客户好评。', 4.8, 326, 58, '/images/covers/tech1_cover.jpg', '', '["/images/certs/tech1_cert1.jpg","/images/certs/tech1_cert2.jpg"]', 'approved'),
(10000000000005002, 10000000000004002, '李芳', 2, '/images/avatars/tech2.jpg', '从业5年，国际认证美容师，擅长面部护理和中医体质调理，手法轻柔专业，让您享受极致放松体验。', 4.9, 218, 42, '/images/covers/tech2_cover.jpg', '', '["/images/certs/tech2_cert1.jpg"]', 'approved');

-- ============================================================
-- 6. 技师排班
-- ============================================================
INSERT IGNORE INTO `erik_technician_schedule` (`id`, `technician_id`, `date`, `time_slots`, `status`) VALUES
(10000000000006001, 10000000000005001, CURDATE(), '[{"start":"09:00","end":"12:00"},{"start":"14:00","end":"18:00"},{"start":"19:00","end":"21:00"}]', 1),
(10000000000006002, 10000000000005002, CURDATE(), '[{"start":"09:00","end":"12:00"},{"start":"13:00","end":"17:00"},{"start":"18:00","end":"20:00"}]', 1);

-- ============================================================
-- 7. 技师可服务项目
-- ============================================================
INSERT IGNORE INTO `erik_technician_service` (`id`, `technician_id`, `service_id`) VALUES
(10000000000007001, 10000000000005001, 10000000000002001),
(10000000000007002, 10000000000005001, 10000000000002002),
(10000000000007003, 10000000000005001, 10000000000002007),
(10000000000007004, 10000000000005001, 10000000000002008),
(10000000000007005, 10000000000005002, 10000000000002003),
(10000000000007006, 10000000000005002, 10000000000002004),
(10000000000007007, 10000000000005002, 10000000000002005),
(10000000000007008, 10000000000005002, 10000000000002007);

-- ============================================================
-- 8. 优惠券
-- ============================================================
INSERT IGNORE INTO `erik_coupon` (`id`, `name`, `type`, `amount`, `min_amount`, `total_qty`, `remain_qty`, `start_at`, `end_at`, `status`) VALUES
(10000000000008001, '新用户专享券', 'fixed', 30.00, 100.00, 999, 998, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1),
(10000000000008002, '满200减40', 'fixed', 40.00, 200.00, 500, 499, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1),
(10000000000008003, '全场9折券', 'percent', 0.90, 0.00, 200, 198, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1);

-- ============================================================
-- 9. 会员卡
-- ============================================================
INSERT IGNORE INTO `erik_member_card` (`id`, `name`, `type`, `price`, `duration_days`, `total_times`, `services`, `status`) VALUES
(10000000000009001, '月卡会员', 'month', 99.00, 30, 0, NULL, 1),
(10000000000009002, '季卡会员', 'month', 268.00, 90, 0, NULL, 1),
(10000000000009003, '年卡VIP', 'vip', 888.00, 365, 0, NULL, 1),
(10000000000009004, '肩颈调理次卡(10次)', 'times', 1080.00, 180, 10, '[{"service_id":10000000000002002,"times":10}]', 1);

-- ============================================================
-- 10. 轮播图
-- ============================================================
INSERT IGNORE INTO `erik_banner` (`id`, `position`, `image`, `jump_type`, `jump_value`, `sort`, `status`) VALUES
(10000000000010001, 'home', '/images/banners/banner1.jpg', 'url', '', 1, 1),
(10000000000010002, 'home', '/images/banners/banner2.jpg', 'detail', '10000000000002001', 2, 1),
(10000000000010003, 'home', '/images/banners/banner3.jpg', 'detail', '10000000000002003', 3, 1);

-- ============================================================
-- 11. 公告
-- ============================================================
INSERT IGNORE INTO `erik_announcement` (`id`, `title`, `content`, `sort`, `status`, `published_at`) VALUES
(10000000000011001, '康悦养生APP全新上线！', '康悦养生预约平台正式上线啦！在线预约、上门服务、实名技师、品质保障。首次注册即送30元优惠券！', 1, 1, NOW()),
(10000000000011002, '五一假期营业时间调整通知', '尊敬的顾客，五一劳动节期间（5月1日-5月5日），各门店正常营业，营业时间为10:00-20:00，请提前预约。', 2, 1, NOW());

-- ============================================================
-- 12. 系统配置值
-- ============================================================
INSERT IGNORE INTO `erik_system_config` (`id`, `group`, `key`, `value`, `type`, `description`) VALUES
(10000000000012001, 'app', 'app_name', '康悦养生', 'string', '应用名称'),
(10000000000012002, 'app', 'app_slogan', '专业养生，品质生活', 'string', '应用口号'),
(10000000000012003, 'app', 'contact_phone', '400-888-9999', 'string', '客服电话'),
(10000000000012004, 'app', 'max_advance_days', '7', 'integer', '最大可预约提前天数'),
(10000000000012005, 'app', 'cancel_free_minutes', '15', 'integer', '下单后免费取消时间（分钟）'),
(10000000000012006, 'app', 'points_per_yuan', '1', 'integer', '每消费1元获得积分数'),
(10000000000012007, 'app', 'referral_reward_points', '100', 'integer', '推荐新用户注册奖励积分');

-- ============================================================
-- 13. 电子签名表结构
-- ============================================================
CREATE TABLE IF NOT EXISTS `erik_signature` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '技师ID',
    `image_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '签名图片URL（SVG/PNG）',
    `signed_at` DATETIME DEFAULT NULL COMMENT '签名时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='电子签名记录表';
CREATE TABLE IF NOT EXISTS `erik_card_transfer` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `card_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'card_id',
    `from_user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'from_user_id',
    `to_user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'to_user_id',
    `transferred_at` DATETIME DEFAULT NULL COMMENT 'transferred_at',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_card_id` (`card_id`),
    KEY `idx_from_user_id` (`from_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员卡转赠记录表';

CREATE TABLE IF NOT EXISTS `erik_check_in` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `date` DATE DEFAULT NULL COMMENT 'date',
    `points_awarded` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'points_awarded',
    `consecutive_days` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'consecutive_days',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';

CREATE TABLE IF NOT EXISTS `erik_community_post` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'title',
    `content` TEXT COMMENT 'content',
    `images` JSON COMMENT 'images',
    `likes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'likes',
    `comments_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'comments_count',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `is_pinned` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'is_pinned',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区帖子表';

CREATE TABLE IF NOT EXISTS `erik_community_comment` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `post_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'post_id',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'parent_id',
    `content` TEXT COMMENT 'content',
    `likes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'likes',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区评论表';

CREATE TABLE IF NOT EXISTS `erik_exam` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'title',
    `course_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'course_id',
    `passing_score` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'passing_score',
    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'duration_minutes',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_course_id` (`course_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师考试表';

CREATE TABLE IF NOT EXISTS `erik_exam_attempt` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `exam_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'exam_id',
    `technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'technician_id',
    `score` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'score',
    `total_score` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'total_score',
    `passed` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'passed',
    `started_at` DATETIME DEFAULT NULL COMMENT 'started_at',
    `submitted_at` DATETIME DEFAULT NULL COMMENT 'submitted_at',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_exam_id` (`exam_id`),
    KEY `idx_technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试答题记录表';

CREATE TABLE IF NOT EXISTS `erik_exam_question` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `exam_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'exam_id',
    `content` TEXT COMMENT 'content',
    `type` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'type',
    `options` JSON COMMENT 'options',
    `answer` JSON COMMENT 'answer',
    `score` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'score',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试题目表';

CREATE TABLE IF NOT EXISTS `erik_invoice` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'order_id',
    `type` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'type',
    `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'title',
    `tax_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'tax_no',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'email',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'amount',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `issued_at` DATETIME DEFAULT NULL COMMENT 'issued_at',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发票申请表';

CREATE TABLE IF NOT EXISTS `erik_promotion` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'name',
    `type` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'type',
    `service_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'service_id',
    `min_people` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'min_people',
    `max_people` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'max_people',
    `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'discount_percent',
    `start_at` DATETIME DEFAULT NULL COMMENT 'start_at',
    `end_at` DATETIME DEFAULT NULL COMMENT 'end_at',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_service_id` (`service_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='营销活动表';

CREATE TABLE IF NOT EXISTS `erik_promotion_participant` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `promotion_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'promotion_id',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'order_id',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_promotion_id` (`promotion_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='活动参与记录表';

CREATE TABLE IF NOT EXISTS `erik_queue_number` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'store_id',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `number` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'number',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `called_at` DATETIME DEFAULT NULL COMMENT 'called_at',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_store_id` (`store_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='门店排队叫号表';

CREATE TABLE IF NOT EXISTS `erik_service_package` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'name',
    `description` TEXT COMMENT 'description',
    `cover_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'cover_image',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'price',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'original_price',
    `services` JSON COMMENT 'services',
    `duration_days` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'duration_days',
    `sales_volume` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'sales_volume',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='服务套餐表';

CREATE TABLE IF NOT EXISTS `erik_service_record` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'order_id',
    `technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'technician_id',
    `before_photos` JSON COMMENT 'before_photos',
    `after_photos` JSON COMMENT 'after_photos',
    `notes` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'notes',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='服务记录表';

CREATE TABLE IF NOT EXISTS `erik_share` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `sharer_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'sharer_id',
    `share_type` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'share_type',
    `target_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'target_id',
    `platform` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'platform',
    `clicked_at` DATETIME DEFAULT NULL COMMENT 'clicked_at',
    `converted_at` DATETIME DEFAULT NULL COMMENT 'converted_at',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_sharer_id` (`sharer_id`),
    KEY `idx_target_id` (`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享记录表';

CREATE TABLE IF NOT EXISTS `erik_user_device` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `platform` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'platform',
    `device_token` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'device_token',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户设备表';

CREATE TABLE IF NOT EXISTS `erik_video_post` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'technician_id',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'title',
    `video_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'video_url',
    `cover_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'cover_url',
    `duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'duration',
    `views` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'views',
    `likes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'likes',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'status',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_status` (`status`),
    KEY `idx_views` (`views`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师短视频表';

CREATE TABLE IF NOT EXISTS `erik_waitlist` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'user_id',
    `service_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'service_id',
    `technician_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'technician_id',
    `preferred_date` DATE DEFAULT NULL COMMENT 'preferred_date',
    `preferred_time` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'preferred_time',
    `status` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'status',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_service_id` (`service_id`),
    KEY `idx_technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='排队候补表';

-- ============================================================
-- [第4轮审计 P1/P2/P4] 收益/提现/卡券查询复合索引
-- idx_tech_status:            收益汇总 (technician_id, status) — EarningController/WithdrawController 汇总聚合
--                              （erik_technician_withdrawal 已存在 idx_tech_status，不重复添加）
-- idx_tech_status_created:    markCompleted 核销按 created_at 顺序取 settled 收益
-- idx_status_end (coupon):    expireCoupons EXISTS 子查询按 end_at 范围过滤
-- idx_status_end (user_card): expireMemberCards 按 status=active + end_at 范围过滤
-- ============================================================
ALTER TABLE `erik_technician_earnings`
ADD KEY `idx_tech_status` (`technician_id`, `status`);

ALTER TABLE `erik_technician_earnings`
ADD KEY `idx_tech_status_created` (`technician_id`, `status`, `created_at`);

ALTER TABLE `erik_coupon`
ADD KEY `idx_status_end` (`status`, `end_at`);

ALTER TABLE `erik_user_member_card`
ADD KEY `idx_status_end` (`status`, `end_at`);
