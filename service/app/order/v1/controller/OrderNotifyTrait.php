<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\NotificationReminderService;
use app\common\PushService;
use app\common\WechatTemplateMessageService;
use app\model\Notification;
use app\model\Order;
use app\model\OrderRefund;
use app\model\User;
use support\Log;

/**
 * 通知（订阅消息/模板消息/站内/WebSocket 推送）
 *
 * 全部非阻塞：失败仅记日志，不影响主流程。
 */
trait OrderNotifyTrait
{
    /**
     * 订单事件订阅消息通知（非阻塞，失败不影响主流程）
     *
     * 委托 NotificationReminderService::sendSubscribeForOrderEvent：与预约提醒同一
     * 发送链路（WechatTemplateMessageService::sendSubscribeMessage，独立小程序
     * access_token），幂等基于 erik_notification.push_sent_at（同订单同场景只推
     * 一次；微信失败不写标记，不影响主流程）。
     *
     * @param Order  $order 订单
     * @param string $scene 场景（NotificationReminderService::SCENE_PAY/REFUND/VERIFIED）
     * @param array  $extra 场景补充数据（refund → refund_amount/refund_reason）
     */
    protected function notifySubscribeEvent(Order $order, string $scene, array $extra = []): void
    {
        try {
            (new NotificationReminderService())->sendSubscribeForOrderEvent($order, $scene, $extra);
        } catch (\Throwable $e) {
            Log::warning('[OrderController] notifySubscribeEvent failed: ' . $e->getMessage());
        }
    }

    /**
     * WebSocket 实时推送订单状态更新
     *
     * 非阻塞调用，失败不影响主流程。
     * 注意: 当 WebSocket 进程与 HTTP 进程分离时，PushService 的静态连接池
     * 可能为空。生产环境需配合 Redis Pub/Sub 或 webman Channel 实现跨进程推送。
     */
    private function pushOrderUpdate(Order $order): void
    {
        try {
            $technicianId = $order->technician_id ? (int)$order->technician_id : 0;
            $clientUserId = (int)$order->user_id;

            PushService::sendOrderUpdate(
                $clientUserId,
                $technicianId,
                $order->id,
                $order->order_no,
                $order->status,
                [
                    'order_type' => $order->order_type,
                    'paid_amount' => $order->paid_amount,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[OrderController] pushOrderUpdate failed: ' . $e->getMessage());
        }
    }

    /**
     * 核销成功后发送站内消息通知用户（type='order'，非阻塞）
     */
    private function notifyVerified(Order $order): void
    {
        try {
            Notification::create([
                'id'       => Notification::generateId(),
                'user_id'  => $order->user_id,
                'type'     => 'order',
                'title'    => '订单已核销',
                'content'  => '您的订单 ' . $order->order_no . ' 已核销，服务即将开始，祝您体验愉快。',
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[OrderController] notifyVerified failed: ' . $e->getMessage());
        }
    }

    /**
     * 发送订单确认模板消息（非阻塞）
     */
    private function sendOrderConfirmTemplate(string $userId, Order $order): void
    {
        try {
            $user = User::find($userId);
            if (!$user || empty($user->wx_openid)) {
                return;
            }

            $service = new WechatTemplateMessageService();

            $serviceName = '';
            $items = $order->items()->get();
            if ($items->isNotEmpty()) {
                $serviceName = $items->first()->name;
            }

            $service->sendOrderConfirm($user->wx_openid, [
                'order_no'     => $order->order_no,
                'service_name' => $serviceName,
                'service_time' => $order->service_time ? $order->service_time->format('Y-m-d H:i') : '',
                'technician'   => '',
                'store'        => '',
                'remark'       => $order->remark ?? '感谢您的预约',
            ]);
        } catch (\Throwable $e) {
            Log::warning('[OrderController] sendOrderConfirmTemplate failed: ' . $e->getMessage());
        }
    }

    /**
     * 发送退款通知模板消息（非阻塞）
     */
    private function sendRefundNotifyTemplate(string $userId, Order $order, float $refundAmount, string $reason): void
    {
        try {
            $user = User::find($userId);
            if (!$user || empty($user->wx_openid)) {
                return;
            }

            $service = new WechatTemplateMessageService();

            $refund = OrderRefund::where('order_id', $order->id)->latest()->first();
            $refundNo = $refund ? $refund->refund_no : '';

            $service->sendRefundNotify($user->wx_openid, [
                'order_no'      => $order->order_no,
                'refund_no'     => $refundNo,
                'refund_amount' => number_format($refundAmount, 2) . ' 元',
                'reason'        => $reason ?: '用户申请退款',
            ]);
        } catch (\Throwable $e) {
            Log::warning('[OrderController] sendRefundNotifyTemplate failed: ' . $e->getMessage());
        }
    }

    /**
     * 站内退款通知（幂等，写失败不影响主流程）
     *
     * 标题按退款单状态推导：pending → 「退款申请已受理」；success → 「退款已到账」。
     * 幂等：同订单同标题已存在则跳过——补偿（completeOneRefundCompensation）与主路径
     * （doRefund/doCancel 成功分支）并发时不会重复写。
     */
    private function writeRefundNotification(Order $order, OrderRefund $refund): void
    {
        try {
            $title = match ($refund->status) {
                OrderRefund::STATUS_PENDING => '退款申请已受理',
                OrderRefund::STATUS_SUCCESS => '退款已到账',
                default => null,
            };
            if ($title === null) {
                return;
            }

            $exists = Notification::where('order_id', $order->id)
                ->where('title', $title)
                ->exists();
            if ($exists) {
                return;
            }

            $amount = number_format((float) $refund->amount, 2);
            $content = $refund->status === OrderRefund::STATUS_SUCCESS
                ? "您的订单 {$order->order_no} 已退款 ¥{$amount}，款项将原路退回至支付账户。"
                : "您的订单 {$order->order_no} 退款申请已受理，退款金额 ¥{$amount}，处理完成后将原路退回。";

            Notification::create([
                'id'       => Notification::generateId(),
                'user_id'  => (string) $order->user_id,
                'type'     => 'order',
                'title'    => $title,
                'content'  => $content,
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[OrderController] writeRefundNotification failed: ' . $e->getMessage());
        }
    }
}
