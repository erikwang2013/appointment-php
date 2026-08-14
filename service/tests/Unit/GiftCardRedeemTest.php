<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\marketing\v1\controller\GiftCardController;
use app\model\GiftCard;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 礼品卡兑换闭环测试
 *
 * 覆盖：cash 兑换入账钱包 + 流水、余额累加正确、重复兑换防双入账、
 * 无效码 404、gift 类型仅标记、我的列表只返回本人。
 * 基建与 WalletTest 一致（真实 DB + tearDown 清理）。
 */
class GiftCardRedeemTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理钱包两表 */
    private array $userIds = [];

    /** @var string[] 用例礼品卡 ID，tearDown 统一清理 */
    private array $cardIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            WalletTxn::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
            GiftCard::where('used_by', $uid)->delete();
        }
        if ($this->cardIds) {
            GiftCard::whereIn('id', $this->cardIds)->delete();
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

    private function newUserId(): string
    {
        $uid = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $uid;
        return $uid;
    }

    private function makeGiftCard(string $type, float $amount = 0, string $giftName = ''): GiftCard
    {
        $card = GiftCard::create([
            'id'         => GiftCard::generateId(),
            'code'       => 'GC' . strtoupper(substr(md5(uniqid('', true)), 0, 10)),
            'type'       => $type,
            'amount'     => $type === 'cash' ? $amount : 0,
            'gift_name'  => $type === 'gift' ? $giftName : '',
            'status'     => 'unused',
        ]);
        $this->cardIds[] = $card->id;
        return $card;
    }

    private function redeem(string $userId, string $code): array
    {
        $request = $this->makeRequest(['code' => $code]);
        $request->user_id = $userId;
        return $this->body((new GiftCardController())->redeem($request));
    }

    private function decodeId(string $hashid): int
    {
        return (int) Container::get('hashids')->decode($hashid)[0];
    }

    // ── cash 兑换入账钱包 + 流水 ──

    #[Test] public function cash_redeem_credits_wallet_and_writes_txn(): void
    {
        $userId = $this->newUserId();
        $card = $this->makeGiftCard('cash', 100.0);

        $resp = $this->redeem($userId, $card->code);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('used', $resp['data']['status']);

        // 钱包已创建且余额 = 金额
        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(100.0, (float) $wallet->balance);

        // 礼品卡已标记使用
        $fresh = GiftCard::find($card->id);
        $this->assertSame('used', $fresh->status);
        $this->assertSame($userId, (string) $fresh->used_by);
        $this->assertNotNull($fresh->used_at);

        // 流水：gift_card 类型 + balance_after + remark 含兑换码
        $txn = WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_GIFT_CARD)->first();
        $this->assertNotNull($txn);
        $this->assertSame(100.0, (float) $txn->amount);
        $this->assertSame(100.0, (float) $txn->balance_after);
        $this->assertStringContainsString($card->code, (string) $txn->remark);
    }

    #[Test] public function cash_redeem_accumulates_existing_balance(): void
    {
        $userId = $this->newUserId();
        UserWallet::create([
            'user_id'        => $userId,
            'balance'        => 50.0,
            'total_recharge' => 0.0,
            'total_consume'  => 0.0,
        ]);
        $card = $this->makeGiftCard('cash', 100.0);

        $resp = $this->redeem($userId, $card->code);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(150.0, (float) $wallet->balance);
        $txn = WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_GIFT_CARD)->first();
        $this->assertSame(150.0, (float) $txn->balance_after);
    }

    // ── 重复兑换防双入账 ──

    #[Test] public function duplicate_redeem_rejected_without_double_credit(): void
    {
        $userId = $this->newUserId();
        $card = $this->makeGiftCard('cash', 100.0);

        $r1 = $this->redeem($userId, $card->code);
        $r2 = $this->redeem($userId, $card->code);

        $this->assertSame(0, $r1['code']);
        $this->assertSame(400, $r2['code']);
        $this->assertStringContainsString('已被使用', (string) $r2['message']);

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(100.0, (float) $wallet->balance, '重复兑换不得重复加钱');
        $this->assertSame(1, WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_GIFT_CARD)->count());
    }

    #[Test] public function redeem_second_user_cannot_steal_used_card(): void
    {
        $first = $this->newUserId();
        $second = $this->newUserId();
        $card = $this->makeGiftCard('cash', 100.0);

        $this->redeem($first, $card->code);
        $resp = $this->redeem($second, $card->code);

        $this->assertSame(400, $resp['code']);
        $this->assertNull(UserWallet::where('user_id', $second)->first());
    }

    // ── 无效码 ──

    #[Test] public function redeem_rejects_invalid_code(): void
    {
        $userId = $this->newUserId();

        $resp = $this->redeem($userId, 'NOT_EXIST_CODE');

        $this->assertSame(404, $resp['code']);
        $this->assertStringContainsString('无效', (string) $resp['message']);
        $this->assertNull(UserWallet::where('user_id', $userId)->first());
    }

    // ── gift 类型仅标记 ──

    #[Test] public function gift_redeem_only_marks_used(): void
    {
        $userId = $this->newUserId();
        $card = $this->makeGiftCard('gift', 0, '运动损伤康复礼包');

        $resp = $this->redeem($userId, $card->code);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('gift', $resp['data']['type']);
        $this->assertSame('运动损伤康复礼包', $resp['data']['gift_name']);

        $fresh = GiftCard::find($card->id);
        $this->assertSame('used', $fresh->status);
        $this->assertSame($userId, (string) $fresh->used_by);
        // 不产生钱包与流水
        $this->assertNull(UserWallet::where('user_id', $userId)->first());
        $this->assertSame(0, WalletTxn::where('user_id', $userId)->count());
    }

    // ── 我的列表只看本人 ──

    #[Test] public function my_returns_only_own_cards_sorted_by_used_at_desc(): void
    {
        $userA = $this->newUserId();
        $userB = $this->newUserId();
        $card1 = $this->makeGiftCard('cash', 100.0);
        $card2 = $this->makeGiftCard('gift', 0, '筋膜枪');
        $cardB = $this->makeGiftCard('cash', 50.0);

        $this->redeem($userA, $card1->code);
        $this->redeem($userA, $card2->code);
        $this->redeem($userB, $cardB->code);

        // 人工制造 used_at 先后，验证 desc 排序
        GiftCard::where('id', $card1->id)->update(['used_at' => '2026-08-14 09:00:00']);
        GiftCard::where('id', $card2->id)->update(['used_at' => '2026-08-14 10:00:00']);

        $request = $this->makeRequest();
        $request->user_id = $userA;
        $data = $this->body((new GiftCardController())->my($request))['data'];

        $this->assertCount(2, $data, '只返回本人礼品卡');
        $this->assertSame('2026-08-14 10:00:00', $data[0]['used_at'], '按 used_at desc');
        $this->assertSame($card2->id, (string) $this->decodeId((string) $data[0]['id']), 'id 应为 hashid 编码且可还原');
        $this->assertSame($card1->id, (string) $this->decodeId((string) $data[1]['id']));
        $this->assertSame('gift', $data[0]['type']);
        $this->assertSame('筋膜枪', $data[0]['gift_name']);
        $this->assertSame(0.0, (float) $data[0]['amount']);
        $this->assertSame('cash', $data[1]['type']);
        $this->assertSame(100.0, (float) $data[1]['amount']);
    }
}
