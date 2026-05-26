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
