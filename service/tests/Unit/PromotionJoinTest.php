<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\PromotionController;
use app\model\Promotion;
use app\model\PromotionParticipant;
use app\model\User;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 拼团参与闭环测试
 *
 * 覆盖：秒杀促销下线后参与被拒 422/400、拼团满员锁定（is_locked + 状态提升）、
 * 已成团拒绝 422、重复参与 422、到期未满员惰性关闭（join/show）。
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 */
class PromotionJoinTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例活动 ID，tearDown 统一清理参与记录与活动 */
    private array $promotionIds = [];

    protected function tearDown(): void
    {
        foreach ($this->promotionIds as $pid) {
            PromotionParticipant::where('promotion_id', $pid)->delete();
            Promotion::where('id', $pid)->delete();
        }
        if ($this->userIds) {
            User::whereIn('id', $this->userIds)->forceDelete();
        }
        $this->userIds = [];
        $this->promotionIds = [];
    }

    private function makeRequest(): Request
    {
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: 0\r\n";
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    private function makePromotion(string $type, int $minPeople, int $maxPeople, string $startAt, string $endAt): Promotion
    {
        $promotion = Promotion::create([
            'id'               => Promotion::generateId(),
            'name'             => $type === Promotion::TYPE_GROUP_BUY ? '限时拼团' : '限时秒杀',
            'type'             => $type,
            'service_id'       => 0,
            'min_people'       => $minPeople,
            'max_people'       => $maxPeople,
            'discount_percent' => 50.0,
            'start_at'         => $startAt,
            'end_at'           => $endAt,
            'status'           => 1,
        ]);
        $this->promotionIds[] = $promotion->id;
        return $promotion;
    }

    private function activePromotion(string $type, int $minPeople, int $maxPeople): Promotion
    {
        return $this->makePromotion(
            $type,
            $minPeople,
            $maxPeople,
            date('Y-m-d H:i:s', time() - 3600),
            date('Y-m-d H:i:s', time() + 3600)
        );
    }

    private function join(string $userId, Promotion $promotion): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        $hashId = Container::get('hashids')->encode((int) $promotion->id);
        return $this->body((new PromotionController())->join($hashId, $request));
    }

    private function show(string $userId, Promotion $promotion): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        $hashId = Container::get('hashids')->encode((int) $promotion->id);
        return $this->body((new PromotionController())->show($hashId, $request));
    }

    // ── 秒杀促销下线：不再接受参与 ──

    #[Test] public function flash_sale_join_rejected_after_channel_removal(): void
    {
        $promo = $this->activePromotion(Promotion::TYPE_FLASH_SALE, 0, 1);
        $u1 = $this->makeUser();

        $r1 = $this->join($u1->id, $promo);
        $this->assertSame(400, $r1['code'], json_encode($r1));
        $this->assertStringContainsString('不存在或已结束', (string) $r1['message']);
        $this->assertSame(0, PromotionParticipant::where('promotion_id', $promo->id)->count());
    }

    // ── 拼团：满员锁定 ──

    #[Test] public function group_buy_locks_when_min_people_reached(): void
    {
        $promo = $this->activePromotion(Promotion::TYPE_GROUP_BUY, 2, 5);
        $u1 = $this->makeUser();
        $u2 = $this->makeUser();

        $r1 = $this->join($u1->id, $promo);
        $this->assertSame(0, $r1['code'], json_encode($r1));
        $this->assertFalse($r1['data']['is_locked'], '未满员不得锁定');

        $r2 = $this->join($u2->id, $promo);
        $this->assertSame(0, $r2['code'], json_encode($r2));
        $this->assertTrue($r2['data']['is_locked'], '满员应锁定成团');
        $this->assertSame(2, $r2['data']['current_count']);

        // 全部参与者状态提升为 joined
        $joined = PromotionParticipant::where('promotion_id', $promo->id)
            ->where('status', PromotionParticipant::STATUS_JOINED)
            ->count();
        $this->assertSame(2, $joined);
    }

    #[Test] public function group_buy_rejects_new_joiner_after_full(): void
    {
        $promo = $this->activePromotion(Promotion::TYPE_GROUP_BUY, 2, 2);
        $u1 = $this->makeUser();
        $u2 = $this->makeUser();
        $u3 = $this->makeUser();

        $this->join($u1->id, $promo);
        $this->join($u2->id, $promo);

        $r3 = $this->join($u3->id, $promo);
        $this->assertSame(422, $r3['code'], json_encode($r3));
        $this->assertStringContainsString('已成团', (string) $r3['message']);
        $this->assertSame(2, PromotionParticipant::where('promotion_id', $promo->id)->count());
    }

    // ── 幂等 ──

    #[Test] public function duplicate_join_rejected_with_422(): void
    {
        $promo = $this->activePromotion(Promotion::TYPE_GROUP_BUY, 2, 5);
        $u1 = $this->makeUser();

        $r1 = $this->join($u1->id, $promo);
        $r2 = $this->join($u1->id, $promo);

        $this->assertSame(0, $r1['code'], json_encode($r1));
        $this->assertSame(422, $r2['code'], json_encode($r2));
        $this->assertStringContainsString('已参与', (string) $r2['message']);
        $this->assertSame(1, PromotionParticipant::where('promotion_id', $promo->id)->count());
    }

    // ── 到期未满员：惰性关闭 ──

    #[Test] public function expired_group_buy_closes_and_joiner_gets_ungrouped_hint(): void
    {
        $promo = $this->makePromotion(
            Promotion::TYPE_GROUP_BUY,
            2,
            5,
            date('Y-m-d H:i:s', time() - 7200),
            date('Y-m-d H:i:s', time() - 60)
        );
        $u1 = $this->makeUser();

        $r = $this->join($u1->id, $promo);
        $this->assertSame(422, $r['code'], json_encode($r));
        $this->assertStringContainsString('未成团', (string) $r['message']);

        $fresh = Promotion::find($promo->id);
        $this->assertSame(0, (int) $fresh->status, '到期未满员应自动关闭');
        $this->assertSame(0, PromotionParticipant::where('promotion_id', $promo->id)->count());
    }

    #[Test] public function expired_group_buy_closed_on_show(): void
    {
        $promo = $this->makePromotion(
            Promotion::TYPE_GROUP_BUY,
            2,
            5,
            date('Y-m-d H:i:s', time() - 7200),
            date('Y-m-d H:i:s', time() - 60)
        );

        $r = $this->show($this->makeUser()->id, $promo);
        $this->assertSame(400, $r['code'], json_encode($r));
        $this->assertStringContainsString('不存在或已结束', (string) $r['message']);

        $fresh = Promotion::find($promo->id);
        $this->assertSame(0, (int) $fresh->status, 'show 惰性判定应关闭活动');
    }
}
