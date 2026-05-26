<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Encryptable\Encryptable;
use Erikwang2013\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\SoftDeletes;
use support\Model;

class Store extends Model
{
    use Encryptable, SoftDeletes;

    protected $table = 'erik_store';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected array $encryptable = [
        'phone',
    ];

    protected $fillable = [
        'name', 'address', 'lat', 'lng', 'phone',
        'business_hours', 'images', 'status',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'business_hours' => 'array',
        'images' => 'array',
        'status' => 'integer',
    ];

    protected $hidden = ['deleted_at'];

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
