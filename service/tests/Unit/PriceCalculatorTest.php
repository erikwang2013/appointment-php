<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\PriceCalculator;
use app\model\Coupon;
use app\model\MemberCard;
use app\model\MemberCardUsage;
use app\model\UserCoupon;
use app\model\UserMemberCard;

/**
 * PriceCalculator 单元测试（本次返工核心：计价引擎拆分）
 *
 * 覆盖：
 *   - calculate()：无优惠纯计算（零 DB）、fixed 券满/不满门槛、percent 券折扣额、
 *     次卡命中免单/未命中/次数不足、券卡互斥、积分未开放、金额分转元精度
 *   - consume()：券原子置 used / 已用影响 0 行报错、次卡 whereRaw 原子扣次防超扣、
 *     写 erik_member_card_usage、used_up 判定
 *
 * 策略：与本仓库现有测试一致——测试 DB 直连（bootstrap.php 已建 Eloquent Capsule），
 * 每用例自造数据并 tearDown 清理，绝不触碰既有数据（ID 用 snowflake 生成）。
 * consume() 与 calculate() 的券/卡分支依赖 DB（模型查询），属本仓库测试基建可承载范围；
 * 纯计算路径（无优惠/互斥/积分/精度）不触 DB。
 */
class PriceCalculatorTest extends TestCase
{
    /** @var int[] 用例创建的优惠券定义 ID */
    private array $couponIds = [];

    /** @var int[] 用例创建的用户券记录 ID */
    private array $userCouponIds = [];

    /** @var int[] 用例创建的会员卡定义 ID */
    private array $memberCardIds = [];

    /** @var int[] 用例创建的用户会员卡 ID */
    private array $userMemberCardIds = [];

    /** @var int[] 用例产生的次卡使用记录 order_id */
    private array $usageOrderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->usageOrderIds as $orderId) {
            MemberCardUsage::where('order_id', $orderId)->delete();
        }
        foreach ($this->userMemberCardIds as $id) {
            UserMemberCard::where('id', $id)->delete();
        }
        foreach ($this->memberCardIds as $id) {
            MemberCard::where('id', $id)->delete();
        }
        foreach ($this->userCouponIds as $id) {
            UserCoupon::where('id', $id)->delete();
        }
        foreach ($this->couponIds as $id) {
            Coupon::where('id', $id)->delete();
        }
        $this->couponIds = [];
        $this->userCouponIds = [];
        $this->memberCardIds = [];
        $this->userMemberCardIds = [];
        $this->usageOrderIds = [];
    }

    // ── 测试数据工厂 ──

    private function newUserId(): int
    {
        return 9900000000000000 + random_int(1, 999999);
    }

    /** 建优惠券定义（返回模型，id 由 support\Model 创建钩子自动生成） */
    private function makeCoupon(string $type, float $amount, float $minAmount = 0): Coupon
    {
        $coupon = Coupon::create([
            'name'       => '测试券-' . uniqid(),
            'type'       => $type,
            'amount'     => $amount,
            'min_amount' => $minAmount,
            'status'     => 1, // erik_coupon.status 为 tinyint
        ]);
        $this->couponIds[] = $coupon->id;
        return $coupon;
    }

    /** 建用户券记录（返回模型） */
    private function makeUserCoupon(int $userId, Coupon $coupon, string $status = 'available'): UserCoupon
    {
        $uc = UserCoupon::create([
            'user_id'   => $userId,
            'coupon_id' => $coupon->id,
            'status'    => $status,
            'received_at' => date('Y-m-d H:i:s'),
        ]);
        $this->userCouponIds[] = $uc->id;
        return $uc;
    }

    /**
     * 建次卡（会员卡定义 + 用户会员卡，返回用户卡模型）
     */
    private function makeTimesCard(int $userId, array $serviceIds, int $totalTimes, int $usedTimes = 0, string $status = 'active'): UserMemberCard
    {
        $card = MemberCard::create([
            'name'        => '测试次卡-' . uniqid(),
            'type'        => 'times',
            'price'       => 100,
            'total_times' => $totalTimes,
            // 与生产一致：services 存对象数组 [{"service_id":..,"times":..}]（见迁移注释与种子数据）
            'services'    => array_map(
                fn($sid) => ['service_id' => (string) $sid, 'times' => 1],
                $serviceIds
            ),
            'status'      => 1, // erik_member_card.status 为 tinyint
        ]);
        $this->memberCardIds[] = $card->id;

        $userCard = UserMemberCard::create([
            'user_id'     => $userId,
            'card_id'     => $card->id,
            'start_at'    => date('Y-m-d H:i:s'),
            'end_at'      => null,
            'total_times' => $totalTimes,
            'used_times'  => $usedTimes,
            'status'      => $status,
        ]);
        $this->userMemberCardIds[] = $userCard->id;
        return $userCard;
    }

    /** 订单项快捷构造 */
    private function item(string $targetId, float $price, int $quantity = 1): array
    {
        return [
            'target_type' => 'service',
            'target_id'   => $targetId,
            'name'        => '服务' . $targetId,
            'price'       => $price,
            'quantity'    => $quantity,
        ];
    }

    // ── calculate()：纯计算（零 DB） ──

    #[Test] public function calculate_no_discount_returns_totals(): void
    {
        $result = PriceCalculator::calculate([
            $this->item('S1', 100, 1),
            $this->item('S2', 50.5, 2),
        ], ['user_id' => $this->newUserId()]);

        $this->assertSame(201.0, $result['total_amount']);
        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(201.0, $result['paid_amount']);
        $this->assertNull($result['coupon_id']);
        $this->assertNull($result['user_coupon_id']);
        $this->assertNull($result['member_card_usage_id']);
    }

    #[Test] public function calculate_rejects_use_points(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('积分抵扣暂未开放');
        PriceCalculator::calculate([$this->item('S1', 100)], ['user_id' => 1, 'use_points' => 100]);
    }

    #[Test] public function calculate_rejects_mutually_exclusive_coupon_and_card(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('一次订单仅可使用一种优惠方式');
        PriceCalculator::calculate(
            [$this->item('S1', 100)],
            ['user_id' => 1, 'coupon_id' => 10001, 'member_card_usage_id' => 20001]
        );
    }

    #[Test] public function calculate_converts_fen_to_yuan_at_cent_precision(): void
    {
        // 0.01 元精度：3 × 0.01 = 0.03；1 × 0.01 + 3 × 0.10 = 0.31
        $r1 = PriceCalculator::calculate([$this->item('S1', 0.01, 3)], ['user_id' => 1]);
        $this->assertSame(0.03, $r1['total_amount']);
        $this->assertSame(0.03, $r1['paid_amount']);

        $r2 = PriceCalculator::calculate([
            $this->item('S1', 0.01, 1),
            $this->item('S2', 0.1, 3),
        ], ['user_id' => 1]);
        $this->assertSame(0.31, $r2['total_amount']);
        $this->assertSame(0.31, $r2['paid_amount']);
    }

    #[Test] public function calculate_treats_zero_quantity_as_one(): void
    {
        $result = PriceCalculator::calculate([$this->item('S1', 30, 0)], ['user_id' => 1]);
        $this->assertSame(30.0, $result['total_amount']);
    }

    // ── calculate()：fixed 券 ──

    #[Test] public function calculate_fixed_coupon_discounts_when_min_amount_met(): void
    {
        $coupon = $this->makeCoupon('fixed', 10, 20);
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon);

        $result = PriceCalculator::calculate(
            [$this->item('S1', 30)],
            ['user_id' => $userId, 'user_coupon_id' => $uc->id]
        );

        $this->assertSame(30.0, $result['total_amount']);
        $this->assertSame(10.0, $result['discount_amount']);
        $this->assertSame(20.0, $result['paid_amount']);
        $this->assertSame((int) $coupon->id, $result['coupon_id']);
        $this->assertSame((int) $uc->id, $result['user_coupon_id']);
    }

    #[Test] public function calculate_fixed_coupon_rejects_below_min_amount(): void
    {
        $coupon = $this->makeCoupon('fixed', 10, 50);
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('未满足优惠券使用门槛');
        PriceCalculator::calculate(
            [$this->item('S1', 30)],
            ['user_id' => $userId, 'user_coupon_id' => $uc->id]
        );
    }

    #[Test] public function calculate_discount_capped_at_total_amount(): void
    {
        $coupon = $this->makeCoupon('fixed', 50, 0);
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon);

        $result = PriceCalculator::calculate(
            [$this->item('S1', 30)],
            ['user_id' => $userId, 'user_coupon_id' => $uc->id]
        );

        $this->assertSame(30.0, $result['discount_amount']);
        $this->assertSame(0.0, $result['paid_amount']);
    }

    #[Test] public function calculate_fixed_coupon_by_definition_id(): void
    {
        // M4: coupon_id 直通路径已禁用——券必须先领取（erik_user_coupon 领券记录），直通路径不校验有效期/状态且不消费
        $coupon = $this->makeCoupon('fixed', 5, 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('请先领取优惠券');
        PriceCalculator::calculate(
            [$this->item('S1', 12)],
            ['user_id' => $this->newUserId(), 'coupon_id' => $coupon->id]
        );
    }

    #[Test] public function calculate_rejects_used_user_coupon(): void
    {
        $coupon = $this->makeCoupon('fixed', 5, 0);
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon, 'used');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('券已被使用');
        PriceCalculator::calculate(
            [$this->item('S1', 12)],
            ['user_id' => $userId, 'user_coupon_id' => $uc->id]
        );
    }

    #[Test] public function calculate_rejects_unknown_coupon(): void
    {
        // M4: coupon_id 直通路径已禁用，未领取的券统一提示先领取（原提示 '优惠券不存在'）
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('请先领取优惠券');
        PriceCalculator::calculate(
            [$this->item('S1', 12)],
            ['user_id' => $this->newUserId(), 'coupon_id' => 999999999999999999]
        );
    }

    // ── calculate()：percent 券 ──

    #[Test] public function calculate_percent_coupon_discounts_percentage_of_original_price(): void
    {
        $coupon = $this->makeCoupon('percent', 20, 0); // 折扣 20%
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon);

        $result = PriceCalculator::calculate(
            [$this->item('S1', 100)],
            ['user_id' => $userId, 'user_coupon_id' => $uc->id]
        );

        $this->assertSame(20.0, $result['discount_amount']);
        $this->assertSame(80.0, $result['paid_amount']);
    }

    #[Test] public function calculate_rejects_unsupported_coupon_type(): void
    {
        $coupon = $this->makeCoupon('cashback', 5, 0);
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券类型不支持');
        PriceCalculator::calculate(
            [$this->item('S1', 100)],
            ['user_id' => $userId, 'user_coupon_id' => $uc->id]
        );
    }

    // ── calculate()：次卡 ──

    #[Test] public function calculate_times_card_frees_matched_service_items(): void
    {
        $userId = $this->newUserId();
        $userCard = $this->makeTimesCard($userId, ['S1'], 5, 1);

        $result = PriceCalculator::calculate(
            [
                $this->item('S1', 50, 2), // 命中：免单 100 元，占 2 次
                $this->item('S2', 30),    // 未命中：照付
            ],
            ['user_id' => $userId, 'member_card_usage_id' => $userCard->id]
        );

        $this->assertSame(130.0, $result['total_amount']);
        $this->assertSame(100.0, $result['discount_amount']);
        $this->assertSame(30.0, $result['paid_amount']);
        // 未消费前回传卡片 ID，供支付成功时 consume() 定位
        $this->assertSame((int) $userCard->id, $result['member_card_usage_id']);
    }

    #[Test] public function calculate_times_card_services_object_array_format_hits(): void
    {
        // 回归：erik_member_card.services 为对象数组 [{"service_id":..,"times":..}]（迁移注释 + demo_seeds.sql 格式），
        // 此前按标量数组解析导致次卡抵扣完全失效
        $userId = $this->newUserId();
        $userCard = $this->makeTimesCard($userId, ['S1'], 5, 1);

        $result = PriceCalculator::calculate(
            [$this->item('S1', 50, 1)],
            ['user_id' => $userId, 'member_card_usage_id' => $userCard->id]
        );

        $this->assertSame(50.0, $result['total_amount']);
        $this->assertSame(50.0, $result['discount_amount']);
        $this->assertSame(0.0, $result['paid_amount']);
        $this->assertSame((int) $userCard->id, $result['member_card_usage_id']);
    }

    #[Test] public function calculate_times_card_without_match_rejects(): void
    {
        $userId = $this->newUserId();
        $userCard = $this->makeTimesCard($userId, ['S9'], 5);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('会员卡服务与订单项目不匹配');
        PriceCalculator::calculate(
            [$this->item('S1', 50)],
            ['user_id' => $userId, 'member_card_usage_id' => $userCard->id]
        );
    }

    #[Test] public function calculate_times_card_insufficient_remaining_times_rejects(): void
    {
        $userId = $this->newUserId();
        $userCard = $this->makeTimesCard($userId, ['S1'], 2, 2); // 剩余 0 次

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('会员卡剩余次数不足');
        PriceCalculator::calculate(
            [$this->item('S1', 50)],
            ['user_id' => $userId, 'member_card_usage_id' => $userCard->id]
        );
    }

    // ── consume()：券 ──

    #[Test] public function consume_coupon_marks_user_coupon_used(): void
    {
        $coupon = $this->makeCoupon('fixed', 5, 0);
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon);

        $result = PriceCalculator::consume([$this->item('S1', 10)], [
            'user_id'        => $userId,
            'order_id'       => 12345,
            'user_coupon_id' => $uc->id,
        ]);

        $this->assertSame(['member_card_usage_id' => null], $result);

        $fresh = UserCoupon::find($uc->id);
        $this->assertSame('used', $fresh->status);
        $this->assertNotNull($fresh->used_at);
    }

    #[Test] public function consume_coupon_already_used_throws(): void
    {
        $coupon = $this->makeCoupon('fixed', 5, 0);
        $userId = $this->newUserId();
        $uc = $this->makeUserCoupon($userId, $coupon, 'used');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('券已被使用');
        PriceCalculator::consume([$this->item('S1', 10)], [
            'user_id'        => $userId,
            'order_id'       => 12345,
            'user_coupon_id' => $uc->id,
        ]);
    }

    // ── consume()：次卡 ──

    #[Test] public function consume_times_card_atomically_increments_and_writes_usage(): void
    {
        $userId = $this->newUserId();
        // 生产 target_id 为数字 snowflake ID，此处用数字 ID 贴合真实数据
        $userCard = $this->makeTimesCard($userId, ['1001'], 5, 2);
        $orderId = 9900000000000000 + random_int(1, 999999);
        $this->usageOrderIds[] = $orderId;

        $result = PriceCalculator::consume(
            [
                $this->item('1001', 50, 1),
                $this->item('1001', 80, 2), // 同服务数量 2 → 拆 2 条使用记录
            ],
            ['user_id' => $userId, 'order_id' => $orderId, 'member_card_usage_id' => $userCard->id]
        );

        // 返回首条使用记录 ID
        $this->assertIsInt($result['member_card_usage_id']);
        $this->assertGreaterThan(0, $result['member_card_usage_id']);

        // 次数原子 +3（1 + 2）
        $fresh = UserMemberCard::find($userCard->id);
        $this->assertSame(5, (int) $fresh->used_times);

        // 使用记录按数量拆 3 条
        $usages = MemberCardUsage::where('order_id', $orderId)->orderBy('service_id')->get();
        $this->assertCount(3, $usages);
        foreach ($usages as $usage) {
            $this->assertSame((string) $userCard->id, (string) $usage->user_card_id);
            $this->assertSame('1001', (string) $usage->service_id);
            $this->assertNotNull($usage->used_at);
        }
    }

    #[Test] public function consume_times_card_marks_used_up_when_exhausted(): void
    {
        $userId = $this->newUserId();
        $userCard = $this->makeTimesCard($userId, ['S1'], 2, 1); // 剩 1 次
        $orderId = 9900000000000000 + random_int(1, 999999);
        $this->usageOrderIds[] = $orderId;

        PriceCalculator::consume([$this->item('S1', 50)], [
            'user_id' => $userId, 'order_id' => $orderId, 'member_card_usage_id' => $userCard->id,
        ]);

        $fresh = UserMemberCard::find($userCard->id);
        $this->assertSame(2, (int) $fresh->used_times);
        $this->assertSame('used_up', $fresh->status);
    }

    #[Test] public function consume_times_card_rejects_overdraw(): void
    {
        $userId = $this->newUserId();
        $userCard = $this->makeTimesCard($userId, ['S1'], 2, 2); // 已用满

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('会员卡次数不足，请刷新后重试');
        PriceCalculator::consume([$this->item('S1', 50)], [
            'user_id' => $userId, 'order_id' => 12345, 'member_card_usage_id' => $userCard->id,
        ]);

        // 超扣被 whereRaw 守卫拦截：次数未被改动
        $this->assertSame(2, (int) UserMemberCard::find($userCard->id)->used_times);
    }

    #[Test] public function consume_times_card_without_match_throws_before_any_write(): void
    {
        $userId = $this->newUserId();
        $userCard = $this->makeTimesCard($userId, ['S9'], 5);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('会员卡服务与订单项目不匹配');
        PriceCalculator::consume([$this->item('S1', 50)], [
            'user_id' => $userId, 'order_id' => 12345, 'member_card_usage_id' => $userCard->id,
        ]);

        $this->assertSame(0, (int) UserMemberCard::find($userCard->id)->used_times);
    }
}
