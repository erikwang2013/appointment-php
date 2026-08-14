<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Notification;
use app\model\Order;
use app\model\TechnicianProfile;
use app\technician\v1\controller\WorkController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 技师工作台闭环测试（WorkController）
 *
 * 覆盖：
 * - 今日任务：只返回我的 confirmed/serving 且服务时间为今日的订单
 * - 开始服务：confirmed→serving + service_start_at + 通知用户；非本人 403；状态错误 422；重复开始幂等
 * - 完成服务：serving→completed + service_end_at；状态错误 422
 * - 完成记录：分页只含 serving/completed
 * - 无效 hashid 返回 422
 *
 * 依赖真实 DB（与 OrderVerificationFlowTest 同基建）。
 */
class TechnicianWorkTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            Notification::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->profileIds = [];
    }

    private function makeRequest(string $method = 'GET', string $query = ''): Request
    {
        $target = '/' . ($query !== '' ? '?' . $query : '');
        return new Request("$method $target HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function encodeId(string $id): string
    {
        return (string) Container::get('hashids')->encode((int) $id);
    }

    /** 造已审核技师档案 */
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

    /** 造订单（默认：我的、今天、confirmed） */
    private function makeOrder(string $technicianId, string $status = Order::STATUS_CONFIRMED, mixed $serviceTime = null): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_WORK_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => $technicianId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'service_time'    => $serviceTime ?? now(),
            'status'          => $status,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    // ── 今日任务 ──

    #[Test] public function today_returns_my_confirmed_and_serving_orders(): void
    {
        $tech = $this->makeTechnician();
        $other = $this->makeTechnician();
        $mineConfirmed = $this->makeOrder($tech->id, Order::STATUS_CONFIRMED);
        $mineServing = $this->makeOrder($tech->id, Order::STATUS_SERVING);
        $minePaid = $this->makeOrder($tech->id, Order::STATUS_PAID);            // 状态不符，排除
        $mineTomorrow = $this->makeOrder($tech->id, Order::STATUS_CONFIRMED, now()->addDay()); // 非今日，排除
        $this->makeOrder($other->id, Order::STATUS_CONFIRMED);                  // 非本人，排除

        $request = $this->makeRequest();
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->today($request);

        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $ids = array_column($body['data'] ?? [], 'id');
        $this->assertContains($this->encodeId($mineConfirmed->id), $ids);
        $this->assertContains($this->encodeId($mineServing->id), $ids);
        $this->assertNotContains($this->encodeId($minePaid->id), $ids);
        $this->assertNotContains($this->encodeId($mineTomorrow->id), $ids);
    }

    // ── 开始服务 ──

    #[Test] public function start_transitions_confirmed_to_serving_and_notifies(): void
    {
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($tech->id, Order::STATUS_CONFIRMED);

        $request = $this->makeRequest('POST');
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->start($request, $this->encodeId($order->id));

        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $this->assertSame('开始服务成功', $body['message']);

        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_SERVING, $fresh->status);
        $this->assertNotNull($fresh->service_start_at);

        // 站内消息通知用户
        $this->assertTrue(
            Notification::where('order_id', $order->id)->where('type', 'order')->exists()
        );
    }

    #[Test] public function start_rejected_for_non_owner_technician(): void
    {
        $owner = $this->makeTechnician();
        $intruder = $this->makeTechnician();
        $order = $this->makeOrder($owner->id, Order::STATUS_CONFIRMED);

        $request = $this->makeRequest('POST');
        $request->technician_id = $intruder->id;
        $resp = (new WorkController())->start($request, $this->encodeId($order->id));

        $body = $this->body($resp);
        $this->assertSame(403, $body['code']);
        $this->assertSame('无权操作该订单', $body['message']);
        $this->assertSame(Order::STATUS_CONFIRMED, Order::find($order->id)->status);
    }

    #[Test] public function start_rejected_when_status_not_confirmed(): void
    {
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($tech->id, Order::STATUS_PAID);

        $request = $this->makeRequest('POST');
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->start($request, $this->encodeId($order->id));

        $body = $this->body($resp);
        $this->assertSame(422, $body['code']);
        $this->assertSame('当前订单状态不可开始服务', $body['message']);
        $this->assertSame(Order::STATUS_PAID, Order::find($order->id)->status);
    }

    #[Test] public function start_idempotent_when_already_serving(): void
    {
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($tech->id, Order::STATUS_SERVING);

        $request = $this->makeRequest('POST');
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->start($request, $this->encodeId($order->id));

        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $this->assertSame('服务已在进行中', $body['message']);
        $this->assertSame(Order::STATUS_SERVING, Order::find($order->id)->status);
    }

    // ── 完成服务 ──

    #[Test] public function complete_transitions_serving_to_completed(): void
    {
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($tech->id, Order::STATUS_SERVING);

        $request = $this->makeRequest('POST');
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->complete($request, $this->encodeId($order->id));

        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $this->assertSame('完成服务成功', $body['message']);

        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->service_end_at);

        // 站内消息通知用户
        $this->assertTrue(
            Notification::where('order_id', $order->id)->where('type', 'order')->exists()
        );
    }

    #[Test] public function complete_rejected_when_status_not_serving(): void
    {
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($tech->id, Order::STATUS_CONFIRMED);

        $request = $this->makeRequest('POST');
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->complete($request, $this->encodeId($order->id));

        $body = $this->body($resp);
        $this->assertSame(422, $body['code']);
        $this->assertSame('当前订单状态不可完成服务', $body['message']);
        $this->assertSame(Order::STATUS_CONFIRMED, Order::find($order->id)->status);
    }

    // ── 完成记录（分页）──

    #[Test] public function records_paginated_and_filters_by_status(): void
    {
        $tech = $this->makeTechnician();
        $done1 = $this->makeOrder($tech->id, Order::STATUS_COMPLETED);
        $doing = $this->makeOrder($tech->id, Order::STATUS_SERVING);
        $this->makeOrder($tech->id, Order::STATUS_CONFIRMED); // 未进入闭环，排除
        $this->makeOrder($tech->id, Order::STATUS_PAID);      // 未进入闭环，排除

        $request = $this->makeRequest('GET', 'page=1&per_page=2');
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->records($request);

        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $ids = array_column($body['data'] ?? [], 'id');
        $this->assertContains($this->encodeId($done1->id), $ids);
        $this->assertContains($this->encodeId($doing->id), $ids);
        $this->assertSame(2, $body['meta']['total']);
        $this->assertFalse($body['meta']['has_more']);
    }

    #[Test] public function records_paginates_past_first_page(): void
    {
        $tech = $this->makeTechnician();
        for ($i = 0; $i < 3; $i++) {
            $this->makeOrder($tech->id, Order::STATUS_COMPLETED);
        }

        $request = $this->makeRequest('GET', 'page=2&per_page=2');
        $request->technician_id = $tech->id;
        $body = $this->body((new WorkController())->records($request));

        $this->assertSame(0, $body['code']);
        $this->assertSame(3, $body['meta']['total']);
        $this->assertCount(1, $body['data']);
    }

    // ── 无效 hashid ──

    #[Test] public function start_rejected_for_invalid_hashid(): void
    {
        $tech = $this->makeTechnician();

        $request = $this->makeRequest('POST');
        $request->technician_id = $tech->id;
        $resp = (new WorkController())->start($request, 'not-a-valid-hashid');

        $body = $this->body($resp);
        $this->assertSame(422, $body['code']);
        $this->assertSame('无效的订单ID', $body['message']);
    }
}
