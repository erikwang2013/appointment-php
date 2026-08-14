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
    `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '使用时间',    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
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
-- 8. 扩展 erik_operation_log 表，增加来源端字段
-- ============================================================

ALTER TABLE `erik_operation_log`
ADD COLUMN IF NOT EXISTS `source` VARCHAR(20) NOT NULL DEFAULT 'web' COMMENT '操作来源端: web=Web后台 iPadOS macOS Windows Linux ios android harmonyOS'
AFTER `ip`;

-- ============================================================
-- 初始数据: 提现默认配置
-- ============================================================

INSERT INTO `erik_withdrawal_config` (`id`, `min_amount`, `reserve_amount`, `round_to_hundred`, `withdrawal_day`, `arrival_days`) VALUES
(10000000000000001, 10.00, 0.00, 1, 20, 1);
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
