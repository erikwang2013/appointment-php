<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 会员卡转赠记录模型
 */
class CardTransfer extends Model
{
    protected $table = 'erik_card_transfer';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'card_id', 'from_user_id', 'to_user_id', 'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    /**
     * 所属会员卡
     */
    public function userCard()
    {
        return $this->belongsTo(UserMemberCard::class, 'card_id');
    }

    /**
     * 转出用户
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * 接收用户
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
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
