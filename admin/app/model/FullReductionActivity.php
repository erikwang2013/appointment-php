<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 满减活动表（满 X 减 Y）
 *
 * threshold 为满减门槛（满多少元），reduction 为减免金额；
 * status=1 且 start_at <= now <= end_at 时活动生效。
 */
class FullReductionActivity extends Model
{
    protected $table = 'erik_full_reduction_activity';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'title', 'threshold', 'reduction', 'status', 'start_at', 'end_at',
    ];

    protected $casts = [
        'threshold' => 'decimal:2',
        'reduction' => 'decimal:2',
        'status'    => 'int',
    ];

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
