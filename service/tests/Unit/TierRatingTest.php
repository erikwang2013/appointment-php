<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\TierRatingService;
use app\model\Notification;
use app\model\Order;
use app\model\OrderReview;
use app\model\TechnicianProfile;
use app\model\TechnicianTierConfig;
use app\model\TechnicianTierLog;
use app\model\User;
use support\Db;

/**
 * 技师等级自动评定测试
 *
 * 覆盖：订单量达标升级、评分达标升级、条件未达不升、
 * 降级保护（默认不降级）/ 允许降级、幂等（重复评定不重复写日志）、
 * 等级变更日志与站内通知落库。
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 * 依赖等级配置：junior(0/0.0) senior(100/4.0) expert(500/4.5)。
 */
class TierRatingTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    /** @var string[] 用例订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例评价 ID，tearDown 统一清理 */
    private array $reviewIds = [];

    protected function tearDown(): void
    {
        if ($this->profileIds) {
            TechnicianTierLog::whereIn('technician_id', $this->profileIds)->delete();
        }
        if ($this->userIds) {
            Notification::whereIn('user_id', $this->userIds)->delete();
        }
        if ($this->reviewIds) {
            OrderReview::whereIn('id', $this->reviewIds)->delete();
        }
        if ($this->orderIds) {
            Order::whereIn('id', $this->orderIds)->delete();
        }
        if ($this->profileIds) {
            TechnicianProfile::whereIn('id', $this->profileIds)->delete();
        }
        if ($this->userIds) {
            User::whereIn('id', $this->userIds)->delete();
        }
        $this->userIds = $this->profileIds = $this->orderIds = $this->reviewIds = [];
    }

    /** 造用户 + 技师档案 */
    private function makeTechnician(): TechnicianProfile
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = (string) $user->id;

        $profile = TechnicianProfile::create([
            'id'        => TechnicianProfile::generateId(),
            'user_id'   => $user->id,
            'real_name' => '测试技师' . substr((string) $user->id, -4),
            'status'    => 'approved',
        ]);
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    /** 造已完成订单（批量 insert，status=completed 计入技师订单量） */
    private function makeCompletedOrders(string $technicianId, int $count): void
    {
        if ($count <= 0) {
            return;
        }
        $rows = [];
        $seq  = random_int(1, 999999);
        // snowflake 同毫秒批量生成会撞主键，批内加随机偏移保证唯一
        $base = random_int(0, 99999) * 1000;
        for ($i = 0; $i < $count; $i++) {
            $id = (string) ((int) Order::generateId() + $base + $i);
            $rows[] = [
                'id'            => $id,
                'order_no'      => 'T' . $seq . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'user_id'       => $this->userIds[0],
                'technician_id' => $technicianId,
                'status'        => Order::STATUS_COMPLETED,
                'paid_amount'   => 100.00,
            ];
            $this->orderIds[] = $id;
        }
        Db::table('appointment_order')->insert($rows);
    }

    /** 造技师评价（批量 insert，参与评分均值计算） */
    private function makeReviews(string $technicianId, array $ratings): void
    {
        if (!$ratings) {
            return;
        }
        $rows = [];
        $base = random_int(0, 99999) * 1000;
        foreach ($ratings as $i => $rating) {
            $id = (string) ((int) OrderReview::generateId() + $base + $i);
            $rows[] = [
                'id'            => $id,
                // uk_order_id 一单一条评价：以评价 ID 自充订单 ID 保证唯一
                'order_id'      => $id,
                'user_id'       => $this->userIds[0],
                'technician_id' => $technicianId,
                'rating'        => $rating,
                'status'        => OrderReview::STATUS_VISIBLE,
            ];
            $this->reviewIds[] = $id;
        }
        Db::table('appointment_order_review')->insert($rows);
    }

    private function tierBySlug(string $slug): TechnicianTierConfig
    {
        return TechnicianTierConfig::where('slug', $slug)->firstOrFail();
    }

    private function logCount(int|string $technicianId): int
    {
        return TechnicianTierLog::where('technician_id', (string) $technicianId)->count();
    }

    #[Test]
    public function orderCountReachesThresholdUpgrades(): void
    {
        $profile = $this->makeTechnician();
        $this->makeCompletedOrders($profile->id, 100);
        $this->makeReviews($profile->id, array_fill(0, 20, 5));

        $result = TierRatingService::evaluate((string) $profile->id);

        $this->assertSame(TierRatingService::ACTION_UPGRADE, $result['action']);
        $this->assertSame((string) $this->tierBySlug('senior')->id, $result['new_tier_id']);
        $this->assertTrue($result['changed']);
        $this->assertSame(100, $result['order_count']);
        $this->assertSame(5.0, $result['rating']);

        // 落库断言：日志 1 条 + 技师站内通知 1 条
        $this->assertSame(1, $this->logCount($profile->id));
        $log = TechnicianTierLog::where('technician_id', $profile->id)->latest()->first();
        $this->assertNull($log->old_tier_id);
        $this->assertSame((string) $this->tierBySlug('senior')->id, (string) $log->new_tier_id);
        $this->assertNotEmpty($log->reason);
        $this->assertSame(1, Notification::where('user_id', $profile->user_id)
            ->where('type', 'tier')->count());

        // 档案已写等级
        $profile->refresh();
        $this->assertSame((string) $this->tierBySlug('senior')->id, (string) $profile->tier_id);
        $this->assertSame(100, $profile->order_count);
    }

    #[Test]
    public function ratingReachesThresholdUpgrades(): void
    {
        $profile = $this->makeTechnician();
        // 订单量达标但无评价（评分 0）：仅归最低等级 junior，未达评分条件不升 senior
        $this->makeCompletedOrders($profile->id, 100);
        $first = TierRatingService::evaluate((string) $profile->id);
        $this->assertSame((string) $this->tierBySlug('junior')->id, $first['new_tier_id']);

        // 补好评后评分达标 → 升级 senior
        $this->makeReviews($profile->id, array_fill(0, 20, 5));
        $second = TierRatingService::evaluate((string) $profile->id);
        $this->assertSame(TierRatingService::ACTION_UPGRADE, $second['action']);
        $this->assertSame((string) $this->tierBySlug('senior')->id, $second['new_tier_id']);
        $this->assertSame(2, $this->logCount($profile->id));
    }

    #[Test]
    public function conditionNotMetKeepsLowestTier(): void
    {
        $profile = $this->makeTechnician();
        $this->makeCompletedOrders($profile->id, 5);
        $this->makeReviews($profile->id, [3, 3, 3]);

        // 首次评定：无匹配等级归入最低（junior）
        $result = TierRatingService::evaluate((string) $profile->id);
        $this->assertSame((string) $this->tierBySlug('junior')->id, $result['new_tier_id']);
        $this->assertSame(1, $this->logCount($profile->id));

        // 条件未达低等级不升：junior 之上是 senior，5 单 3 分不满足
        $again = TierRatingService::evaluate((string) $profile->id);
        $this->assertSame(TierRatingService::ACTION_NONE, $again['action']);
        $this->assertSame(1, $this->logCount($profile->id));
    }

    #[Test]
    public function downgradeBlockedByDefault(): void
    {
        $profile = $this->makeTechnician();
        $this->makeCompletedOrders($profile->id, 100);
        $this->makeReviews($profile->id, array_fill(0, 20, 5));
        TierRatingService::evaluate((string) $profile->id);
        $this->assertSame(1, $this->logCount($profile->id));

        // 数据下滑（清空订单与评价）→ 默认降级保护拦截，保持 senior
        Order::where('technician_id', $profile->id)->delete();
        OrderReview::where('technician_id', $profile->id)->delete();

        $result = TierRatingService::evaluate((string) $profile->id);
        $this->assertSame(TierRatingService::ACTION_KEEP, $result['action']);
        $this->assertSame((string) $this->tierBySlug('senior')->id, $result['new_tier_id']);
        $profile->refresh();
        $this->assertSame((string) $this->tierBySlug('senior')->id, (string) $profile->tier_id);
        $this->assertSame(1, $this->logCount($profile->id));

        // 允许降级时才执行 → 降到 junior，日志 +1
        $downgraded = TierRatingService::evaluate((string) $profile->id, true);
        $this->assertSame(TierRatingService::ACTION_DOWNGRADE, $downgraded['action']);
        $this->assertSame((string) $this->tierBySlug('junior')->id, $downgraded['new_tier_id']);
        $this->assertSame(2, $this->logCount($profile->id));
        // 同秒多条日志 created_at 相同，按 snowflake id 倒序取最新一条
        $log = TechnicianTierLog::where('technician_id', $profile->id)->orderBy('id', 'desc')->first();
        $this->assertSame((string) $this->tierBySlug('senior')->id, (string) $log->old_tier_id);
        $this->assertSame((string) $this->tierBySlug('junior')->id, (string) $log->new_tier_id);
    }

    #[Test]
    public function evaluateIsIdempotent(): void
    {
        $profile = $this->makeTechnician();
        $this->makeCompletedOrders($profile->id, 100);
        $this->makeReviews($profile->id, array_fill(0, 20, 5));

        TierRatingService::evaluate((string) $profile->id);
        $logs   = $this->logCount($profile->id);
        $notifs = Notification::where('user_id', $profile->user_id)->where('type', 'tier')->count();
        $this->assertSame(1, $logs);
        $this->assertSame(1, $notifs);

        // 重复评定：等级未变，不写日志不发通知
        $again = TierRatingService::evaluate((string) $profile->id);
        $this->assertSame(TierRatingService::ACTION_NONE, $again['action']);
        $this->assertFalse($again['changed']);
        $this->assertSame($logs, $this->logCount($profile->id));
        $this->assertSame($notifs, Notification::where('user_id', $profile->user_id)
            ->where('type', 'tier')->count());
    }

    #[Test]
    public function technicianNotExistsReturnsNone(): void
    {
        $result = TierRatingService::evaluate((string) (9100000000000000 + random_int(1, 999)));
        $this->assertSame(TierRatingService::ACTION_NONE, $result['action']);
        $this->assertFalse($result['changed']);
    }
}
