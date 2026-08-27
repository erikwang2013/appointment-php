<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

/**
 * 支付宝支付配置
 *
 * 回调验签（B1 安全加固）：
 * - RSA/RSA2（推荐）：使用 alipay_public_key（支付宝公钥）openssl_verify 验签；
 * - MD5：使用 md5_key（支付宝 MD5 密钥），未配置时直接拒绝 MD5 分支（强制 RSA2）。
 * 配置来源：appointment_system_config group=alipay_pay 优先，缺省回落到本文件（.env ALIPAY_* 键）。
 */
return [
    // 支付宝应用 ID（开放平台创建应用后获取）
    'app_id' => (string) (getenv('ALIPAY_APP_ID') ?: ''),

    // 应用私钥（RSA2，用于支付请求签名；回调验签不使用）
    'private_key' => (string) (getenv('ALIPAY_PRIVATE_KEY') ?: ''),

    // 支付宝公钥（RSA2，回调验签用；从支付宝开放平台「密钥管理」下载）
    'alipay_public_key' => (string) (getenv('ALIPAY_PUBLIC_KEY') ?: ''),

    // 支付宝 MD5 密钥（旧版接口；未配置时拒绝 MD5 签名回调，强制 RSA2）
    'md5_key' => (string) (getenv('ALIPAY_MD5_KEY') ?: ''),

    // 支付回调通知 URL
    'notify_url' => (string) (getenv('ALIPAY_NOTIFY_URL') ?: ''),

    // 回调来源 IP 白名单（逗号分隔，如 "110.75.0.0/16,121.0.0.1"；留空表示不限制来源）
    'notify_ip_whitelist' => (string) (getenv('ALIPAY_NOTIFY_IP_WHITELIST') ?: ''),
];
