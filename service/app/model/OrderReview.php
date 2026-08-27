<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 订单评价模型
 */
class OrderReview extends Model
{
    protected $table = 'appointment_order_review';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'order_id', 'user_id', 'technician_id',
        'rating', 'content', 'images', 'reply', 'replied_at', 'status',
        'append_content', 'append_images', 'append_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'replied_at' => 'datetime',
        'status' => 'integer',
        'append_images' => 'array',
        'append_at' => 'datetime',
    ];

    // ── 评价状态常量 ──
    public const STATUS_HIDDEN  = 0;
    public const STATUS_VISIBLE = 1;

    /**
     * 评价用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * 按订单查找评价（order/technician 两端 ReviewController 共用）
     */
    public static function findByOrderId(string $orderId): ?self
    {
        return static::where('order_id', $orderId)->first();
    }

}
