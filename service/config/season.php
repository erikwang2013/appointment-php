<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

/**
 * Season 配置
 * 国家旗帜、国际化数据
 * @link https://github.com/erikwang2013/season
 */
return [
    // 默认语言
    'locale' => getenv('SEASON_LOCALE') ?: 'zh_CN',

    // 默认时区
    'timezone' => getenv('SEASON_TIMEZONE') ?: 'Asia/Shanghai',
];
