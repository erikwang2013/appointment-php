-- ============================================================
-- 技师等级自动评定（表结构 + 权限种子）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：service 端新增 TierRatingService（技师完成订单/评价写入/查看资料时
-- 懒判定等级）。erik_technician_tier_config 已有条件列（min_orders/min_rating），
-- 本次补充：
--   1. erik_technician_profile 增加 tier_id 记录当前等级；
--   2. 新建 erik_technician_tier_log 记录每次等级变更（升级/降级）；
--   3. TechnicianTierController 新增 logs 接口（GET /admin/technician-tiers/logs），
--      权限种子 id 接续 2026_08_14_000014 的 379，下一个 380。
-- 升降级规则（TierRatingService）：仅升级不降级（降级保护，等级绑定佣金率与
-- 价格系数，自动降级直接影响技师收入；异常下滑由 admin 手动兜底），
-- allowDowngrade=true 时才执行降级。
-- ============================================================

-- 1. 技师档案增加当前等级列
ALTER TABLE `erik_technician_profile`
    ADD COLUMN `tier_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT '当前等级ID（erik_technician_tier_config.id），空=未评定' AFTER `favorite_count`;

-- 2. 等级变更日志表
CREATE TABLE IF NOT EXISTS `erik_technician_tier_log` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `technician_id` BIGINT UNSIGNED NOT NULL COMMENT '技师ID（erik_technician_profile.id）',
    `old_tier_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT '变更前等级ID，空=首次评定',
    `new_tier_id` BIGINT UNSIGNED NOT NULL COMMENT '变更后等级ID',
    `reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '变更原因（统计值快照）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_technician_id` (`technician_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='技师等级变更日志';

-- 3. 等级变更日志查看权限种子见 2026_08_14_000016_technician_tier_permissions.sql
