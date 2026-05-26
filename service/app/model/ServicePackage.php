<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 服务套餐模型
 * 套餐包含多个服务项目（JSON），用户可一次性购买
 */
class ServicePackage extends Model
{
    protected $table = 'erik_service_package';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'description', 'cover_image', 'price', 'original_price',
        'services', 'duration_days', 'sales_volume', 'status',
    ];

    protected $casts = [
        'services' => 'array',
        'price' => 'float',
        'original_price' => 'float',
        'duration_days' => 'integer',
        'sales_volume' => 'integer',
        'status' => 'integer',
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
