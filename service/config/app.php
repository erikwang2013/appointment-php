<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

use support\Request;

/**
 * 应用基础配置
 * 调试模式、时区、请求类等核心设置
 */
return [
    // 调试模式：true 开启调试（开发环境），false 关闭（生产环境务必关闭）
    'debug' => true,
    // 错误报告级别：E_ALL 报告所有错误
    'error_reporting' => E_ALL,
    // 默认时区：Asia/Shanghai 北京时间
    'default_timezone' => 'Asia/Shanghai',
    // 请求类
    'request_class' => Request::class,
    // 公共资源目录
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    // 运行时目录（日志、缓存、视图编译等）
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    // 控制器后缀，自动查找 FooController 类
    'controller_suffix' => 'Controller',
    // 控制器复用：false 每次请求新建实例，避免状态污染
    'controller_reuse' => false,
];
