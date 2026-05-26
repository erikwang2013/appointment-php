<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

/**
 * 全局中间件配置
 *
 * 格式说明（webman v2.2+）:
 * - '@' 键表示全局中间件，对所有请求生效
 * - 插件中间件在各插件自己的 config/middleware.php 中配置
 *
 * 执行顺序: Cors → SecurityMiddleware (erikwang2013/security-php) → {路由组中间件} → Controller
 */

return [
    '@' => [
        // 跨域中间件：处理 OPTIONS 预检请求，设置 CORS 响应头
        app\middleware\Cors::class,
        // 安全中间件：erikwang2013/security-php 提供的 31 种攻击检测（XSS/SQL注入/CSRF等）
        \Erikwang2013\Security\Middleware\Webman\SecurityMiddleware::class,
    ],
];
