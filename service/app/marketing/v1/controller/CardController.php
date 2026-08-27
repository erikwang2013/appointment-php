<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\MemberCard;
use app\model\MemberCardUsage;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\Service;
use app\model\UserMemberCard;
use support\Db;
use support\Redis;
use Webman\Http\Request;

/**
 * 会员卡控制器
 */
class CardController extends BaseController
{
    /**
     * 获取可购买的会员卡列表
     * GET /api/marketing/cards
     */
    public function index(Request $request)
    {
        $cards = MemberCard::where('status', 1)->orderBy('created_at', 'desc')->get();

        return $this->success($cards);
    }

    /**
     * 购买会员卡
     * POST /api/marketing/cards/buy
     */
    public function buy(Request $request)
    {
        $userId = $request->user_id;
        $cardId = $this->decodeId($request->input('card_id'));

        if (!$cardId) {
            return $this->error('会员卡ID无效');
        }

        $card = MemberCard::find($cardId);
        if (!$card) {
            return $this->error('会员卡不存在', 404);
        }

        if ($card->status != 1) {
            return $this->error('会员卡已下架');
        }

        $now = date('Y-m-d H:i:s');

        Db::beginTransaction();
        try {
            $userCard = new UserMemberCard();
            $userCard->id = UserMemberCard::generateId();
            $userCard->user_id = $userId;
            $userCard->card_id = $cardId;
            $userCard->start_at = $now;

            if (!empty($card->duration_days) && $card->duration_days > 0) {
                $userCard->end_at = date('Y-m-d H:i:s', strtotime("+{$card->duration_days} days"));
            }

            $userCard->total_times = $card->total_times ?? 0;
            $userCard->used_times = 0;
            $userCard->status = 'active';
            $userCard->save();

            Db::commit();

            return $this->success([
                'id' => $userCard->id,
                'card_id' => $userCard->card_id,
                'start_at' => $userCard->start_at,
                'end_at' => $userCard->end_at,
                'total_times' => $userCard->total_times,
                'status' => $userCard->status,
            ], '购买成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('购买失败，请稍后重试');
        }
    }

    /**
     * 我的次卡列表
     * GET /api/marketing/cards/my
     */
    public function my(Request $request)
    {
        $userId = $request->user_id;

        $list = UserMemberCard::where('user_id', $userId)
            ->with('card')
            ->orderBy('created_at', 'desc')
            ->get();

        $now = time();
        $result = [];
        foreach ($list as $uc) {
            $remaining = (int) $uc->total_times - (int) $uc->used_times;
            $status = $uc->status;
            if ($status === 'active') {
                if ($remaining <= 0) {
                    $status = 'used_up';
                } elseif ($uc->end_at && strtotime((string) $uc->end_at) < $now) {
                    $status = 'expired';
                }
            }
            $result[] = [
                'id' => $uc->id,
                'card_id' => $uc->card_id,
                'name' => $uc->card?->name ?? '',
                'type' => $uc->card?->type ?? '',
                'services' => $uc->card?->services ?? [],
                'total_times' => (int) $uc->total_times,
                'used_times' => (int) $uc->used_times,
                'remaining_times' => max(0, $remaining),
                'start_at' => (string) $uc->start_at,
                'end_at' => $uc->end_at ? (string) $uc->end_at : null,
                'status' => $status,
            ];
        }

        return $this->success($result);
    }

    /**
     * 核销次卡：扣减一次次数并生成一笔已完成订单（pay_type=card）
     * POST /api/marketing/cards/use
     *
     * 幂等：Redis NX 锁 card_use:{user_card_id}:{service_id}（30s TTL）拒绝短时间重复提交；
     * 并发扣次由 appointment_user_member_card 行锁 lockForUpdate 串行化。
     */
    public function use(Request $request)
    {
        $userId = $request->user_id;
        $userCardId = $this->decodeId($request->input('user_card_id'));
        $serviceId = $this->decodeId($request->input('service_id'));
        $remark = trim((string) $request->input('remark', ''));

        if (!$userCardId || !$serviceId) {
            return $this->error('次卡或服务ID无效', 422);
        }

        $service = Service::find($serviceId);
        if (!$service) {
            return $this->error('服务不存在', 422);
        }

        // 幂等锁：同一用户卡 + 同一服务 30 秒内重复提交直接拒绝
        $lockKey = 'card_use:' . $userCardId . ':' . $serviceId;
        try {
            $acquired = (bool) Redis::connection()->set($lockKey, (string) $userId, 'EX', 30, 'NX');
        } catch (\Throwable) {
            $acquired = true; // Redis 不可用时降级：仅靠 DB 行锁防并发，不防短时重复
        }
        if (!$acquired) {
            return $this->error('操作过于频繁，请稍后重试', 400);
        }

        try {
            $result = Db::transaction(function () use ($userId, $userCardId, $service, $remark) {
                // 行锁防并发扣次：串行化同一张卡的并发核销
                $userCard = UserMemberCard::where('id', $userCardId)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (!$userCard) {
                    throw new \RuntimeException('次卡不存在', 404);
                }
                if ($userCard->end_at && strtotime((string) $userCard->end_at) < time()) {
                    throw new \RuntimeException('次卡已过期', 400);
                }
                $remaining = (int) $userCard->total_times - (int) $userCard->used_times;
                if ($remaining <= 0) {
                    throw new \RuntimeException('次卡剩余次数不足', 422);
                }
                if ($userCard->status !== 'active') {
                    throw new \RuntimeException('次卡不可用', 400);
                }

                // 扣减次数
                $userCard->used_times = (int) $userCard->used_times + 1;
                if ((int) $userCard->used_times >= (int) $userCard->total_times) {
                    $userCard->status = 'used_up';
                }
                $userCard->save();

                // 写使用记录
                $now = date('Y-m-d H:i:s');
                $usage = new MemberCardUsage();
                $usage->id = MemberCardUsage::generateId();
                $usage->user_card_id = $userCard->id;
                $usage->service_id = $service->id;
                $usage->used_at = $now;
                $usage->status = 'active';
                $usage->save();

                // 生成已完成订单（pay_type=card 记录在 appointment_order_payment 上）
                $order = new Order();
                $order->id = Order::generateId();
                $order->order_no = 'CARD' . date('YmdHis') . random_int(10000, 99999);
                $order->user_id = $userId;
                $order->order_type = Order::ORDER_TYPE_APPOINTMENT;
                $order->total_amount = 0.00;
                $order->discount_amount = 0.00;
                $order->paid_amount = 0.00;
                $order->member_card_usage_id = $usage->id;
                $order->status = Order::STATUS_COMPLETED;
                $order->remark = $remark;
                $order->service_start_at = $now;
                $order->service_end_at = $now;
                $order->save();

                $usage->order_id = $order->id;
                $usage->save();

                $item = new OrderItem();
                $item->id = OrderItem::generateId();
                $item->order_id = $order->id;
                $item->target_type = 'service';
                $item->target_id = $service->id;
                $item->name = $service->name;
                $item->cover_image = (string) ($service->cover_image ?? '');
                $item->price = (float) $service->price;
                $item->quantity = 1;
                $item->save();

                $payment = new OrderPayment();
                $payment->id = OrderPayment::generateId();
                $payment->order_id = $order->id;
                $payment->payment_no = 'PAYCARD' . date('YmdHis') . random_int(10000, 99999);
                $payment->pay_type = 'card';
                $payment->transaction_id = '';
                $payment->amount = 0.00;
                $payment->status = OrderPayment::STATUS_SUCCESS;
                $payment->paid_at = $now;
                $payment->save();

                return [
                    'order_id' => $order->id,
                    'usage_id' => $usage->id,
                    'remaining_times' => (int) $userCard->total_times - (int) $userCard->used_times,
                ];
            });

            return $this->success($result, '核销成功');
        } catch (\RuntimeException $e) {
            // 失败路径释放锁，允许立即重试
            try {
                Redis::connection()->del($lockKey);
            } catch (\Throwable) {
            }
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            try {
                Redis::connection()->del($lockKey);
            } catch (\Throwable) {
            }
            return $this->error('核销失败，请稍后重试');
        }
    }
}
