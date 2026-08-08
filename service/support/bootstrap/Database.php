<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace support\bootstrap;

use Webman\Bootstrap;
use Webman\Database\Initializer;
use Workerman\Worker;

/**
 * 数据库引导
 *
 * 预热 Eloquent：Initializer 由 support\Db 门面懒加载，但模型静态查询
 * （Model::where()）不会触发门面加载，需在此显式初始化连接 resolver。
 */
class Database implements Bootstrap
{
    public static function start(?Worker $worker): void
    {
        Initializer::init(config('database', []));
    }
}
