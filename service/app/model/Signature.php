<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 电子签名模型
 *
 * 存储订单完成后的电子签名记录，支持 SVG/PNG 格式
 */
class Signature extends Model
{
    protected $table = 'appointment_signature';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'order_id', 'user_id', 'technician_id',
        'image_url', 'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联技师
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

}
