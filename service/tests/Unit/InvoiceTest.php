<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Invoice;
use app\model\Order;
use app\model\Store;
use app\model\User;
use app\model\WalletRecharge;
use app\user\v1\controller\InvoiceController;
use support\Container;
use Webman\Http\Request;

/**
 * 用户电子发票测试（真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 服务订单开票成功：pending + 金额自动带出（paid_amount）
 * - 充值开票成功：pending + 金额自动带出（充值金额）
 * - company 抬头缺税号 422
 * - 一单重复开票 422
 * - 非本人订单 404
 * - 未完成订单（非 completed）422
 *
 * 管理端开票/驳回流转见 admin/tests/InvoiceControllerTest.php。
 */
class InvoiceTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例订单 ID */
    private array $orderIds = [];

    /** @var string[] 用例门店 ID */
    private array $storeIds = [];

    /** @var string[] 用例充值单 ID */
    private array $rechargeIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            Invoice::where('user_id', $id)->delete();
            WalletRecharge::where('user_id', $id)->delete();
            Order::where('user_id', $id)->delete();
        }
        foreach ($this->storeIds as $id) {
            Store::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->userIds    = [];
        $this->orderIds   = [];
        $this->storeIds   = [];
        $this->rechargeIds = [];
    }

    /** 造用户 */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '发票测试用户',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造门店 */
    private function makeStore(): Store
    {
        $store = Store::create([
            'id'      => Store::generateId(),
            'name'    => '发票测试门店',
            'address' => '测试路 3 号',
            'status'  => 1,
        ]);
        $this->storeIds[] = $store->id;
        return $store;
    }

    /** 造订单（status 由调用方控制） */
    private function makeOrder(string $status, User $customer, float $paidAmount = 88.5): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_INV_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => 0,
            'store_id'        => $this->makeStore()->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'service_time'    => date('Y-m-d H:i:s'),
            'status'          => $status,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    /** 造充值单（status 由调用方控制） */
    private function makeRecharge(string $status, User $customer, float $amount = 66.66): WalletRecharge
    {
        $recharge = WalletRecharge::create([
            'id'       => WalletRecharge::generateId(),
            'user_id'  => $customer->id,
            'order_no' => 'R_INV_' . uniqid(),
            'amount'   => $amount,
            'status'   => $status,
            'paid_at'  => $status === WalletRecharge::STATUS_PAID ? date('Y-m-d H:i:s') : null,
        ]);
        $this->rechargeIds[] = $recharge->id;
        return $recharge;
    }

    /** 造 JSON 请求（user_id 由 Auth 中间件注入，测试直接赋值） */
    private function makeRequest(string $method, string $userId, array $body = []): Request
    {
        $encoded = json_encode($body);
        $head    = "Host: localhost\r\n"
            . "Content-Type: application/json\r\n"
            . "Content-Length: " . strlen($encoded) . "\r\n";
        $request = new Request($method . " / HTTP/1.1\r\n" . $head . "\r\n" . $encoded);
        $request->user_id = $userId;
        return $request;
    }

    /** 调控制器并解码响应 */
    private function callController(string $method, string $userId, array $body = [], ?string $pathId = null): array
    {
        $controller = new InvoiceController();
        $response   = match ($method) {
            'POST'  => $controller->store($this->makeRequest('POST', $userId, $body)),
            'SHOW'  => $controller->show($this->makeRequest('GET', $userId), $pathId),
            default => $controller->index($this->makeRequest('GET', $userId, $body)),
        };
        return json_decode($response->rawBody(), true);
    }

    /** hashid 编码 */
    private function hashidOf(string $id): string
    {
        return (string) Container::get('hashids')->encode((int) $id);
    }

    /** 提交开票（默认参数 + 覆盖） */
    private function submit(User $user, string $orderId, array $overrides = []): array
    {
        $body = array_merge([
            'order_id'      => $this->hashidOf($orderId),
            'order_type'    => Invoice::ORDER_TYPE_SERVICE,
            'title_type'    => Invoice::TITLE_TYPE_PERSONAL,
            'invoice_title' => '个人发票抬头',
        ], $overrides);
        return $this->callController('POST', (string) $user->id, $body);
    }

    // ── 服务订单开票成功 ──

    #[Test] public function store_service_order_success_with_auto_amount(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder(Order::STATUS_COMPLETED, $user, 88.5);

        $resp = $this->submit($user, $order->id);

        $this->assertSame(0, $resp['code']);
        $this->assertSame('pending', $resp['data']['status']);
        $this->assertSame('88.50', $resp['data']['amount']);
        $this->assertSame('个人发票抬头', $resp['data']['invoice_title']);

        // 落库断言：pending + 金额自动带出
        $row = Invoice::where('order_id', $order->id)
            ->where('order_type', Invoice::ORDER_TYPE_SERVICE)->first();
        $this->assertNotNull($row);
        $this->assertSame(Invoice::STATUS_PENDING, $row->status);
        $this->assertSame('88.50', (string) $row->amount);
    }

    // ── 充值开票成功 ──

    #[Test] public function store_recharge_success_with_auto_amount(): void
    {
        $user     = $this->makeUser();
        $recharge = $this->makeRecharge(WalletRecharge::STATUS_PAID, $user, 66.66);

        $resp = $this->submit($user, $recharge->id, [
            'order_type' => Invoice::ORDER_TYPE_RECHARGE,
        ]);

        $this->assertSame(0, $resp['code']);
        $this->assertSame('pending', $resp['data']['status']);
        $this->assertSame('66.66', $resp['data']['amount']);

        $row = Invoice::where('order_id', $recharge->id)
            ->where('order_type', Invoice::ORDER_TYPE_RECHARGE)->first();
        $this->assertNotNull($row);
        $this->assertSame('66.66', (string) $row->amount);
    }

    // ── company 缺税号 422 ──

    #[Test] public function store_company_requires_tax_no(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder(Order::STATUS_COMPLETED, $user);

        $resp = $this->submit($user, $order->id, [
            'title_type' => Invoice::TITLE_TYPE_COMPANY,
        ]);

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, Invoice::where('order_id', $order->id)->count());
    }

    // ── 重复开票 422 ──

    #[Test] public function store_duplicate_order_rejected(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder(Order::STATUS_COMPLETED, $user);

        $this->assertSame(0, $this->submit($user, $order->id)['code']);
        $resp = $this->submit($user, $order->id);

        $this->assertSame(422, $resp['code']);
        $this->assertSame('该订单已申请开票', $resp['message']);
        $this->assertSame(1, Invoice::where('order_id', $order->id)->count());
    }

    // ── 非本人 404 ──

    #[Test] public function store_not_owner_returns_404(): void
    {
        $owner = $this->makeUser();
        $thief = $this->makeUser();
        $order = $this->makeOrder(Order::STATUS_COMPLETED, $owner);

        $resp = $this->submit($thief, $order->id);

        $this->assertSame(404, $resp['code']);
        $this->assertSame(0, Invoice::where('order_id', $order->id)->count());
    }

    // ── 未完成订单 422 ──

    #[Test] public function store_uncompleted_order_rejected(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder(Order::STATUS_PAID, $user);

        $resp = $this->submit($user, $order->id);

        $this->assertSame(422, $resp['code']);
        $this->assertSame('订单完成后才能申请开票', $resp['message']);
        $this->assertSame(0, Invoice::where('order_id', $order->id)->count());
    }

    // ── 我的发票列表 + 详情 ──

    #[Test] public function index_and_show_return_own_invoices(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder(Order::STATUS_COMPLETED, $user);
        $this->submit($user, $order->id);

        $listResp = $this->callController('GET', (string) $user->id, ['status' => 'pending']);
        $this->assertSame(0, $listResp['code']);
        $this->assertSame(1, $listResp['meta']['total']);
        $this->assertSame('pending', $listResp['data'][0]['status']);

        $detailResp = $this->callController('SHOW', (string) $user->id, [], (string) $listResp['data'][0]['id']);
        $this->assertSame(0, $detailResp['code']);
        $this->assertSame('pending', $detailResp['data']['status']);

        // 他人详情 404
        $other   = $this->makeUser();
        $foreign = $this->callController('SHOW', (string) $other->id, [], (string) $listResp['data'][0]['id']);
        $this->assertSame(404, $foreign['code']);
    }
}
