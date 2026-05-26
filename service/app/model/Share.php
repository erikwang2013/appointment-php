<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 分享记录模型
 * 追踪服务/技师的分享点击与转化
 */
class Share extends Model
{
    protected $table = 'erik_share';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'sharer_id', 'share_type', 'target_id',
        'platform', 'clicked_at', 'converted_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    // ── 分享类型常量 ──
    public const SHARE_TYPE_SERVICE    = 'service';
    public const SHARE_TYPE_TECHNICIAN = 'technician';

    // ── 平台常量 ──
    public const PLATFORM_WECHAT   = 'wechat';
    public const PLATFORM_TIMELINE = 'timeline';

    /**
     * 分享者
     */
    public function sharer()
    {
        return $this->belongsTo(User::class, 'sharer_id');
    }

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
