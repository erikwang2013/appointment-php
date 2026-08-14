<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Order;
use app\model\OrderAftersale;
use app\model\OrderPayment;
use app\order\v1\controller\AftersaleController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 售后（退换货）用户端闭环测试
 *
 * 覆盖：申请成功（refund/exchange）、非本人订单 404、重复申请 422、
 * 类型/原因/订单状态校验、列表只返回本人、详情归属校验。
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 */
class AftersaleTest extends TestCase
{
    /** @var int[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderAftersale::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        $this->orderIds = [];
    }

    private function makeRequest(array $post = [], string $method = 'POST'): Request
    {
        if ($method === 'GET') {
            $query = $post ? '?' . http_build_query($post) : '';
            $head = "Host: localhost\r\n"
                . "Content-Type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: 0\r\n";
            return new Request("GET /api/aftersales{$query} HTTP/1.1\r\n" . $head . "\r\n");
        }
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
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    /** 造指定用户的订单（默认 paid，可选 status） */
    private function makeOrder(string $userId, string $status = Order::STATUS_PAID, float $paidAmount = 100.0): Order
    {
        $order = Order::create([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_TEST_' . uniqid(),
            'user_id'         => $userId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => $status,
            'service_time'    => date('Y-m-d H:i:s', time() + 43200),
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function apply(string $userId, array $post): array
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $this->body((new AftersaleController())->store($request));
    }

    private function encodeId(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    private function decodeId(string $hashid): int
    {
        return (int) Container::get('hashids')->decode($hashid)[0];
    }

    // ── 申请成功 ──

    #[Test] public function apply_refund_succeeds_on_paid_order(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeOrder($userId);

        $resp = $this->apply($userId, [
            'type'     => 'refund',
            'order_id' => $this->encodeId((int) $order->id),
            'reason'   => '服务与描述不符',
        ]);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('pending', $resp['data']['status']);
        $this->assertSame('refund', $resp['data']['type']);
        $this->assertSame('服务与描述不符', $resp['data']['reason']);
        $this->assertStringStartsWith('AS', (string) $resp['data']['aftersale_no']);
        $this->assertSame(100.0, (float) $resp['data']['refund_amount']);

        // 落库校验 + id/order_id 均为 hashid 且可还原
        $row = OrderAftersale::where('order_id', $order->id)->first();
        $this->assertNotNull($row);
        $this->assertSame($userId, (string) $row->user_id);
        $this->assertSame((int) $row->id, $this->decodeId((string) $resp['data']['id']), 'id 应为 hashid 且可还原');
        $this->assertSame((int) $order->id, $this->decodeId((string) $resp['data']['order_id']));
    }

    #[Test] public function apply_exchange_succeeds_on_completed_order(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeOrder($userId, Order::STATUS_COMPLETED);

        $resp = $this->apply($userId, [
            'type'     => 'exchange',
            'order_id' => $this->encodeId((int) $order->id),
            'reason'   => '想要更换款式',
        ]);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('exchange', $resp['data']['type']);
        $this->assertSame(0.0, (float) $resp['data']['refund_amount'], '换货不涉及退款金额');
    }

    // ── 非本人订单 ──

    #[Test] public function apply_foreign_order_rejected_with_404(): void
    {
        $owner = $this->newUserId();
        $attacker = $this->newUserId();
        $order = $this->makeOrder($owner);

        $resp = $this->apply($attacker, [
            'type'     => 'refund',
            'order_id' => $this->encodeId((int) $order->id),
            'reason'   => '越权申请',
        ]);

        $this->assertSame(404, $resp['code']);
        $this->assertSame(0, OrderAftersale::where('order_id', $order->id)->count(), '不得落库他人订单的售后');
    }

    // ── 重复申请 ──

    #[Test] public function duplicate_apply_rejected_with_422(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeOrder($userId);

        $r1 = $this->apply($userId, ['type' => 'refund', 'order_id' => $this->encodeId((int) $order->id), 'reason' => '第一次']);
        $r2 = $this->apply($userId, ['type' => 'refund', 'order_id' => $this->encodeId((int) $order->id), 'reason' => '第二次']);

        $this->assertSame(0, $r1['code']);
        $this->assertSame(422, $r2['code']);
        $this->assertStringContainsString('进行中', (string) $r2['message']);
        $this->assertSame(1, OrderAftersale::where('order_id', $order->id)->count());
    }

    // ── 参数与状态校验 ──

    #[Test] public function apply_rejects_invalid_type(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeOrder($userId);

        $resp = $this->apply($userId, ['type' => 'return', 'order_id' => $this->encodeId((int) $order->id), 'reason' => 'x']);

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, OrderAftersale::where('order_id', $order->id)->count());
    }

    #[Test] public function apply_requires_reason(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeOrder($userId);

        $resp = $this->apply($userId, ['type' => 'refund', 'order_id' => $this->encodeId((int) $order->id), 'reason' => '  ']);

        $this->assertSame(422, $resp['code']);
    }

    #[Test] public function apply_rejects_unpaid_order(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeOrder($userId, Order::STATUS_PENDING);

        $resp = $this->apply($userId, ['type' => 'refund', 'order_id' => $this->encodeId((int) $order->id), 'reason' => 'x']);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('不支持', (string) $resp['message']);
    }

    // ── 我的列表只看本人 ──

    #[Test] public function list_returns_only_own_aftersales(): void
    {
        $userA = $this->newUserId();
        $userB = $this->newUserId();
        $o1 = $this->makeOrder($userA);
        $o2 = $this->makeOrder($userA);
        $o3 = $this->makeOrder($userB);

        $this->apply($userA, ['type' => 'refund', 'order_id' => $this->encodeId((int) $o1->id), 'reason' => 'r1']);
        $this->apply($userA, ['type' => 'exchange', 'order_id' => $this->encodeId((int) $o2->id), 'reason' => 'r2']);
        $this->apply($userB, ['type' => 'refund', 'order_id' => $this->encodeId((int) $o3->id), 'reason' => 'rb']);

        // 人工制造 created_at 先后（DATETIME 秒级精度，同秒内排序不确定），验证 desc 排序
        OrderAftersale::where('order_id', $o1->id)->update(['created_at' => '2026-08-14 09:00:00']);
        OrderAftersale::where('order_id', $o2->id)->update(['created_at' => '2026-08-14 10:00:00']);

        $request = $this->makeRequest(['page' => 1, 'limit' => 10], 'GET');
        $request->user_id = $userA;
        $resp = $this->body((new AftersaleController())->index($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(2, $resp['meta']['total'], '只返回本人售后记录');
        $this->assertSame(2, count($resp['data']));
        $this->assertSame($o2->id, (string) $this->decodeId((string) $resp['data'][0]['order_id']), '按 created_at desc');
        $this->assertSame($o1->id, (string) $this->decodeId((string) $resp['data'][1]['order_id']));
    }

    // ── 详情归属 ──

    #[Test] public function show_returns_own_detail_and_hides_foreign(): void
    {
        $owner = $this->newUserId();
        $other = $this->newUserId();
        $order = $this->makeOrder($owner);
        $this->apply($owner, ['type' => 'refund', 'order_id' => $this->encodeId((int) $order->id), 'reason' => '详情']);

        $aftersale = OrderAftersale::where('order_id', $order->id)->first();

        // 本人可见
        $req1 = $this->makeRequest([], 'GET');
        $req1->user_id = $owner;
        $resp1 = $this->body((new AftersaleController())->show($req1, $this->encodeId((int) $aftersale->id)));
        $this->assertSame(0, $resp1['code']);
        $this->assertSame('详情', $resp1['data']['reason']);

        // 他人不可见（404）
        $req2 = $this->makeRequest([], 'GET');
        $req2->user_id = $other;
        $resp2 = $this->body((new AftersaleController())->show($req2, $this->encodeId((int) $aftersale->id)));
        $this->assertSame(404, $resp2['code']);

        // 无效 hashid → 404
        $req3 = $this->makeRequest([], 'GET');
        $req3->user_id = $owner;
        $resp3 = $this->body((new AftersaleController())->show($req3, 'invalid-hashid'));
        $this->assertSame(404, $resp3['code']);
    }
}
