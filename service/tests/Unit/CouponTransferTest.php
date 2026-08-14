<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\marketing\v1\controller\CouponController;
use app\model\Coupon;
use app\model\User;
use app\model\UserCoupon;
use app\model\UserCouponTransfer;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 优惠券转赠闭环测试
 *
 * 覆盖：转赠成功（码生成+记录落库）、非本人券 422、已使用券 422、
 * 重复转赠同一券 422、领取成功（原券 used+新券绑定接收人）、
 * 已领取码再领 422、过期码领取→置 expired+原券恢复 available、自己领自己 422。
 *
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 */
class CouponTransferTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理用户及其券/转赠记录 */
    private array $userIds = [];

    /** @var string[] 用例券定义 ID，tearDown 统一清理 */
    private array $couponIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserCouponTransfer::where('from_user_id', $uid)->delete();
            UserCouponTransfer::where('to_user_id', $uid)->delete();
            UserCoupon::where('user_id', $uid)->delete();
        }
        if ($this->couponIds) {
            Coupon::whereIn('id', $this->couponIds)->delete();
        }
        foreach ($this->userIds as $uid) {
            User::where('id', $uid)->forceDelete();
        }
        $this->userIds = [];
        $this->couponIds = [];
    }

    private function makeRequest(array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
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

    private function makeCoupon(): Coupon
    {
        $coupon = new Coupon();
        $coupon->id = Coupon::generateId();
        $coupon->name = '转赠测试券';
        $coupon->type = 'fixed';
        $coupon->amount = 20.0;
        $coupon->min_amount = 0.0;
        $coupon->total_qty = 100;
        $coupon->remain_qty = 100;
        $coupon->start_at = date('Y-m-d H:i:s', time() - 86400);
        $coupon->end_at = date('Y-m-d H:i:s', time() + 86400);
        $coupon->status = 1;
        $coupon->save();
        $this->couponIds[] = $coupon->id;
        return $coupon;
    }

    private function makeUserCoupon(Coupon $coupon, string $userId, string $status = 'available'): UserCoupon
    {
        $uc = new UserCoupon();
        $uc->id = UserCoupon::generateId();
        $uc->user_id = $userId;
        $uc->coupon_id = $coupon->id;
        $uc->status = $status;
        $uc->received_at = date('Y-m-d H:i:s');
        $uc->save();
        return $uc;
    }

    private function hash(int $id): string
    {
        return (string) Container::get('hashids')->encode($id);
    }

    private function transfer(string $userId, string $userCouponIdHash): array
    {
        $request = $this->makeRequest(['user_coupon_id' => $userCouponIdHash]);
        $request->user_id = $userId;
        return $this->body((new CouponController())->transfer($request));
    }

    private function claim(string $userId, string $code): array
    {
        $request = $this->makeRequest(['code' => $code]);
        $request->user_id = $userId;
        return $this->body((new CouponController())->claim($request));
    }

    // ── 转赠成功：码生成 + 记录落库 ──

    #[Test] public function transfer_creates_code_and_record(): void
    {
        $userA = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userA->id);

        $resp = $this->transfer($userA->id, $this->hash((int) $uc->id));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(8, strlen((string) $resp['data']['code']));
        $this->assertNotSame('', (string) $resp['data']['expire_at']);

        $record = UserCouponTransfer::where('code', $resp['data']['code'])->first();
        $this->assertNotNull($record);
        $this->assertSame((string) $uc->id, (string) $record->user_coupon_id);
        $this->assertSame((string) $coupon->id, (string) $record->coupon_id);
        $this->assertSame((string) $userA->id, (string) $record->from_user_id);
        $this->assertSame('pending', $record->status);
        $this->assertNull($record->to_user_id);
        // expire_at ≈ now + 7 天
        $this->assertGreaterThan(time() + 6 * 86400, strtotime((string) $record->expire_at));
    }

    // ── 非本人券 422 ──

    #[Test] public function transfer_rejects_coupon_not_owned(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userB->id);

        $resp = $this->transfer($userA->id, $this->hash((int) $uc->id));

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, UserCouponTransfer::count());
    }

    // ── 已使用券 422 ──

    #[Test] public function transfer_rejects_used_coupon(): void
    {
        $userA = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userA->id, 'used');

        $resp = $this->transfer($userA->id, $this->hash((int) $uc->id));

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, UserCouponTransfer::count());
    }

    // ── 重复转赠同一券 422 ──

    #[Test] public function transfer_rejects_duplicate_transfer(): void
    {
        $userA = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userA->id);

        $r1 = $this->transfer($userA->id, $this->hash((int) $uc->id));
        $r2 = $this->transfer($userA->id, $this->hash((int) $uc->id));

        $this->assertSame(0, $r1['code']);
        $this->assertSame(422, $r2['code']);
        $this->assertSame(1, UserCouponTransfer::where('user_coupon_id', $uc->id)->count());
    }

    // ── 领取成功：原券 used + 新券绑定接收人 ──

    #[Test] public function claim_marks_original_used_and_creates_new_for_receiver(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userA->id);

        $r1 = $this->transfer($userA->id, $this->hash((int) $uc->id));
        $resp = $this->claim($userB->id, (string) $r1['data']['code']);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('available', $resp['data']['status']);

        // 原券置 used
        $fresh = UserCoupon::find($uc->id);
        $this->assertSame('used', $fresh->status);
        $this->assertNotNull($fresh->used_at);

        // 新券绑定接收人，coupon_id 不变（有效期不变）
        $newUc = UserCoupon::where('user_id', $userB->id)->where('coupon_id', $coupon->id)->first();
        $this->assertNotNull($newUc);
        $this->assertNotSame($uc->id, $newUc->id);
        $this->assertSame('available', $newUc->status);

        // 转赠记录置 claimed
        $record = UserCouponTransfer::where('code', $r1['data']['code'])->first();
        $this->assertSame('claimed', $record->status);
        $this->assertSame((string) $userB->id, (string) $record->to_user_id);
        $this->assertNotNull($record->claimed_at);
    }

    // ── 已领取码再领 422 ──

    #[Test] public function claim_claimed_code_rejected(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userA->id);

        $r1 = $this->transfer($userA->id, $this->hash((int) $uc->id));
        $c1 = $this->claim($userB->id, (string) $r1['data']['code']);
        $c2 = $this->claim($userB->id, (string) $r1['data']['code']);

        $this->assertSame(0, $c1['code']);
        $this->assertSame(422, $c2['code']);
        $this->assertStringContainsString('已被领取', (string) $c2['message']);
        // 接收人只有一张新券，不被双花
        $this->assertSame(1, UserCoupon::where('user_id', $userB->id)->count());
    }

    // ── 过期码领取 → 置 expired + 原券恢复 available ──

    #[Test] public function claim_expired_code_marks_expired_and_restores_original(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userA->id);

        $r1 = $this->transfer($userA->id, $this->hash((int) $uc->id));
        $code = (string) $r1['data']['code'];

        // 模拟：码已过期 + 原券已被标记 used（验证恢复分支真正生效）
        UserCouponTransfer::where('code', $code)->update(['expire_at' => date('Y-m-d H:i:s', time() - 60)]);
        $uc->status = 'used';
        $uc->used_at = date('Y-m-d H:i:s');
        $uc->save();

        $resp = $this->claim($userB->id, $code);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('过期', (string) $resp['message']);

        $record = UserCouponTransfer::where('code', $code)->first();
        $this->assertSame('expired', $record->status);

        // 原券恢复 available
        $fresh = UserCoupon::find($uc->id);
        $this->assertSame('available', $fresh->status);
        $this->assertNull($fresh->used_at);
        // 接收人未获得券
        $this->assertSame(0, UserCoupon::where('user_id', $userB->id)->count());
    }

    // ── 自己领自己的 422 ──

    #[Test] public function claim_own_transfer_rejected(): void
    {
        $userA = $this->makeUser();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userA->id);

        $r1 = $this->transfer($userA->id, $this->hash((int) $uc->id));
        $resp = $this->claim($userA->id, (string) $r1['data']['code']);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('自己', (string) $resp['message']);
        $record = UserCouponTransfer::where('code', $r1['data']['code'])->first();
        $this->assertSame('pending', $record->status);
    }

    // ── 转赠记录列表：发出 + 收到 ──

    #[Test] public function transfers_lists_sent_and_received(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $coupon1 = $this->makeCoupon();
        $coupon2 = $this->makeCoupon();
        $uc1 = $this->makeUserCoupon($coupon1, $userA->id);
        $uc2 = $this->makeUserCoupon($coupon2, $userA->id);

        // A 转两张给 B：一张领取成功，一张待领取
        $t1 = $this->transfer($userA->id, $this->hash((int) $uc1->id));
        $t2 = $this->transfer($userA->id, $this->hash((int) $uc2->id));
        $this->claim($userB->id, (string) $t1['data']['code']);
        // 固定 created_at 保证 desc 排序确定：pending 更新在后
        UserCouponTransfer::where('code', $t1['data']['code'])->update(['created_at' => '2026-08-13 08:00:00']);
        UserCouponTransfer::where('code', $t2['data']['code'])->update(['created_at' => '2026-08-13 09:00:00']);

        // B 侧：收到 1 条 claimed
        $reqB = $this->makeRequest();
        $reqB->user_id = $userB->id;
        $respB = $this->body((new CouponController())->transfers($reqB));
        $this->assertSame(0, $respB['code']);
        $this->assertSame(1, (int) $respB['meta']['total']);

        // A 侧：发出 2 条（pending + claimed）
        $reqA = $this->makeRequest();
        $reqA->user_id = $userA->id;
        $respA = $this->body((new CouponController())->transfers($reqA));
        $this->assertSame(0, $respA['code']);
        $this->assertSame(2, (int) $respA['meta']['total']);
        $this->assertSame('pending', $respA['data'][0]['status']);
        $this->assertSame('claimed', $respA['data'][1]['status']);
    }
}
