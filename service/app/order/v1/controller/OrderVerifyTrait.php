<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\Money;
use app\common\NotificationReminderService;
use app\model\Order;
use app\model\OrderStatusLog;
use app\model\OrderVerification;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\UserPoints;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 核销（verify/verifyByCode + 佣金/返积分）
 *
 * 仅订单所属技师（已审核）可核销（M1）；核销事务内同步生成技师佣金收益
 * 与消费返积分（均幂等）。
 */
trait OrderVerifyTrait
{
    /**
     * M4: 返积分比例：1 元 = 1 积分（可按运营策略调整）
     */
    private const POINTS_PER_YUAN = 1;

    /**
     * 核销订单（核销码走 URL 路径）
     * POST /api/order/verify/{id}
     *
     * @deprecated 遗留入口，仅保留兼容；技师端小程序已统一走 POST /api/order/verify-by-code（核销码在请求体）
     * @param string $code 核销码（从路由参数 {id} 获取）
     */
    public function verify(Request $request, string $code)
    {
        // 已弃用：新入口为 POST /api/order/verify-by-code（核销码放请求体），此处仅保留兼容
        $code = trim((string) $code);
        if ($code === '') {
            return $this->error('核销码不能为空');
        }
        return $this->doVerify($request, $code);
    }

    /**
     * 扫码核销订单（核销码走请求体，供技师端小程序 wx.scanCode 调用）
     * POST /api/order/verify-by-code
     *
     * body: { code: string, verify_type?: string, location?: string }
     */
    public function verifyByCode(Request $request)
    {
        $code = trim((string) $request->input('code', ''));
        if ($code === '') {
            return $this->error('核销码不能为空');
        }
        return $this->doVerify($request, $code);
    }

    /**
     * 核销公共逻辑（verify / verifyByCode 共用）
     *
     * 状态机：paid/confirmed → serving（记录 service_start_at）；
     * 幂等：同一核销码重复核销返回已核销（不报错）；
     * M1：仅订单所属技师（已审核）可核销，拒绝任意登录用户越权操作。
     */
    private function doVerify(Request $request, string $code)
    {
        $userId = $request->user_id;

        $verification = OrderVerification::where('code', $code)->first();

        if (!$verification) {
            return $this->error('核销码无效', 404);
        }

        $order = Order::find($verification->order_id);

        if (!$order) {
            return $this->error('关联订单不存在', 404);
        }

        // 幂等：已核销直接返回成功，不重复推进状态（客户端可据此提示「已核销」）
        if ($verification->verified_at) {
            return $this->success(['already_verified' => true, 'order' => $order], '该订单已核销');
        }

        // B1: 统一 per-order 互斥锁，防核销与退款/取消并发
        $lockKey = 'order_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            // 锁内重新读取核销码与订单状态
            $verification = OrderVerification::where('code', $code)->first();
            if (!$verification) {
                return $this->error('核销码无效', 404);
            }
            $order = Order::find($verification->order_id);
            if (!$order) {
                return $this->error('关联订单不存在', 404);
            }
            // 幂等（锁内复查，防并发重复核销）
            if ($verification->verified_at) {
                return $this->success(['already_verified' => true, 'order' => $order], '该订单已核销');
            }
            if (!in_array($order->status, [Order::STATUS_PAID, Order::STATUS_CONFIRMED], true)) {
                return $this->error('当前订单状态不可核销');
            }

            // M1: 水平越权防护 —— 仅订单所属技师（已审核）可核销，拒绝任意登录用户越权操作
            $technician = TechnicianProfile::where('user_id', $userId)
                ->where('status', 'approved')
                ->first();
            if (!$technician || (string)$order->technician_id !== (string)$technician->id) {
                return $this->error('无权限核销该订单', 403);
            }

            $verifyType = $request->input('verify_type', OrderVerification::VERIFY_TYPE_SCAN);
            $location   = $request->input('location', '');

            $verification->verified_by  = $userId;
            $verification->verify_type  = $verifyType;
            $verification->location     = $location;
            $verification->verified_at  = now();
            $verification->save();

            // 更新订单状态 + M1 生成技师收益（同事务；幂等：同 order_id 的 commission 不重复生成）
            Db::beginTransaction();
            try {
                if ($order->status !== Order::STATUS_SERVING) {
                    $verifyFromStatus = $order->status;
                    $order->status = Order::STATUS_SERVING;
                    $order->service_start_at = now();
                    $order->save();
                }
                $this->createCommissionEarning($order);
                $this->rewardOrderPoints($order);
                Db::commit();

                // 状态时间线：→ serving（技师核销，状态实际推进时记录）
                if (isset($verifyFromStatus)) {
                    OrderStatusLog::record($order->id, $verifyFromStatus, Order::STATUS_SERVING, '核销开始服务', 'technician');
                }
            } catch (\Throwable $e) {
                Db::rollBack();
                Log::error('[OrderController] verify persist failed: ' . $e->getMessage());
                return $this->error('核销失败，请稍后重试');
            }

            // 站内消息通知用户（非阻塞，失败不影响主流程）
            $this->notifyVerified($order);

            // 订阅消息：核销成功（非阻塞，失败不影响主流程）
            $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_VERIFIED);

            // WebSocket 实时推送
            $this->pushOrderUpdate($order);

            return $this->success($order, '核销成功');
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * M1: 核销成功后生成技师佣金收益（幂等：同订单同类型不重复生成）
     *
     * 金额 = 订单实付 × 佣金率（appointment_technician_commission_config.commission_rate，百分比）。
     * 状态初始 pending（待结算），由 autoSettle 置 settled，提现时置 withdrawn。
     */
    private function createCommissionEarning(Order $order): void
    {
        if (!$order->technician_id || (float)$order->paid_amount <= 0) {
            return;
        }

        // 幂等：同 order_id 的 commission 收益已存在则不重复生成
        $exists = TechnicianEarning::where('order_id', $order->id)
            ->where('type', 'commission')
            ->exists();
        if ($exists) {
            return;
        }

        $rate = (float) Db::table('appointment_technician_commission_config')
            ->where('technician_id', $order->technician_id)
            ->value('commission_rate');
        if ($rate <= 0) {
            return; // 未配置佣金率则不生成收益
        }

        // 佣金 = 实付 × 费率(%)，string 域乘除防浮点丢分
        $amount = (float) Money::round(Money::div(Money::mul((string)$order->paid_amount, (string)$rate), '100'), 2);
        if (Money::cmp((string)$amount, '0') <= 0) {
            return;
        }

        TechnicianEarning::create([
            'id'            => TechnicianEarning::generateId(),
            'technician_id' => $order->technician_id,
            'order_id'      => $order->id,
            'type'          => 'commission',
            'amount'        => $amount,
            'description'   => '服务佣金（订单 ' . $order->order_no . '）',
            'status'        => 'pending',
        ]);
    }

    /**
     * M4: 消费返积分（核销时发放，与佣金同事务，失败随核销整体回滚）
     *
     * 规则：按订单实付金额返积分，1 元 = 1 积分，向下取整（POINTS_PER_YUAN 可配置）。
     * 幂等：同 order_id + source=order 的返积分记录已存在则不重复发放（覆盖重试/并发场景）。
     * balance 为逐条快照：上一条余额 + 本次积分（同事务内锁定最后一条流水，防并发串行）。
     */
    private function rewardOrderPoints(Order $order): void
    {
        $points = (int) floor((float) $order->paid_amount * self::POINTS_PER_YUAN);
        if ($points <= 0) {
            return;
        }

        // 幂等：同订单的返积分已发放则不重复发放
        $exists = UserPoints::where('order_id', $order->id)
            ->where('source', 'order')
            ->exists();
        if ($exists) {
            return;
        }

        // balance = 上一条余额 + 本次积分（快照累加，锁最后一条流水防同用户并发串行）
        $lastBalance = (int) (UserPoints::where('user_id', $order->user_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->value('balance') ?? 0);

        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'earn',
            'points'      => $points,
            'balance'     => $lastBalance + $points,
            'source'      => 'order',
            'order_id'    => $order->id,
            'description' => '订单消费返积分（订单 ' . $order->order_no . '）',
            'expires_at'  => UserPoints::expiryAt(),
        ]);
    }
}
