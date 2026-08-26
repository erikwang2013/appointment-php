<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 订单核销记录模型
 */
class OrderVerification extends Model
{
    protected $table = 'erik_order_verification';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'order_id', 'code', 'verified_by',
        'verify_type', 'location', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    // ── 核销类型常量 ──
    public const VERIFY_TYPE_SCAN = 'scan';
    public const VERIFY_TYPE_SELF = 'self';

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * 生成64位随机核销码
     */
    public static function generateCode(): string
    {
        return bin2hex(random_bytes(32));
    }

}
