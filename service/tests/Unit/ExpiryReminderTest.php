<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Coupon;
use app\model\MemberCard;
use app\model\Notification;
use app\model\User;
use app\model\UserCoupon;
use app\model\UserMemberCard;
use app\process\ExpiryReminderTimer;

/**
 * 会员卡/优惠券到期提醒闭环测试
 *
 * 覆盖：到期前 3 天内（含窗口补扫范围）的 active 会员卡 / available 优惠券
 * 生成 card_expiry / coupon_expiry 站内通知（内容含名称与到期日）、到期日
 * 过远或已过期的记录不提醒、非 active/available 状态不提醒、重复扫描幂等。
 * 基建与 PointsExpiryTest 一致（真实 DB + tearDown 清理）。
 */
class ExpiryReminderTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例会员卡定义 ID */
    private array $cardIds = [];

    /** @var string[] 用例优惠券定义 ID */
    private array $couponIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserMemberCard::where('user_id', $uid)->delete();
            UserCoupon::where('user_id', $uid)->delete();
            Notification::where('user_id', $uid)->delete();
            User::where('id', $uid)->forceDelete();
        }
        foreach ($this->cardIds as $id) {
            MemberCard::where('id', $id)->delete();
        }
        foreach ($this->couponIds as $id) {
            Coupon::where('id', $id)->delete();
        }
        $this->userIds = [];
        $this->cardIds = [];
        $this->couponIds = [];
    }

    /** 造用户（wx_openid 留空避免测试触发真实微信订阅消息） */
    private function makeUser(): string
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $userId = (string) $user->id;
        $this->userIds[] = $userId;
        return $userId;
    }

    /** 造会员卡定义 + 用户持有卡（endAt 由调用方控制，status 默认 active） */
    private function makeUserCard(string $userId, ?string $endAt, string $status = 'active'): UserMemberCard
    {
        $card = MemberCard::create([
            'id'            => MemberCard::generateId(),
            'name'          => 'VIP年卡',
            'type'          => 'vip',
            'price'         => 100.0,
            'duration_days' => 365,
            'total_times'   => 0,
            'status'        => 1,
        ]);
        $this->cardIds[] = $card->id;

        return UserMemberCard::create([
            'id'          => UserMemberCard::generateId(),
            'user_id'     => $userId,
            'card_id'     => $card->id,
            'start_at'    => date('Y-m-d H:i:s'),
            'end_at'      => $endAt,
            'total_times' => 10,
            'used_times'  => 0,
            'status'      => $status,
        ]);
    }

    /** 造优惠券定义 + 用户持有券（endAt 由调用方控制，status 默认 available） */
    private function makeUserCoupon(string $userId, ?string $endAt, string $status = 'available'): UserCoupon
    {
        $coupon = Coupon::create([
            'id'          => Coupon::generateId(),
            'name'        => '满100减20券',
            'type'        => 'fixed',
            'amount'      => 20.0,
            'min_amount'  => 100.0,
            'total_qty'   => 100,
            'remain_qty'  => 100,
            'start_at'    => date('Y-m-d H:i:s'),
            'end_at'      => $endAt,
            'status'      => 1,
        ]);
        $this->couponIds[] = $coupon->id;

        return UserCoupon::create([
            'id'         => UserCoupon::generateId(),
            'user_id'    => $userId,
            'coupon_id'  => $coupon->id,
            'status'     => $status,
        ]);
    }

    /** 反射实例化进程（构造函数注册 Workerman Timer，CLI 单测下不可用） */
    private function scan(): void
    {
        $timer = (new \ReflectionClass(ExpiryReminderTimer::class))->newInstanceWithoutConstructor();
        $timer->scanAndRemind();
    }

    // ── 提醒生成 ──

    #[Test] public function active_card_within_3_days_generates_card_expiry(): void
    {
        $userId = $this->makeUser();
        // 到期前 2 天 23 小时（< 3 天，落在扫描范围）
        $card = $this->makeUserCard($userId, Carbon::now()->addDays(2)->subHour()->format('Y-m-d H:i:s'));

        $this->scan();

        $notifications = Notification::where('user_id', $userId)
            ->where('type', 'card_expiry')
            ->get();
        $this->assertCount(1, $notifications, '到期前 3 天内应生成一条 card_expiry 通知');
        $this->assertSame((string) $card->id, (string) $notifications[0]->order_id, 'order_id 记来源卡 id');
        $this->assertSame('会员卡即将到期', (string) $notifications[0]->title);
        $content = (string) $notifications[0]->content;
        $this->assertStringContainsString('VIP年卡', $content, '内容应含卡名');
        $this->assertStringContainsString(Carbon::now()->addDays(2)->subHour()->format('Y-m-d'), $content, '内容应含到期日');
    }

    #[Test] public function available_coupon_within_3_days_generates_coupon_expiry(): void
    {
        $userId = $this->makeUser();
        // 到期前 3 天 + 1 小时（刚过 3 天阈值，落在窗口内）
        $userCoupon = $this->makeUserCoupon($userId, Carbon::now()->addDays(3)->addHour()->format('Y-m-d H:i:s'));

        $this->scan();

        $notifications = Notification::where('user_id', $userId)
            ->where('type', 'coupon_expiry')
            ->get();
        $this->assertCount(1, $notifications, '到期前 3 天内应生成一条 coupon_expiry 通知');
        $this->assertSame((string) $userCoupon->id, (string) $notifications[0]->order_id, 'order_id 记来源券 id');
        $this->assertSame('优惠券即将到期', (string) $notifications[0]->title);
        $content = (string) $notifications[0]->content;
        $this->assertStringContainsString('满100减20券', $content, '内容应含券名');
        $this->assertStringContainsString(Carbon::now()->addDays(3)->addHour()->format('Y-m-d'), $content, '内容应含到期日');
    }

    // ── 不提醒分支 ──

    #[Test] public function far_future_expiry_is_not_reminded(): void
    {
        $userId = $this->makeUser();
        $this->makeUserCard($userId, Carbon::now()->addDays(10)->format('Y-m-d H:i:s'));
        $this->makeUserCoupon($userId, Carbon::now()->addDays(10)->format('Y-m-d H:i:s'));

        $this->scan();

        $this->assertSame(0, Notification::where('user_id', $userId)->count());
    }

    #[Test] public function already_expired_records_are_not_reminded(): void
    {
        $userId = $this->makeUser();
        // end_at 已过（过期懒判定未置状态，定时器按 end_at 过滤）
        $this->makeUserCard($userId, Carbon::now()->subDay()->format('Y-m-d H:i:s'));
        $this->makeUserCoupon($userId, Carbon::now()->subDay()->format('Y-m-d H:i:s'));

        $this->scan();

        $this->assertSame(0, Notification::where('user_id', $userId)->count());
    }

    #[Test] public function non_active_card_and_used_coupon_are_not_reminded(): void
    {
        $userId = $this->makeUser();
        $this->makeUserCard($userId, Carbon::now()->addDays(2)->format('Y-m-d H:i:s'), 'inactive');
        $this->makeUserCoupon($userId, Carbon::now()->addDays(2)->format('Y-m-d H:i:s'), 'used');

        $this->scan();

        $this->assertSame(0, Notification::where('user_id', $userId)->count());
    }

    // ── 幂等 ──

    #[Test] public function scan_is_idempotent_on_repeat_runs(): void
    {
        $userId = $this->makeUser();
        $this->makeUserCard($userId, Carbon::now()->addDays(2)->format('Y-m-d H:i:s'));
        $this->makeUserCoupon($userId, Carbon::now()->addDays(2)->format('Y-m-d H:i:s'));

        $this->scan();
        $this->scan();

        $this->assertSame(1, Notification::where('user_id', $userId)
            ->where('type', 'card_expiry')->count(), '重复扫描不得重复卡提醒');
        $this->assertSame(1, Notification::where('user_id', $userId)
            ->where('type', 'coupon_expiry')->count(), '重复扫描不得重复券提醒');
    }
}
