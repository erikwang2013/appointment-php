<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\user\v1\controller\ReferralController;
use app\model\Order;
use app\model\User;
use app\model\UserPoints;
use app\model\UserReferral;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 推荐/邀请控制器测试
 *
 * 覆盖：无推荐码自动生成 + 统计（推荐人数/首单人数/推荐积分）、
 * 用户不存在 404、推广二维码 invite_url、被推荐人列表字段映射、
 * 返佣明细只含已发放记录并带订单号。
 */
class ReferralControllerTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例订单号 */
    private array $orderNos = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserReferral::where('referrer_id', $uid)->orWhere('referred_user_id', $uid)->delete();
            UserPoints::where('user_id', $uid)->delete();
            Order::where('user_id', $uid)->delete();
            User::where('id', $uid)->forceDelete();
        }
        if ($this->orderNos) {
            Order::whereIn('order_no', $this->orderNos)->delete();
        }
        $this->userIds = [];
        $this->orderNos = [];
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

    private function newUser(array $attrs = []): User
    {
        $u = User::create(array_merge([
            'phone'     => '195' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'wx_openid' => '',
            'nickname'  => '昵称' . random_int(1, 9999),
            'user_type' => 'user',
            'status'    => 1,
        ], $attrs));
        $this->userIds[] = $u->id;
        return $u;
    }

    private function makeReferral(User $referrer, User $referred, array $attrs = []): UserReferral
    {
        return UserReferral::create(array_merge([
            'id'              => UserReferral::generateId(),
            'referrer_id'     => $referrer->id,
            'referred_user_id' => $referred->id,
            'reward_type'     => 'points',
            'reward_amount'   => '20.00',
            'registered_at'   => date('Y-m-d H:i:s'),
            'rewarded_at'     => null,
            'first_order_at'  => null,
        ], $attrs));
    }

    private function withUser(string $userId): Request
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return $request;
    }

    #[Test] public function index_auto_generates_code_and_returns_stats(): void
    {
        $referrer = $this->newUser();
        $a = $this->newUser();
        $b = $this->newUser();
        $this->makeReferral($referrer, $a, ['first_order_at' => '2026-08-01 10:00:00', 'rewarded_at' => '2026-08-01 10:00:01']);
        $this->makeReferral($referrer, $b);
        // 推荐积分：source=referral type=earn 合计
        UserPoints::create([
            'id' => UserPoints::generateId(), 'user_id' => $referrer->id,
            'type' => 'earn', 'points' => 30, 'balance' => 30,
            'source' => 'referral', 'description' => '推荐奖励',
        ]);

        $resp = $this->body((new ReferralController())->index($this->withUser($referrer->id)));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNotEmpty($resp['data']['referral_code'], '无推荐码自动生成');
        $this->assertSame(2, $resp['data']['referral_count']);
        $this->assertSame(1, $resp['data']['first_order_count']);
        $this->assertSame(30, $resp['data']['earned_points']);
    }

    #[Test] public function index_returns_404_for_missing_user(): void
    {
        $request = $this->withUser('9999999999999999');

        $resp = $this->body((new ReferralController())->index($request));

        $this->assertSame(404, $resp['code']);
        $this->assertStringContainsString('用户不存在', (string) $resp['message']);
    }

    #[Test] public function qrcode_returns_code_with_invite_url(): void
    {
        $user = $this->newUser();

        $resp = $this->body((new ReferralController())->qrcode($this->withUser($user->id)));

        $this->assertSame(0, $resp['code']);
        $this->assertNotEmpty($resp['data']['referral_code']);
        $expectedBase = getenv('APP_URL') ?: 'https://appointment.example.com';
        $this->assertSame($expectedBase . '/invite?code=' . $resp['data']['referral_code'], $resp['data']['invite_url']);
    }

    #[Test] public function referred_users_lists_referrals_with_nickname(): void
    {
        $referrer = $this->newUser();
        $referred = $this->newUser(['nickname' => '小明']);
        $this->makeReferral($referrer, $referred, ['first_order_at' => '2026-08-01 10:00:00']);

        $resp = $this->body((new ReferralController())->referredUsers($this->withUser($referrer->id)));

        $this->assertSame(0, $resp['code']);
        $this->assertCount(1, $resp['data']);
        $item = $resp['data'][0];
        $this->assertSame('小明', $item['nickname']);
        $this->assertTrue($item['has_first_order']);
        $this->assertNotEmpty($item['first_order_at']);
    }

    #[Test] public function earnings_lists_only_rewarded_with_order_no(): void
    {
        $referrer = $this->newUser();
        $referred = $this->newUser(['nickname' => '小红']);
        $this->makeReferral($referrer, $referred, [
            'rewarded_at' => '2026-08-01 10:00:00', 'first_order_at' => '2026-08-01 09:00:00',
        ]);
        $this->makeReferral($referrer, $this->newUser()); // 未发放，不应出现

        $order = Order::create([
            'id' => Order::generateId(),
            'order_no' => 'REF_EARN_' . uniqid(),
            'user_id' => $referred->id,
            'status' => Order::STATUS_COMPLETED,
            'service_end_at' => '2026-08-01 08:00:00',
        ]);
        $this->orderNos[] = $order->order_no;

        $resp = $this->body((new ReferralController())->earnings($this->withUser($referrer->id)));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(1, $resp['meta']['total'], '只返回已发放记录');
        $item = $resp['data'][0];
        $this->assertSame('小红', $item['nickname']);
        $this->assertSame($order->order_no, $item['order_no']);
        $this->assertSame(20.0, (float) $item['reward_amount']);
    }
}
