<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\PaymentNotifyController;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\UserWallet;
use app\model\WalletRecharge;
use app\model\WalletTxn;
use app\order\v1\controller\OrderController;
use app\wallet\v1\controller\WalletController;
use support\Container;
use support\Db;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;
use Carbon\Carbon;

/**
 * 储值支付（余额体系）测试
 *
 * 覆盖：余额查询、充值创建与金额校验、充值支付归属校验、充值回调入账与幂等、
 * 余额支付成功/余额不足/已支付拒绝、余额退款回充、流水分页筛选、退款补偿回充。
 * 基建与 OrderRefundFlowTest 一致（真实 DB + tearDown 清理）。
 * 涉及 order_lock（Redis）的用例在 Redis 不可用时 skip（与 PaymentNotifyControllerTest 同策略）。
 */
class WalletTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理钱包三表 */
    private array $userIds = [];

    /** @var int[] 用例订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例 Redis key，tearDown 统一清理 */
    private array $redisKeys = [];

    /** @var string 原 wechat_pay api_key 配置值（notify 用例临时覆盖后恢复） */
    private string $savedApiKey = '';

    protected function setUp(): void
    {
        $this->savedApiKey = (string) Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->where('key', 'api_key')
            ->value('value');
    }

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            WalletTxn::where('user_id', $uid)->delete();
            WalletRecharge::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
        }
        foreach ($this->orderIds as $id) {
            Db::table('appointment_notification')->where('order_id', $id)->delete();
            OrderRefund::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }
        // 恢复 wechat_pay api_key 配置（若被用例覆盖）
        Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->where('key', 'api_key')
            ->update(['value' => $this->savedApiKey]);

        $this->userIds = [];
        $this->orderIds = [];
        $this->redisKeys = [];
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

    private function makeWallet(string $userId, float $balance): UserWallet
    {
        return UserWallet::create([
            'user_id'         => $userId,
            'balance'         => $balance,
            'total_recharge'  => 0.00,
            'total_consume'   => 0.00,
        ]);
    }

    private function makeRecharge(string $userId, float $amount, string $status = WalletRecharge::STATUS_PENDING): WalletRecharge
    {
        return WalletRecharge::create([
            'user_id'    => $userId,
            'order_no'   => WalletRecharge::generateOrderNo(),
            'amount'     => $amount,
            'status'     => $status,
            'pay_channel' => 'wechat',
        ]);
    }

    private function makePendingOrder(string $userId, float $paidAmount = 100.0, string $payType = 'wechat'): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_WAL_' . uniqid(),
            'user_id'         => $userId,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => Order::STATUS_PENDING,
            'service_time'    => Carbon::now()->addHours(12),
        ]);
        $this->orderIds[] = $order->id;

        OrderPayment::create([
            'order_id'   => $order->id,
            'payment_no' => 'PAYWAL_' . uniqid(),
            'pay_type'   => $payType,
            'amount'     => $paidAmount,
            'status'     => OrderPayment::STATUS_PENDING,
        ]);

        return $order;
    }

    private static function invokePrivate(OrderController $ctl, string $method, array $args = []): mixed
    {
        $m = new \ReflectionMethod(OrderController::class, $method);
        $m->setAccessible(true);
        return $m->invokeArgs($ctl, $args);
    }

    private function redisAvailable(): bool
    {
        try {
            $probe = 'test:probe:' . uniqid();
            $this->redisKeys[] = $probe;
            Redis::setex($probe, 5, '1');
            return Redis::get($probe) === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    private function trackRedisKey(string $key): void
    {
        if (!in_array($key, $this->redisKeys, true)) {
            $this->redisKeys[] = $key;
        }
    }

    // ── 微信回调工具 ──

    /** 独立实现微信 MD5 签名（与生产同算法） */
    private static function wechatSign(array $data, string $apiKey): string
    {
        unset($data['sign']);
        ksort($data);
        $pairs = [];
        foreach ($data as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $pairs[] = $k . '=' . $v;
        }
        return strtoupper(md5(implode('&', $pairs) . '&key=' . $apiKey));
    }

    /** 构造携带原生 XML body 的 POST 请求 */
    private function makeNotifyRequest(string $xml): Request
    {
        $head = "POST /payment/wechat-notify HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "Content-Type: text/xml\r\n"
            . "Content-Length: " . strlen($xml) . "\r\n";
        return new Request($head . "\r\n" . $xml);
    }

    /** 临时写入 wechat_pay api_key 测试密钥（tearDown 恢复），返回 key */
    private function enableWechatSign(): string
    {
        $key = 'TEST_API_KEY_' . uniqid();
        Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->where('key', 'api_key')
            ->update(['value' => $key]);
        return $key;
    }

    /** 构造带签名的充值回调 XML */
    private function buildRechargeNotifyXml(WalletRecharge $recharge, int $totalFeeCents, string $apiKey): string
    {
        $data = [
            'appid'          => 'wx_test',
            'mch_id'         => '1900000001',
            'out_trade_no'   => $recharge->order_no,
            'transaction_id' => 'TX_' . uniqid(),
            'total_fee'      => $totalFeeCents,
            'result_code'    => 'SUCCESS',
            'return_code'    => 'SUCCESS',
        ];
        $data['sign'] = self::wechatSign($data, $apiKey);

        $xml = '<xml>';
        foreach ($data as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    private function xmlBody(Response $response): array
    {
        $parsed = simplexml_load_string($response->rawBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
        return $parsed === false ? [] : (array) $parsed;
    }

    // ── 余额查询 ──

    #[Test] public function index_returns_zero_balance_and_lazily_creates_wallet(): void
    {
        $userId = $this->newUserId();
        $request = $this->makeRequest();
        $request->user_id = $userId;

        $data = $this->body((new WalletController())->index($request))['data'];

        $this->assertSame(0.0, (float) $data['balance']);
        $this->assertSame(0.0, (float) $data['total_recharge']);
        $this->assertSame(0.0, (float) $data['total_consume']);
        // 钱包行已惰性创建
        $this->assertNotNull(UserWallet::where('user_id', $userId)->first());
    }

    // ── 充值创建与校验 ──

    #[Test] public function recharge_creates_pending_order_with_r_prefix(): void
    {
        $userId = $this->newUserId();
        $request = $this->makeRequest(['amount' => 100]);
        $request->user_id = $userId;

        $data = $this->body((new WalletController())->recharge($request))['data'];

        $this->assertStringStartsWith('R', (string) $data['order_no']);
        $this->assertSame(100.0, (float) $data['amount']);

        $recharge = WalletRecharge::find(Container::get('hashids')->decode((string) $data['recharge_id'])[0]);
        $this->assertSame(WalletRecharge::STATUS_PENDING, $recharge->status);
        $this->assertSame($userId, (string) $recharge->user_id);
        $this->assertSame(100.0, (float) $recharge->amount);
    }

    #[Test] public function recharge_rejects_invalid_amounts(): void
    {
        $userId = $this->newUserId();
        $ctl = new WalletController();

        foreach ([0, -1, 0.001, 50000.01, 100000] as $invalid) {
            $request = $this->makeRequest(['amount' => $invalid]);
            $request->user_id = $userId;
            $resp = $this->body($ctl->recharge($request));
            $this->assertSame(422, $resp['code'], "amount={$invalid} 应被拒绝");
            $this->assertStringContainsString('充值金额', (string) $resp['message']);
        }
        $this->assertSame(0, WalletRecharge::where('user_id', $userId)->count());

        // 边界：0.01 与 50000 合法
        $ok1 = $this->body($ctl->recharge($this->authRequest($userId, ['amount' => 0.01])));
        $this->assertSame(0, $ok1['code']);
        $ok2 = $this->body($ctl->recharge($this->authRequest($userId, ['amount' => 50000])));
        $this->assertSame(0, $ok2['code']);
        $this->assertSame(2, WalletRecharge::where('user_id', $userId)->count());
    }

    private function authRequest(string $userId, array $post = []): Request
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $request;
    }

    // ── 充值支付归属与状态校验 ──

    #[Test] public function recharge_pay_rejects_other_users_recharge(): void
    {
        $owner = $this->newUserId();
        $other = $this->newUserId();
        $recharge = $this->makeRecharge($owner, 50.0);

        $id = Container::get('hashids')->encode((int) $recharge->id);
        $resp = $this->body((new WalletController())->pay($this->authRequest($other), (string) $id));

        $this->assertSame(404, $resp['code']);
    }

    #[Test] public function recharge_pay_rejects_non_pending_status(): void
    {
        $userId = $this->newUserId();
        $recharge = $this->makeRecharge($userId, 50.0, WalletRecharge::STATUS_PAID);

        $id = Container::get('hashids')->encode((int) $recharge->id);
        $resp = $this->body((new WalletController())->pay($this->authRequest($userId), (string) $id));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('不可支付', (string) $resp['message']);
    }

    // ── 充值回调入账与幂等 ──

    #[Test] public function recharge_notify_credits_wallet_and_writes_txn(): void
    {
        $userId = $this->newUserId();
        $recharge = $this->makeRecharge($userId, 100.0);
        $apiKey = $this->enableWechatSign();

        $resp = (new PaymentNotifyController())->wechatNotify(
            $this->makeNotifyRequest($this->buildRechargeNotifyXml($recharge, 10000, $apiKey))
        );

        $this->assertSame('SUCCESS', $this->xmlBody($resp)['return_code']);

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(100.0, (float) $wallet->balance);
        $this->assertSame(100.0, (float) $wallet->total_recharge);

        $fresh = WalletRecharge::find($recharge->id);
        $this->assertSame(WalletRecharge::STATUS_PAID, $fresh->status);
        $this->assertNotNull($fresh->paid_at);

        $txn = WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_RECHARGE)->first();
        $this->assertNotNull($txn);
        $this->assertSame(100.0, (float) $txn->amount);
        $this->assertSame(100.0, (float) $txn->balance_after);
        $this->assertSame($recharge->id, (string) $txn->recharge_id);
    }

    #[Test] public function recharge_notify_is_idempotent(): void
    {
        $userId = $this->newUserId();
        $recharge = $this->makeRecharge($userId, 100.0);
        $apiKey = $this->enableWechatSign();
        $ctl = new PaymentNotifyController();
        $xml = $this->buildRechargeNotifyXml($recharge, 10000, $apiKey);

        // 连续两次回调（微信超时重试场景）
        $r1 = $ctl->wechatNotify($this->makeNotifyRequest($xml));
        $r2 = $ctl->wechatNotify($this->makeNotifyRequest($xml));

        $this->assertSame('SUCCESS', $this->xmlBody($r1)['return_code']);
        $this->assertSame('SUCCESS', $this->xmlBody($r2)['return_code']);

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(100.0, (float) $wallet->balance, '重复回调不得重复加钱');
        $this->assertSame(1, WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_RECHARGE)->count());
    }

    #[Test] public function recharge_notify_rejects_amount_mismatch(): void
    {
        $userId = $this->newUserId();
        $recharge = $this->makeRecharge($userId, 100.0);
        $apiKey = $this->enableWechatSign();

        // 回调金额 99.00 与充值单 100.00 不符
        $resp = (new PaymentNotifyController())->wechatNotify(
            $this->makeNotifyRequest($this->buildRechargeNotifyXml($recharge, 9900, $apiKey))
        );

        $this->assertSame('FAIL', $this->xmlBody($resp)['return_code']);
        $this->assertNull(UserWallet::where('user_id', $userId)->first());
        $this->assertSame(WalletRecharge::STATUS_PENDING, WalletRecharge::find($recharge->id)->status);
    }

    #[Test] public function recharge_notify_accumulates_multiple_recharges(): void
    {
        $userId = $this->newUserId();
        $apiKey = $this->enableWechatSign();
        $ctl = new PaymentNotifyController();

        $r1 = $this->makeRecharge($userId, 100.0);
        $r2 = $this->makeRecharge($userId, 50.0);
        $ctl->wechatNotify($this->makeNotifyRequest($this->buildRechargeNotifyXml($r1, 10000, $apiKey)));
        $ctl->wechatNotify($this->makeNotifyRequest($this->buildRechargeNotifyXml($r2, 5000, $apiKey)));

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(150.0, (float) $wallet->balance);
        $this->assertSame(150.0, (float) $wallet->total_recharge);
        $this->assertSame(2, WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_RECHARGE)->count());
    }

    // ── 余额支付 ──

    #[Test] public function balance_pay_success_debits_wallet_and_marks_order_paid(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过 order_lock 用例');
        }
        $userId = $this->newUserId();
        $this->makeWallet($userId, 200.0);
        $order = $this->makePendingOrder($userId, 100.0);
        $lockKey = 'order_lock:' . $order->id;
        $this->trackRedisKey($lockKey);

        $resp = $this->body((new OrderController())->pay(
            $this->authRequest($userId, ['pay_channel' => 'balance']),
            (string) Container::get('hashids')->encode((int) $order->id)
        ));

        $this->assertSame(0, $resp['code']);
        $this->assertSame(Order::STATUS_PAID, $resp['data']['status']);

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(100.0, (float) $wallet->balance);
        $this->assertSame(100.0, (float) $wallet->total_consume);

        $order = Order::find($order->id);
        $this->assertSame(Order::STATUS_PAID, $order->status);

        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertSame(OrderPayment::STATUS_SUCCESS, $payment->status);
        $this->assertSame('balance', $payment->pay_type);

        $txn = WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_CONSUME)->first();
        $this->assertNotNull($txn);
        $this->assertSame(100.0, (float) $txn->amount);
        $this->assertSame(100.0, (float) $txn->balance_after);
        $this->assertSame((string) $order->id, (string) $txn->order_id);
    }

    #[Test] public function balance_pay_insufficient_returns_422(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过 order_lock 用例');
        }
        $userId = $this->newUserId();
        $this->makeWallet($userId, 50.0);
        $order = $this->makePendingOrder($userId, 100.0);
        $lockKey = 'order_lock:' . $order->id;
        $this->trackRedisKey($lockKey);

        $resp = $this->body((new OrderController())->pay(
            $this->authRequest($userId, ['pay_channel' => 'balance']),
            (string) Container::get('hashids')->encode((int) $order->id)
        ));

        $this->assertSame(422, $resp['code']);
        $this->assertSame('余额不足', $resp['message']);
        // 订单仍 pending，钱包未被扣减
        $this->assertSame(Order::STATUS_PENDING, Order::find($order->id)->status);
        $this->assertSame(50.0, (float) UserWallet::where('user_id', $userId)->first()->balance);
        $this->assertSame(0, WalletTxn::where('user_id', $userId)->count());
    }

    #[Test] public function balance_pay_rejects_paid_order(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过 order_lock 用例');
        }
        $userId = $this->newUserId();
        $this->makeWallet($userId, 200.0);
        $order = $this->makePendingOrder($userId, 100.0);
        $order->status = Order::STATUS_PAID;
        $order->save();
        $lockKey = 'order_lock:' . $order->id;
        $this->trackRedisKey($lockKey);

        $resp = $this->body((new OrderController())->pay(
            $this->authRequest($userId, ['pay_channel' => 'balance']),
            (string) Container::get('hashids')->encode((int) $order->id)
        ));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('不可支付', (string) $resp['message']);
        // 幂等：钱包未被扣减
        $this->assertSame(200.0, (float) UserWallet::where('user_id', $userId)->first()->balance);
    }

    // ── 余额退款回充 ──

    #[Test] public function balance_refund_credits_wallet_and_marks_refunded(): void
    {
        $userId = $this->newUserId();
        $this->makeWallet($userId, 0.0);
        $order = $this->makePendingOrder($userId, 100.0, 'balance');
        // 置为已支付（balance 渠道）
        $order->status = Order::STATUS_PAID;
        $order->created_at = Carbon::now()->subMinutes(30);
        $order->save();
        OrderPayment::where('order_id', $order->id)->update(['status' => OrderPayment::STATUS_SUCCESS, 'pay_type' => 'balance']);

        $resp = self::invokePrivate(new OrderController(), 'doRefund', [
            $this->makeRequest(['reason' => '测试退款']),
            $order,
            1.0,
        ]);

        $body = $this->body($resp);
        $this->assertSame(0, $body['code'], json_encode($body));
        $data = $body['data'];
        $this->assertSame(100.0, (float) $data['refund_amount']);

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(100.0, (float) $wallet->balance, '退款应回充余额');

        $refund = OrderRefund::where('order_id', $order->id)->first();
        $this->assertSame(OrderRefund::STATUS_SUCCESS, $refund->status);
        $this->assertNotNull($refund->refunded_at);
        $this->assertSame(Order::STATUS_REFUNDED, Order::find($order->id)->status);

        $txn = WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_REFUND)->first();
        $this->assertNotNull($txn);
        $this->assertSame(100.0, (float) $txn->amount);
        $this->assertSame(100.0, (float) $txn->balance_after);
        $this->assertSame($order->id, (string) $txn->order_id);
    }

    #[Test] public function balance_refund_compensation_credits_wallet_idempotently(): void
    {
        $userId = $this->newUserId();
        $this->makeWallet($userId, 0.0);
        $order = $this->makePendingOrder($userId, 100.0, 'balance');
        $order->status = Order::STATUS_REFUNDING;
        $order->save();
        OrderPayment::where('order_id', $order->id)->update(['status' => OrderPayment::STATUS_SUCCESS, 'pay_type' => 'balance']);

        // 滞留单：退款单 pending + 11 分钟前（越过补偿阈值）
        $payment = OrderPayment::where('order_id', $order->id)->first();
        $refund = OrderRefund::create([
            'id'         => OrderRefund::generateId(),
            'order_id'   => $order->id,
            'payment_id' => $payment->id,
            'refund_no'  => OrderRefund::generateRefundNo(),
            'amount'     => 100.0,
            'ratio'      => 1.0,
            'reason'     => '落库失败滞留',
            'status'     => OrderRefund::STATUS_PENDING,
        ]);
        Db::table('appointment_order_refund')
            ->where('id', $refund->id)
            ->update(['created_at' => date('Y-m-d H:i:s', time() - 660)]);

        $ctl = new OrderController();
        $ctl->completeRefundCompensation();
        $ctl->completeRefundCompensation(); // 幂等：重复扫描不得重复入账

        $wallet = UserWallet::where('user_id', $userId)->first();
        $this->assertSame(100.0, (float) $wallet->balance, '补偿应回充余额且仅一次');
        $this->assertSame(1, WalletTxn::where('user_id', $userId)->where('type', WalletTxn::TYPE_REFUND)->count());
        $this->assertSame(OrderRefund::STATUS_SUCCESS, OrderRefund::find($refund->id)->status);
        $this->assertSame(Order::STATUS_REFUNDED, Order::find($order->id)->status);
    }

    // ── 流水分页与筛选 ──

    #[Test] public function txns_list_filters_by_type(): void
    {
        $userId = $this->newUserId();
        WalletTxn::create(['user_id' => $userId, 'type' => WalletTxn::TYPE_RECHARGE, 'amount' => 100.0, 'balance_after' => 100.0, 'remark' => '余额充值']);
        WalletTxn::create(['user_id' => $userId, 'type' => WalletTxn::TYPE_CONSUME, 'amount' => 60.0, 'balance_after' => 40.0, 'remark' => '余额支付订单']);
        WalletTxn::create(['user_id' => $userId, 'type' => WalletTxn::TYPE_REFUND, 'amount' => 10.0, 'balance_after' => 50.0, 'remark' => '订单退款']);

        $request = $this->makeRequest(['per_page' => 15, 'type' => WalletTxn::TYPE_CONSUME]);
        $request->user_id = $userId;
        $data = $this->body((new WalletController())->txns($request))['data'];

        $this->assertCount(1, $data);
        $this->assertSame(WalletTxn::TYPE_CONSUME, $data[0]['type']);
        $this->assertSame('消费', $data[0]['type_text']);
        $this->assertSame(3, $this->body((new WalletController())->txns($this->authRequest($userId, ['per_page' => 15])))['meta']['total']);
    }
}
