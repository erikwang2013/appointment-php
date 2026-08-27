<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 订单明细模型
 */
class OrderItem extends Model
{
    protected $table = 'appointment_order_item';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id', 'order_id', 'target_type', 'target_id',
        'name', 'cover_image', 'price', 'quantity', 'spec_info',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'spec_info' => 'array',
    ];

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

}
