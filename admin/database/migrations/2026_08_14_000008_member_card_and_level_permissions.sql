-- ============================================================
-- 会员等级列 + 会员卡定义管理（S10 会员等级 + 卡定义管理）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：
-- 1. erik_user 表无 member_level 列，会员列表「等级」列恒为 -，
--    此处补齐 DDL（默认空字符串，兼容既有数据）。
-- 2. 新增 MemberCardController 管理 erik_member_card（会员卡定义），
--    /admin 组新增 /member-cards 资源路由（index/store/show/update/destroy）。
-- AdminPermission 中间件按 method.path 精确匹配，本迁移为已部署环境
-- 补齐权限条目，并给超级管理员角色授予全部新权限。
-- 全新安装由 install.sql（已同步本清单）覆盖。
--
-- 注意：本地 MySQL 8.0 不支持 ADD COLUMN IF NOT EXISTS，
-- 执行前需先确认列不存在（本迁移假设目标列不存在）。
--
-- id 沿用 21000000000000xxx 系列，接续 2026_08_14_000006 的 364。
-- ============================================================

-- 1. erik_user 增加 member_level 列
ALTER TABLE `erik_user`
    ADD COLUMN `member_level` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '会员等级' AFTER `active_role`;

-- 2. 会员卡定义权限种子
INSERT INTO `erik_admin_permission` (`id`, `parent_id`, `name`, `slug`, `type`, `icon`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(21000000000000365, 0, '会员卡定义列表', 'get.admin/member-cards', 3, '', '', 164, NOW(), NOW()),
(21000000000000366, 0, '创建会员卡定义', 'post.admin/member-cards', 3, '', '', 165, NOW(), NOW()),
(21000000000000367, 0, '会员卡定义详情', 'get.admin/member-cards/{id}', 3, '', '', 166, NOW(), NOW()),
(21000000000000368, 0, '更新会员卡定义', 'put.admin/member-cards/{id}', 3, '', '', 167, NOW(), NOW()),
(21000000000000369, 0, '删除会员卡定义', 'delete.admin/member-cards/{id}', 3, '', '', 168, NOW(), NOW());

-- 3. 超级管理员角色关联新增权限（幂等）
INSERT INTO `erik_admin_role_permission` (`role_id`, `permission_id`)
SELECT 10000000000000001, `id` FROM `erik_admin_permission`
WHERE `id` NOT IN (
    SELECT `permission_id` FROM `erik_admin_role_permission` WHERE `role_id` = 10000000000000001
);
