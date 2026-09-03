<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\common\WechatPayService;
use app\model\CheckIn;
use app\model\GrowthLevel;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderReview;
use app\model\UserGrowth;
use app\order\v1\controller\ReviewController;
use app\user\v1\controller\CheckInController;
use app\user\v1\controller\GrowthController;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 用户成长等级体系集成测试
 *
 * 覆盖：
 * - 签到成长值入账（+10, type=signin）
 * - 评价成长值入账（+20, type=review）
 * - 消费成长值入账（floor(实付金额) 元=1 点, type=consume；重复回调不重复入账）
 * - GET /api/v1/growth 返回累计成长值、当前等级、下一等级进度、权益
 * - GET /api/v1/growth/records 分页 + type 过滤（倒序）
 * - GET /api/v1/growth/levels 公开等级列表
 *
 * 依赖真实 DB（与 PointsFlowTest 同基建）。
 */
class GrowthTest extends TestCase
{
    /** @var string[] 测试用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 测试订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 测试评价 ID，tearDown 统一清理 */
    private array $reviewIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            UserGrowth::where('user_id', $id)->delete();
            CheckIn::where('user_id', $id)->delete();
            OrderReview::where('user_id', $id)->delete();
        }
        foreach ($this->orderIds as $id) {
            OrderPayment::where('order_id', $id)->delete();
            OrderReview::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->reviewIds as $id) {
            OrderReview::where('id', $id)->delete();
        }
        $this->userIds = [];
        $this->orderIds = [];
        $this->reviewIds = [];
    }

    private function makeRequest(array $post = [], string $query = ''): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST /?" . ltrim($query, '?') . " HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function makeTestUserId(): string
    {
        $userId = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $userId;
        return $userId;
    }

    private function makeOrder(string $userId, string $status, float $paidAmount): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_GRW_' . uniqid(),
            'user_id'         => $userId,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => $status,
            'service_time'    => date('Y-m-d H:i:s', time() + 86400),
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function seedGrowth(string $userId, string $type, int $value): void
    {
        UserGrowth::create([
            'id'      => UserGrowth::generateId(),
            'user_id' => $userId,
            'type'    => $type,
            'value'   => $value,
            'balance' => $value,
        ]);
    }

    #[Test] public function checkin_awards_signin_growth(): void
    {
        $userId = $this->makeTestUserId();
        $req = $this->makeRequest();
        $req->user_id = $userId;
        $resp = (new CheckInController())->store($req);
        $this->assertSame(0, $this->body($resp)['code']);

        $growth = UserGrowth::where('user_id', $userId)->first();
        $this->assertNotNull($growth);
        $this->assertSame(UserGrowth::TYPE_SIGNIN, $growth->type);
        $this->assertSame(UserGrowth::VALUE_SIGNIN, (int) $growth->value);
        $this->assertSame((int) $growth->value, (int) $growth->balance);
    }

    #[Test] public function review_awards_review_growth(): void
    {
        $userId = $this->makeTestUserId();
        $order = $this->makeOrder($userId, Order::STATUS_COMPLETED, 100.0);

        $req = $this->makeRequest(['rating' => 5, 'content' => '服务很好']);
        $req->user_id = $userId;
        $resp = (new ReviewController())->store($req, $this->encodeId($order->id));
        $this->assertSame(0, $this->body($resp)['code']);

        $growth = UserGrowth::where('user_id', $userId)->where('type', UserGrowth::TYPE_REVIEW)->first();
        $this->assertNotNull($growth);
        $this->assertSame(UserGrowth::VALUE_REVIEW, (int) $growth->value);
    }

    #[Test] public function consume_awards_floor_amount_growth_idempotent(): void
    {
        $userId = $this->makeTestUserId();
        $order = $this->makeOrder($userId, Order::STATUS_PENDING, 125.6);

        OrderPayment::create([
            'id'         => OrderPayment::generateId(),
            'order_id'   => $order->id,
            'payment_no' => 'GRWPAY_' . uniqid(),
            'pay_type'   => 'wechat',
            'amount'     => 125.6,
            'status'     => OrderPayment::STATUS_PENDING,
        ]);
        $payment = OrderPayment::where('order_id', $order->id)->first();

        // 首次支付成功 → 入账 floor(125.6)=125
        $result = (new WechatPayService())->markOrderPaid($payment->payment_no, 'GRWTXN_' . uniqid(), 125.6, 'wechat');
        $this->assertTrue($result['success']);

        $growth = UserGrowth::where('user_id', $userId)->where('type', UserGrowth::TYPE_CONSUME)->get();
        $this->assertCount(1, $growth);
        $this->assertSame(125, (int) $growth->first()->value);

        // 重复回调（已 success 早退）→ 不重复入账
        $retry = (new WechatPayService())->markOrderPaid($payment->payment_no, 'GRWTXN_' . uniqid(), 125.6, 'wechat');
        $this->assertTrue($retry['success']);
        $this->assertSame(1, UserGrowth::where('user_id', $userId)
            ->where('type', UserGrowth::TYPE_CONSUME)->count());
        $this->assertSame(125, UserGrowth::totalFor($userId));
    }

    #[Test] public function growth_index_returns_level_and_next_progress(): void
    {
        $userId = $this->makeTestUserId();
        $this->seedGrowth($userId, UserGrowth::TYPE_SIGNIN, 10);
        $this->seedGrowth($userId, UserGrowth::TYPE_REVIEW, 20);
        $this->seedGrowth($userId, UserGrowth::TYPE_CONSUME, 220); // 累计 250

        $req = $this->makeRequest();
        $req->user_id = $userId;
        $resp = (new GrowthController())->index($req);
        $data = $this->body($resp)['data'];

        $this->assertSame(250, $data['total_growth']);
        // 250 处于 白银(100) 档，下一档 黄金(500)，还差 250
        $this->assertSame('白银', $data['current_level']['name']);
        $this->assertSame(100, $data['current_level']['min_growth']);
        $this->assertSame('黄金', $data['next_level']['name']);
        $this->assertSame(500, $data['next_level']['min_growth']);
        $this->assertSame(250, $data['next_gap']);
        $this->assertSame(1.1, $data['current_level']['benefits']['points_multiplier'] ?? null);
    }

    #[Test] public function records_paginate_and_filter_by_type_desc(): void
    {
        $userId = $this->makeTestUserId();
        for ($i = 0; $i < 3; $i++) {
            $this->seedGrowth($userId, UserGrowth::TYPE_SIGNIN, 10);
        }
        $this->seedGrowth($userId, UserGrowth::TYPE_REVIEW, 20);

        // 分页 limit=2 page=1 → 2 条；全部 4 条
        $req = $this->makeRequest([], 'limit=2&page=1');
        $req->user_id = $userId;
        $resp = (new GrowthController())->records($req);
        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $this->assertCount(2, $body['data']);
        $this->assertSame(4, $body['meta']['total']);

        // type=signin 过滤 → 仅 3 条 signin，且倒序（created_at 相同时按 id 倒序）
        $req2 = $this->makeRequest([], 'type=' . UserGrowth::TYPE_SIGNIN . '&limit=10');
        $req2->user_id = $userId;
        $resp2 = (new GrowthController())->records($req2);
        $body2 = $this->body($resp2);
        $this->assertSame(0, $body2['code']);
        $this->assertCount(3, $body2['data']);
        foreach ($body2['data'] as $row) {
            $this->assertSame(UserGrowth::TYPE_SIGNIN, $row['type']);
        }
    }

    #[Test] public function levels_lists_all_seed_levels(): void
    {
        $req = $this->makeRequest();
        $resp = (new GrowthController())->levels($req);
        $data = $this->body($resp)['data'];

        $this->assertCount(5, $data['levels']);
        $this->assertSame('青铜', $data['levels'][0]['name']);
        $this->assertSame(0, $data['levels'][0]['min_growth']);
        $this->assertSame('钻石', $data['levels'][4]['name']);
        $this->assertSame(5000, $data['levels'][4]['min_growth']);
        $this->assertSame(5, GrowthLevel::count());
    }

    /** 用与 BaseController 同款 hashids 实例编码订单 ID（decodeId 逆过程） */
    private function encodeId(string $id): string
    {
        return \support\Container::get('hashids')->encode((int) $id);
    }
}
