<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Builder;
use support\Model;

/**
 * 订单模型
 *
 * @method static Builder byStatus(string $status) 按状态筛选
 * @method static Builder byUser(string $userId)    按用户筛选
 */
class Order extends Model
{
    protected $table = 'erik_order';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'created_at', 'updated_at',
        'order_no', 'user_id', 'technician_id', 'store_id',
        'order_type', 'total_amount', 'discount_amount', 'paid_amount',
        'coupon_id', 'user_coupon_id', 'member_card_usage_id',
        'service_time', 'status', 'cancel_reason', 'cancel_at',
        'remark', 'voice_remark_url', 'service_start_at', 'service_end_at',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'paid_amount' => 'float',
        'cancel_at' => 'datetime',
        'service_time' => 'datetime',
        'created_at' => 'datetime',
        'service_start_at' => 'datetime',
        'service_end_at' => 'datetime',
    ];

    // ── 订单类型常量 ──
    public const ORDER_TYPE_APPOINTMENT = 'appointment';
    public const ORDER_TYPE_PRODUCT = 'product';

    // ── 订单状态常量 ──
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SERVING   = 'serving';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDING = 'refunding';
    public const STATUS_REFUNDED  = 'refunded';

    // ── 关联关系 ──

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class, 'order_id');
    }

    public function review()
    {
        return $this->hasOne(OrderReview::class, 'order_id');
    }

    public function verification()
    {
        return $this->hasOne(OrderVerification::class, 'order_id');
    }

    public function refunds()
    {
        return $this->hasMany(OrderRefund::class, 'order_id');
    }

    // ── 查询作用域 ──

    /**
     * 按订单状态筛选
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * 按用户筛选
     */
    public function scopeByUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ── 业务方法 ──

    /**
     * 计算退款比例
     *
     * 下单15分钟内或距服务开始>6小时 → 全额退款 1.00
     * 距服务开始≤6小时 → 0.90
     * 已核销开始服务（serving）/ 已确认 / 已完成 → 0（不可退）
     *
     * 规则：核销即开始服务则不可退（M8），与 isRefundable()/refund() 保持一致。
     *
     * @return float 退款比例 0.00 ~ 1.00
     */
    public function calcRefundRatio(): float
    {
        $now = time();
        $createdAt = $this->created_at ? $this->created_at->getTimestamp() : $now;
        $serviceTime = $this->service_time ? $this->service_time->getTimestamp() : 0;

        // 下单15分钟内 → 全额
        if (($now - $createdAt) <= 900) {
            return 1.00;
        }

        // 服务已开始（serving）/ 已确认 / 已完成 → 不可退
        if (in_array($this->status, [self::STATUS_SERVING, self::STATUS_CONFIRMED, self::STATUS_COMPLETED], true)) {
            return 0.00;
        }

        // B8: 服务时间已过（未开始但已过时）的 paid 订单 → 不可退。
        // 与服务状态机一致（M8：serving/confirmed/completed 为 0），
        // 避免「已过预约时段仍可 90% 退款」占用技师档期却零损失。
        if ($serviceTime > 0 && $serviceTime <= $now) {
            return 0.00;
        }

        // 距服务开始时间 > 6小时 → 全额
        if ($serviceTime > 0 && ($serviceTime - $now) > 21600) {
            return 1.00;
        }

        // 距服务开始时间 ≤ 6小时 → 0.90
        if ($serviceTime > 0 && ($serviceTime - $now) <= 21600) {
            return 0.90;
        }

        // 无服务时间（商品订单等）默认全额
        return 1.00;
    }

    /**
     * 判断订单是否可退款
     * 状态为 paid 时可退（confirmed/serving 不可退，核销即开始服务）
     */
    public function isRefundable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
        ], true);
    }

    // ── ID 生成 ──

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
