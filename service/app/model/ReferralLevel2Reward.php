<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 二级返佣发放记录模型
 *
 * erik_referral_level2_reward，UNIQUE KEY uk_order_referred (order_id, referred_user_id)
 * 保证同一被推荐人同一订单只发一次二级返佣。
 */
class ReferralLevel2Reward extends Model
{
    protected $table = 'erik_referral_level2_reward';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // 表仅 created_at（DB 默认 CURRENT_TIMESTAMP）

    protected $fillable = [
        'order_id', 'referred_user_id', 'referrer_id', 'amount', 'status',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
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
