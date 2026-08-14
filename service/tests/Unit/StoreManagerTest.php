<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\StoreManagerController;
use app\model\Order;
use app\model\OrderVerification;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use app\model\User;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 店长工作台测试（StoreManagerController）
 *
 * 覆盖：
 * - 无门店权限（store_id=0）一律 403
 * - overview 今日口径统计正确（订单数/营收/进行中/技师数/核销数）
 * - orders 只返回本店 + status 筛选 + hashid 编码
 * - technicians 只返回本店技师且携带今日排班
 * - revenue 近 7 天按日聚合（无数据补零，超窗不统计）
 *
 * 依赖真实 DB（与 TechnicianWorkTest 同基建：真实 DB + tearDown 清理）。
 */
class StoreManagerTest extends TestCase
{
    private const STORE_A = 9900000000000001;
    private const STORE_B = 9900000000000002;

    /** @var string[] 用例用户 ID，tearDown 清理 */
    private array $userIds = [];

    /** @var string[] 用例订单 ID，tearDown 清理 */
    private array $orderIds = [];

    /** @var string[] 用例核销 ID，tearDown 清理 */
    private array $verificationIds = [];

    /** @var string[] 用例技师档案 ID，tearDown 清理 */
    private array $profileIds = [];

    protected function tearDown(): void
    {
        foreach ($this->verificationIds as $id) {
            OrderVerification::where('id', $id)->delete();
        }
        foreach ($this->orderIds as $id) {
            OrderVerification::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianSchedule::where('technician_id', $id)->delete();
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->userIds = [];
        $this->orderIds = [];
        $this->verificationIds = [];
        $this->profileIds = [];
    }

    private function makeRequest(string $query = ''): Request
    {
        $target = '/' . ($query !== '' ? '?' . $query : '');
        return new Request("GET $target HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function decodeId(string $hashid): int
    {
        return (int) Container::get('hashids')->decode($hashid)[0];
    }

    /** 造用户；storeId=0 表示无门店 */
    private function makeUser(int $storeId = 0): User
    {
        $user = User::create([
            'id'             => User::generateId(),
            'phone'          => '139' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password'       => 'test-pass',
            'wx_openid'      => '',
            'wx_unionid'     => '',
            'avatar'         => '',
            'nickname'       => '测试用户',
            'real_name'      => '测试用户',
            'last_login_ip'  => '127.0.0.1',
            'store_id'       => $storeId,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造订单；$createdAt/$updatedAt 控制归属日期 */
    private function makeOrder(
        int $storeId,
        string $status,
        float $paid = 0.0,
        mixed $createdAt = null,
        mixed $updatedAt = null,
        ?string $technicianId = null
    ): Order {
        $order = Order::create([
            'order_no'        => 'ORD_SM_' . uniqid(),
            'user_id'         => User::generateId(),
            'technician_id'   => $technicianId ?? 0,
            'store_id'        => $storeId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paid,
            'discount_amount' => 0.0,
            'paid_amount'     => $paid,
            'status'          => $status,
            'created_at'      => $createdAt ?? now(),
            'updated_at'      => $updatedAt ?? $createdAt ?? now(),
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function makeVerification(Order $order, mixed $verifiedAt = null): OrderVerification
    {
        $verification = OrderVerification::create([
            'id'          => OrderVerification::generateId(),
            'order_id'    => $order->id,
            'code'        => bin2hex(random_bytes(8)),
            'verified_by' => 0,
            'verify_type' => OrderVerification::VERIFY_TYPE_SCAN,
            'verified_at' => $verifiedAt ?? now(),
        ]);
        $this->verificationIds[] = $verification->id;
        return $verification;
    }

    /** 造技师档案（user_id 即订单 technician_id） */
    private function makeTechnician(string $userId): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id        = TechnicianProfile::generateId();
        $profile->user_id   = $userId;
        $profile->real_name = '测试技师';
        $profile->gender    = 1;
        $profile->status    = 'approved';
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    private function makeSchedule(TechnicianProfile $profile, mixed $date = null): TechnicianSchedule
    {
        $schedule = TechnicianSchedule::create([
            'id'            => TechnicianSchedule::generateId(),
            'technician_id' => $profile->id,
            'date'          => $date ?? date('Y-m-d'),
            'time_slots'    => ['09:00', '10:00'],
            'status'        => 1,
        ]);
        return $schedule;
    }

    // ── 权限：无门店 403 ──

    #[Test] public function no_store_permission_returns_403(): void
    {
        $user = $this->makeUser(0);
        $request = $this->makeRequest();
        $request->user_id = $user->id;

        $resp = $this->body((new StoreManagerController())->overview($request));
        $this->assertSame(403, $resp['code']);
        $this->assertStringContainsString('无门店权限', (string) $resp['message']);

        $resp = $this->body((new StoreManagerController())->orders($request));
        $this->assertSame(403, $resp['code']);

        $resp = $this->body((new StoreManagerController())->technicians($request));
        $this->assertSame(403, $resp['code']);

        $resp = $this->body((new StoreManagerController())->revenue($request));
        $this->assertSame(403, $resp['code']);
    }

    // ── overview 今日口径 ──

    #[Test] public function overview_returns_today_stats_for_own_store(): void
    {
        $user = $this->makeUser(self::STORE_A);
        $tech1 = $this->makeTechnician(User::generateId());
        $tech2 = $this->makeTechnician(User::generateId());

        $completedToday = $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 100.0, now(), now(), $tech1->user_id);
        $pendingToday   = $this->makeOrder(self::STORE_A, Order::STATUS_PENDING, 0.0, now(), now(), $tech1->user_id);
        $servingToday   = $this->makeOrder(self::STORE_A, Order::STATUS_SERVING, 50.0, now(), now(), $tech2->user_id);
        // 昨日已完成：不计今日订单数/营收，但计入技师数（本店曾服务）
        $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 200.0, now()->subDay(), now()->subDay(), $tech2->user_id);
        // 他店订单：不得影响本店统计
        $this->makeOrder(self::STORE_B, Order::STATUS_COMPLETED, 999.0, now(), now(), $tech2->user_id);

        $this->makeVerification($completedToday, now());
        $this->makeVerification($pendingToday, now()->subDay());

        $request = $this->makeRequest();
        $request->user_id = $user->id;
        $data = $this->body((new StoreManagerController())->overview($request))['data'];

        $this->assertSame(3, $data['today_orders'], '今日订单数=今日创建的 3 单');
        $this->assertSame(100.0, (float) $data['today_revenue'], '今日营收=今日完成 1 单 100');
        $this->assertSame(2, $data['ongoing_orders'], '进行中=pending+serving');
        $this->assertSame(2, $data['technician_count'], '本店曾服务的技师去重数');
        $this->assertSame(1, $data['verification_count'], '今日核销数');
    }

    // ── orders 门店隔离 + status 筛选 ──

    #[Test] public function orders_only_return_own_store(): void
    {
        $user = $this->makeUser(self::STORE_A);
        $aCompleted = $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 100.0);
        $aPaid      = $this->makeOrder(self::STORE_A, Order::STATUS_PAID, 60.0);
        $bCompleted = $this->makeOrder(self::STORE_B, Order::STATUS_COMPLETED, 50.0);
        $bPending   = $this->makeOrder(self::STORE_B, Order::STATUS_PENDING, 0.0);

        $request = $this->makeRequest();
        $request->user_id = $user->id;
        $resp = $this->body((new StoreManagerController())->orders($request));

        $this->assertSame(0, $resp['code']);
        $this->assertSame(2, $resp['meta']['total'], '只返回本店 2 单');
        $ids = array_column($resp['data'], 'id');
        $this->assertSame((int) $aCompleted->id, $this->decodeId((string) $ids[0]), 'id 应为 hashid 编码且可还原');
        $this->assertContains((string) $aPaid->id, array_map(fn($v) => (string) $this->decodeId((string) $v), $ids));
        $this->assertNotContains((string) $bCompleted->id, array_map(fn($v) => (string) $this->decodeId((string) $v), $ids), '他店订单不得出现');
        $this->assertNotContains((string) $bPending->id, array_map(fn($v) => (string) $this->decodeId((string) $v), $ids));

        // status 筛选
        $filtered = $this->body((new StoreManagerController())->orders($this->withUser($this->makeRequest('status=completed'), $user->id)));
        $this->assertSame(1, $filtered['meta']['total']);
        $this->assertSame((string) $aCompleted->id, (string) $this->decodeId((string) $filtered['data'][0]['id']));
    }

    // ── technicians 本店技师 + 今日排班 ──

    #[Test] public function technicians_return_own_store_with_today_schedule(): void
    {
        $user = $this->makeUser(self::STORE_A);
        $techA1 = $this->makeTechnician(User::generateId());
        $techA2 = $this->makeTechnician(User::generateId());
        $techB  = $this->makeTechnician(User::generateId());

        $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 100.0, now(), now(), $techA1->user_id);
        $this->makeOrder(self::STORE_A, Order::STATUS_CONFIRMED, 80.0, now(), now(), $techA2->user_id);
        $this->makeOrder(self::STORE_B, Order::STATUS_COMPLETED, 70.0, now(), now(), $techB->user_id);

        $scheduleA2 = $this->makeSchedule($techA2, date('Y-m-d'));
        $this->makeSchedule($techB, date('Y-m-d')); // 他店技师排班不得出现

        $request = $this->makeRequest();
        $request->user_id = $user->id;
        $data = $this->body((new StoreManagerController())->technicians($request))['data'];

        $this->assertCount(2, $data, '只返回本店 2 名技师');
        $byUser = [];
        foreach ($data as $item) {
            $byUser[(string) $this->decodeId((string) $item['user_id'])] = $item;
        }
        $this->assertArrayHasKey((string) $techA1->user_id, $byUser);
        $this->assertArrayHasKey((string) $techA2->user_id, $byUser);
        $this->assertArrayNotHasKey((string) $techB->user_id, $byUser, '他店技师不得出现');
        $this->assertNull($byUser[(string) $techA1->user_id]['today_schedule'], '无排班时为 null');
        $this->assertNotNull($byUser[(string) $techA2->user_id]['today_schedule'], '有今日排班');
        $this->assertSame((string) $scheduleA2->id, (string) $this->decodeId((string) $byUser[(string) $techA2->user_id]['today_schedule']['id']));
    }

    // ── revenue 近 7 天聚合 ──

    #[Test] public function revenue_aggregates_last_7_days(): void
    {
        $user = $this->makeUser(self::STORE_A);

        $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 50.0, now()->subDays(2), now()->subDays(2));
        $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 150.0, now()->subDays(2), now()->subDays(2));
        $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 300.0, now(), now());
        // 8 天前完成：超出 7 天窗口，不计
        $this->makeOrder(self::STORE_A, Order::STATUS_COMPLETED, 999.0, now()->subDays(8), now()->subDays(8));
        // 今日 pending：未完成，不计
        $this->makeOrder(self::STORE_A, Order::STATUS_PENDING, 888.0, now(), now());
        // 他店：不计
        $this->makeOrder(self::STORE_B, Order::STATUS_COMPLETED, 777.0, now(), now());

        $request = $this->makeRequest();
        $request->user_id = $user->id;
        $data = $this->body((new StoreManagerController())->revenue($request))['data'];

        $this->assertCount(7, $data, '含今天在内共 7 天');

        $byDate = [];
        foreach ($data as $row) {
            $byDate[$row['date']] = $row;
        }
        $this->assertSame(2, $byDate[date('Y-m-d', strtotime('-2 days'))]['order_count']);
        $this->assertSame(200.0, (float) $byDate[date('Y-m-d', strtotime('-2 days'))]['revenue']);
        $this->assertSame(1, $byDate[date('Y-m-d')]['order_count']);
        $this->assertSame(300.0, (float) $byDate[date('Y-m-d')]['revenue']);
        $this->assertSame(0, $byDate[date('Y-m-d', strtotime('-1 day'))]['order_count'], '无数据日期补零');
        $this->assertSame(0.0, (float) $byDate[date('Y-m-d', strtotime('-1 day'))]['revenue']);
    }

    private function withUser(Request $request, string $userId): Request
    {
        $request->user_id = $userId;
        return $request;
    }
}
