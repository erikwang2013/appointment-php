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
