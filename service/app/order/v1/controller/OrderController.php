<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\common\NotificationReminderService;
use app\common\PriceCalculator;
use app\common\PushService;
use app\common\WechatPayService;
use app\common\WechatTemplateMessageService;
use app\model\MemberCardUsage;
use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\OrderVerification;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\User;
use app\model\UserCoupon;
use app\model\UserMemberCard;
use app\model\UserPoints;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 订单控制器
 *
 * 处理订单创建、支付、退款、核销、评价等业务
 */
class OrderController extends BaseController
{
    /**
     * B4: 退款补偿扫描阈值（秒）——退款单 pending 超过该时长仍未被推进，视为「微信已退款但落库失败」，
     * 由 completeRefundCompensation() 幂等补写（微信退款接口为同步返回，正常场景 10 分钟内必然落库）。
     */
    private const REFUND_COMPENSATE_AFTER = 600;

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

        // 券 ID hashid 解码失败（非法字符串）直接 422，避免静默跳过优惠导致实付与预期不符
        if ($request->input('coupon_id') !== null && $couponId === null) {
            return $this->error('优惠券ID无效', 422);
        }
        if ($request->input('user_coupon_id') !== null && $userCouponId === null) {
            return $this->error('优惠券ID无效', 422);
        }

        // 预约订单需要技师和服务时间（必填校验，不依赖锁）
        if ($orderType === Order::ORDER_TYPE_APPOINTMENT && (!$technicianId || !$serviceTime)) {
            return $this->error('预约订单需要选择技师和服务时间');
        }

        // B7: 订单项组装与校验提前到取锁之前——此前 items 校验 return 在 try 外，
        // 已获取的技师锁会残留 180s。现所有「取锁后的早退 return」均已消除。
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

        // 预约订单：技师时间槽锁定（成功路径由取消/退款/自动取消释放，EX 180s 兜底）
        $lockKey = null;
        if ($orderType === Order::ORDER_TYPE_APPOINTMENT) {
            $timeSlot = date('YmdHi', strtotime($serviceTime));
            $lockKey = "technician_lock:{$technicianId}:{$timeSlot}";
            $acquired = Redis::connection()->set($lockKey, $userId, 'EX', 180, 'NX');

            if (!$acquired) {
                return $this->error('该时段技师已被他人锁定，请选择其他时间段');
            }
        }

        $orderNo = generate_order_no();
        // 预生成订单 ID：次卡使用记录需要在订单事务内落库，依赖订单 ID
        $orderId = Order::generateId();

        Db::beginTransaction();
        try {
            // B2: 排班冲突 DB 校验（防超卖兜底）——同技师同 service_time 已有 pending/paid 订单则拒绝
            if ($orderType === Order::ORDER_TYPE_APPOINTMENT && $technicianId && $serviceTime) {
                $conflict = Order::where('technician_id', $technicianId)
                    ->where('service_time', $serviceTime)
                    ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PAID])
                    ->exists();
                if ($conflict) {
                    throw new \InvalidArgumentException('该时段技师已被预约，请选择其他时间段');
                }
            }

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
                $verifyCode = OrderVerification::generateCode();
                // erik_order_verification.uk_code 唯一索引：核销码禁止为空
                if ($verifyCode === '') {
                    throw new \RuntimeException('核销码生成失败');
                }
                OrderVerification::create([
                    'id'       => OrderVerification::generateId(),
                    'order_id' => $order->id,
                    'code'     => $verifyCode,
                ]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            // 释放技师锁
            if ($lockKey) {
                Redis::connection()->del($lockKey);
            }
            // 计价引擎的业务校验错误（券已被使用/次数不足等）为业务文案，直接透出；
            // 异常 code 携带 HTTP 状态（如他人券 404、门槛/有效期 422），映射为响应状态码
            if ($e instanceof \InvalidArgumentException) {
                $status = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 400;
                return $this->error($e->getMessage(), $status);
            }
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[OrderController] order create failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('订单创建失败，请稍后重试');
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

        // 退款信息预取（N+1 防护）：按订单批量取最近一条退款记录
        $orders = $paginator->getCollection();
        if ($orders->isNotEmpty()) {
            $refunds = OrderRefund::whereIn('order_id', $orders->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('order_id')
                ->map->first();
            foreach ($orders as $order) {
                $this->appendRefundInfo($order, $refunds->get($order->id));
            }
        }

        return $this->paginate($paginator);
    }

    /**
     * 订单详情
     * GET /api/order/detail/{id}
     */
    public function show(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
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

        // 追加退款信息（单订单退款取最近一条）
        $refund = OrderRefund::where('order_id', $order->id)
            ->orderBy('created_at', 'desc')
            ->first();
        $this->appendRefundInfo($order, $refund);

        return $this->success($order);
    }

    /**
     * 取消订单
     * POST /api/order/cancel/{id}
     */
    public function cancel(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        // B1: 统一 per-order 互斥锁（pay/cancel/refund/支付回调/自动取消共用 order_lock）
        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $lockKey = 'order_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            // 锁内重新读取订单并校验状态（防并发：支付回调/自动取消与取消同锁互斥）
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }
            if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID], true)) {
                return $this->error('当前订单状态不可取消');
            }
            return $this->doCancel($request, $order);
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 取消订单（在 order_lock 内执行）
     *
     * 阶段一：事务内建退款单(pending) + 订单置 cancelled 并提交；
     * 阶段二：事务外调微信退款；失败回滚订单为 paid（可重试），成功订单置 refunded。
     * 优惠归还（M3）：仅订单终态为 cancelled/refunded 时归还券/次卡（退款失败回滚则保持已消费）。
     */
    private function doCancel(Request $request, Order $order)
    {
        $cancelReason = $request->input('cancel_reason', '');

        // B1.3: cancel 前查微信——订单存在 pending 支付记录时，先确认微信侧未支付；
        // 若微信侧已支付（回调未达），先落库对齐为 paid，再走退款路径而非置 cancelled。
        $payment = $order->payment()->first();
        if ($payment && $payment->status === OrderPayment::STATUS_PENDING) {
            $queryResult = (new WechatPayService())->queryOrder($order->order_no);
            if (!empty($queryResult['error'])) {
                return $this->error('无法确认支付状态，请稍后再试');
            }
            $tradeState = (string)($queryResult['trade_state'] ?? '');
            if ($tradeState === 'SUCCESS') {
                // 微信侧已支付：标记支付成功（幂等单一消费点），订单对齐为 paid 后走退款路径
                $mark = (new WechatPayService())->markOrderPaid(
                    $payment->payment_no,
                    (string)($queryResult['transaction_id'] ?? ''),
                    (float)($queryResult['total_fee'] ?? 0) / 100,
                    'wechat'
                );
                if (empty($mark['success'])) {
                    return $this->error('支付状态同步失败，请稍后再试');
                }
                $order = $order->fresh();
                if (!$order) {
                    return $this->error('订单状态异常，请稍后再试');
                }
            } elseif (!in_array($tradeState, ['NOTPAY', 'CLOSED', 'REVOKED', 'USERPAYING', 'PAYERROR'], true)) {
                return $this->error('支付状态异常，请稍后再试');
            }
        }

        // 阶段一：事务内建退款单(pending) + 订单置 cancelled 并提交；微信 IO 一律事务外
        $refundRecord = null;
        $refundAmount = 0.00;

        Db::beginTransaction();
        try {
            // 已支付的订单需计算退款（B3: 与 doRefund 共用 calcRefundAmount，保证比例口径一致）
            if ($order->status === Order::STATUS_PAID) {
                $ratio = $order->calcRefundRatio();
                $refundAmount = $this->calcRefundAmount($order, $ratio);

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

            // 站内通知：退款申请已受理（幂等：同订单同标题去重）
            if ($refundRecord) {
                $this->writeRefundNotification($order, $refundRecord);
            }
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[OrderController] order cancel failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('取消订单失败，请稍后重试');
        }

        // 阶段二：按支付渠道退款 —— balance 渠道无微信 IO，事务内原子回充余额；
        // wechat 渠道保持现有两段式（事务外调微信退款 + 落库/补偿）
        if ($refundRecord) {
            $orderPayment = OrderPayment::where('order_id', $order->id)->first();
            if ($orderPayment && $orderPayment->pay_type === 'balance') {
                // 余额支付订单取消：回充余额（幂等：已补偿单直接跳过，通知走尾部去重）
                try {
                    $this->creditRefundToWallet($order, $refundRecord, $refundAmount);
                } catch (\Throwable $e) {
                    Log::error('[OrderController] balance refund failed on cancel, order_no: ' . $order->order_no . ': ' . $e->getMessage());
                    return $this->error('退款处理失败请重试');
                }
            } else {
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
                // B3: 仅全额退款（ratio>=1.0）归还券/次卡，部分退款不归还（与 doRefund 对齐）
                Db::beginTransaction();
                try {
                    $refundRecord->status = OrderRefund::STATUS_SUCCESS;
                    $refundRecord->refunded_at = now();
                    $refundRecord->save();
                    $order->status = Order::STATUS_REFUNDED;
                    $order->save();
                    if ($this->shouldRestoreBenefits($ratio)) {
                        $this->restoreCouponAndCard($order);
                    }
                    Db::commit();
                } catch (\Throwable $e2) {
                    Db::rollBack();
                    Log::error('[OrderController] refund success persist failed: ' . $e2->getMessage());
                    // B4: 微信侧已退款但落库失败 → 立即幂等补偿（补写退款单+归还券/次卡）；
                    // 仍失败则保持可被 AutoCancelTimer 周期扫描兜底，绝不静默卡死
                    $compensated = false;
                    try {
                        $compensated = $this->completeOneRefundCompensation($refundRecord);
                    } catch (\Throwable $e3) {
                        Log::error('[OrderController] refund compensation retry failed: ' . $e3->getMessage());
                    }
                    if (!$compensated) {
                        return $this->error('退款处理失败请重试');
                    }
                }
            }
        } else {
            // 无退款路径的取消（未支付/全额优惠零元/比例=0）为终态 cancelled：归还券/次卡
            Db::beginTransaction();
            try {
                $this->restoreCouponAndCard($order);
                Db::commit();
            } catch (\Throwable $e2) {
                Db::rollBack();
                Log::error('[OrderController] restore benefits on cancel failed: ' . $e2->getMessage());
            }
        }

        // 站内通知：退款已到账（直接成功或补偿成功均落此；补偿路径由 completeOneRefundCompensation 幂等补写）
        if ($refundRecord) {
            $this->writeRefundNotification($order, $refundRecord->fresh() ?: $refundRecord);
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
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        // B1: 统一 per-order 互斥锁（pay/cancel/refund/支付回调/自动取消共用 order_lock）
        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $lockKey = 'order_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('支付处理中，请稍后再试');
        }

        try {
            // 锁内重新读取订单并校验状态
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }
            if ($order->status !== Order::STATUS_PENDING) {
                return $this->error('当前订单状态不可支付');
            }

            // 支付渠道：wechat=微信支付（默认）/ balance=余额支付
            $payChannel = (string) $request->input('pay_channel', 'wechat');

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
                // 订阅消息：支付成功（非阻塞，失败不影响主流程）
                $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_PAY);
                return $this->success([
                    'order_no'   => $order->order_no,
                    'payment_no' => $payment->payment_no,
                    'amount'     => 0,
                    'status'     => Order::STATUS_PAID,
                ], '订单支付成功');
            }

            // 余额支付：无微信预下单，事务内钱包扣款 + 标记支付成功（order_lock 内串行，幂等）
            if ($payChannel === 'balance') {
                return $this->doBalancePay($order, $payment);
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
     * 余额支付（在 order_lock 内执行，调用方已校验订单 pending）
     *
     * 单事务原子完成：钱包行 lockForUpdate → 余额充足校验（不足 422 余额不足）
     * → balance 扣减 + total_consume 累加 → 写流水(consume, balance_after)
     * → 调 markOrderPaid('balance')（嵌套事务=savepoint，单一消费点：
     * 支付记录 success/pay_type=balance + 原子消费券/次卡 + 订单置 PAID）。
     * 任一步失败整体回滚，绝无「扣款成功但订单未支付」。
     * 幂等：order_lock 串行 + markOrderPaid 状态复验（已支付直接成功）。
     */
    private function doBalancePay(Order $order, OrderPayment $payment)
    {
        $amount = (float) $payment->amount;
        $payService = new WechatPayService();

        Db::beginTransaction();
        try {
            // 钱包行锁（不存在则创建；余额扣减与订单支付同事务）
            $wallet = UserWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserWallet::create([
                    'user_id'        => $order->user_id,
                    'balance'        => 0.00,
                    'total_recharge' => 0.00,
                    'total_consume'  => 0.00,
                ]);
            }

            // 余额充足校验（转分比对，防浮点误差）
            if (UserWallet::toCents((float) $wallet->balance) < UserWallet::toCents($amount)) {
                throw new \InvalidArgumentException('余额不足');
            }

            $wallet->balance = round((float) $wallet->balance - $amount, 2);
            $wallet->total_consume = round((float) $wallet->total_consume + $amount, 2);
            $wallet->save();

            WalletTxn::create([
                'user_id'       => $order->user_id,
                'type'          => WalletTxn::TYPE_CONSUME,
                'amount'        => $amount,
                'balance_after' => (float) $wallet->balance,
                'order_id'      => $order->id,
                'remark'        => '余额支付订单 ' . $order->order_no,
            ]);

            // 单一消费点（嵌套事务）：支付记录置 success(pay_type=balance) + 消费券/次卡 + 订单置 PAID
            $result = $payService->markOrderPaid(
                $payment->payment_no,
                'BALANCE' . $payment->payment_no,
                $amount,
                'balance'
            );
            if (empty($result['success'])) {
                throw new \RuntimeException($result['message'] ?? '订单状态更新失败');
            }

            Db::commit();
        } catch (\InvalidArgumentException $e) {
            Db::rollBack();
            // 余额不足等业务校验文案直接透出（422）
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[OrderController] balance pay failed, order_no: ' . $order->order_no . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('余额支付失败，请稍后重试');
        }

        // 订阅消息：支付成功（非阻塞，失败不影响主流程）
        $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_PAY);

        // WebSocket 实时推送已由 markOrderPaid 内部完成（订单上下文一致），此处不重复推送
        return $this->success([
            'order_no'   => $order->order_no,
            'payment_no' => $payment->payment_no,
            'amount'     => $amount,
            'status'     => Order::STATUS_PAID,
        ], '余额支付成功');
    }

    /**
     * 申请退款
     * POST /api/order/refund/{id}
     */
    public function refund(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        // B1: 统一 per-order 互斥锁（pay/cancel/refund/支付回调/自动取消共用 order_lock）
        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $lockKey = 'order_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            // 锁内重新读取订单并校验状态
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }

            // M8: 核销即开始服务则不可退
            if ($order->status === Order::STATUS_SERVING) {
                return $this->error('服务已开始，不可退款');
            }

            if (!$order->isRefundable()) {
                return $this->error('当前订单状态不可退款');
            }

            $ratio = $order->calcRefundRatio();
            if ($ratio <= 0) {
                return $this->error('当前订单不支持退款');
            }

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

        $refundAmount = $this->calcRefundAmount($order, $ratio);

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

            // 站内通知：退款申请已受理（幂等：同订单同标题去重）
            $this->writeRefundNotification($order, $refundRecord);
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[OrderController] order refund apply failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('申请退款失败，请稍后重试');
        }

        // 阶段二：按支付渠道退款 —— balance 渠道无微信 IO，单事务原子回充；
        // wechat 渠道保持现有两段式（事务外调微信退款 + 落库/补偿）
        $orderPayment = OrderPayment::where('order_id', $order->id)->first();
        if ($orderPayment && $orderPayment->pay_type === 'balance') {
            return $this->refundToBalance($order, $refundRecord, $refundAmount, $ratio);
        }

        // 阶段二（微信渠道）：事务外调微信退款
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

        // 小事务：退款单置 success + refunded_at，订单 refunded；全额退款时归还券/次卡（M3/B3）
        Db::beginTransaction();
        try {
            $refundRecord->status = OrderRefund::STATUS_SUCCESS;
            $refundRecord->refunded_at = now();
            $refundRecord->save();
            $order->status = Order::STATUS_REFUNDED;
            $order->save();
            if ($this->shouldRestoreBenefits($ratio)) {
                $this->restoreCouponAndCard($order);
            }
            Db::commit();
        } catch (\Throwable $e2) {
            Db::rollBack();
            Log::error('[OrderController] refund success persist failed: ' . $e2->getMessage());
            // B4: 微信侧已退款但落库失败 → 立即幂等补偿；仍失败由定时器兜底，避免永久卡 REFUNDING
            $compensated = false;
            try {
                $compensated = $this->completeOneRefundCompensation($refundRecord);
            } catch (\Throwable $e3) {
                Log::error('[OrderController] refund compensation retry failed: ' . $e3->getMessage());
            }
            if ($compensated) {
                return $this->success([
                    'refund_amount' => $refundAmount,
                    'ratio'         => $ratio,
                ], '退款成功');
            }
            return $this->error('退款处理失败请重试');
        }

        // 站内通知：退款已到账（补偿成功路径已由 completeOneRefundCompensation 幂等补写，此处去重）
        $this->writeRefundNotification($order, $refundRecord);

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // 发送退款通知模板消息（非阻塞，失败不影响主流程）
        $this->sendRefundNotifyTemplate((string) $order->user_id, $order, $refundAmount, $reason);

        // 订阅消息：退款到账（非阻塞，失败不影响主流程）
        $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_REFUND, [
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
        ]);

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

        return $this->success([
            'refund_amount' => $refundAmount,
            'ratio'         => $ratio,
        ], '退款成功');
    }

    /**
     * 余额支付订单退款（无微信 IO，单事务原子完成）
     *
     * 内部复用 creditRefundToWallet（退款单行锁幂等 + 钱包回充 + 落库）。
     * 失败仅可能为 DB 异常，退款单保持 pending 由补偿扫描幂等兜底
     * （补偿侧对 balance 渠道同样回充余额，见 completeOneRefundCompensation）。
     */
    private function refundToBalance(Order $order, OrderRefund $refundRecord, float $refundAmount, float $ratio)
    {
        try {
            $credited = $this->creditRefundToWallet($order, $refundRecord, $refundAmount);
        } catch (\Throwable $e) {
            Log::error('[OrderController] balance refund failed, order_no: ' . $order->order_no . ': ' . $e->getMessage());
            return $this->error('退款处理失败请重试');
        }

        // 已被补偿处理完成（幂等），直接返回成功
        if (!$credited) {
            return $this->success([
                'refund_amount' => $refundAmount,
                'ratio'         => $ratio,
            ], '退款成功');
        }

        // 站内通知：退款已到账（幂等：同订单同标题去重）
        $this->writeRefundNotification($order, $refundRecord->fresh() ?: $refundRecord);

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // 发送退款通知模板消息（非阻塞，失败不影响主流程）
        $this->sendRefundNotifyTemplate((string) $order->user_id, $order, $refundAmount, $refundRecord->reason);

        // 订阅消息：退款到账（非阻塞，失败不影响主流程）
        $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_REFUND, [
            'refund_amount' => $refundAmount,
            'refund_reason' => $refundRecord->reason,
        ]);

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

        return $this->success([
            'refund_amount' => $refundAmount,
            'ratio'         => $ratio,
        ], '退款成功');
    }

    /**
     * 余额退款核心（doRefund/doCancel 共用，单事务原子完成）
     *
     * 退款单行锁 + status 复验（幂等：防与补偿 completeOneRefundCompensation 并发双处理）
     * → 钱包行 lockForUpdate → balance 回充 + 写流水(refund, balance_after)
     * → 退款单置 success/refunded_at → 订单置 refunded → 全额退款归还券/次卡。
     *
     * @return bool true=本次完成入账；false=退款单已被补偿处理（幂等跳过）
     */
    private function creditRefundToWallet(Order $order, OrderRefund $refundRecord, float $refundAmount): bool
    {
        Db::beginTransaction();
        try {
            $locked = OrderRefund::where('id', $refundRecord->id)
                ->where('status', OrderRefund::STATUS_PENDING)
                ->lockForUpdate()
                ->first();
            if (!$locked) {
                Db::rollBack();
                return false;
            }

            $wallet = UserWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \RuntimeException('用户钱包不存在');
            }
            $wallet->balance = round((float) $wallet->balance + $refundAmount, 2);
            $wallet->save();

            WalletTxn::create([
                'user_id'       => $order->user_id,
                'type'          => WalletTxn::TYPE_REFUND,
                'amount'        => $refundAmount,
                'balance_after' => (float) $wallet->balance,
                'order_id'      => $order->id,
                'remark'        => '订单退款 ' . $order->order_no,
            ]);

            $locked->status = OrderRefund::STATUS_SUCCESS;
            $locked->refunded_at = now();
            $locked->save();

            $order->status = Order::STATUS_REFUNDED;
            $order->save();
            if ($this->shouldRestoreBenefits((float) $locked->ratio)) {
                $this->restoreCouponAndCard($order);
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

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
                    $order->status = Order::STATUS_SERVING;
                    $order->service_start_at = now();
                    $order->save();
                }
                $this->createCommissionEarning($order);
                Db::commit();
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
     *
     * B2: 校验持有者 token（锁值为下单用户 ID，仅归属匹配时删除，防误删他人锁）
     */
    private function releaseTechnicianLock(Order $order): void
    {
        if (!$order->technician_id || !$order->service_time) {
            return;
        }

        $timeSlot = date('YmdHi', $order->service_time->getTimestamp());
        $lockKey = "technician_lock:{$order->technician_id}:{$timeSlot}";

        $redis = Redis::connection();
        if ((string)($redis->get($lockKey) ?? '') === (string)$order->user_id) {
            $redis->del($lockKey);
        }
    }

    /**
     * M1: 核销成功后生成技师佣金收益（幂等：同订单同类型不重复生成）
     *
     * 金额 = 订单实付 × 佣金率（erik_technician_commission_config.commission_rate，百分比）。
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

        $rate = (float) Db::table('erik_technician_commission_config')
            ->where('technician_id', $order->technician_id)
            ->value('commission_rate');
        if ($rate <= 0) {
            return; // 未配置佣金率则不生成收益
        }

        $amount = round((float)$order->paid_amount * $rate / 100, 2);
        if ($amount <= 0) {
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
     * M3: 归还订单使用的优惠券与次卡次数（仅终态为 cancelled/refunded 时调用，幂等）
     *
     * - 优惠券：used → available（条件更新，防并发重复归还）
     * - 次卡：按使用记录数扣回 used_times，used_up 恢复 active，使用记录软置 cancelled
     */
    private function restoreCouponAndCard(Order $order): void
    {
        // 优惠券归还（幂等：仅 status=used 时可置回 available）
        if ((int)$order->user_coupon_id > 0) {
            UserCoupon::where('id', $order->user_coupon_id)
                ->where('status', 'used')
                ->update(['status' => 'available', 'used_at' => null]);
        }

        $this->restoreMemberCardTimes($order);
    }

    /**
     * M3: 次卡次数归还（consume 的逆操作）
     *
     * 订单的 member_card_usage_id 列在支付后回写为首条使用记录 ID（非卡片 ID），
     * 因此按 order_id 查使用记录获取卡片与扣回次数。
     */
    private function restoreMemberCardTimes(Order $order): void
    {
        if ((int)$order->member_card_usage_id <= 0) {
            return;
        }

        $usages = MemberCardUsage::where('order_id', $order->id)
            ->where('status', 'active')
            ->get();
        if ($usages->isEmpty()) {
            return;
        }

        $count  = $usages->count();
        $cardId = (int)$usages->first()->user_card_id;

        // 原子扣回（防并发重复归还）
        UserMemberCard::where('id', $cardId)
            ->whereRaw('used_times - ? >= 0', [$count])
            ->decrement('used_times', $count);

        // used_up → active 恢复
        $card = UserMemberCard::find($cardId);
        if ($card && $card->status === 'used_up' && (int)$card->used_times < (int)$card->total_times) {
            $card->status = 'active';
            $card->save();
        }

        // 使用记录软置 cancelled（保留审计轨迹）
        MemberCardUsage::whereIn('id', $usages->pluck('id'))
            ->update(['status' => 'cancelled']);
    }

    /**
     * B3: 计算退款金额（元）——doCancel/doRefund 共用，保证比例口径一致
     */
    private function calcRefundAmount(Order $order, float $ratio): float
    {
        return round((float) $order->paid_amount * $ratio, 2);
    }

    /**
     * B3: 是否归还优惠（券/次卡）——仅全额退款（比例 >= 1.0）归还，部分退款不归还（与 doRefund 对齐）。
     * 全额优惠零元单（无退款单路径）走 doCancel 的 else 分支直接归还。
     */
    private function shouldRestoreBenefits(float $ratio): bool
    {
        return (float) $ratio >= 1.0;
    }

    /**
     * B4: 退款补偿（幂等，周期扫描入口）——处理「微信已退款但落库失败」的滞留单
     *
     * 扫描：退款单 status=pending 且创建超过 REFUND_COMPENSATE_AFTER 秒，关联订单处于
     * refunding（doRefund 落库失败）或 cancelled（doCancel 落库失败）状态。
     * 处理：补写退款单 success + refunded_at；全额退款归还券/次卡；refunding 单置 refunded，
     * cancelled 单保持终态 cancelled（不覆盖状态）。
     * 幂等：仅 status=pending 的退款单可被补写，重复扫描不产生副作用。
     */
    public function completeRefundCompensation(): void
    {
        $threshold = date('Y-m-d H:i:s', time() - self::REFUND_COMPENSATE_AFTER);

        try {
            $records = OrderRefund::where('status', OrderRefund::STATUS_PENDING)
                ->where('created_at', '<', $threshold)
                ->limit(50)
                ->get();

            foreach ($records as $record) {
                try {
                    $this->completeOneRefundCompensation($record);
                } catch (\Throwable $e) {
                    Log::error('[OrderController] completeRefundCompensation item failed, refund: '
                        . $record->id . ': ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error('[OrderController] completeRefundCompensation scan error: ' . $e->getMessage());
        }
    }

    /**
     * B4: 单条退款补偿（幂等）
     *
     * @param OrderRefund $refundRecord 待补偿退款单
     * @return bool 是否完成补偿
     */
    private function completeOneRefundCompensation(OrderRefund $refundRecord): bool
    {
        try {
            Db::beginTransaction();

            // 行锁 + 状态复验：仅 pending 退款单可被补写（防并发重复补偿）
            $locked = OrderRefund::where('id', $refundRecord->id)
                ->where('status', OrderRefund::STATUS_PENDING)
                ->lockForUpdate()
                ->first();
            $order = $locked ? Order::where('id', $locked->order_id)->lockForUpdate()->first() : null;

            if (!$locked || !$order) {
                Db::rollBack();
                return false;
            }
            if (!in_array($order->status, [Order::STATUS_REFUNDING, Order::STATUS_CANCELLED], true)) {
                Db::rollBack();
                return false;
            }

            // 余额渠道退款补偿：同步回充余额 + 写流水（幂等——仅 pending 退款单可被补写，
            // 与 refundToBalance/doCancel 行锁互斥，重复扫描不重复入账）
            $payment = $locked->payment_id ? OrderPayment::find($locked->payment_id) : null;
            if ($payment && $payment->pay_type === 'balance') {
                $wallet = UserWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
                if (!$wallet) {
                    Db::rollBack();
                    return false;
                }
                $wallet->balance = round((float) $wallet->balance + (float) $locked->amount, 2);
                $wallet->save();
                WalletTxn::create([
                    'user_id'       => $order->user_id,
                    'type'          => WalletTxn::TYPE_REFUND,
                    'amount'        => (float) $locked->amount,
                    'balance_after' => (float) $wallet->balance,
                    'order_id'      => $order->id,
                    'remark'        => '订单退款补偿 ' . $order->order_no,
                ]);
            }

            $locked->status = OrderRefund::STATUS_SUCCESS;
            $locked->refunded_at = now();
            $locked->save();

            if ($this->shouldRestoreBenefits((float) $locked->ratio)) {
                $this->restoreCouponAndCard($order);
            }

            // refunding → refunded；cancelled 保持终态
            if ($order->status === Order::STATUS_REFUNDING) {
                $order->status = Order::STATUS_REFUNDED;
                $order->save();
            }

            Db::commit();

            // 站内通知：退款已到账（幂等：同订单同标题去重，主路径并发时不会双写）
            $this->writeRefundNotification($order, $locked);

            Log::info('[OrderController] refund compensation done, order_no: ' . $order->order_no
                . ', refund_id: ' . $locked->id);
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
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

    /**
     * 追加退款信息到订单响应（show/index 共用，契约字段：refund_status/refund_amount/refunded_at）
     *
     * 无退款记录时三个字段均为 null，保证响应形状一致。
     */
    private function appendRefundInfo(Order $order, ?OrderRefund $refund): void
    {
        $order->refund_status = $refund ? $refund->status : null;
        $order->refund_amount = $refund ? (float) $refund->amount : null;
        $order->refunded_at   = $refund && $refund->refunded_at ? $refund->refunded_at->format('Y-m-d H:i:s') : null;
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
