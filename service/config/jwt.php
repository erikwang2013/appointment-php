<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

/**
 * JWT 认证配置
 * 注意: 实际驱动层使用 config/plugin/erikwang2013/jwt/jwt.php 中的配置
 * 此文件为应用层参考配置，需与插件配置保持一致的 env 变量名
 * @link https://github.com/erikwang2013/jwt-webman
 */
// JWT 签名密钥，必须通过环境变量注入，无公开默认值；缺失或仍为已知默认值时拒绝启动
$secret = getenv('JWT_SECRET_KEY');
$knownInsecureKeys = ['appointment-service-jwt-secret-change-in-production', 'test-jwt-secret-key-for-testing', 'open-admin-jwt-secret-change-in-production'];
if ($secret === false || $secret === '' || in_array($secret, $knownInsecureKeys, true)) {
    throw new RuntimeException('请配置 JWT_SECRET_KEY（.env），不允许使用公开默认值，系统拒绝启动');
}

return [
    // JWT 签名密钥，生产环境请使用 64 位以上随机字符串并通过环境变量注入
    'secret' => $secret,

    // 签名算法，支持 HS256/HS384/HS512/RS256
    'algorithm' => getenv('JWT_ALGORITHM') ?: 'HS256',

    // 访问令牌有效期（秒），默认 2 小时
    'ttl' => (int)(getenv('JWT_DEFAULT_EXPIRE') ?: 7200),

    // 刷新令牌有效期（秒），默认 14 天
    'refresh_ttl' => (int)(getenv('JWT_REFRESH_EXPIRE') ?: 1209600),

    // 签发者标识
    'issuer' => getenv('JWT_ISSUER') ?: 'appointment-service',

    // 受众标识
    'audience' => getenv('JWT_AUDIENCE') ?: 'appointment-service',
];
