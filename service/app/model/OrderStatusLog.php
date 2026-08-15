<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Log;
use support\Model;

/**
 * 订单状态变更时间线模型
 */
class OrderStatusLog extends Model
{
    protected $table = 'erik_order_status_log';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'order_id', 'from_status', 'to_status',
        'remark', 'operator',
    ];

    protected $casts = [
        'from_status' => 'string',
        'to_status'   => 'string',
        'remark'      => 'string',
        'operator'    => 'string',
    ];

    /**
     * 记录一次状态变更（幂等由业务侧状态机保证；失败仅记日志，绝不阻断主流程）
     *
     * 参数用 mixed + 内部转型：调用方 strict_types=1 时强类型声明会在调用点抛
     * TypeError（跳出本方法 try/catch），故参数不设类型约束。
     */
    public static function record(mixed $orderId, mixed $fromStatus, mixed $toStatus, mixed $remark = '', mixed $operator = 'system'): void
    {
        try {
            self::create([
                'id'          => self::generateId(),
                'order_id'    => (string) $orderId,
                'from_status' => $fromStatus === null || $fromStatus === '' ? null : (string) $fromStatus,
                'to_status'   => (string) $toStatus,
                'remark'      => $remark === null || $remark === '' ? null : (string) $remark,
                'operator'    => (string) $operator,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[OrderStatusLog] record failed, order: ' . $orderId
                . ' -> ' . $toStatus . ': ' . $e->getMessage());
        }
    }
}
