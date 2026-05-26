<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

class TechnicianTierConfig extends Model
{
    protected $table = 'erik_technician_tier_config';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'slug', 'min_orders', 'min_rating',
        'commission_rate', 'price_multiplier', 'sort',
    ];

    protected $casts = [
        'min_orders' => 'integer',
        'min_rating' => 'decimal:1',
        'commission_rate' => 'decimal:2',
        'price_multiplier' => 'decimal:2',
        'sort' => 'integer',
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
