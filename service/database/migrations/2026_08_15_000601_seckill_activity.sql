-- ============================================================
-- 秒杀活动表（R24 秒杀功能）
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 应用方式：mysql -uroot -proot appointment < service/database/migrations/2026_08_15_000601_seckill_activity.sql
-- 幂等：CREATE TABLE IF NOT EXISTS，可重复执行。
-- ============================================================

CREATE TABLE IF NOT EXISTS `erik_seckill_activity` (
    `id`             BIGINT       NOT NULL COMMENT '活动 ID（snowflake）',
    `name`           VARCHAR(100) NOT NULL COMMENT '活动名称',
    `service_id`     BIGINT       NOT NULL COMMENT '关联服务 ID（erik_service.id）',
    `seckill_price`  DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '秒杀价（实付）',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价（订单项展示价）',
    `stock`          INT          NOT NULL DEFAULT 0 COMMENT '剩余库存（售罄拦截，扣减不回补，取消订单不返还）',
    `start_at`       DATETIME     NOT NULL COMMENT '开抢时间',
    `end_at`         DATETIME     NOT NULL COMMENT '结束时间',
    `status`         TINYINT      NOT NULL DEFAULT 0 COMMENT '状态：0 下架 1 上架',
    `created_at`     DATETIME     NULL,
    `updated_at`     DATETIME     NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status_time` (`status`, `start_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '秒杀活动';
