<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class UserCoupon extends Model
{
    protected $table = 'erik_user_coupon';
    public $incrementing = false;
    protected $keyType = 'string';
    // 双库兼容：appointment 库该表有 created_at/updated_at（DB 默认值自动填充），
    // open_admin 库该表无这两列——关闭 Eloquent 自动写时间戳，避免 update() 报
    // "Unknown column updated_at"（生日券去重等 whereYear 查询仅依赖列，不受影响）
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'coupon_id', 'status', 'used_at', 'received_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

}
