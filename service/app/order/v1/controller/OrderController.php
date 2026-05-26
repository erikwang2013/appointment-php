<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\common\WechatTemplateMessageService;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\OrderVerification;
use app\model\User;
use Illuminate\Support\Facades\Redis;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 订单控制器
 *
 * 处理订单创建、支付、退款、核销、评价等业务
 */
class OrderController extends BaseController
{
    /**
     * 创建订单
     * POST /api/order
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;

        $items = $request->input('items', []);
        if (empty($items) || !is_array($items)) {
            return $this->error('请选择服务或商品');
        }

        $items = $this->decodeIds($items);

        $orderType      = $request->input('order_type', 'appointment');
        $technicianId   = $request->input('technician_id');
        $storeId        = $request->input('store_id');
        $serviceTime    = $request->input('service_time');
        $couponId       = $request->input('coupon_id');
        $userCouponId   = $request->input('user_coupon_id');
        $remark         = $request->input('remark', '');
        $voiceRemarkUrl = $request->input('voice_remark_url', '');

        if ($technicianId) {
            $technicianId = $this->decodeId($technicianId);
        }
        if ($storeId) {
            $storeId = $this->decodeId($storeId);
        }
        if ($couponId) {
            $couponId = $this->decodeId($couponId);
        }
        if ($userCouponId) {
            $userCouponId = $this->decodeId($userCouponId);
        }

        // 预约订单需要技师和服务时间
        if ($orderType === Order::ORDER_TYPE_APPOINTMENT) {
            if (!$technicianId || !$serviceTime) {
                return $this->error('预约订单需要选择技师和服务时间');
            }

            // 技师时间槽锁定
            $timeSlot = date('YmdHi', strtotime($serviceTime));
            $lockKey = "technician_lock:{$technicianId}:{$timeSlot}";
            $acquired = Redis::connection()->set($lockKey, $userId, 'EX', 180, 'NX');

            if (!$acquired) {
                return $this->error('该时段技师已被他人锁定，请选择其他时间段');
            }
        }

        $lockKey = null;
        if ($orderType === Order::ORDER_TYPE_APPOINTMENT) {
            $timeSlot = date('YmdHi', strtotime($serviceTime));
            $lockKey = "technician_lock:{$technicianId}:{$timeSlot}";
        }

        // 计算金额
        $totalAmount = 0;
        $orderItemsData = [];

        foreach ($items as $item) {
            $targetType = $item['target_type'] ?? 'service';
            $targetId   = $item['target_id'] ?? 0;
            $name       = $item['name'] ?? '';
            $coverImage = $item['cover_image'] ?? '';
            $price      = (float)($item['price'] ?? 0);
            $quantity   = (int)($item['quantity'] ?? 1);
            $specInfo   = $item['spec_info'] ?? null;

            if (empty($name) || $price <= 0) {
                return $this->error('订单项信息不完整');
            }

            $subtotal = $price * $quantity;
            $totalAmount += $subtotal;

            $orderItemsData[] = [
                'id'          => OrderItem::generateId(),
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'name'        => $name,
                'cover_image' => $coverImage,
                'price'       => $price,
                'quantity'    => $quantity,
                'spec_info'   => $specInfo ? json_encode($specInfo, JSON_UNESCAPED_UNICODE) : null,
            ];
        }

        $discountAmount = 0.00;
        $paidAmount = $totalAmount - $discountAmount;
        if ($paidAmount < 0) {
            $paidAmount = 0.00;
        }

        $orderNo = generate_order_no();

        Db::beginTransaction();
        try {
            $order = Order::create([
                'id'              => Order::generateId(),
                'order_no'        => $orderNo,
                'user_id'         => $userId,
                'technician_id'   => $technicianId,
                'store_id'        => $storeId,
                'order_type'      => $orderType,
                'total_amount'    => $totalAmount,
                'discount_amount' => $discountAmount,
                'paid_amount'     => $paidAmount,
                'coupon_id'       => $couponId,
                'user_coupon_id'  => $userCouponId,
                'service_time'    => $serviceTime ?: null,
                'status'           => Order::STATUS_PENDING,
                'remark'           => $remark,
                'voice_remark_url' => $voiceRemarkUrl ?: null,
            ]);

            // 创建订单明细
            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            // 创建支付记录（待支付）
            OrderPayment::create([
                'id'         => OrderPayment::generateId(),
                'order_id'   => $order->id,
                'payment_no' => OrderPayment::generatePaymentNo(),
                'pay_type'   => 'wechat',
                'amount'     => $paidAmount,
                'status'     => OrderPayment::STATUS_PENDING,
            ]);

            // 生成核销码（仅预约订单）
            if ($orderType === Order::ORDER_TYPE_APPOINTMENT) {
                OrderVerification::create([
                    'id'       => OrderVerification::generateId(),
                    'order_id' => $order->id,
                    'code'     => OrderVerification::generateCode(),
                ]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            // 释放技师锁
            if ($lockKey) {
                Redis::connection()->del($lockKey);
            }
            return $this->error('订单创建失败: ' . $e->getMessage());
        }

        $order->load(['items', 'payment']);

        // 发送订单确认模板消息（非阻塞，失败不影响主流程）
        $this->sendOrderConfirmTemplate($userId, $order);

        return $this->success($order, '订单创建成功');
    }

    /**
     * 用户订单列表
     * GET /api/order/list
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $status = $request->input('status', '');

        $query = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $perPage = (int)$request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->paginate($paginator);
    }

    /**
     * 订单详情
     * GET /api/order/detail/{id}
     */
    public function show(Request $request, string $id)
    {
        $userId = $request->user_id;

        $order = Order::with(['items', 'payment', 'technician', 'store', 'verification', 'review'])
            ->where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        // 追加退款比例信息
        $order->refund_ratio = $order->calcRefundRatio();
        $order->is_refundable = $order->isRefundable();

        return $this->success($order);
    }

    /**
     * 取消订单
     * POST /api/order/cancel/{id}
     */
    public function cancel(Request $request, string $id)
    {
        $userId = $request->user_id;

        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID], true)) {
            return $this->error('当前订单状态不可取消');
        }

        $cancelReason = $request->input('cancel_reason', '');

        Db::beginTransaction();
        try {
            // 已支付的订单需计算退款
            if ($order->status === Order::STATUS_PAID) {
                $ratio = $order->calcRefundRatio();
                $refundAmount = round($order->paid_amount * $ratio, 2);

                if ($refundAmount > 0) {
                    $payment = $order->payment()->first();
                    OrderRefund::create([
                        'id'         => OrderRefund::generateId(),
                        'order_id'   => $order->id,
                        'payment_id' => $payment->id ?? null,
                        'refund_no'  => OrderRefund::generateRefundNo(),
                        'amount'     => $refundAmount,
                        'ratio'      => $ratio,
                        'reason'     => $cancelReason ?: '用户取消订单',
                        'status'     => OrderRefund::STATUS_PENDING,
                    ]);
                }
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->cancel_reason = $cancelReason;
            $order->cancel_at = now();
            $order->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('取消订单失败: ' . $e->getMessage());
        }

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        return $this->success(null, '订单已取消');
    }

    /**
     * 发起支付
     * POST /api/order/pay/{id}
     */
    public function pay(Request $request, string $id)
    {
        $userId = $request->user_id;

        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return $this->error('当前订单状态不可支付');
        }

        // 查找或创建支付记录
        $payment = OrderPayment::where('order_id', $order->id)->first();

        if (!$payment) {
            $payment = OrderPayment::create([
                'id'         => OrderPayment::generateId(),
                'order_id'   => $order->id,
                'payment_no' => OrderPayment::generatePaymentNo(),
                'pay_type'   => 'wechat',
                'amount'     => $order->paid_amount,
                'status'     => OrderPayment::STATUS_PENDING,
            ]);
        } elseif ($payment->status === OrderPayment::STATUS_CLOSED || $payment->status === OrderPayment::STATUS_FAILED) {
            $payment->payment_no = OrderPayment::generatePaymentNo();
            $payment->amount = $order->paid_amount;
            $payment->status = OrderPayment::STATUS_PENDING;
            $payment->save();
        }

        // 微信支付占位 — 返回支付参数
        $payParams = [
            'payment_no'  => $payment->payment_no,
            'amount'      => $payment->amount,
            'order_no'    => $order->order_no,
            'pay_type'    => 'wechat',
        ];

        return $this->success($payParams, '支付参数已生成');
    }

    /**
     * 申请退款
     * POST /api/order/refund/{id}
     */
    public function refund(Request $request, string $id)
    {
        $userId = $request->user_id;

        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if (!$order->isRefundable()) {
            return $this->error('当前订单状态不可退款');
        }

        $ratio = $order->calcRefundRatio();
        if ($ratio <= 0) {
            return $this->error('当前订单不支持退款');
        }

        $reason = $request->input('reason', '');

        $refundAmount = round($order->paid_amount * $ratio, 2);

        Db::beginTransaction();
        try {
            $payment = $order->payment()->first();

            OrderRefund::create([
                'id'         => OrderRefund::generateId(),
                'order_id'   => $order->id,
                'payment_id' => $payment->id ?? null,
                'refund_no'  => OrderRefund::generateRefundNo(),
                'amount'     => $refundAmount,
                'ratio'      => $ratio,
                'reason'     => $reason,
                'status'     => OrderRefund::STATUS_PENDING,
            ]);

            $order->status = Order::STATUS_REFUNDING;
            $order->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('申请退款失败: ' . $e->getMessage());
        }

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // 发送退款通知模板消息（非阻塞，失败不影响主流程）
        $this->sendRefundNotifyTemplate($userId, $order, $refundAmount, $reason);

        return $this->success([
            'refund_amount' => $refundAmount,
            'ratio'         => $ratio,
        ], '退款申请已提交');
    }

    /**
     * 核销订单
     * POST /api/order/verify/{id}
     *
     * @param string $code 核销码（从路由参数 {id} 获取）
     */
    public function verify(Request $request, string $code)
    {
        $userId = $request->user_id;

        $verification = OrderVerification::where('code', $code)->first();

        if (!$verification) {
            return $this->error('核销码无效', 404);
        }

        if ($verification->verified_at) {
            return $this->error('该核销码已被使用');
        }

        $order = Order::find($verification->order_id);

        if (!$order) {
            return $this->error('关联订单不存在', 404);
        }

        if (!in_array($order->status, [Order::STATUS_PAID, Order::STATUS_CONFIRMED], true)) {
            return $this->error('当前订单状态不可核销');
        }

        $verifyType = $request->input('verify_type', OrderVerification::VERIFY_TYPE_SCAN);
        $location   = $request->input('location', '');

        $verification->verified_by  = $userId;
        $verification->verify_type  = $verifyType;
        $verification->location     = $location;
        $verification->verified_at  = now();
        $verification->save();

        // 更新订单状态
        if ($order->status !== Order::STATUS_SERVING) {
            $order->status = Order::STATUS_SERVING;
            $order->service_start_at = now();
            $order->save();
        }

        return $this->success($order, '核销成功');
    }

    /**
     * 释放技师时间槽锁定
     */
    private function releaseTechnicianLock(Order $order): void
    {
        if (!$order->technician_id || !$order->service_time) {
            return;
        }

        $timeSlot = date('YmdHi', $order->service_time->getTimestamp());
        $lockKey = "technician_lock:{$order->technician_id}:{$timeSlot}";

        Redis::connection()->del($lockKey);
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
}
