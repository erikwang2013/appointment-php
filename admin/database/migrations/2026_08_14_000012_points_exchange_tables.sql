-- ============================================================
-- 积分兑换商城两表
-- Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
--
-- 背景：service 端新增积分兑换商城（/api/marketing/points-exchange）：
-- 商品定义表 + 兑换记录表。兑换扣减用户积分（UserPoints type=consume
-- source=exchange，SUM 聚合余额），结果落到优惠券/钱包/礼品卡三域，
-- result 列留存 JSON 快照。
-- 幂等规则：同用户同商品限兑换一次（uk_user_goods 唯一索引兜底并发），
-- 商品库存行锁（lockForUpdate）防超兑。
-- ============================================================

-- 兑换商品表
CREATE TABLE IF NOT EXISTS `erik_points_exchange_goods` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '商品名称',
    `type` VARCHAR(20) NOT NULL DEFAULT 'coupon' COMMENT '兑换类型: coupon=优惠券 gift_card=礼品卡 wallet=钱包余额',
    `points_cost` INT NOT NULL DEFAULT 0 COMMENT '所需积分',
    `value` DECIMAL(25,2) NOT NULL DEFAULT 0.00 COMMENT '兑换值: coupon=优惠券ID(雪崩ID) gift_card=卡面金额(元) wallet=入账金额(元)',
    `stock` INT NOT NULL DEFAULT 0 COMMENT '库存（剩余可兑数量）',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 1=上架 0=下架',
    `sort` INT NOT NULL DEFAULT 0 COMMENT '排序（大在前）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换商品表';

-- 兑换记录表
CREATE TABLE IF NOT EXISTS `erik_user_points_exchange` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键ID，由snowflake生成',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `goods_id` BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    `goods_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '兑换时商品名称快照',
    `points_cost` INT NOT NULL DEFAULT 0 COMMENT '兑换时消耗积分快照',
    `result` TEXT NOT NULL COMMENT '兑换结果快照(JSON): coupon=用户券ID wallet=入账金额 gift_card=卡密',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_goods` (`user_id`, `goods_id`),
    KEY `idx_goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户积分兑换记录表';
