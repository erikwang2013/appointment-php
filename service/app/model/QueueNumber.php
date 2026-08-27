<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Illuminate\Database\Eloquent\Builder;
use support\Model;

/**
 * 门店排队叫号模型
 *
 * 表: appointment_queue_number
 *
 * @method static Builder byStore(string $storeId) 按门店筛选
 * @method static Builder byStatus(string $status) 按状态筛选
 */
class QueueNumber extends Model
{
    protected $table = 'appointment_queue_number';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'store_id', 'user_id', 'number',
        'status', 'called_at',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'number'    => 'integer',
        'called_at' => 'datetime',
    ];

    // ── 队列状态常量 ──
    public const STATUS_WAITING   = 'waiting';
    public const STATUS_CALLED    = 'called';
    public const STATUS_SERVING   = 'serving';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // ── 关联关系 ──

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── 查询作用域 ──

    /**
     * 按门店筛选
     */
    public function scopeByStore(Builder $query, string $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * 按状态筛选
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // ── 业务方法 ──

    /**
     * 为指定门店生成今天的排号
     *
     * 号牌为当天递增序号，每天从 1 开始
     *
     * @param string $storeId
     * @return int
     */
    public static function generateTodayNumber(string $storeId): int
    {
        $todayMax = self::where('store_id', $storeId)
            ->whereDate('created_at', date('Y-m-d'))
            ->max('number');

        return ($todayMax ?? 0) + 1;
    }

    /**
     * 获取指定门店当前叫到的号
     *
     * @param string $storeId
     * @return int|null
     */
    public static function getCurrentNumber(string $storeId): ?int
    {
        $lastCalled = self::where('store_id', $storeId)
            ->whereDate('created_at', date('Y-m-d'))
            ->where('status', self::STATUS_SERVING)
            ->orderBy('called_at', 'desc')
            ->first();

        return $lastCalled?->number;
    }

    /**
     * 计算预估等待时间（分钟）
     *
     * 按当前队列中 waiting 人数 * 平均服务时长估算
     *
     * @param string $storeId
     * @return int 预估等待分钟数
     */
    public static function estimateWaitTime(string $storeId): int
    {
        $waitingCount = self::where('store_id', $storeId)
            ->whereDate('created_at', date('Y-m-d'))
            ->where('status', self::STATUS_WAITING)
            ->count();

        // 假设每位平均服务 15 分钟
        $avgServiceMin = 15;

        return $waitingCount * $avgServiceMin;
    }

    // ── ID 生成 ──

}
