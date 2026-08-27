<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Db;
use support\Model;

class UserPoints extends Model
{
    protected $table = 'appointment_user_points';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'type', 'points', 'balance',
        'source', 'order_id', 'description', 'expires_at',
    ];

    /**
     * 积分有效期（天）：appointment_system_config (group=points, key=expiry_days)，缺省 365
     */
    public static function expiryDays(): int
    {
        try {
            $value = Db::table('appointment_system_config')
                ->where('group', 'points')
                ->where('key', 'expiry_days')
                ->value('value');
        } catch (\Throwable) {
            $value = null;
        }
        if ($value === null) {
            return 365;
        }
        return max(0, (int) $value);
    }

    /**
     * earn 类型积分到期时间（now + expiryDays()）；expiry_days<=0 视为永不过期（返回 null）
     */
    public static function expiryAt(): ?string
    {
        $days = self::expiryDays();
        if ($days <= 0) {
            return null;
        }
        return date('Y-m-d H:i:s', time() + $days * 86400);
    }

}
