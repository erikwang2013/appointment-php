<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 秒杀活动
 *
 * stock 为剩余库存：下单时行锁扣减，售罄后拦截（取消订单不回补）。
 * 已售量 = erik_order.seckill_id 订单数。
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
}
