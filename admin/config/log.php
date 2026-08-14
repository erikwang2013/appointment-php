<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

// 日志级别由环境变量 LOG_LEVEL 控制（DEBUG/INFO/WARNING/ERROR/CRITICAL），默认 DEBUG
// 生产环境建议设置 LOG_LEVEL=INFO，避免 DEBUG 日志刷盘
$logLevelMap = [
    'DEBUG'     => Monolog\Logger::DEBUG,
    'INFO'      => Monolog\Logger::INFO,
    'WARNING'   => Monolog\Logger::WARNING,
    'ERROR'     => Monolog\Logger::ERROR,
    'CRITICAL'  => Monolog\Logger::CRITICAL,
];
$logLevel = $logLevelMap[strtoupper((string)getenv('LOG_LEVEL', 'DEBUG'))] ?? Monolog\Logger::DEBUG;

return [
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/webman.log',
                    7, //$maxFiles
                    $logLevel,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
];
