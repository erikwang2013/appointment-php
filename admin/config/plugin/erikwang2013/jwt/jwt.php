<?php
/*
 * JWT Webman Plugin - JWT authentication for webman framework
 * Copyright (c) 2026 erik
 * Author: erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

// 签名密钥必须由环境变量注入，无公开默认值；缺失或仍为已知默认值时拒绝启动
$jwtSecretKey = getenv('JWT_SECRET_KEY');
$knownInsecureKeys = ['appointment-service-jwt-secret-change-in-production', 'test-jwt-secret-key-for-testing', 'open-admin-jwt-secret-change-in-production'];
if ($jwtSecretKey === false || $jwtSecretKey === '' || in_array($jwtSecretKey, $knownInsecureKeys, true)) {
    throw new RuntimeException('请配置 JWT_SECRET_KEY（.env），不允许使用公开默认值，系统拒绝启动');
}

return [
    'enable' => true,
    'secret_key' => $jwtSecretKey,
    'algorithm' => getenv('JWT_ALGORITHM') ?: 'HS256',
    'issuer' => getenv('JWT_ISSUER') ?: 'open-admin',
    'audience' => getenv('JWT_AUDIENCE') ?: 'open-admin',
    'leeway' => (int)(getenv('JWT_LEEWAY') ?: 60),
    'default_expire' => (int)(getenv('JWT_DEFAULT_EXPIRE') ?: 7200),
    'refresh_expire' => (int)(getenv('JWT_REFRESH_EXPIRE') ?: 1209600),
    'storage' => [
        //存储类型：redis, database, memcached, file
        //默认 redis：token 黑名单必须跨重启/多副本共享，file（/tmp）存储会导致
        //进程重启后已登出/已刷新 token 复活。生产环境请保持 redis。
        'type' => getenv('JWT_STORAGE_TYPE') ?: 'redis',
        'database' => getenv('JWT_STORAGE_DATABASE') ?: '',
        'prefix' => getenv('JWT_STORAGE_PREFIX') ?: 'jwt_token:'
    ],
    'advanced' => [
        'retry_attempts' => (int)(getenv('JWT_ADVANCED_RETRY_ATTEMPTS') ?: 1),
        'retry_delay' => (int)(getenv('JWT_ADVANCED_RETRY_DELAY') ?: 100),
        'auto_cleanup' => filter_var(getenv('JWT_ADVANCED_AUTO_CLEANUP') ?: '0', FILTER_VALIDATE_BOOLEAN),
        'cleanup_interval' => (int)(getenv('JWT_ADVANCED_CLEANUP_INTERVAL') ?: 3600)
    ]
];
