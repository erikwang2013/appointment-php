<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 促销活动模型
 * 支持团购（group_buy）和秒杀（flash_sale）
 */
class Promotion extends Model
{
    protected $table = 'erik_promotion';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    // ── 活动类型常量 ──
    public const TYPE_GROUP_BUY  = 'group_buy';
    public const TYPE_FLASH_SALE = 'flash_sale';

    protected $fillable = [
        'name', 'type', 'service_id', 'min_people', 'max_people',
        'discount_percent', 'start_at', 'end_at', 'status',
    ];

    protected $casts = [
        'min_people' => 'integer',
        'max_people' => 'integer',
        'discount_percent' => 'float',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'status' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function participants()
    {
        return $this->hasMany(PromotionParticipant::class, 'promotion_id');
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
