<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 秒杀活动（管理后台）
 */
class SeckillActivity extends Model
{
    protected $table = 'erik_seckill_activity';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'service_id', 'seckill_price', 'original_price',
        'stock', 'start_at', 'end_at', 'status',
    ];

    protected $casts = [
        'seckill_price'  => 'float',
        'original_price' => 'float',
        'stock'          => 'int',
        'status'         => 'int',
        'start_at'       => 'datetime',
        'end_at'         => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public static function generateId(): string
    {
        $config = config('snowflake', []);
        $snowflake = new Snowflake(
            (int)($config['datacenter_id'] ?? 1),
            (int)($config['worker_id'] ?? 1)
        );
        return (string)$snowflake->id();
    }
}
