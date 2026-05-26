<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
return [
    'captcha' => [
        'storage' => 'file',    // 测试环境使用文件存储
        'ttl'     => 300,
        'max_attempts' => 3,
        'tolerance' => [
            'click'  => 18,
            'rotate' => 5,
            'slider' => 4,
        ],
    ],
];
