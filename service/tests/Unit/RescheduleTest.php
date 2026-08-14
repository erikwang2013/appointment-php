<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Notification;
use app\model\Order;
use app\model\OrderReschedule;
use app\model\User;
use app\order\v1\controller\OrderController;
use support\Container;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 预约改期闭环测试（真实 DB / Redis，与 GiftCardRedeemTest 同基建）
 *
 * 覆盖：成功改期（字段正确 + 记录落库 + 通知 + 锁迁移）、非本人 404、
 * 不可改期状态 422、距原服务开始不足 6 小时 422、新时段技师冲突 422、
 * 并发改期（Redis 新时段锁）只成功一次。
 */
class RescheduleTest extends TestCase
{
    /** @var string[] 用例订单 ID，tearDown 统一清理（含改期记录/通知/Redis 锁） */
    private array $orderIds = [];

    /** @var string[] 用例用户 ID */
    private array $userIds = [];

    protected function setUp(): void
    {
        // 清空订阅消息 env：保证改期通知走「仅站内通知」降级路径（不触碰真实微信 HTTP）
        foreach ([
            'WECHAT_SUBSCRIBE_TEMPLATE_ID',
            'WECHAT_SUBSCRIBE_TEMPLATE_RESCHEDULE',
            'WECHAT_SUBSCRIBE_APP_ID',
            'WECHAT_SUBSCRIBE_APP_SECRET',
        ] as $key) {
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderReschedule::where('order_id', $id)->delete();
            Notification::where('order_id', $id)->delete();
            $order = Order::find($id);
            if ($order) {
                // 清理改期成功后仍持有的新时段锁（原时段锁成功路径已释放）
                if ($order->technician_id && $order->service_time) {
                    $slot = date('YmdHi', $order->service_time->getTimestamp());
                    Redis::connection()->del("technician_lock:{$order->technician_id}:{$slot}");
                }
                Redis::connection()->del('order_lock:' . $order->id);
                $order->delete();
            }
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->userIds = [];
    }

    // ── 造数 ──

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

    private function makeUser(): User
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

    private function techId(): int
    {
        return (int) (9900000000000000 + random_int(1, 999999));
    }

    private function makeOrder(User $user, int $technicianId, string $serviceTime, string $status = Order::STATUS_PAID): Order
    {
        $order = Order::create([
            'order_no'        => 'RS_' . uniqid(),
            'user_id'         => $user->id,
            'technician_id'   => (string) $technicianId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'service_time'    => $serviceTime,
            'status'          => $status,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function reschedule(User $user, Order $order, array $post = []): array
    {
        $request = $this->makeRequest($post);
        $request->user_id = $user->id;
        $id = Container::get('hashids')->encode((int) $order->id);
        return $this->body((new OrderController())->reschedule($request, (string) $id));
    }

    // ── 成功改期 ──

    #[Test] public function reschedule_updates_time_writes_record_and_notifies(): void
    {
        $user = $this->makeUser();
        $technicianId = $this->techId();
        $oldTime = date('Y-m-d H:i:s', time() + 48 * 3600);
        $newTime = date('Y-m-d H:i:s', time() + 72 * 3600);
        $order = $this->makeOrder($user, $technicianId, $oldTime);

        // 模拟下单时持有的原时段锁（锁值为用户 ID）
        $oldSlot = date('YmdHi', strtotime($oldTime));
        Redis::connection()->set("technician_lock:{$technicianId}:{$oldSlot}", $user->id, 'EX', 300);

        $resp = $this->reschedule($user, $order, ['new_service_time' => $newTime, 'reason' => '临时有事']);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('改期成功', $resp['message']);
        // 响应中 datetime 序列化为 ISO8601（与既有订单响应一致），按时间戳比对
        $this->assertSame(strtotime($newTime), strtotime((string) ($resp['data']['service_time'] ?? '')), '响应中订单服务时间已更新');

        // 订单落库时间已更新
        $fresh = Order::find($order->id);
        $this->assertSame($newTime, $fresh->service_time->format('Y-m-d H:i:s'));

        // 改期记录落库（old → new 字段正确）
        $record = OrderReschedule::where('order_id', $order->id)->first();
        $this->assertNotNull($record, '改期记录应落库');
        $this->assertSame($oldTime, $record->old_service_time->format('Y-m-d H:i:s'));
        $this->assertSame($newTime, $record->new_service_time->format('Y-m-d H:i:s'));
        $this->assertSame((string) $technicianId, (string) $record->old_technician_id);
        $this->assertSame((string) $technicianId, (string) $record->new_technician_id);
        $this->assertSame('临时有事', $record->reason);

        // 站内通知（订阅消息未配置 → 降级仅站内通知）
        $this->assertSame(1, Notification::where('order_id', $order->id)
            ->where('title', '预约改期成功')
            ->count());

        // 原时段锁释放，新时段锁由本单继续持有
        $redis = Redis::connection();
        $this->assertNull($redis->get("technician_lock:{$technicianId}:{$oldSlot}"), '原时段锁应释放');
        $newSlot = date('YmdHi', strtotime($newTime));
        $this->assertSame((string) $user->id, (string) $redis->get("technician_lock:{$technicianId}:{$newSlot}"), '新时段锁应由本单持有');
    }

    // ── 非本人 404 ──

    #[Test] public function reschedule_rejects_non_owner_with_404(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $order = $this->makeOrder($owner, $this->techId(), date('Y-m-d H:i:s', time() + 48 * 3600));

        $resp = $this->reschedule($other, $order, ['new_service_time' => date('Y-m-d H:i:s', time() + 72 * 3600)]);

        $this->assertSame(404, $resp['code']);
        $this->assertStringContainsString('订单不存在', $resp['message']);
        $this->assertSame(0, OrderReschedule::where('order_id', $order->id)->count());
    }

    // ── 不可改期状态 422 ──

    #[Test] public function reschedule_rejects_terminal_or_active_statuses(): void
    {
        foreach ([Order::STATUS_SERVING, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED] as $status) {
            $user = $this->makeUser();
            $order = $this->makeOrder($user, $this->techId(), date('Y-m-d H:i:s', time() + 48 * 3600), $status);

            $resp = $this->reschedule($user, $order, ['new_service_time' => date('Y-m-d H:i:s', time() + 72 * 3600)]);

            $this->assertSame(422, $resp['code'], "状态 {$status} 应拒绝改期: " . json_encode($resp));
            $this->assertStringContainsString('不可改期', $resp['message']);
        }
    }

    // ── 距原服务开始不足 6 小时 422 ──

    #[Test] public function reschedule_rejects_within_six_hours_of_original(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user, $this->techId(), date('Y-m-d H:i:s', time() + 2 * 3600));

        $resp = $this->reschedule($user, $order, ['new_service_time' => date('Y-m-d H:i:s', time() + 72 * 3600)]);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('6 小时', $resp['message']);
        $this->assertSame(0, OrderReschedule::where('order_id', $order->id)->count());
    }

    // ── 新时段与技师冲突 422 ──

    #[Test] public function reschedule_rejects_when_new_slot_taken_by_other_order(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $technicianId = $this->techId();
        $newTime = date('Y-m-d H:i:s', time() + 72 * 3600);
        $orderA = $this->makeOrder($userA, $technicianId, date('Y-m-d H:i:s', time() + 48 * 3600));
        // 同技师同新时间已有一笔 paid 订单占用
        $this->makeOrder($userB, $technicianId, $newTime);

        $resp = $this->reschedule($userA, $orderA, ['new_service_time' => $newTime]);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('已被预约', $resp['message']);
        $this->assertSame(0, OrderReschedule::where('order_id', $orderA->id)->count());
        // 冲突失败后新时段锁已释放，不残留占用
        $newSlot = date('YmdHi', strtotime($newTime));
        $this->assertNull(Redis::connection()->get("technician_lock:{$technicianId}:{$newSlot}"));
    }

    // ── 并发改期（Redis 新时段锁）只成功一次 ──

    #[Test] public function concurrent_reschedule_only_one_succeeds(): void
    {
        $user = $this->makeUser();
        $technicianId = $this->techId();
        $newTime = date('Y-m-d H:i:s', time() + 72 * 3600);
        $order = $this->makeOrder($user, $technicianId, date('Y-m-d H:i:s', time() + 48 * 3600));

        $first = $this->reschedule($user, $order, ['new_service_time' => $newTime]);
        $second = $this->reschedule($user, $order, ['new_service_time' => $newTime]);

        $this->assertSame(0, $first['code'], json_encode($first));
        $this->assertSame(422, $second['code'], '第二次并发改期应被新时段锁拒绝');
        $this->assertStringContainsString('已被他人锁定', $second['message']);
        $this->assertSame(1, OrderReschedule::where('order_id', $order->id)->count(), '只成功一次，只落一条记录');
    }
}
