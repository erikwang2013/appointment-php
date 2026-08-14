<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\marketing\v1\controller\PointsExchangeController;
use app\model\Coupon;
use app\model\GiftCard;
use app\model\PointsExchangeGoods;
use app\model\User;
use app\model\UserCoupon;
use app\model\UserPoints;
use app\model\UserPointsExchange;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 积分兑换商城闭环测试
 *
 * 覆盖：wallet 兑换入账钱包 + 流水 + 扣积分、coupon 发券、gift_card 返回卡密、
 * 积分不足 422、下架 422、库存 0 拒绝、重复兑换幂等（同用户同商品限一次）、
 * 兑换记录落库、商品列表含已兑数与剩余库存。
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 */
class PointsExchangeTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例商品 ID，tearDown 统一清理 */
    private array $goodsIds = [];

    /** @var string[] 用例优惠券 ID，tearDown 统一清理 */
    private array $couponIds = [];

    /** @var string[] 用例生成的礼品卡 ID，tearDown 统一清理 */
    private array $cardIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserPoints::where('user_id', $uid)->delete();
            UserPointsExchange::where('user_id', $uid)->delete();
            UserCoupon::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
            WalletTxn::where('user_id', $uid)->delete();
            User::where('id', $uid)->delete();
        }
        if ($this->goodsIds) {
            PointsExchangeGoods::whereIn('id', $this->goodsIds)->delete();
        }
        if ($this->couponIds) {
            Coupon::whereIn('id', $this->couponIds)->delete();
        }
        if ($this->cardIds) {
            GiftCard::whereIn('id', $this->cardIds)->delete();
        }
        $this->userIds = [];
        $this->goodsIds = [];
        $this->couponIds = [];
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
        User::create([
            'id'         => $uid,
            'phone'      => '199' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'wx_openid'  => '',
            'user_type'  => 'user',
            'status'     => 1,
        ]);
        $this->userIds[] = $uid;
        return $uid;
    }

    private function creditPoints(string $userId, int $points): void
    {
        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $userId,
            'type'        => 'earn',
            'points'      => $points,
            'balance'     => $points,
            'source'      => 'test_seed',
            'description' => '测试预置积分',
        ]);
    }

    private function makeGoods(array $attrs = []): PointsExchangeGoods
    {
        $goods = PointsExchangeGoods::create(array_merge([
            'id'          => PointsExchangeGoods::generateId(),
            'name'        => '测试兑换商品',
            'type'        => 'wallet',
            'points_cost' => 100,
            'value'       => 10.00,
            'stock'       => 10,
            'status'      => 1,
            'sort'        => 0,
        ], $attrs));
        $this->goodsIds[] = $goods->id;
        return $goods;
    }

    private function makeCoupon(): Coupon
    {
        $coupon = Coupon::create([
            'id'         => Coupon::generateId(),
            'name'       => '测试优惠券',
            'type'       => 'fixed',
            'amount'     => 20.00,
            'min_amount' => 0.00,
            'total_qty'  => 100,
            'remain_qty' => 100,
            'start_at'   => date('Y-m-d H:i:s'),
            'end_at'     => date('Y-m-d H:i:s', strtotime('+30 days')),
            'status'     => 1,
        ]);
        $this->couponIds[] = $coupon->id;
        return $coupon;
    }

    private function encodeId(int $id): string
    {
        return (string) Container::get('hashids')->encode($id);
    }

    private function exchange(string $userId, int $goodsId): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return $this->body((new PointsExchangeController())->exchange($request, $this->encodeId($goodsId)));
    }

    private function index(): array
    {
        $request = $this->makeRequest();
        return $this->body((new PointsExchangeController())->index($request));
    }

    private function decodeId(string $hashid): int
    {
        return (int) Container::get('hashids')->decode($hashid)[0];
    }

    // ── wallet 兑换：扣积分 + 入账 + 流水 ──

    #[Test] public function wallet_exchange_deducts_points_and_credits_wallet(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 1000);
        $goods = $this->makeGoods(['type' => 'wallet', 'points_cost' => 100, 'value' => 10.00, 'stock' => 10]);

        $resp = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(100, (int) $resp['data']['points_cost']);

        // 积分扣减流水：type=consume source=exchange，负值，balance 快照 = 1000-100
        $pointRow = UserPoints::where('user_id', $userId)->where('source', 'exchange')->first();
        $this->assertNotNull($pointRow);
        $this->assertSame('consume', $pointRow->type);
        $this->assertSame(-100, (int) $pointRow->points);
        $this->assertSame(900, (int) $pointRow->balance);

        // 钱包入账 + 流水
        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(10.0, (float) $wallet->balance);
        $txn = WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_POINTS_EXCHANGE)->first();
        $this->assertNotNull($txn);
        $this->assertSame(10.0, (float) $txn->amount);
        $this->assertSame(10.0, (float) $txn->balance_after);

        // 库存扣减 + 兑换记录落库
        $this->assertSame(9, (int) PointsExchangeGoods::find($goods->id)->stock);
        $record = UserPointsExchange::where('user_id', $userId)->where('goods_id', $goods->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('测试兑换商品', $record->goods_name);
        $this->assertSame(100, (int) $record->points_cost);
        $this->assertStringContainsString('"type":"wallet"', (string) $record->result);
    }

    #[Test] public function wallet_exchange_accumulates_existing_balance(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 500);
        UserWallet::create([
            'user_id'        => $userId,
            'balance'        => 50.0,
            'total_recharge' => 0.0,
            'total_consume'  => 0.0,
        ]);
        $goods = $this->makeGoods(['type' => 'wallet', 'points_cost' => 100, 'value' => 10.00]);

        $resp = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(60.0, (float) $wallet->balance);
    }

    // ── coupon 兑换发券 ──

    #[Test] public function coupon_exchange_grants_user_coupon(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 500);
        $coupon = $this->makeCoupon();
        // 注意：coupon 类型 value 存优惠券雪崩 ID，必须整型/字符串传递，float 会丢精度
        $goods = $this->makeGoods(['type' => 'coupon', 'points_cost' => 100, 'value' => (string) $coupon->id]);

        $resp = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('coupon', $resp['data']['result']['type']);

        $userCoupon = UserCoupon::where('user_id', $userId)->where('coupon_id', $coupon->id)->first();
        $this->assertNotNull($userCoupon);
        $this->assertSame('available', $userCoupon->status);
        $this->assertNotNull($userCoupon->received_at);
    }

    // ── gift_card 兑换生成卡密 ──

    #[Test] public function gift_card_exchange_returns_card_code(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 500);
        $goods = $this->makeGoods(['type' => 'gift_card', 'points_cost' => 100, 'value' => 50.00]);

        $resp = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $result = $resp['data']['result'];
        $this->assertSame('gift_card', $result['type']);
        $this->assertNotEmpty($result['code']);
        $this->assertSame(50.0, (float) $result['amount']);

        $card = GiftCard::where('code', $result['code'])->first();
        $this->assertNotNull($card);
        $this->assertSame('unused', $card->status);
        $this->assertSame(50.0, (float) $card->amount);
        $this->cardIds[] = $card->id;
    }

    // ── 积分不足 ──

    #[Test] public function exchange_rejects_insufficient_points(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 50);
        $goods = $this->makeGoods(['type' => 'wallet', 'points_cost' => 100, 'value' => 10.00]);

        $resp = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('积分不足', (string) $resp['message']);
        $this->assertNull(UserWallet::where('user_id', $userId)->first());
        $this->assertSame(10, (int) PointsExchangeGoods::find($goods->id)->stock, '失败不得扣库存');
    }

    // ── 下架商品 ──

    #[Test] public function exchange_rejects_offline_goods(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 500);
        $goods = $this->makeGoods(['status' => 0]);

        $resp = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('已下架', (string) $resp['message']);
        $this->assertSame(0, UserPoints::where('user_id', $userId)->where('source', 'exchange')->count());
    }

    // ── 库存为 0 拒兑（防超兑） ──

    #[Test] public function exchange_rejects_zero_stock(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 500);
        $goods = $this->makeGoods(['stock' => 0]);

        $resp = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('已兑完', (string) $resp['message']);
        $this->assertSame(0, UserPoints::where('user_id', $userId)->where('source', 'exchange')->count());
    }

    // ── 重复兑换幂等：同用户同商品限一次 ──

    #[Test] public function duplicate_exchange_rejected_without_double_credit(): void
    {
        $userId = $this->newUserId();
        $this->creditPoints($userId, 1000);
        $goods = $this->makeGoods(['type' => 'wallet', 'points_cost' => 100, 'value' => 10.00]);

        $r1 = $this->exchange($userId, (int) $goods->id);
        $r2 = $this->exchange($userId, (int) $goods->id);

        $this->assertSame(0, $r1['code']);
        $this->assertSame(400, $r2['code']);
        $this->assertStringContainsString('已兑换过', (string) $r2['message']);

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(10.0, (float) $wallet->balance, '重复兑换不得重复入账');
        $this->assertSame(1, UserPoints::where('user_id', $userId)->where('source', 'exchange')->count());
        $this->assertSame(1, WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_POINTS_EXCHANGE)->count());
        $this->assertSame(9, (int) PointsExchangeGoods::find($goods->id)->stock, '重复兑换不得重复扣库存');
    }

    // ── 列表：上架商品 + 已兑数 + 剩余库存 ──

    #[Test] public function index_lists_onshelf_goods_with_exchanged_count(): void
    {
        $userA = $this->newUserId();
        $userB = $this->newUserId();
        $this->creditPoints($userA, 1000);
        $this->creditPoints($userB, 1000);
        $onShelf  = $this->makeGoods(['name' => '上架商品', 'stock' => 5]);
        $offShelf = $this->makeGoods(['name' => '下架商品', 'status' => 0]);

        $this->exchange($userA, (int) $onShelf->id);
        $this->exchange($userB, (int) $onShelf->id);

        $resp = $this->index();
        $this->assertSame(0, $resp['code']);

        $items = $resp['data'];
        $this->assertCount(1, $items, '只返回上架商品');
        $this->assertSame((string) $onShelf->id, (string) $this->decodeId((string) $items[0]['id']));
        $this->assertSame('上架商品', $items[0]['name']);
        $this->assertSame(2, (int) $items[0]['exchanged_count']);
        $this->assertSame(3, (int) $items[0]['stock'], '剩余库存 = 原库存 - 已兑数');
    }
}
