-- ============================================================
-- 回头客奖励（R24）：30 天内二次消费奖金
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 1. erik_technician_earnings.type 注释补充 return_customer=回头客奖励
--    （复用收益流水表落账，与 commission 同表同结算链，无需新建表；
--     幂等由服务层同 order_id+type 查重保证）
-- 2. erik_system_config 增加 return_customer 分组：
--    enabled 开关（默认 1=开启，'0'/'false'/'off' 关闭）
--    ratio 奖励比例（默认 0.05，非法值（<=0 或 >1）回落默认）
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000602_return_customer_reward.sql
-- ============================================================

ALTER TABLE `erik_technician_earnings`
    MODIFY COLUMN `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'commission'
    COMMENT '收益类型: commission=服务提成 bonus=奖金 penalty=罚款 subsidy=补贴 attendance=考勤奖励 return_customer=回头客奖励';

INSERT INTO `erik_system_config`
    (`id`, `group`, `key`, `value`, `type`, `description`)
VALUES
    (91000000000000025, 'return_customer', 'enabled', '1', 'string',
     '回头客奖励开关：1=开启 0=关闭（用户对同一技师30天内二次消费时给技师发放奖金）'),
    (91000000000000026, 'return_customer', 'ratio', '0.05', 'string',
     '回头客奖励比例：奖金=订单实付×比例（0-1，非法值回落 0.05）')
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`);
