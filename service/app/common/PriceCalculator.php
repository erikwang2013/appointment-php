<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\MemberCardUsage;
use app\model\UserCoupon;
use app\model\UserMemberCard;

/**
 * 优惠计价引擎
 *
 * 规则（已定稿）：
 * - 一次订单仅一种优惠（互斥）：次卡/会员卡 > 券 > 积分（积分暂未开放）
 * - min_amount 按原价 total_amount 判断
 * - percent 券折扣额 = 原价 × 百分比（百分比存于 Coupon.amount，如 10 表示折扣 10%）
 * - 内部计算全程使用分（int），落库时转元
 * - 积分比例 100 积分 = 1 元（本次未实施）
 *
 * 消费时机（统一为支付成功时）：
 * - calculate()：纯计算，零 DB 写副作用（只读校验 + 算额）
 * - consume()：支付成功时调用，原子消费（券置 used / 次卡 whereRaw 扣次 + 写使用记录 + used_up 判定）
 *
 * 金额转换与 WechatPayService 保持一致：round($fen / 100, 2) 转元，(int) round($yuan * 100) 转分。
 */
class PriceCalculator
{
    /**
     * 计算订单金额与优惠（纯计算，零 DB 写副作用）
     *
     * @param array $items 订单项（已解码），每项含 target_type/target_id/name/price(元)/quantity
     * @param array $options 优惠参数: user_id/coupon_id/user_coupon_id/member_card_usage_id/use_points
     * @return array{total_amount: float, discount_amount: float, paid_amount: float, coupon_id: int|null, user_coupon_id: int|null, member_card_usage_id: int|null}
     * @throws \InvalidArgumentException 优惠参数不合法或优惠不可用时抛出
     */
    public static function calculate(array $items, array $options): array
    {
        $userId            = (int)($options['user_id'] ?? 0);
        $couponId          = self::intOrNull($options['coupon_id'] ?? null);
        $userCouponId      = self::intOrNull($options['user_coupon_id'] ?? null);
        $memberCardUsageId = self::intOrNull($options['member_card_usage_id'] ?? null);
        $usePoints         = (int)($options['use_points'] ?? 0);

        // 原价（分）
        $totalFen = 0;
        foreach ($items as $item) {
            $priceFen  = (int) round(((float)($item['price'] ?? 0)) * 100);
            $quantity  = max(1, (int)($item['quantity'] ?? 1));
            $totalFen += $priceFen * $quantity;
        }

        // 积分抵扣本次未实施
        if ($usePoints > 0) {
            throw new \InvalidArgumentException('积分抵扣暂未开放');
        }

        $hasCoupon = $couponId !== null || $userCouponId !== null;
        $hasCard   = $memberCardUsageId !== null;

        // 互斥：券 / 次卡 同时传入报错
        if (($hasCoupon ? 1 : 0) + ($hasCard ? 1 : 0) > 1) {
            throw new \InvalidArgumentException('一次订单仅可使用一种优惠方式');
        }

        $discountFen      = 0;
        $usedCouponId     = null;
        $usedUserCouponId = null;

        if ($hasCard) {
            // 次卡：只读校验 + 算额，不产生任何写副作用（消费在支付成功时由 consume() 完成）
            $userCard = self::loadMemberCard($memberCardUsageId, $userId);
            [$neededTimes, $discountFen] = self::matchCardServices($userCard, $items);
            if ($neededTimes <= 0) {
                throw new \InvalidArgumentException('会员卡服务与订单项目不匹配');
            }
            $remaining = (int)($userCard->total_times ?? 0) - (int)($userCard->used_times ?? 0);
            if ($remaining < $neededTimes) {
                throw new \InvalidArgumentException('会员卡剩余次数不足');
            }
        } elseif ($hasCoupon) {
            [$discountFen, $usedCouponId, $usedUserCouponId] = self::applyCoupon($couponId, $userCouponId, $userId, $totalFen);
        }

        $discountFen = min($discountFen, $totalFen);
        $paidFen     = max(0, $totalFen - $discountFen);

        return [
            'total_amount'         => round($totalFen / 100, 2),
            'discount_amount'      => round($discountFen / 100, 2),
            'paid_amount'          => round($paidFen / 100, 2),
            'coupon_id'            => $usedCouponId,
            'user_coupon_id'       => $usedUserCouponId,
            // 未消费前回传用户会员卡 ID（即入参 member_card_usage_id），
            // 供支付成功时 consume() 定位卡片；消费后由调用方回写首条使用记录 ID
            'member_card_usage_id' => $memberCardUsageId,
        ];
    }

    /**
     * 支付成功时原子消费优惠（唯一消费点）
     *
     * 幂等：券仅 available 可置 used；次卡 whereRaw 防并发超扣。
     * 任一优惠已被并发消费时抛 \InvalidArgumentException，由调用方整体回滚。
     *
     * @param array $items 订单项（同 calculate()）
     * @param array $options user_id/order_id/user_coupon_id/member_card_usage_id（后两者取订单落库值）
     * @return array{member_card_usage_id: int|null} 次卡首条使用记录 ID（供订单回写），无次卡时为 null
     * @throws \InvalidArgumentException
     */
    public static function consume(array $items, array $options): array
    {
        $userId            = (int)($options['user_id'] ?? 0);
        $orderId           = (int)($options['order_id'] ?? 0);
        $userCouponId      = self::intOrNull($options['user_coupon_id'] ?? null);
        $memberCardUsageId = self::intOrNull($options['member_card_usage_id'] ?? null);

        $usageId = null;

        if ($userCouponId !== null) {
            self::consumeCoupon($userCouponId, $userId);
        } elseif ($memberCardUsageId !== null) {
            $usageId = self::consumeMemberCard($memberCardUsageId, $userId, $orderId, $items);
        }

        return ['member_card_usage_id' => $usageId];
    }

    /**
     * 原子置券已使用（防并发重复消费，影响 0 行说明已被使用）
     */
    private static function consumeCoupon(int $userCouponId, int $userId): void
    {
        $affected = UserCoupon::where('id', $userCouponId)
            ->where('user_id', $userId)
            ->where('status', 'available')
            ->update(['status' => 'used', 'used_at' => date('Y-m-d H:i:s')]);
        if ($affected === 0) {
            throw new \InvalidArgumentException('券已被使用');
        }
    }

    /**
     * 原子扣次卡次数 + 写使用记录 + used_up 判定
     *
     * @return int 首条使用记录 ID
     */
    private static function consumeMemberCard(int $userCardId, int $userId, int $orderId, array $items): int
    {
        $userCard = self::loadMemberCard($userCardId, $userId);
        [$neededTimes, , $matchedItems] = self::matchCardServices($userCard, $items);

        if ($neededTimes <= 0) {
            throw new \InvalidArgumentException('会员卡服务与订单项目不匹配');
        }

        // 原子扣次：防并发超扣
        $affected = UserMemberCard::where('id', $userCard->id)
            ->whereRaw('used_times + ? <= total_times', [$neededTimes])
            ->increment('used_times', $neededTimes);
        if ($affected === 0) {
            throw new \InvalidArgumentException('会员卡次数不足，请刷新后重试');
        }

        // used_up 判定
        $userCard->refresh();
        if ((int) $userCard->used_times >= (int) $userCard->total_times && $userCard->status === 'active') {
            $userCard->status = 'used_up';
            $userCard->save();
        }

        // 写次卡使用记录（每项一条，数量按条拆分）
        $firstUsageId = 0;
        foreach ($matchedItems as $match) {
            for ($i = 0; $i < $match['quantity']; $i++) {
                $usage = MemberCardUsage::create([
                    'id'           => MemberCardUsage::generateId(),
                    'user_card_id' => $userCard->id,
                    'order_id'     => $orderId,
                    'service_id'   => $match['service_id'],
                    'used_at'      => date('Y-m-d H:i:s'),
                ]);
                $firstUsageId = $firstUsageId ?: (int) $usage->id;
            }
        }

        return $firstUsageId;
    }

    /**
     * 加载并校验用户会员卡（只读）
     *
     * @throws \InvalidArgumentException
     */
    private static function loadMemberCard(int $userCardId, int $userId): UserMemberCard
    {
        $userCard = UserMemberCard::with('card')
            ->where('id', $userCardId)
            ->where('user_id', $userId)
            ->first();
        if (!$userCard || !$userCard->card) {
            throw new \InvalidArgumentException('会员卡不存在');
        }
        if ((string)($userCard->card->type ?? '') !== 'times') {
            throw new \InvalidArgumentException('该会员卡不支持次卡抵扣');
        }
        if ($userCard->status !== 'active') {
            throw new \InvalidArgumentException('会员卡不可用');
        }
        if (!empty($userCard->end_at) && strtotime((string) $userCard->end_at) < time()) {
            throw new \InvalidArgumentException('会员卡已过期');
        }
        return $userCard;
    }

    /**
     * 命中订单中的服务项
     *
     * @return array{0: int, 1: int, 2: array} [所需次数, 免单金额(分), 命中项列表]
     */
    private static function matchCardServices(UserMemberCard $userCard, array $items): array
    {
        // services 存储为对象数组：[{"service_id":..,"times":..}]（见迁移 erik_member_card.services 注释与种子数据），
        // 统一用 array_column 取 service_id；兼容历史标量数组 [sid1, sid2]
        $cardServices = (array) ($userCard->card->services ?? []);
        if (is_array(reset($cardServices))) {
            $serviceIds = array_column($cardServices, 'service_id');
        } else {
            $serviceIds = $cardServices;
        }

        $cardServiceMap = [];
        foreach ($serviceIds as $sid) {
            if ($sid === null || $sid === '') {
                continue;
            }
            $cardServiceMap[(string) $sid] = true;
        }

        $discountFen  = 0;
        $neededTimes  = 0;
        $matchedItems = [];
        foreach ($items as $item) {
            if ((string)($item['target_type'] ?? 'service') !== 'service') {
                continue;
            }
            if (!isset($cardServiceMap[(string)($item['target_id'] ?? '')])) {
                continue;
            }
            $quantity     = max(1, (int)($item['quantity'] ?? 1));
            $neededTimes += $quantity;
            $discountFen += (int) round(((float)($item['price'] ?? 0)) * 100) * $quantity;
            $matchedItems[] = [
                'service_id' => (int) $item['target_id'],
                'quantity'   => $quantity,
            ];
        }

        return [$neededTimes, $discountFen, $matchedItems];
    }

    /**
     * 优惠券抵扣（只读校验与算额，不产生写副作用）
     *
     * @param int|null $couponId     优惠券定义 ID
     * @param int|null $userCouponId 用户优惠券记录 ID
     * @param int      $userId       用户 ID
     * @param int      $totalFen     原价（分）
     * @return array{0: int, 1: int|null, 2: int|null} [折扣额(分), 券定义ID, 用户券记录ID]
     * @throws \InvalidArgumentException
     */
    private static function applyCoupon(?int $couponId, ?int $userCouponId, int $userId, int $totalFen): array
    {
        $userCoupon = null;
        $coupon     = null;

        if ($userCouponId !== null) {
            $userCoupon = UserCoupon::with('coupon')
                ->where('id', $userCouponId)
                ->where('user_id', $userId)
                ->first();
            if (!$userCoupon || !$userCoupon->coupon) {
                throw new \InvalidArgumentException('优惠券不存在');
            }
            if ($userCoupon->status !== 'available') {
                throw new \InvalidArgumentException('券已被使用');
            }
            $coupon = $userCoupon->coupon;
        } elseif ($couponId !== null) {
            // M4: 禁用 coupon_id 直通路径——券必须先领取（erik_user_coupon 领券记录），
            // 直通路径不校验有效期/状态且 consume() 不消费，可被无限复用
            throw new \InvalidArgumentException('请先领取优惠券');
        }

        // M4: 券有效期与状态校验（start_at/end_at 窗口 + 上架状态）
        $nowTs = time();
        if ((int)($coupon->status ?? 1) !== 1) {
            throw new \InvalidArgumentException('优惠券不可用');
        }
        if (!empty($coupon->start_at) && strtotime((string)$coupon->start_at) > $nowTs) {
            throw new \InvalidArgumentException('优惠券尚未生效');
        }
        if (!empty($coupon->end_at) && strtotime((string)$coupon->end_at) < $nowTs) {
            throw new \InvalidArgumentException('优惠券已过期');
        }

        // 使用门槛按原价 total_amount 判断
        $minAmountFen = (int) round(((float)($coupon->min_amount ?? 0)) * 100);
        if ($totalFen < $minAmountFen) {
            throw new \InvalidArgumentException('未满足优惠券使用门槛');
        }

        $type = (string)($coupon->type ?? '');
        if (!in_array($type, ['fixed', 'percent'], true)) {
            throw new \InvalidArgumentException('优惠券类型不支持');
        }

        if ($type === 'fixed') {
            // 满减券：抵扣固定金额
            $discountFen = (int) round(((float)($coupon->amount ?? 0)) * 100);
        } else {
            // 折扣券：折扣额 = 原价 × 百分比
            $percent     = (float)($coupon->amount ?? 0);
            $discountFen = (int) round($totalFen * $percent / 100);
        }

        return [$discountFen, (int) $coupon->id, $userCoupon ? (int) $userCoupon->id : null];
    }

    /**
     * 将可能为 null/'' 的输入转为 int|null
     */
    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}
