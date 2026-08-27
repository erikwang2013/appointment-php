<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Order;
use app\model\TechnicianEarning;
use support\Db;
use support\Log;

/**
 * 回头客奖励服务（R24）
 *
 * 用户对同一技师 30 天内第 2 次消费（订单完成）时，给技师发放奖金：
 * 金额 = 订单实付 paid_amount × 奖励比例（appointment_system_config group=return_customer
 * key=ratio，默认 0.05）；enabled 开关为 0 时整体停用。
 *
 * 落账复用 appointment_technician_earnings（type='return_customer'，status='pending'），
 * 与佣金同表同结算链：由 admin autoSettle 统一置 settled，技师端
 * GET /api/technician/earnings 汇总/明细自动包含，无需新端点。
 *
 * 幂等：同 order_id + type='return_customer' 已存在则不重复发放；
 * 调用方（WorkController::complete）在行锁事务内调用，同一订单并发完成被串行化，
 * 发放路径同订单至多成功一次。
 *
 * 注意：本服务不管理事务，必须在调用方事务内调用。
 */
class ReturnCustomerRewardService
{
    private const CONFIG_GROUP     = 'return_customer';
    private const CONFIG_KEY_ENABLED = 'enabled';
    private const CONFIG_KEY_RATIO = 'ratio';
    private const DEFAULT_ENABLED  = true;
    private const DEFAULT_RATIO    = 0.05;
    private const WINDOW_DAYS      = 30;

    /** 收益记录类型（appointment_technician_earnings.type） */
    public const TYPE_RETURN_CUSTOMER = 'return_customer';

    /**
     * 订单完成回调：30 天内二次消费发放技师回头客奖金（幂等；需在事务内调用）
     */
    public static function handleOrderCompleted(Order $order): void
    {
        if ((string) $order->status !== Order::STATUS_COMPLETED) {
            return;
        }
        if (!$order->technician_id || (float) $order->paid_amount <= 0) {
            return;
        }
        if (!self::isEnabled()) {
            return;
        }

        // 幂等：同订单的回头客奖励已发放过则不重复发放
        $exists = TechnicianEarning::where('order_id', $order->id)
            ->where('type', self::TYPE_RETURN_CUSTOMER)
            ->exists();
        if ($exists) {
            return;
        }

        // 30 天窗口：该用户在该技师处已有其他已完成订单（第 2 次起发放）
        $hasRecentCompleted = Order::where('user_id', $order->user_id)
            ->where('technician_id', $order->technician_id)
            ->where('status', Order::STATUS_COMPLETED)
            ->where('id', '<>', $order->id)
            ->where('service_end_at', '>=', date('Y-m-d H:i:s', strtotime('-' . self::WINDOW_DAYS . ' days')))
            ->exists();
        if (!$hasRecentCompleted) {
            return;
        }

        $amount = round((float) $order->paid_amount * self::getRatio(), 2);
        if ($amount <= 0) {
            return;
        }

        // 事务由调用方管理：写入失败抛出即整体回滚，可安全重试
        TechnicianEarning::create([
            'id'            => TechnicianEarning::generateId(),
            'technician_id' => $order->technician_id,
            'order_id'      => $order->id,
            'type'          => self::TYPE_RETURN_CUSTOMER,
            'amount'        => $amount,
            'description'   => '回头客奖励（订单 ' . $order->order_no . '，' . self::WINDOW_DAYS . '天内二次消费）',
            'status'        => 'pending',
        ]);
    }

    /**
     * 奖励开关：appointment_system_config (group=return_customer, key=enabled)，
     * 缺省开启；'0'/'false'/'off' 视为关闭
     */
    public static function isEnabled(): bool
    {
        try {
            $value = Db::table('appointment_system_config')
                ->where('group', self::CONFIG_GROUP)
                ->where('key', self::CONFIG_KEY_ENABLED)
                ->value('value');
        } catch (\Throwable) {
            return self::DEFAULT_ENABLED;
        }

        if ($value === null) {
            return self::DEFAULT_ENABLED;
        }
        return !in_array((string) $value, ['0', 'false', 'off'], true);
    }

    /**
     * 奖励比例：appointment_system_config (group=return_customer, key=ratio)，缺省 0.05
     */
    public static function getRatio(): float
    {
        try {
            $ratio = (float) Db::table('appointment_system_config')
                ->where('group', self::CONFIG_GROUP)
                ->where('key', self::CONFIG_KEY_RATIO)
                ->value('value');
        } catch (\Throwable) {
            $ratio = 0.0;
        }

        if ($ratio <= 0 || $ratio > 1) {
            return self::DEFAULT_RATIO;
        }
        return $ratio;
    }
}
