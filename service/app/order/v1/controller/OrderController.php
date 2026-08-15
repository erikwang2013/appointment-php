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
use app\model\FullReductionActivity;
use app\model\GrowthLevel;
use app\model\MemberCardUsage;
use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\OrderReschedule;
use app\model\OrderStatusLog;
use app\model\OrderVerification;
use app\model\Promotion;
use app\model\PromotionParticipant;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\User;
use app\model\UserCoupon;
use app\model\UserGrowth;
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
     * 改期规则：距原服务开始 ≥ 6 小时（21600 秒）方可改期。
     * 与 Order::calcRefundRatio 全额退款窗口一致——临近服务的时段变更风险高，仅允许提前改期。
     */
    private const RESCHEDULE_MIN_LEAD_SECONDS = 21600;

    /** 改期新时段技师锁 TTL（秒），与 store 下单技师锁一致（EX 兜底释放） */
    private const TECHNICIAN_LOCK_TTL = 180;

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

        // ── 活动下单（传 promotion_id 时走活动价：拼团/秒杀）──
        // 拼团：仅 group_buy、活动进行中、调用者是参与者、未满员（已成团锁定拒绝）；
        //       拼团价 = 原价 × discount_percent/100
        // 秒杀：仅 flash_sale、活动进行中、调用者是参与者、服务匹配、未售罄（max_people 为库存，无成团概念）；
        //       秒杀价 = 原价 × (1 - discount_percent/100)
        // 两者均与优惠券/次卡/积分互斥（禁用叠加）
        $promotionId = $request->input('promotion_id');
        $participantId = null;
        $promotion = null;
        if ($promotionId !== null) {
            $promotionId = $this->decodeId((string) $promotionId);
            if ($promotionId === null) {
                return $this->error('活动不存在', 422);
            }

            $promotion = Promotion::withCount('participants')->find($promotionId);
            if (!$promotion) {
                return $this->error('活动不存在', 422);
            }
            if (!in_array($promotion->type, [Promotion::TYPE_GROUP_BUY, Promotion::TYPE_FLASH_SALE], true)) {
                return $this->error('该活动不支持下单', 422);
            }

            $now = date('Y-m-d H:i:s');
            // 惰性关闭：拼团到期未满员 → 关闭活动，并批量取消该活动已创建的未支付订单
            if ($promotion->type === Promotion::TYPE_GROUP_BUY
                && $promotion->status == 1
                && $promotion->end_at < $now
                && $promotion->participants_count < $promotion->min_people) {
                $promotion->status = 0;
                $promotion->save();
                $this->cancelGroupBuyOrders($promotionId, '拼团未成团自动取消');
                return $this->error('拼团已结束，未成团', 422);
            }

            if ($promotion->status != 1) {
                return $this->error('活动不存在或已结束');
            }
            if ($now < $promotion->start_at || $now > $promotion->end_at) {
                return $this->error('活动不在有效时间内', 422);
            }
            // 已成团锁定：满员后不再接受拼团下单
            if ($promotion->type === Promotion::TYPE_GROUP_BUY
                && $promotion->participants_count >= $promotion->min_people) {
                return $this->error('已成团，该活动已锁定', 422);
            }
            // 秒杀售罄：库存以 max_people 计，抢光后不再接受下单（无成团概念）
            if ($promotion->type === Promotion::TYPE_FLASH_SALE
                && $promotion->max_people > 0
                && $promotion->participants_count >= $promotion->max_people) {
                return $this->error('已抢光', 422);
            }

            // 调用者必须是参与者
            $participant = PromotionParticipant::where('promotion_id', $promotionId)
                ->where('user_id', $userId)
                ->first();
            if (!$participant) {
                $tip = $promotion->type === Promotion::TYPE_FLASH_SALE ? '请先参与秒杀' : '请先参与拼团';
                return $this->error('您未参与该活动，' . $tip, 422);
            }

            // 订单首条 service 项必须为活动关联服务（活动未绑定服务时跳过）
            if ((int) $promotion->service_id > 0) {
                $firstServiceId = null;
                foreach ($items as $item) {
                    if (($item['target_type'] ?? 'service') === 'service') {
                        $firstServiceId = (int) ($item['target_id'] ?? 0);
                        break;
                    }
                }
                if ($firstServiceId !== (int) $promotion->service_id) {
                    $label = $promotion->type === Promotion::TYPE_FLASH_SALE ? '秒杀' : '拼团';
                    return $this->error("订单服务与{$label}活动不匹配", 422);
                }
            }

            // 活动订单禁用优惠券/次卡/积分叠加（活动价已含折扣）
            if ($couponId !== null || $userCouponId !== null || $memberCardUsageId !== null || $usePoints > 0) {
                $label = $promotion->type === Promotion::TYPE_FLASH_SALE ? '秒杀' : '拼团';
                return $this->error("{$label}订单不支持叠加其他优惠", 422);
            }

            $participantId = $participant->id;
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

            // 活动价（叠加优惠已在进入事务前拒绝）：拼团价 = 原价 × discount_percent/100；
            // 秒杀价 = 原价 × (1 - discount_percent/100)
            if ($promotion !== null) {
                $total = (float) $pricing['total_amount'];
                $promoPrice = $promotion->type === Promotion::TYPE_FLASH_SALE
                    ? round($total * (100 - $promotion->discount_percent) / 100, 2)
                    : round($total * $promotion->discount_percent / 100, 2);
                $pricing['discount_amount'] = round($total - $promoPrice, 2);
                $pricing['paid_amount'] = $promoPrice;
            }

            // 满减（仅标准订单；拼团/秒杀跳过）：在券/次卡抵扣后的应付金额上判断门槛，
            // 叠加顺序：券/次卡 → 满减 → 等级折扣；优惠额并入 discount_amount、备注追加说明（可追溯）
            if ($promotion === null) {
                $this->applyFullReduction($pricing, $remark);
                $this->applyGrowthDiscount($userId, $pricing, $remark);
            }

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
                'promotion_id'         => $promotionId,
                'participant_id'       => $participantId,
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
     * 应用成长等级折扣（仅标准订单调用）
     *
     * 等级权益 discount_rate（如 0.95 = 95 折）作用于券/次卡优惠后的应付金额，
     * 折扣额并入 discount_amount，订单备注追加「等级折扣」说明以便追溯；
     * 无权益（青铜档 discount_rate=1.0 或未建档用户）不产生任何折扣。
     * 最低价保护：折扣后实付必须 > 0 且不小于 0.01 元（分制下限 100）。
     *
     * @param int|string $userId  用户 ID
     * @param array  $pricing PriceCalculator::calculate 结果，按引用修改 discount_amount/paid_amount
     * @param string $remark  订单备注，按引用追加等级折扣说明
     */
    private function applyGrowthDiscount(int|string $userId, array &$pricing, string &$remark): void
    {
        $level = GrowthLevel::levelForGrowth(UserGrowth::totalFor($userId));
        $benefits = $level ? (array) $level->benefits : [];
        $rate = (float) ($benefits['discount_rate'] ?? 1.0);
        if ($rate <= 0 || $rate >= 1) {
            return; // 无折扣权益
        }

        $baseFen = (int) round((float) $pricing['paid_amount'] * 100);
        if ($baseFen < 100) {
            return; // 应付已低于最低价，不再叠加
        }

        $discountedFen = max(100, (int) round($baseFen * $rate));
        $discountFen = $baseFen - $discountedFen;
        if ($discountFen <= 0) {
            return;
        }

        $pricing['discount_amount'] = round((float) $pricing['discount_amount'] + $discountFen / 100, 2);
        $pricing['paid_amount'] = round($discountedFen / 100, 2);
        $label = rtrim(rtrim(sprintf('%.1f', $rate * 10), '0'), '.');
        $remark = trim($remark . sprintf('（等级折扣：%s %s折，优惠¥%.2f）', $level->name, $label, $discountFen / 100));
    }

    /**
     * 应用满减（仅标准订单调用，在券/次卡优惠后、等级折扣前）
     *
     * 取 status=1 且当前时间在 [start_at, end_at] 内的活动中 reduction 最大者；
     * 以券/次卡抵扣后的应付金额（paid_amount）判断门槛（threshold），
     * 未达门槛直接返回；优惠额并入 discount_amount，备注追加「满减：满X减Y」以便追溯。
     * 最低价保护：满减后实付不小于 0.01 元（分制下限 100）。
     *
     * @param array  $pricing PriceCalculator::calculate 结果，按引用修改 discount_amount/paid_amount
     * @param string $remark  订单备注，按引用追加满减说明
     */
    private function applyFullReduction(array &$pricing, string &$remark): void
    {
        $now = date('Y-m-d H:i:s');
        $activity = FullReductionActivity::where('status', 1)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->orderByDesc('reduction')
            ->first();
        if (!$activity) {
            return; // 无生效活动
        }

        $baseFen = (int) round((float) $pricing['paid_amount'] * 100);
        if ($baseFen < (int) round((float) $activity->threshold * 100)) {
            return; // 券后金额未达门槛
        }
        if ($baseFen < 100) {
            return; // 应付已低于最低价，不再叠加
        }

        $reductionFen = (int) round((float) $activity->reduction * 100);
        if ($reductionFen <= 0) {
            return;
        }
        $finalFen = max(100, $baseFen - $reductionFen);

        $pricing['discount_amount'] = round((float) $pricing['discount_amount'] + ($baseFen - $finalFen) / 100, 2);
        $pricing['paid_amount'] = round($finalFen / 100, 2);
        $remark = trim($remark . sprintf('（满减：满%s减%s）', $activity->threshold, $activity->reduction));
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
     * 物流详情
     * GET /api/order/logistics/{id}
     *
     * 仅本人商品订单且已录入物流信息时返回；非本人订单 / 非商品订单 /
     * 未录入物流信息一律 404。物流信息由 admin 发货接口写入 order.remark：
     * {shipping_company, tracking_no, shipped_at}，无独立轨迹明细表。
     */
    public function logistics(Request $request, string $id)
    {
        $id = $this->decodeId((string) $id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        $order = Order::with('items')
            ->where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        if ($order->order_type !== Order::ORDER_TYPE_PRODUCT) {
            return $this->error('该订单暂无物流信息', 404);
        }

        $ship = $this->parseShippingInfo($order);
        if ($ship === null) {
            return $this->error('该订单暂无物流信息', 404);
        }

        return $this->success([
            'order_no'         => $order->order_no,
            'status'           => $order->status,
            'items'            => $order->items
                ->map(fn ($item) => [
                    'name'        => $item->name,
                    'cover_image' => $item->cover_image,
                    'quantity'    => $item->quantity,
                    'price'       => $item->price,
                    'spec_info'   => $item->spec_info,
                ])
                ->values()
                ->all(),
            'receiver'         => $this->parseReceiver($order),
            'shipping_company' => $ship['shipping_company'],
            'tracking_no'      => $ship['tracking_no'],
            'shipped_at'       => $ship['shipped_at'],
            'traces'           => [],
        ]);
    }

    /**
     * 从 order.remark 解析物流信息，缺失任一关键字段视为未发货
     */
    private function parseShippingInfo(Order $order): ?array
    {
        $remark = json_decode((string) $order->remark, true);
        if (!is_array($remark)) {
            return null;
        }
        $ship = [];
        foreach (['shipping_company', 'tracking_no', 'shipped_at'] as $key) {
            $value = $remark[$key] ?? null;
            if ($value === null || $value === '') {
                return null;
            }
            $ship[$key] = (string) $value;
        }
        return $ship;
    }

    /**
     * 收货信息：当前下单流程未快照收货地址，remark 中无则返回 null
     * （预留 receiver_* 键，后续写入即可透出）
     */
    private function parseReceiver(Order $order): ?array
    {
        $remark = json_decode((string) $order->remark, true);
        if (!is_array($remark)) {
            return null;
        }
        $receiver = [];
        foreach (['receiver_name', 'receiver_phone', 'receiver_address'] as $key) {
            $value = $remark[$key] ?? null;
            if ($value === null || $value === '') {
                return null;
            }
            $receiver[$key] = $key === 'receiver_phone'
                ? $this->maskPhone((string) $value)
                : (string) $value;
        }
        return $receiver;
    }

    /**
     * 手机号脱敏：138****1234
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
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
            $fromStatus = $order->status;
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

            // 状态时间线：→ cancelled（from_status 在变更前捕获，失败仅记日志）
            OrderStatusLog::record($order->id, $fromStatus, Order::STATUS_CANCELLED, $cancelReason ?: '用户取消订单', 'user');

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
                    $this->creditRefundToWallet($order, $refundRecord, $refundAmount, true);
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

                        // 状态时间线：cancelled → paid（退款失败回滚）
                        OrderStatusLog::record($order->id, Order::STATUS_CANCELLED, Order::STATUS_PAID, '退款失败，订单恢复', 'user');
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
                    // 积分抵扣回补（同事务，失败随退款回滚）：取消全额退还抵现积分，幂等
                    $this->refundOffsetPoints($order, $refundRecord, true);
                    Db::commit();

                    // 状态时间线：cancelled → refunded（取消退款成功）
                    OrderStatusLog::record($order->id, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, '取消订单退款成功', 'user');
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
                // 积分抵扣回补（同事务）：无退款单的取消（比例=0）同样全额退还抵现积分，幂等
                $this->refundOffsetPoints($order, null, true);
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

        // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
        if ($refundRecord) {
            try {
                \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                    '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $refundRecord->amount, 2) . '，款项将原路退回。',
                    ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no]);
            } catch (\Throwable $e) {
                Log::warning('[AppPush] refund push failed: ' . $e->getMessage());
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

            // 拼团订单懒判定：活动已关闭（到期未成团）→ 自动取消订单，拒绝支付
            if ((int) $order->promotion_id > 0 && $this->isGroupBuyClosed((int) $order->promotion_id)) {
                $order->status = Order::STATUS_CANCELLED;
                $order->cancel_reason = '拼团未成团自动取消';
                $order->cancel_at = now();
                $order->save();
                OrderStatusLog::record($order->id, Order::STATUS_PENDING, Order::STATUS_CANCELLED, '拼团未成团自动取消', 'system');
                $this->releaseTechnicianLock($order);
                return $this->error('拼团未成团，订单已自动取消', 422);
            }

            // 秒杀订单懒判定：活动已过期 → 自动取消订单并释放技师锁，拒绝支付
            if ((int) $order->promotion_id > 0 && $this->isFlashSaleClosed((int) $order->promotion_id)) {
                $order->status = Order::STATUS_CANCELLED;
                $order->cancel_reason = '秒杀活动已结束自动取消';
                $order->cancel_at = now();
                $order->save();
                OrderStatusLog::record($order->id, Order::STATUS_PENDING, Order::STATUS_CANCELLED, '秒杀活动已结束自动取消', 'system');
                $this->releaseTechnicianLock($order);
                return $this->error('秒杀活动已结束，订单已自动取消', 422);
            }

            // 积分抵扣（可选，use_points 缺省 0 走原逻辑）：余额校验 → 抵扣额计算 → 消费流水写入
            $pointsUsed   = 0;
            $pointsOffset = 0.0;
            $usePoints    = (int) $request->input('use_points', 0);
            if ($usePoints > 0) {
                try {
                    $offset = $this->applyPointsOffset($order, $usePoints);
                    $pointsUsed   = $offset['points_used'];
                    $pointsOffset = $offset['offset_amount'];
                } catch (\InvalidArgumentException $e) {
                    return $this->error($e->getMessage(), 422);
                }
            }

            // 支付渠道：wechat=微信支付（默认）/ balance=余额支付
            $payChannel = (string) $request->input('pay_channel', 'wechat');

            // 实际支付金额 = 订单应付 - 积分抵扣（未用积分时为应付原额，与原有行为一致）
            $payAmount = round((float) $order->paid_amount - $pointsOffset, 2);

            // 查找或创建支付记录
            $payment = OrderPayment::where('order_id', $order->id)->first();

            if (!$payment) {
                $payment = OrderPayment::create([
                    'id'         => OrderPayment::generateId(),
                    'order_id'   => $order->id,
                    'payment_no' => OrderPayment::generatePaymentNo(),
                    'pay_type'   => 'wechat',
                    'amount'     => $payAmount,
                    'status'     => OrderPayment::STATUS_PENDING,
                ]);
            } elseif ($payment->status === OrderPayment::STATUS_CLOSED || $payment->status === OrderPayment::STATUS_FAILED) {
                $payment->payment_no = OrderPayment::generatePaymentNo();
                $payment->amount = $payAmount;
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
                    'order_no'      => $order->order_no,
                    'payment_no'    => $payment->payment_no,
                    'amount'        => 0,
                    'status'        => Order::STATUS_PAID,
                    'points_offset' => $pointsOffset,
                    'points_used'   => $pointsUsed,
                ], '订单支付成功');
            }

            // 余额支付：无微信预下单，事务内钱包扣款 + 标记支付成功（order_lock 内串行，幂等）
            if ($payChannel === 'balance') {
                return $this->doBalancePay($order, $payment, $pointsUsed, $pointsOffset);
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
                'prepay_id'     => $result['prepay_id'],
                'sign_params'   => $result['sign_params'],
                'payment_no'    => $payment->payment_no,
                'amount'        => $payment->amount,
                'order_no'      => $order->order_no,
                'points_offset' => $pointsOffset,
                'points_used'   => $pointsUsed,
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
    private function doBalancePay(Order $order, OrderPayment $payment, int $pointsUsed = 0, float $pointsOffset = 0.0)
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
            'order_no'      => $order->order_no,
            'payment_no'    => $payment->payment_no,
            'amount'        => $amount,
            'status'        => Order::STATUS_PAID,
            'points_offset' => $pointsOffset,
            'points_used'   => $pointsUsed,
        ], '余额支付成功');
    }

    /**
     * 预约改期
     * POST /api/order/reschedule/{id}
     *
     * body: { new_service_time: "Y-m-d H:i[:s]", reason?: string }
     *
     * 规则（RESCHEDULE_MIN_LEAD_SECONDS）：
     * - 仅预约订单、状态 pending/paid/confirmed 可改期（serving/completed/cancelled/refunding/refunded 拒绝 422）
     * - 距原服务开始 ≥ 6 小时方可改期（临近时段拒绝，与 calcRefundRatio 全额退款窗口一致）
     * - 新时段同技师冲突校验复用 store 的 B2 DB 校验（排除本单）：同技师同新时间已有
     *   pending/paid 订单则 422
     * - 新时段技师锁 Redis SETNX（EX 180s 兜底）防并发改期/超卖：并发改期只有一笔能拿到
     *   新时段锁；成功后原时段锁释放、新时段锁由本单继续持有
     *
     * 并发：B1 order_lock（与 pay/cancel/refund/支付回调/自动取消同一互斥族）串行化
     * 同订单状态变更，事务内行锁重读兜底。
     */
    public function reschedule(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        // 入参校验（不依赖锁）
        $newServiceTime = $request->input('new_service_time', '');
        if ($newServiceTime === '') {
            return $this->error('请选择新的服务时间', 422);
        }
        if (strtotime($newServiceTime) === false) {
            return $this->error('服务时间格式不正确', 422);
        }
        $reason = (string) $request->input('reason', '');

        // 订单归属校验（非本人按不存在处理，404）
        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        // B1: 统一 per-order 互斥锁（与 pay/cancel/refund/支付回调/自动取消共用 order_lock）
        $lockKey = 'order_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            // 锁内重新读取订单并校验状态（防并发状态变更）
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }
            if ($order->order_type !== Order::ORDER_TYPE_APPOINTMENT) {
                return $this->error('当前订单不可改期', 422);
            }
            if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_CONFIRMED], true)) {
                return $this->error('当前订单状态不可改期', 422);
            }

            $oldServiceTime = $order->service_time;
            if (!$oldServiceTime) {
                return $this->error('当前订单不可改期', 422);
            }
            if (($oldServiceTime->getTimestamp() - time()) < self::RESCHEDULE_MIN_LEAD_SECONDS) {
                return $this->error('距原服务开始不足 6 小时，无法改期', 422);
            }

            // 新时段技师锁（防并发改期/超卖；store 下单同款 SETNX，EX 兜底释放）
            $timeSlot = date('YmdHi', strtotime($newServiceTime));
            $newLockKey = "technician_lock:{$order->technician_id}:{$timeSlot}";
            $acquired = Redis::connection()->set($newLockKey, $userId, 'EX', self::TECHNICIAN_LOCK_TTL, 'NX');
            if (!$acquired) {
                return $this->error('该时段技师已被他人锁定，请选择其他时间段', 422);
            }

            $oldTechnicianId = $order->technician_id;

            Db::beginTransaction();
            try {
                // 行锁重读（order_lock 外的冗余防护）：状态/时间以锁内最新为准
                $locked = Order::where('id', $order->id)->lockForUpdate()->first();
                if (!$locked || !in_array($locked->status, [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_CONFIRMED], true)) {
                    throw new \InvalidArgumentException('当前订单状态不可改期', 422);
                }
                $lockedOldTime = $locked->service_time;
                if (!$lockedOldTime || ($lockedOldTime->getTimestamp() - time()) < self::RESCHEDULE_MIN_LEAD_SECONDS) {
                    throw new \InvalidArgumentException('距原服务开始不足 6 小时，无法改期', 422);
                }

                // B2: 新时段排班冲突 DB 校验（防超卖兜底）——同技师同新时间已有
                // pending/paid 订单则拒绝（排除本单自身）
                $conflict = Order::where('technician_id', $locked->technician_id)
                    ->where('service_time', $newServiceTime)
                    ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PAID])
                    ->where('id', '!=', $locked->id)
                    ->exists();
                if ($conflict) {
                    throw new \InvalidArgumentException('该时段技师已被预约，请选择其他时间段', 422);
                }

                $locked->service_time = $newServiceTime;
                $locked->save();

                OrderReschedule::create([
                    'id'                => OrderReschedule::generateId(),
                    'order_id'          => $locked->id,
                    'old_service_time'  => $lockedOldTime->format('Y-m-d H:i:s'),
                    'new_service_time'  => $newServiceTime,
                    'old_technician_id' => $oldTechnicianId,
                    'new_technician_id' => $oldTechnicianId,
                    'reason'            => $reason,
                    'created_at'        => date('Y-m-d H:i:s'),
                ]);

                Db::commit();
            } catch (\Throwable $e) {
                Db::rollBack();
                // 释放新时段技师锁（事务失败则本单未占用新时段）
                Redis::connection()->del($newLockKey);
                if ($e instanceof \InvalidArgumentException) {
                    return $this->error($e->getMessage(), 422);
                }
                Log::error('[OrderController] order reschedule failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return $this->error('改期失败，请稍后重试');
            }

            // 释放原时段技师锁（改期后本单占用新时段，原时段归还）
            $this->releaseTechnicianSlotLock($oldTechnicianId, $oldServiceTime, (string) $userId);

            // 订阅消息 + 站内通知（非阻塞，失败不影响主流程；模板未配置时降级仅站内通知）
            $this->notifySubscribeEvent($order->fresh() ?: $order, NotificationReminderService::SCENE_RESCHEDULE, [
                'old_service_time' => $oldServiceTime->format('Y-m-d H:i'),
            ]);

            // WebSocket 实时推送
            $this->pushOrderUpdate($order->fresh() ?: $order);

            return $this->success($order->fresh() ?: $order, '改期成功');
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 积分抵扣（pay 内调用，order_lock 串行执行）
     *
     * 抵扣规则：points_rate 积分 = 1 元，抵扣金额 = floor(use_points / rate) 元；
     * 抵扣后应付不得低于 0.01 元，超出订单应付的抵扣按应付满减（不浪费用户积分）。
     * 可用积分 = SUM(earn) + SUM(consume/use)——balance 列仅是单次增量快照，不可作为余额依据；
     * consume/use 行 points 存负值，故直接累加即得净余额。
     * 消费流水在微信预支付前写入（幂等：同订单同来源已存在则不重复扣，支付重试安全）。
     *
     * @return array{points_used: int, offset_amount: float}
     * @throws \InvalidArgumentException 积分不足（code 422）
     */
    private function applyPointsOffset(Order $order, int $usePoints): array
    {
        $rate = (int) config('app.points_rate', 100);
        if ($rate <= 0) {
            $rate = 100; // 配置异常兜底
        }

        $earned   = (int) UserPoints::where('user_id', $order->user_id)->where('type', 'earn')->sum('points');
        $consumed = (int) UserPoints::where('user_id', $order->user_id)->whereIn('type', ['consume', 'use'])->sum('points');
        $expired  = (int) UserPoints::where('user_id', $order->user_id)->where('type', 'expire')->sum('points');
        $available = $earned + $consumed + $expired; // consume/use/expire 行为负值
        if ($available <= 0 || $usePoints > $available) {
            throw new \InvalidArgumentException('积分不足', 422);
        }

        $paidFen   = (int) round((float) $order->paid_amount * 100);
        $offsetFen = (int) floor($usePoints / $rate) * 100;
        if ($offsetFen <= 0) {
            throw new \InvalidArgumentException('积分不足', 422);
        }
        // 抵扣后金额 >= 0.01：超出应付部分按应付满减（剩余 1 分）
        $capFen = max(0, $paidFen - 1);
        if ($offsetFen > $capFen) {
            $offsetFen = $capFen;
        }
        if ($offsetFen <= 0) {
            throw new \InvalidArgumentException('积分不足', 422);
        }

        $pointsUsed = (int) round($offsetFen / 100 * $rate);

        // 幂等：同订单 points_offset 流水已存在（支付重试）则不重复扣减
        $exists = UserPoints::where('order_id', $order->id)
            ->where('source', 'points_offset')
            ->exists();
        if (!$exists) {
            // balance = 上一条余额 - 本次扣减（快照累加，锁最后一条流水防同用户并发串行）
            $lastBalance = (int) (UserPoints::where('user_id', $order->user_id)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->value('balance') ?? 0);

            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $order->user_id,
                'type'        => 'consume',
                'points'      => -$pointsUsed,
                'balance'     => $lastBalance - $pointsUsed,
                'source'      => 'points_offset',
                'order_id'    => $order->id,
                'description' => '积分抵扣订单 ' . $order->order_no,
            ]);
        }

        return [
            'points_used'   => $pointsUsed,
            'offset_amount' => round($offsetFen / 100, 2),
        ];
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

            // 状态时间线：→ refunding
            OrderStatusLog::record($order->id, Order::STATUS_PAID, Order::STATUS_REFUNDING, $reason ?: '用户申请退款', 'user');

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

                // 状态时间线：refunding → paid（退款失败回滚）
                OrderStatusLog::record($order->id, Order::STATUS_REFUNDING, Order::STATUS_PAID, '退款失败，订单恢复', 'user');
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
            // 积分回扣（同事务，失败随退款回滚）：按退款比例回扣已返积分，幂等
            $this->clawbackOrderPoints($order, $refundRecord);
            // 积分抵扣回补（同事务，失败随退款回滚）：按退款比例退还抵现积分，幂等
            $this->refundOffsetPoints($order, $refundRecord);
            Db::commit();

            // 状态时间线：refunding → refunded
            OrderStatusLog::record($order->id, Order::STATUS_REFUNDING, Order::STATUS_REFUNDED, '退款成功', 'user');
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

        // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
        try {
            \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $refundAmount, 2) . '，款项将原路退回。',
                ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'refund_amount' => (float) $refundAmount]);
        } catch (\Throwable $e) {
            Log::warning('[AppPush] refund push failed: ' . $e->getMessage());
        }

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

        // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
        try {
            \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $refundAmount, 2) . '，款项将原路退回。',
                ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'refund_amount' => (float) $refundAmount]);
        } catch (\Throwable $e) {
            Log::warning('[AppPush] refund push failed: ' . $e->getMessage());
        }

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
     * → 退款单置 success/refunded_at → 订单置 refunded → 全额退款归还券/次卡
     * → 积分回扣 + 积分抵扣回补（幂等）。
     *
     * @param bool $fullOffsetRefund 积分抵现回补是否全额（true=取消，false=退款按比例）
     * @return bool true=本次完成入账；false=退款单已被补偿处理（幂等跳过）
     */
    private function creditRefundToWallet(Order $order, OrderRefund $refundRecord, float $refundAmount, bool $fullOffsetRefund = false): bool
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

            $refundFromStatus = $order->status;
            $order->status = Order::STATUS_REFUNDED;
            $order->save();
            if ($this->shouldRestoreBenefits((float) $locked->ratio)) {
                $this->restoreCouponAndCard($order);
            }

            // 积分回扣（同事务，失败随退款回滚）：按退款比例回扣已返积分，幂等
            $this->clawbackOrderPoints($order, $locked);
            // 积分抵扣回补（同事务，失败随退款回滚）：取消全额/退款按比例退还抵现积分，幂等
            $this->refundOffsetPoints($order, $locked, $fullOffsetRefund);

            Db::commit();

            // 状态时间线：→ refunded（余额退款）
            OrderStatusLog::record($order->id, $refundFromStatus, Order::STATUS_REFUNDED, '余额退款成功', 'user');
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
        $this->releaseTechnicianSlotLock($order->technician_id, $order->service_time, (string) $order->user_id);
    }

    /**
     * 释放指定技师时间槽锁定（改期后释放原时段时复用；与 releaseTechnicianLock 同校验口径）
     *
     * @param mixed $serviceTime \DateTimeInterface|string 服务时间
     */
    private function releaseTechnicianSlotLock($technicianId, $serviceTime, string $userId): void
    {
        if (!$technicianId || !$serviceTime) {
            return;
        }

        $timeSlot = date('YmdHi', $serviceTime instanceof \DateTimeInterface
            ? $serviceTime->getTimestamp()
            : strtotime((string) $serviceTime));
        $lockKey = "technician_lock:{$technicianId}:{$timeSlot}";

        $redis = Redis::connection();
        if ((string)($redis->get($lockKey) ?? '') === $userId) {
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
     * M4: 消费返积分（核销时发放，与佣金同事务，失败随核销整体回滚）
     *
     * 规则：按订单实付金额返积分，1 元 = 1 积分，向下取整（POINTS_PER_YUAN 可配置）。
     * 幂等：同 order_id + source=order 的返积分记录已存在则不重复发放（覆盖重试/并发场景）。
     * balance 为逐条快照：上一条余额 + 本次积分（同事务内锁定最后一条流水，防并发串行）。
     */
    private const POINTS_PER_YUAN = 1; // 返积分比例：1 元 = 1 积分（可按运营策略调整）

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

    /**
     * 订单退款回扣积分（退款事务内调用，失败随退款整体回滚——与 rewardOrderPoints 对称）
     *
     * 规则：回扣 = floor(已返积分 × 本次退款金额 / 实付金额)，与 calcRefundAmount 同口径；
     * 已返积分取该订单 source=order + type=earn 流水合计（未核销未返积分则为 0，直接跳过）。
     * 幂等：同 order_id + source=order + type=use 的回扣流水已存在则不重复回扣
     * （当前退款流程每订单至多一条成功退款单，订单 refunded 后不可再退，键唯一；
     * 并发/补偿场景下与主路径行锁互斥，重复执行不产生第二笔回扣）。
     * balance 为逐条快照：上一条余额 - 本次回扣（同 rewardOrderPoints 锁定最后一条流水防并发串行）。
     */
    private function clawbackOrderPoints(Order $order, OrderRefund $refundRecord): void
    {
        // 已返积分合计（未返积分则无需回扣）
        $earned = (int) UserPoints::where('order_id', $order->id)
            ->where('source', 'order')
            ->where('type', 'earn')
            ->sum('points');
        if ($earned <= 0) {
            return;
        }

        // 幂等：同订单的回扣流水已存在则不重复回扣
        $exists = UserPoints::where('order_id', $order->id)
            ->where('source', 'order')
            ->where('type', 'use')
            ->exists();
        if ($exists) {
            return;
        }

        $paid = (float) $order->paid_amount;
        $refundAmount = (float) $refundRecord->amount;
        if ($paid <= 0 || $refundAmount <= 0) {
            return;
        }

        // 按退款金额比例回扣（向下取整，至多回扣已返积分）
        $points = (int) floor($earned * $refundAmount / $paid);
        if ($points <= 0) {
            return;
        }

        // balance = 上一条余额 - 本次回扣（快照累加，锁最后一条流水防同用户并发串行）
        $lastBalance = (int) (UserPoints::where('user_id', $order->user_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->value('balance') ?? 0);

        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'use',
            'points'      => -$points,
            'balance'     => $lastBalance - $points,
            'source'      => 'order',
            'order_id'    => $order->id,
            'description' => '订单退款回扣积分（退款单 ' . $refundRecord->refund_no . '）',
        ]);
    }

    /**
     * 订单取消/退款退还积分抵扣（points_offset 消费流水的对称回补，与 clawbackOrderPoints 并列）
     *
     * 规则：取消全额退还；退款按比例退还（floor(原扣点 × 退款金额/实付)，
     * 与 clawbackOrderPoints 取整口径一致）；原扣点取该订单 source=points_offset + type=consume
     * 流水合计（未用积分抵现则为 0，直接跳过）。
     * 幂等：同 order_id + source=points_refund 的回补流水已存在则不重复回补
     * （订单终态后不可重复取消/退款；补偿扫描与主路径行锁互斥）。
     * balance 为逐条快照：上一条余额 + 本次回补（同 rewardOrderPoints 锁定最后一条流水防并发串行）。
     */
    private function refundOffsetPoints(Order $order, ?OrderRefund $refundRecord, bool $fullRefund = false): void
    {
        // 原抵扣积分合计（points_offset 消费流水存负值；未抵现则无需回补）
        $consumed = (int) UserPoints::where('order_id', $order->id)
            ->where('source', 'points_offset')
            ->where('type', 'consume')
            ->sum('points');
        if ($consumed >= 0) {
            return;
        }

        // 幂等：同订单的回补流水已存在则不重复回补
        $exists = UserPoints::where('order_id', $order->id)
            ->where('source', 'points_refund')
            ->exists();
        if ($exists) {
            return;
        }

        if ($fullRefund) {
            $points = -$consumed;
        } else {
            $paid = (float) $order->paid_amount;
            $refundAmount = (float) ($refundRecord->amount ?? 0);
            if ($paid <= 0 || $refundAmount <= 0) {
                return;
            }
            // 按退款金额比例回补（向下取整，至多回补原抵扣积分，与 clawbackOrderPoints 同口径）
            $points = (int) floor(-$consumed * $refundAmount / $paid);
            if ($points <= 0) {
                return;
            }
        }

        // balance = 上一条余额 + 本次回补（快照累加，锁最后一条流水防同用户并发串行）
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
            'source'      => 'points_refund',
            'order_id'    => $order->id,
            'description' => $fullRefund
                ? '订单取消退还积分（订单 ' . $order->order_no . '）'
                : '订单退款退还积分（退款单 ' . $refundRecord->refund_no . '）',
            'expires_at'  => UserPoints::expiryAt(),
        ]);
    }

    /**
     * 批量取消活动下未支付的拼团订单（拼团到期未成团懒判定时调用，幂等：仅 pending 单受影响）
     */
    private function cancelGroupBuyOrders(int $promotionId, string $reason): void
    {
        Order::where('promotion_id', $promotionId)
            ->where('status', Order::STATUS_PENDING)
            ->update([
                'status'        => Order::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancel_at'     => now(),
            ]);
    }

    /**
     * 拼团活动是否已关闭（懒判定：到期未满员则关闭活动并取消其未支付订单）
     */
    private function isGroupBuyClosed(int $promotionId): bool
    {
        $promotion = Promotion::withCount('participants')->find($promotionId);
        if (!$promotion) {
            return true;
        }
        if ($promotion->status != 1) {
            return true;
        }
        if ($promotion->type === Promotion::TYPE_GROUP_BUY
            && $promotion->end_at < date('Y-m-d H:i:s')
            && $promotion->participants_count < $promotion->min_people) {
            $promotion->status = 0;
            $promotion->save();
            $this->cancelGroupBuyOrders($promotionId, '拼团未成团自动取消');
            return true;
        }
        return false;
    }

    /**
     * 秒杀活动是否已结束（懒判定：过期则关闭活动并取消其未支付订单，与 isGroupBuyClosed 同模式）
     */
    private function isFlashSaleClosed(int $promotionId): bool
    {
        $promotion = Promotion::find($promotionId);
        if (!$promotion) {
            return true;
        }
        if ($promotion->status != 1) {
            return true;
        }
        if ($promotion->type === Promotion::TYPE_FLASH_SALE
            && $promotion->end_at < date('Y-m-d H:i:s')) {
            $promotion->status = 0;
            $promotion->save();
            $this->cancelGroupBuyOrders($promotionId, '秒杀活动已结束自动取消');
            return true;
        }
        return false;
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

            // 积分回扣（幂等）：主路径落库失败由补偿补写时，回扣在此一并补写
            $this->clawbackOrderPoints($order, $locked);
            // 积分抵扣回补（幂等）：取消单全额、退款单按比例，与主路径口径一致
            $this->refundOffsetPoints($order, $locked, $order->status === Order::STATUS_CANCELLED);

            // refunding → refunded；cancelled 保持终态
            if ($order->status === Order::STATUS_REFUNDING) {
                $order->status = Order::STATUS_REFUNDED;
                $order->save();
            }

            Db::commit();

            // 状态时间线：refunding → refunded（补偿路径）
            if ($order->status === Order::STATUS_REFUNDED) {
                OrderStatusLog::record($order->id, Order::STATUS_REFUNDING, Order::STATUS_REFUNDED, '退款补偿完成', 'system');
            }

            // 站内通知：退款已到账（幂等：同订单同标题去重，主路径并发时不会双写）
            $this->writeRefundNotification($order, $locked);

            // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
            try {
                \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                    '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $locked->amount, 2) . '，款项将原路退回。',
                    ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'refund_amount' => (float) $locked->amount]);
            } catch (\Throwable $e) {
                Log::warning('[AppPush] refund compensation push failed: ' . $e->getMessage());
            }

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
