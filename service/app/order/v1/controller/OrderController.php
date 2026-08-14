<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\common\PriceCalculator;
use app\common\PushService;
use app\common\WechatPayService;
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
        $memberCardUsageId = $request->input('member_card_usage_id');
        $usePoints      = (int)$request->input('use_points', 0);
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
        if ($memberCardUsageId) {
            $memberCardUsageId = $this->decodeId($memberCardUsageId);
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

        // 计算金额（由 PriceCalculator 统一计算，此处仅组装订单项数据）
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

        $orderNo = generate_order_no();
        // 预生成订单 ID：次卡使用记录需要在订单事务内落库，依赖订单 ID
        $orderId = Order::generateId();

        Db::beginTransaction();
        try {
            // 优惠计价引擎（互斥：次卡/券/积分 一次订单仅一种；内部全程按分计算）
            $pricing = PriceCalculator::calculate($items, [
                'user_id'              => $userId,
                'order_id'             => $orderId,
                'coupon_id'            => $couponId,
                'user_coupon_id'       => $userCouponId,
                'member_card_usage_id' => $memberCardUsageId,
                'use_points'           => $usePoints,
            ]);

            $order = Order::create([
                'id'                   => $orderId,
                'order_no'             => $orderNo,
                'user_id'              => $userId,
                'technician_id'        => $technicianId,
                'store_id'             => $storeId,
                'order_type'           => $orderType,
                'total_amount'         => $pricing['total_amount'],
                'discount_amount'      => $pricing['discount_amount'],
                'paid_amount'          => $pricing['paid_amount'],
                'coupon_id'            => (int)($pricing['coupon_id'] ?? 0),
                'user_coupon_id'       => (int)($pricing['user_coupon_id'] ?? 0),
                'member_card_usage_id' => (int)($pricing['member_card_usage_id'] ?? 0),
                'service_time'         => $serviceTime ?: null,
                'status'               => Order::STATUS_PENDING,
                'remark'               => $remark,
                'voice_remark_url'     => $voiceRemarkUrl ?: null,
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
                'amount'     => $pricing['paid_amount'],
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
            // 计价引擎的业务校验错误（券已被使用/次数不足等）直接透出
            if ($e instanceof \InvalidArgumentException) {
                return $this->error($e->getMessage());
            }
            return $this->error('订单创建失败: ' . $e->getMessage());
        }

        $order->load(['items', 'payment']);

        // 发送订单确认模板消息（非阻塞，失败不影响主流程）
        $this->sendOrderConfirmTemplate($userId, $order);

        // WebSocket 实时推送：通知技师有新订单
        $this->pushOrderUpdate($order);

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

        // 防并发取消锁（NX EX 35s，token 校验释放），拿不到锁说明取消流程处理中
        $lockKey = 'cancel_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            return $this->doCancel($request, $order);
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 取消订单（在 cancel_lock 内执行）
     *
     * 阶段一：事务内建退款单(pending) + 订单置 cancelled 并提交；
     * 阶段二：事务外调微信退款；失败回滚订单为 paid（可重试），成功订单置 refunded。
     */
    private function doCancel(Request $request, Order $order)
    {
        $cancelReason = $request->input('cancel_reason', '');

        // 阶段一：事务内建退款单(pending) + 订单置 cancelled 并提交；微信 IO 一律事务外
        $refundRecord = null;
        $refundAmount = 0.00;

        Db::beginTransaction();
        try {
            // 已支付的订单需计算退款
            if ($order->status === Order::STATUS_PAID) {
                $ratio = $order->calcRefundRatio();
                $refundAmount = round($order->paid_amount * $ratio, 2);

                if ($refundAmount > 0) {
                    $payment = $order->payment()->first();
                    $refundRecord = OrderRefund::create([
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

        // 阶段二：事务外调微信退款
        if ($refundRecord) {
            $payService = new WechatPayService();
            $result = $payService->refund(
                $order->order_no,
                $refundRecord->refund_no,
                (float)$order->paid_amount,
                (float)$refundAmount
            );

            if (!empty($result['error'])) {
                Log::error('[OrderController] refund failed on cancel, order_no: ' . $order->order_no . ', error: ' . $result['error']);
                // 小事务：退款单置 failed，订单回滚 paid（可重试），清空取消标记
                Db::beginTransaction();
                try {
                    $refundRecord->status = OrderRefund::STATUS_FAILED;
                    $refundRecord->save();
                    $order->status = Order::STATUS_PAID;
                    $order->cancel_reason = ''; // erik_order.cancel_reason 为 NOT NULL，置空串而非 null
                    $order->cancel_at = null;
                    $order->save();
                    Db::commit();
                } catch (\Throwable $e2) {
                    Db::rollBack();
                    Log::error('[OrderController] refund rollback persist failed: ' . $e2->getMessage());
                }
                return $this->error('退款处理失败请重试');
            }

            // 小事务：退款单置 success + refunded_at，订单 refunded
            Db::beginTransaction();
            try {
                $refundRecord->status = OrderRefund::STATUS_SUCCESS;
                $refundRecord->refunded_at = now();
                $refundRecord->save();
                $order->status = Order::STATUS_REFUNDED;
                $order->save();
                Db::commit();
            } catch (\Throwable $e2) {
                Db::rollBack();
                Log::error('[OrderController] refund success persist failed: ' . $e2->getMessage());
                return $this->error('退款处理失败请重试');
            }
        }

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

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

        // 防并发支付锁（NX EX 35s，token 校验释放），拿不到锁说明支付流程处理中
        $lockKey = 'pay_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('支付处理中，请稍后再试');
        }

        try {
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

            // 全额优惠订单（paid_amount=0）：无支付路径，直接标记支付成功（单一消费点）
            if ((float) $payment->amount <= 0) {
                // 零元直通交易号：FREE + payment_no（payment_no 全局唯一，规避 uk_transaction_id 唯一索引冲突）
                $freeResult = (new WechatPayService())->markOrderPaid($payment->payment_no, 'FREE' . $payment->payment_no, 0.0, 'wechat');
                if (empty($freeResult['success'])) {
                    Log::error('[OrderController] markOrderPaid failed (free order), order_no: ' . $order->order_no . ', error: ' . $freeResult['message']);
                    return $this->error('订单状态更新失败: ' . $freeResult['message']);
                }
                return $this->success([
                    'order_no'   => $order->order_no,
                    'payment_no' => $payment->payment_no,
                    'amount'     => 0,
                    'status'     => Order::STATUS_PAID,
                ], '订单支付成功');
            }

            // 用户 openid（hidden 字段在服务层可读）
            $user = User::find($order->user_id);
            if (!$user || empty($user->wx_openid)) {
                return $this->error('用户微信信息缺失，无法发起支付');
            }

            // 商品描述取首条订单项名称
            $body = '预约服务';
            $firstItem = $order->items()->first();
            if ($firstItem && $firstItem->name) {
                $body = $firstItem->name;
            }

            // 微信统一下单（金额以元传入，服务内部转分）
            $payService = new WechatPayService();
            $result = $payService->unifiedOrder([
                'openid'       => $user->wx_openid,
                'total_fee'    => (float)$payment->amount,
                'out_trade_no' => $order->order_no,
                'body'         => $body,
                'trade_type'   => 'JSAPI',
            ]);

            if (!empty($result['error'])) {
                Log::error('[OrderController] unifiedOrder failed, order_no: ' . $order->order_no . ', error: ' . $result['error']);
                // payment 保持 pending，允许重试
                return $this->error('支付下单失败: ' . $result['error']);
            }

            return $this->success([
                'prepay_id'   => $result['prepay_id'],
                'sign_params' => $result['sign_params'],
                'payment_no'  => $payment->payment_no,
                'amount'      => $payment->amount,
                'order_no'    => $order->order_no,
            ], '支付参数已生成');
        } finally {
            // 释放支付锁（token 校验，仅释放自己持有的锁）
            $this->releaseLock($lockKey, $lockToken);
        }
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

        // 防并发退款锁（NX EX 35s，token 校验释放），拿不到锁说明退款流程处理中
        $lockKey = 'refund_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            return $this->doRefund($request, $order, $ratio);
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 申请退款（在 refund_lock 内执行）
     *
     * 阶段一：事务内建退款单(pending) + 订单置 refunding 并提交；
     * 阶段二：事务外调微信退款；失败回滚订单为 paid（可重试），成功订单置 refunded。
     */
    private function doRefund(Request $request, Order $order, float $ratio)
    {
        $reason = $request->input('reason', '');

        $refundAmount = round($order->paid_amount * $ratio, 2);

        // 阶段一：事务内建退款单(pending) + 订单置 refunding 并提交；微信 IO 一律事务外
        $refundRecord = null;

        Db::beginTransaction();
        try {
            $payment = $order->payment()->first();

            $refundRecord = OrderRefund::create([
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

        // 阶段二：事务外调微信退款
        $payService = new WechatPayService();
        $result = $payService->refund(
            $order->order_no,
            $refundRecord->refund_no,
            (float)$order->paid_amount,
            (float)$refundAmount
        );

        if (!empty($result['error'])) {
            Log::error('[OrderController] refund failed, order_no: ' . $order->order_no . ', error: ' . $result['error']);
            // 小事务：退款单置 failed，订单回滚 paid（可重试），避免订单永久卡 REFUNDING
            Db::beginTransaction();
            try {
                $refundRecord->status = OrderRefund::STATUS_FAILED;
                $refundRecord->save();
                $order->status = Order::STATUS_PAID;
                $order->save();
                Db::commit();
            } catch (\Throwable $e2) {
                Db::rollBack();
                Log::error('[OrderController] refund failed persist error: ' . $e2->getMessage());
            }
            return $this->error('退款处理失败请重试');
        }

        // 小事务：退款单置 success + refunded_at，订单 refunded
        Db::beginTransaction();
        try {
            $refundRecord->status = OrderRefund::STATUS_SUCCESS;
            $refundRecord->refunded_at = now();
            $refundRecord->save();
            $order->status = Order::STATUS_REFUNDED;
            $order->save();
            Db::commit();
        } catch (\Throwable $e2) {
            Db::rollBack();
            Log::error('[OrderController] refund success persist failed: ' . $e2->getMessage());
            return $this->error('退款处理失败请重试');
        }

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // 发送退款通知模板消息（非阻塞，失败不影响主流程）
        $this->sendRefundNotifyTemplate((string) $order->user_id, $order, $refundAmount, $reason);

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

        return $this->success([
            'refund_amount' => $refundAmount,
            'ratio'         => $ratio,
        ], '退款成功');
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

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

        return $this->success($order, '核销成功');
    }

    /**
     * 获取 Redis 分布式锁（NX + 随机 token）
     *
     * token 用于释放时校验，防止超时后误删他人锁。
     *
     * @param string $key           锁 key
     * @param int    $expireSeconds 过期秒数（默认 35s，覆盖微信 HTTP 30s 超时）
     * @return string|null 持有 token，拿不到锁返回 null
     */
    private function acquireLock(string $key, int $expireSeconds = 35): ?string
    {
        $token = bin2hex(random_bytes(16));
        $ok = Redis::connection()->set($key, $token, 'EX', $expireSeconds, 'NX');
        return $ok ? $token : null;
    }

    /**
     * 释放 Redis 分布式锁（仅当持有者 token 匹配时删除）
     */
    private function releaseLock(string $key, ?string $token): void
    {
        if ($token === null) {
            return;
        }
        $redis = Redis::connection();
        if ((string) ($redis->get($key) ?? '') === $token) {
            $redis->del($key);
        }
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
}
