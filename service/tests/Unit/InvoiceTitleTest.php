<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Invoice;
use app\model\InvoiceTitle;
use app\model\Order;
use app\model\Store;
use app\model\User;
use app\user\v1\controller\InvoiceController;
use app\user\v1\controller\InvoiceTitleController;
use support\Container;
use Webman\Http\Request;

/**
 * 常用发票抬头测试（真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 首个保存自动为默认 + 列表 is_default 置顶
 * - 同用户同类型同抬头重复 422
 * - company 缺税号 422
 * - 设为默认切换（同用户其他行清零）
 * - 删除默认后自动转移给最早一条
 * - 申请开票时 title_id 带入抬头信息
 */
class InvoiceTitleTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例订单 ID */
    private array $orderIds = [];

    /** @var string[] 用例门店 ID */
    private array $storeIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            InvoiceTitle::where('user_id', $id)->delete();
            Invoice::where('user_id', $id)->delete();
            Order::where('user_id', $id)->delete();
        }
        foreach ($this->storeIds as $id) {
            Store::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->userIds  = [];
        $this->orderIds = [];
        $this->storeIds = [];
    }

    /** 造用户 */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '198' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '抬头测试用户',
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
            'name'    => '抬头测试门店',
            'address' => '测试路 3 号',
            'status'  => 1,
        ]);
        $this->storeIds[] = $store->id;
        return $store;
    }

    /** 造已完成订单 */
    private function makeCompletedOrder(User $customer): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_TITLE_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => 0,
            'store_id'        => $this->makeStore()->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 99.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 99.0,
            'service_time'    => date('Y-m-d H:i:s'),
            'status'          => Order::STATUS_COMPLETED,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
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

    /** 调 InvoiceTitleController 并解码响应 */
    private function callController(string $method, string $userId, array $body = [], ?string $pathId = null): array
    {
        $controller = new InvoiceTitleController();
        $response   = match ($method) {
            'POST'    => $controller->store($this->makeRequest('POST', $userId, $body)),
            'PUT'     => $controller->update($this->makeRequest('PUT', $userId, $body), $pathId),
            'DELETE'  => $controller->destroy($this->makeRequest('DELETE', $userId), $pathId),
            'DEFAULT' => $controller->setDefault($this->makeRequest('POST', $userId), $pathId),
            default   => $controller->index($this->makeRequest('GET', $userId, $body)),
        };
        return json_decode($response->rawBody(), true);
    }

    /** hashid 编码 */
    private function hashidOf(string $id): string
    {
        return (string) Container::get('hashids')->encode((int) $id);
    }

    /** 保存抬头（默认参数 + 覆盖） */
    private function saveTitle(User $user, array $overrides = []): array
    {
        $body = array_merge([
            'title_type'    => InvoiceTitle::TITLE_TYPE_PERSONAL,
            'invoice_title' => '个人抬头',
        ], $overrides);
        return $this->callController('POST', (string) $user->id, $body);
    }

    // ── 首个自动默认 + 列表置顶 ──

    #[Test] public function store_first_becomes_default_and_list_orders_default_first(): void
    {
        $user = $this->makeUser();

        $first  = $this->saveTitle($user, ['invoice_title' => '第一个抬头']);
        $second = $this->saveTitle($user, ['invoice_title' => '第二个抬头']);

        $this->assertSame(0, $first['code']);
        $this->assertSame(1, $first['data']['is_default']);
        $this->assertSame(0, $second['data']['is_default']);

        $list = $this->callController('GET', (string) $user->id);
        $this->assertSame(0, $list['code']);
        $this->assertSame(2, count($list['data']));
        $this->assertSame('第一个抬头', $list['data'][0]['invoice_title']);
        $this->assertSame(1, $list['data'][0]['is_default']);
    }

    // ── 同用户同类型同抬头重复 422 ──

    #[Test] public function store_duplicate_title_rejected(): void
    {
        $user = $this->makeUser();

        $this->assertSame(0, $this->saveTitle($user)['code']);
        $resp = $this->saveTitle($user);

        $this->assertSame(422, $resp['code']);
        $this->assertSame('该抬头已存在', $resp['message']);
        $this->assertSame(1, InvoiceTitle::where('user_id', $user->id)->count());
    }

    // ── company 缺税号 422 ──

    #[Test] public function store_company_requires_tax_no(): void
    {
        $user = $this->makeUser();

        $resp = $this->saveTitle($user, [
            'title_type' => InvoiceTitle::TITLE_TYPE_COMPANY,
        ]);

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, InvoiceTitle::where('user_id', $user->id)->count());
    }

    // ── 设为默认切换（同用户其他行清零）──

    #[Test] public function set_default_switches_and_clears_others(): void
    {
        $user  = $this->makeUser();
        $first = $this->saveTitle($user, ['invoice_title' => '抬头A']);
        $this->saveTitle($user, ['invoice_title' => '抬头B']);

        $resp = $this->callController('DEFAULT', (string) $user->id, [], (string) $first['data']['id']);

        $this->assertSame(0, $resp['code']);
        $rows = InvoiceTitle::where('user_id', $user->id)->orderBy('created_at', 'asc')->get();
        $this->assertSame(1, (int) $rows[0]->is_default);
        $this->assertSame(0, (int) $rows[1]->is_default);
        $this->assertSame(1, InvoiceTitle::where('user_id', $user->id)->where('is_default', 1)->count());
    }

    // ── 删除默认后自动转移给最早一条 ──

    #[Test] public function destroy_default_transfers_to_earliest(): void
    {
        $user  = $this->makeUser();
        $first = $this->saveTitle($user, ['invoice_title' => '默认抬头']);
        $this->saveTitle($user, ['invoice_title' => '备用抬头']);

        $resp = $this->callController('DELETE', (string) $user->id, [], (string) $first['data']['id']);

        $this->assertSame(0, $resp['code']);
        $rows = InvoiceTitle::where('user_id', $user->id)->orderBy('created_at', 'asc')->get();
        $this->assertSame(1, count($rows));
        $this->assertSame('备用抬头', $rows[0]->invoice_title);
        $this->assertSame(1, (int) $rows[0]->is_default);
    }

    // ── 申请开票时 title_id 带入抬头信息 ──

    #[Test] public function invoice_store_with_title_id_carries_title(): void
    {
        $user = $this->makeUser();
        $order = $this->makeCompletedOrder($user);
        $title = $this->saveTitle($user, [
            'title_type'    => InvoiceTitle::TITLE_TYPE_COMPANY,
            'invoice_title' => '科技公司',
            'tax_no'        => '91110108MA01ABCDEF',
        ]);
        $this->assertSame(0, $title['code']);

        $controller = new InvoiceController();
        $resp = json_decode($controller->store($this->makeRequest('POST', (string) $user->id, [
            'order_id'   => $this->hashidOf($order->id),
            'order_type' => Invoice::ORDER_TYPE_SERVICE,
            'title_id'   => (string) $title['data']['id'],
        ]))->rawBody(), true);

        $this->assertSame(0, $resp['code']);
        $this->assertSame('company', $resp['data']['title_type']);
        $this->assertSame('科技公司', $resp['data']['invoice_title']);
        $this->assertSame('91110108MA01ABCDEF', $resp['data']['tax_no']);
        $this->assertSame('99.00', $resp['data']['amount']);

        $row = Invoice::where('order_id', $order->id)->where('order_type', Invoice::ORDER_TYPE_SERVICE)->first();
        $this->assertNotNull($row);
        $this->assertSame('科技公司', $row->invoice_title);
        $this->assertSame('91110108MA01ABCDEF', $row->tax_no);
    }
}
