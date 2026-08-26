<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 优惠券转赠记录
 *
 * 转赠即消耗原券：claim 时原 erik_user_coupon 置 used，新生成一条绑定接收人。
 * 被转赠的券（接收人新生成的 UserCoupon）无对应转赠记录，自然不可再转赠。
 */
class UserCouponTransfer extends Model
{
    protected $table = 'erik_user_coupon_transfer';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_coupon_id', 'coupon_id', 'from_user_id', 'to_user_id',
        'code', 'status', 'claimed_at', 'expire_at',
    ];

}
