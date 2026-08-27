<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Erikwang2013\Encryptable\Encryptable;
use ErikJwt\JWTFactory;
use support\Model;

class User extends Model
{
    use SoftDeletes;

    protected $table = 'appointment_user';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $casts = [
        // phone/wx_openid/wx_unionid 必须明文存储：登录/查重/唯一键依赖明文精确查询，
        // 而加密包不支持对密文做等价查询（Encryption::db() 与自身加密格式不兼容，实测返回 NULL）
        'real_name' => Encryptable::class,
        'store_id' => 'integer',
    ];

    protected $fillable = [
        'phone', 'password', 'wx_openid', 'wx_unionid', 'avatar', 'nickname',
        'real_name', 'gender', 'user_type', 'active_role', 'referral_code',
        'referrer_id', 'store_id', 'status', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'wx_openid', 'wx_unionid', 'deleted_at'];

    public function technicianProfile()
    {
        return $this->hasOne(TechnicianProfile::class, 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function favorites()
    {
        return $this->hasMany(UserFavorite::class, 'user_id');
    }

    /**
     * 生成唯一推荐码
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid((string)random_int(0, 99999), true)), 0, 8));
        } while (self::where('referral_code', $code)->exists());
        return $code;
    }

    /**
     * 生成JWT token
     */
    public function generateToken(): string
    {
        $jwt = JWTFactory::createFromConfig(config('plugin.erikwang2013.jwt.jwt'), null, ['redis' => fn() => \support\Redis::connection()]);

        return $jwt->encode([
            'user_id' => $this->id,
            'user_type' => $this->user_type,
        ], 86400 * 7);
    }

}
