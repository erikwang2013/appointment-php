<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

class UserCoupon extends Model
{
    protected $table = 'erik_user_coupon';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

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
