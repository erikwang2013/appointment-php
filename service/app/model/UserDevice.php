<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 用户设备模型
 *
 * 表: erik_user_device
 * 存储用户推送设备 token，支持 iOS 和 Android
 */
class UserDevice extends Model
{
    protected $table = 'erik_user_device';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'platform', 'device_token', 'created_at', 'updated_at',
    ];

    // ── 平台常量 ──
    public const PLATFORM_IOS     = 'ios';
    public const PLATFORM_ANDROID = 'android';

    // ── 关联关系 ──

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── ID 生成 ──

    /**
     * 生成 snowflake ID
     */
    public static function generateId(): string
    {
        $snowflakeConfig = config('snowflake');
        $snowflake = new Snowflake(
            (int)($snowflakeConfig['datacenter_id'] ?? 1),
            (int)($snowflakeConfig['worker_id'] ?? 1)
        );
        return (string)$snowflake->id();
    }
}
