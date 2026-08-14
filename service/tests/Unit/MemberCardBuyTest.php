<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\marketing\v1\controller\MemberCardController;
use app\model\MemberCard;
use app\model\User;
use app\model\UserMemberCard;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 会员卡购买闭环测试
 *
 * 覆盖：列表只含启用非次卡、余额支付成功（扣减+流水+member_level 联动）、
 * 余额不足拒绝、重复购买拒绝、下架/次卡/无效ID 拒绝、我的列表只返回本人。
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 */
class MemberCardBuyTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例会员卡定义 ID，tearDown 统一清理 */
    private array $cardIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserMemberCard::where('user_id', $uid)->delete();
            WalletTxn::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
            User::where('id', $uid)->delete();
        }
        if ($this->cardIds) {
            MemberCard::whereIn('id', $this->cardIds)->delete();
        }
        $this->userIds = [];
        $this->cardIds = [];
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

    private function newUser(): User
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

    private function seedWallet(string $userId, float $balance): UserWallet
    {
        return UserWallet::create([
            'user_id'        => $userId,
            'balance'        => $balance,
            'total_recharge' => 0.0,
            'total_consume'  => 0.0,
        ]);
    }

    private function makeCard(string $type = 'vip', float $price = 100.0, int $status = 1): MemberCard
    {
        $card = MemberCard::create([
            'id'            => MemberCard::generateId(),
            'name'          => 'VIP年卡',
            'type'          => $type,
            'price'         => $price,
            'duration_days' => $type === 'vip' ? 365 : 30,
            'total_times'   => 0,
            'status'        => $status,
        ]);
        $this->cardIds[] = $card->id;
        return $card;
    }

    private function hashid(string $rawId): string
    {
        return (string) Container::get('hashids')->encode((int) $rawId);
    }

    private function decodeId(string $hashid): string
    {
        return (string) Container::get('hashids')->decode($hashid)[0];
    }

    private function buy(string $userId, string $cardIdHashid): array
    {
        $request = $this->makeRequest(['card_id' => $cardIdHashid]);
        $request->user_id = $userId;
        return $this->body((new MemberCardController())->buy($request));
    }

    // ── 列表 ──

    #[Test] public function index_returns_only_enabled_non_times_cards(): void
    {
        $vip = $this->makeCard('vip', 100.0);
        $month = $this->makeCard('month', 30.0);
        $times = $this->makeCard('times', 50.0);
        $disabled = $this->makeCard('vip', 200.0, 0);

        $request = $this->makeRequest();
        $data = $this->body((new MemberCardController())->index($request))['data'];

        $ids = array_map(fn($c) => $this->decodeId($c['id']), $data);
        $this->assertContains($vip->id, $ids);
        $this->assertContains($month->id, $ids);
        $this->assertNotContains($times->id, $ids, '次卡不进会员卡列表');
        $this->assertNotContains($disabled->id, $ids, '下架卡不进列表');
    }

    // ── 余额支付成功 ──

    #[Test] public function buy_success_deducts_balance_writes_txn_and_upgrades_level(): void
    {
        $user = $this->newUser();
        $this->seedWallet($user->id, 200.0);
        $card = $this->makeCard('vip', 100.0);

        $resp = $this->buy($user->id, $this->hashid($card->id));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('购买成功', $resp['message']);

        // 余额扣减 + total_consume 累加
        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame(100.0, (float) $wallet->balance);
        $this->assertSame(100.0, (float) $wallet->total_consume);

        // consume 流水 + remark 含卡名
        $txn = WalletTxn::where('user_id', $user->id)->where('type', WalletTxn::TYPE_CONSUME)->first();
        $this->assertNotNull($txn);
        $this->assertSame(100.0, (float) $txn->amount);
        $this->assertSame(100.0, (float) $txn->balance_after);
        $this->assertStringContainsString($card->name, (string) $txn->remark);

        // 用户会员卡创建（含有效期）
        $uc = UserMemberCard::where('user_id', $user->id)->first();
        $this->assertNotNull($uc);
        $this->assertSame($card->id, (string) $uc->card_id);
        $this->assertSame('active', $uc->status);
        $this->assertNotNull($uc->end_at, 'vip 卡应计算到期时间');

        // member_level 联动
        $fresh = User::find($user->id);
        $this->assertSame('vip', $fresh->member_level);
    }

    // ── 余额不足 ──

    #[Test] public function buy_rejected_when_balance_insufficient(): void
    {
        $user = $this->newUser();
        $this->seedWallet($user->id, 50.0);
        $card = $this->makeCard('vip', 100.0);

        $resp = $this->buy($user->id, $this->hashid($card->id));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('余额不足', (string) $resp['message']);
        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame(50.0, (float) $wallet->balance, '余额不足不得扣款');
        $this->assertNull(UserMemberCard::where('user_id', $user->id)->first());
        $this->assertSame(0, WalletTxn::where('user_id', $user->id)->count());
    }

    // ── 重复购买 ──

    #[Test] public function buy_rejects_duplicate_active_card(): void
    {
        $user = $this->newUser();
        $this->seedWallet($user->id, 500.0);
        $card = $this->makeCard('vip', 100.0);

        $r1 = $this->buy($user->id, $this->hashid($card->id));
        $r2 = $this->buy($user->id, $this->hashid($card->id));

        $this->assertSame(0, $r1['code']);
        $this->assertSame(422, $r2['code']);
        $this->assertStringContainsString('已拥有', (string) $r2['message']);

        // 只扣一次
        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame(400.0, (float) $wallet->balance);
        $this->assertSame(1, UserMemberCard::where('user_id', $user->id)->count());
    }

    // ── 卡状态校验 ──

    #[Test] public function buy_rejects_disabled_card(): void
    {
        $user = $this->newUser();
        $this->seedWallet($user->id, 500.0);
        $card = $this->makeCard('vip', 100.0, 0);

        $resp = $this->buy($user->id, $this->hashid($card->id));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('下架', (string) $resp['message']);
        $this->assertNull(UserMemberCard::where('user_id', $user->id)->first());
    }

    #[Test] public function buy_rejects_times_type_card(): void
    {
        $user = $this->newUser();
        $this->seedWallet($user->id, 500.0);
        $card = $this->makeCard('times', 50.0);

        $resp = $this->buy($user->id, $this->hashid($card->id));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('次卡', (string) $resp['message']);
        $this->assertNull(UserMemberCard::where('user_id', $user->id)->first());
    }

    #[Test] public function buy_rejects_invalid_card_id(): void
    {
        $user = $this->newUser();

        $resp = $this->buy($user->id, 'invalid_hashid');

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('ID无效', (string) $resp['message']);
        $this->assertNull(UserMemberCard::where('user_id', $user->id)->first());
    }

    // ── 我的列表 ──

    #[Test] public function my_returns_only_own_cards(): void
    {
        $userA = $this->newUser();
        $userB = $this->newUser();
        $this->seedWallet($userA->id, 500.0);
        $this->seedWallet($userB->id, 500.0);
        $cardA = $this->makeCard('vip', 100.0);
        $cardB = $this->makeCard('month', 30.0);

        $this->buy($userA->id, $this->hashid($cardA->id));
        $this->buy($userA->id, $this->hashid($cardB->id));
        $this->buy($userB->id, $this->hashid($cardA->id));

        $request = $this->makeRequest();
        $request->user_id = $userA->id;
        $data = $this->body((new MemberCardController())->my($request))['data'];

        $this->assertCount(2, $data, '只返回本人会员卡');
        // 同秒购买 created_at 并列，不依赖返回顺序：按类型查找 vip 卡
        $vipItem = null;
        foreach ($data as $item) {
            if ($item['type'] === 'vip') {
                $vipItem = $item;
                break;
            }
        }
        $this->assertNotNull($vipItem, '应包含 vip 卡');
        $this->assertSame($cardA->id, (string) $this->decodeId((string) $vipItem['card_id']), 'id 应为 hashid 编码且可还原');
        $this->assertSame(100.0, (float) $vipItem['price']);
        $this->assertSame('active', $vipItem['status']);
    }
}
