<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * APP 版本模型
 *
 * platform 枚举 android/ios；force_update 1=强制更新 0=非强制；
 * status 1=上架 0=下架。ID 由 support\Model 自动生成（snowflake）。
 */
class AppVersion extends Model
{
    protected $table = 'erik_app_version';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'platform', 'version_code', 'version_name',
        'force_update', 'changelog', 'download_url', 'status',
    ];

    protected $casts = [
        'force_update' => 'int',
        'status'       => 'int',
    ];
}
