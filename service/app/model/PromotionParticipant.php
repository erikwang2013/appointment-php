<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 促销活动参与记录
 */
class PromotionParticipant extends Model
{
    protected $table = 'erik_promotion_participant';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    // ── 参与状态常量（与 erik_promotion_participant.status tinyint 一致）──
    public const STATUS_PENDING   = 0;
    public const STATUS_JOINED    = 1;
    public const STATUS_PAID      = 2;
    public const STATUS_COMPLETED = 3;

    protected $fillable = [
        'promotion_id', 'user_id', 'order_id', 'status',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

}
