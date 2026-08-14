-- ============================================================
-- 成长等级权益默认值（第 21 轮任务 #4）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：R20 已建 5 档 erik_growth_level（benefits JSON），但权益从未被业务引用。
-- 本迁移将权益默认值统一固化，供业务引用：
--   discount_rate      等级折扣率（0.95 = 95 折，1.0 = 无折扣）
--   points_multiplier  消费积分倍率（每实付 1 元 1 点 × 倍率）
-- 档位：青铜 1.0/1.0 → 白银 0.98/1.1 → 黄金 0.95/1.2 → 铂金 0.92/1.3 → 钻石 0.9/1.5
-- 幂等：UPDATE 按 level 无条件重放，重复执行结果一致。
-- ============================================================

UPDATE `erik_growth_level` SET `benefits` = JSON_OBJECT('discount_rate', 1.0, 'points_multiplier', 1.0) WHERE `level` = 1;
UPDATE `erik_growth_level` SET `benefits` = JSON_OBJECT('discount_rate', 0.98, 'points_multiplier', 1.1) WHERE `level` = 2;
UPDATE `erik_growth_level` SET `benefits` = JSON_OBJECT('discount_rate', 0.95, 'points_multiplier', 1.2) WHERE `level` = 3;
UPDATE `erik_growth_level` SET `benefits` = JSON_OBJECT('discount_rate', 0.92, 'points_multiplier', 1.3) WHERE `level` = 4;
UPDATE `erik_growth_level` SET `benefits` = JSON_OBJECT('discount_rate', 0.9, 'points_multiplier', 1.5) WHERE `level` = 5;
