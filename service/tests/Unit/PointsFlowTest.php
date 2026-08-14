<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\marketing\v1\controller\PointController;
use app\model\Notification;
use app\model\Order;
use app\model\OrderVerification;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\UserPoints;
use app\order\v1\controller\OrderController;
use Illuminate\Pagination\Paginator;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 积分体系闭环集成测试
 *
 * 覆盖：
 * - 消费返积分：订单核销后按实付金额返积分（1 元 = 1 分），balance 逐条累加正确
 * - 幂等：同订单不重复返积分
 * - 明细分页：balance + records + meta 结构、per_page 分页
 * - 来源/类型过滤：source=order / type=earn 只返回对应流水
 *
 * 依赖真实 DB / Redis（与 OrderVerificationFlowTest 同基建）。
 */
class PointsFlowTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    /** @var string[] 用例创建的测试用户 ID（含直接造积分的用户），tearDown 统一清理 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            UserPoints::where('order_id', $id)->delete();
            Notification::where('order_id', $id)->delete();
            TechnicianEarning::where('order_id', $id)->delete();
            OrderVerification::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            UserPoints::where('user_id', $id)->delete();
        }
        $this->orderIds = [];
        $this->profileIds = [];
        $this->userIds = [];
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

    private function makeTestUserId(): string
    {
        $userId = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $userId;
        return $userId;
    }

    /** 造已审核技师档案（返回模型，id/user_id 均为测试段 snowflake 段） */
    private function makeTechnician(): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id       = TechnicianProfile::generateId();
        $profile->user_id  = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = '测试技师';
        $profile->gender   = 1;
        $profile->status   = 'approved';
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    /** 造已支付订单 + 核销码记录（返回 [order, code]） */
    private function makePaidOrderWithCode(string $technicianId, float $paidAmount, ?string $userId = null): array
    {
        $order = Order::create([
            'order_no'        => 'ORD_PTS_' . uniqid(),
            'user_id'         => $userId ?? $this->makeTestUserId(),
            'technician_id'   => $technicianId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => Order::STATUS_PAID,
        ]);
        $this->orderIds[] = $order->id;

        $code = bin2hex(random_bytes(16));
        OrderVerification::create([
            'id'       => OrderVerification::generateId(),
            'order_id' => $order->id,
            'code'     => $code,
        ]);

        return [$order, $code];
    }

    #[Test] public function verify_earns_order_points_with_accumulated_balance(): void
    {
        $technician = $this->makeTechnician();
        $userA = $this->makeTestUserId();

        // 第一单实付 250 → 返 250 分，balance=250
        [$order1, $code1] = $this->makePaidOrderWithCode($technician->id, 250.0, $userA);
        $req = $this->makeRequest(['code' => $code1]);
        $req->user_id = $technician->user_id;
        $resp = (new OrderController())->verifyByCode($req);
        $this->assertSame(0, $this->body($resp)['code']);

        // 第二单实付 50.9 → 向下取整返 50 分，balance=250+50=300
        [$order2, $code2] = $this->makePaidOrderWithCode($technician->id, 50.9, $userA);
        $req = $this->makeRequest(['code' => $code2]);
        $req->user_id = $technician->user_id;
        $resp = (new OrderController())->verifyByCode($req);
        $this->assertSame(0, $this->body($resp)['code']);

        $r1 = UserPoints::where('order_id', $order1->id)->where('source', 'order')->first();
        $this->assertNotNull($r1);
        $this->assertSame('earn', $r1->type);
        $this->assertSame(250, (int) $r1->points);
        $this->assertSame(250, (int) $r1->balance);

        $r2 = UserPoints::where('order_id', $order2->id)->where('source', 'order')->first();
        $this->assertNotNull($r2);
        $this->assertSame(50, (int) $r2->points);
        $this->assertSame(300, (int) $r2->balance);

        // 用户总流水 2 条，余额快照逐条累加正确
        $this->assertSame(2, UserPoints::where('user_id', $userA)->count());
        $this->assertSame(300, (int) UserPoints::where('user_id', $userA)->max('balance'));
    }

    #[Test] public function same_order_points_not_awarded_twice(): void
    {
        $technician = $this->makeTechnician();
        [$order, $code] = $this->makePaidOrderWithCode($technician->id, 100.0);

        $verify = function () use ($code, $technician) {
            $req = $this->makeRequest(['code' => $code]);
            $req->user_id = $technician->user_id;
            return (new OrderController())->verifyByCode($req);
        };

        $this->assertSame(0, $this->body($verify())['code']);
        // 重复核销：已核销返回成功但不重复返积分
        $this->assertSame(0, $this->body($verify())['code']);
        $this->assertSame(1, UserPoints::where('order_id', $order->id)->where('source', 'order')->count());
    }

    #[Test] public function points_index_paginates_with_meta(): void
    {
        $userId = $this->makeTestUserId();
        $balance = 0;
        for ($i = 1; $i <= 15; $i++) {
            $balance += 10;
            $this->createPointsRow($userId, $i, $balance);
        }

        $req = $this->makeRequest(['page' => 2, 'per_page' => 5]);
        $req->user_id = $userId;
        $resp = $this->callIndex($req);
        $body = $this->body($resp);

        $this->assertSame(0, $body['code']);
        $this->assertSame(150, (int) $body['data']['balance']);
        $this->assertCount(5, $body['data']['records']);
        $this->assertSame(15, $body['meta']['total']);
        $this->assertSame(2, $body['meta']['current_page']);
        $this->assertSame(3, $body['meta']['last_page']);
        $this->assertTrue($body['meta']['has_more']);
    }

    #[Test] public function points_index_filters_by_source_and_type(): void
    {
        $userId = $this->makeTestUserId();
        $balance = 0;
        for ($i = 1; $i <= 6; $i++) {
            $balance += 10;
            $this->createPointsRow($userId, $i, $balance);
        }

        $req = $this->makeRequest(['source' => 'order', 'per_page' => 20]);
        $req->user_id = $userId;
        $body = $this->body($this->callIndex($req));
        $this->assertSame(0, $body['code']);
        $this->assertSame(4, (int) $body['meta']['total']);
        foreach ($body['data']['records'] as $r) {
            $this->assertSame('order', $r['source']);
        }

        $req = $this->makeRequest(['type' => 'earn', 'per_page' => 20]);
        $req->user_id = $userId;
        $body = $this->body($this->callIndex($req));
        $this->assertSame(0, $body['code']);
        $this->assertSame(3, (int) $body['meta']['total']);
        foreach ($body['data']['records'] as $r) {
            $this->assertSame('earn', $r['type']);
        }
    }

    /** 调 PointController::index 并把分页 page 参数绑定到测试请求（单测环境无容器 request） */
    private function callIndex(Request $request): Response
    {
        Paginator::currentPageResolver(
            fn (string $pageName = 'page') => max(1, (int) $request->input($pageName, 1))
        );
        return (new PointController())->index($request);
    }

    /** 直接造一条积分流水（created_at 递增保证分页/余额确定性） */
    private function createPointsRow(string $userId, int $seq, int $balance): void
    {
        $isEarn = $seq % 2 === 1;
        $row = new UserPoints();
        $row->id          = UserPoints::generateId();
        $row->user_id     = $userId;
        $row->type        = $isEarn ? 'earn' : 'use';
        $row->points      = $isEarn ? 10 : -10;
        $row->balance     = $balance;
        $row->source      = $seq % 3 === 0 ? 'check_in' : 'order';
        $row->description = '积分测试流水 ' . $seq;
        $row->created_at  = date('Y-m-d H:i:s', strtotime('2020-01-01 00:00:00') + $seq);
        $row->save();
    }
}
