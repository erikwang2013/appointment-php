<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\PriceCalculator;
use app\model\FullReductionActivity;
use app\model\GrowthLevel;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderVerification;
use app\model\Promotion;
use app\model\PromotionParticipant;
use app\model\SeckillActivity;
use app\model\UserGrowth;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 订单创建（store + 计价辅助）
 *
 * 拼团/秒杀活动下单、订单项组装、技师锁、事务内行锁扣秒杀库存；
 * 秒杀统一走 SeckillActivity 通道（seckill_id），旧 flash_sale 促销通道已下线。
 */
trait OrderCreateTrait
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

        // ── 拼团下单（传 promotion_id 时走拼团价）──
        // 仅 group_buy、活动进行中、调用者是参与者、未满员（已成团锁定拒绝）；
        // 拼团价 = 原价 × discount_percent/100；与优惠券/次卡/积分互斥（禁用叠加）。
        // 秒杀已统一走 SeckillActivity 通道（seckill_id），旧 flash_sale 促销通道已下线
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
            if ($promotion->type !== Promotion::TYPE_GROUP_BUY) {
                return $this->error('该活动不支持下单', 422);
            }

            $now = date('Y-m-d H:i:s');
            // 惰性关闭：拼团到期未满员 → 关闭活动，并批量取消该活动已创建的未支付订单
            if ($promotion->status == 1
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
            if ($promotion->participants_count >= $promotion->min_people) {
                return $this->error('已成团，该活动已锁定', 422);
            }

            // 调用者必须是参与者
            $participant = PromotionParticipant::where('promotion_id', $promotionId)
                ->where('user_id', $userId)
                ->first();
            if (!$participant) {
                return $this->error('您未参与该活动，请先参与拼团', 422);
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
                    return $this->error('订单服务与拼团活动不匹配', 422);
                }
            }

            // 活动订单禁用优惠券/次卡/积分叠加（活动价已含折扣）
            if ($couponId !== null || $userCouponId !== null || $memberCardUsageId !== null || $usePoints > 0) {
                return $this->error('拼团订单不支持叠加其他优惠', 422);
            }

            $participantId = $participant->id;
        }

        // ── 秒杀下单（传 seckill_id 时走秒杀价，独立于拼团促销通道）──
        // 仅秒杀活动进行中、服务匹配、未售罄；订单须仅含秒杀服务一项（qty=1），
        // 订单项原价以活动 original_price 为准（防客户端篡改），实付以秒杀价计；
        // 与优惠券/次卡/积分互斥（禁用叠加）。
        $seckillId = $request->input('seckill_id');
        $seckill = null;
        if ($seckillId !== null) {
            $seckillId = $this->decodeId((string) $seckillId);
            if ($seckillId === null) {
                return $this->error('秒杀活动不存在', 422);
            }
            if ($promotionId !== null) {
                return $this->error('秒杀订单不支持叠加拼团/促销活动', 422);
            }

            $seckill = SeckillActivity::find($seckillId);
            if (!$seckill) {
                return $this->error('秒杀活动不存在', 422);
            }

            $now = date('Y-m-d H:i:s');
            if ($seckill->status != 1) {
                return $this->error('秒杀活动不存在或已结束', 422);
            }
            if ($now < $seckill->start_at || $now > $seckill->end_at) {
                return $this->error('秒杀不在有效时间内', 422);
            }
            if ((int) $seckill->stock <= 0) {
                return $this->error('已售罄', 422);
            }

            // 订单须仅含秒杀服务一项且数量为 1
            if (count($items) !== 1
                || ($items[0]['target_type'] ?? 'service') !== 'service'
                || (int) ($items[0]['target_id'] ?? 0) !== (int) $seckill->service_id
                || (int) ($items[0]['quantity'] ?? 1) !== 1) {
                return $this->error('订单服务与秒杀活动不匹配', 422);
            }
            // 原价以活动记录为准，防止客户端篡改订单项价格
            $items[0]['price'] = (float) $seckill->original_price;

            // 秒杀订单禁用优惠券/次卡/积分叠加（秒杀价已含折扣）
            if ($couponId !== null || $userCouponId !== null || $memberCardUsageId !== null || $usePoints > 0) {
                return $this->error('秒杀订单不支持叠加其他优惠', 422);
            }
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

            // 秒杀：行锁复验状态/时间/库存并扣减（防并发超卖；扣减与订单创建同事务，任一步失败整体回滚，
            // 无需回补——直接 POST /api/order 带 seckill_id 的绕过路径同样在此受控）
            if ($seckill !== null) {
                $lockedSeckill = SeckillActivity::where('id', $seckillId)->lockForUpdate()->first();
                if (!$lockedSeckill || $lockedSeckill->status != 1) {
                    throw new \InvalidArgumentException('秒杀活动不存在或已结束', 422);
                }
                $now = date('Y-m-d H:i:s');
                if ($now < $lockedSeckill->start_at || $now > $lockedSeckill->end_at) {
                    throw new \InvalidArgumentException('秒杀不在有效时间内', 422);
                }
                if ((int) $lockedSeckill->stock <= 0) {
                    throw new \InvalidArgumentException('已售罄', 422);
                }
                $lockedSeckill->stock = (int) $lockedSeckill->stock - 1;
                $lockedSeckill->save();
                $seckill = $lockedSeckill;
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

            // 拼团价（叠加优惠已在进入事务前拒绝）：拼团价 = 原价 × discount_percent/100
            if ($promotion !== null) {
                $total = (float) $pricing['total_amount'];
                $promoPrice = round($total * $promotion->discount_percent / 100, 2);
                $pricing['discount_amount'] = round($total - $promoPrice, 2);
                $pricing['paid_amount'] = $promoPrice;
            }

            // 秒杀价：实付固定为活动秒杀价，优惠额 = 原价 - 秒杀价
            if ($seckill !== null) {
                $total = (float) $pricing['total_amount'];
                $pricing['discount_amount'] = round($total - (float) $seckill->seckill_price, 2);
                $pricing['paid_amount'] = (float) $seckill->seckill_price;
            }

            // 满减（仅标准订单；拼团/秒杀跳过）：在券/次卡抵扣后的应付金额上判断门槛，
            // 叠加顺序：券/次卡 → 满减 → 等级折扣；优惠额并入 discount_amount、备注追加说明（可追溯）
            if ($promotion === null && $seckill === null) {
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
                'seckill_id'           => $seckillId,
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
}
