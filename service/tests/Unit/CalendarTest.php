<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\CalendarController;
use app\model\Order;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 预约月历接口测试（CalendarController）
 *
 * 覆盖：
 * - 月历返回整月每日结构（date/available/slots/booked），无排班日 available=false
 * - 已约时段（paid/confirmed/serving + appointment）从可约时段中排除并计入 booked
 * - 技师不存在/非法 hashid 404、未审核技师 404
 * - 月份/日期参数非法 422
 * - 单日接口：排班时段明细、无排班 available=false、日期非法 422
 *
 * 依赖真实 DB（与 TechnicianWorkTest 同基建）。
 */
class CalendarTest extends TestCase
{
    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    /** @var string[] 用例创建的排班 ID，tearDown 统一清理 */
    private array $scheduleIds = [];

    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->scheduleIds as $id) {
            TechnicianSchedule::where('id', $id)->delete();
        }
        foreach ($this->orderIds as $id) {
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        $this->profileIds = [];
        $this->scheduleIds = [];
        $this->orderIds = [];
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

    private function encodeId(string $id): string
    {
        return (string) Container::get('hashids')->encode((int) $id);
    }

    /** 造技师档案（默认 approved） */
    private function makeTechnician(string $status = 'approved'): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id        = TechnicianProfile::generateId();
        $profile->user_id   = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = '月历测试技师';
        $profile->gender    = 1;
        $profile->status    = $status;
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    /** 造排班：date + time_slots 区间数组 */
    private function makeSchedule(string $technicianId, string $date, array $ranges): TechnicianSchedule
    {
        $schedule = TechnicianSchedule::create([
            'id'            => TechnicianSchedule::generateId(),
            'technician_id' => $technicianId,
            'date'          => $date,
            'time_slots'    => $ranges,
            'status'        => 1,
        ]);
        $this->scheduleIds[] = $schedule->id;
        return $schedule;
    }

    /** 造预约订单 */
    private function makeOrder(string $technicianId, string $serviceTime, string $status = Order::STATUS_CONFIRMED): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_CAL_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => $technicianId,
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

    // ── 月历：整月结构 ──

    #[Test] public function month_returns_daily_structure_for_whole_month(): void
    {
        $tech = $this->makeTechnician();
        $this->makeSchedule($tech->id, '2026-08-01', [
            ['start' => '09:00', 'end' => '12:00'],
        ]);

        $request = $this->makeRequest('month=2026-08');
        $resp = (new CalendarController())->month($this->encodeId($tech->id), $request);

        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $days = $body['data'];
        $this->assertCount(31, $days); // 2026-08 共 31 天

        $first = $days[0];
        $this->assertSame('2026-08-01', $first['date']);
        $this->assertTrue($first['available']);
        $this->assertSame(['09:00', '10:00', '11:00'], $first['slots']);
        $this->assertSame(0, $first['booked']);

        // 无排班日 available=false 且 slots 为空
        $second = $days[1];
        $this->assertSame('2026-08-02', $second['date']);
        $this->assertFalse($second['available']);
        $this->assertSame([], $second['slots']);
        $this->assertSame(0, $second['booked']);
    }

    #[Test] public function month_marks_all_days_unavailable_without_schedule(): void
    {
        $tech = $this->makeTechnician();

        $request = $this->makeRequest('month=2026-08');
        $body = $this->body((new CalendarController())->month($this->encodeId($tech->id), $request));

        $this->assertSame(0, $body['code']);
        $this->assertCount(31, $body['data']);
        foreach ($body['data'] as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('available', $day);
            $this->assertArrayHasKey('slots', $day);
            $this->assertArrayHasKey('booked', $day);
            $this->assertFalse($day['available']);
            $this->assertSame([], $day['slots']);
            $this->assertSame(0, $day['booked']);
        }
    }

    // ── 月历：已约时段排除 ──

    #[Test] public function month_excludes_booked_slots_and_counts_them(): void
    {
        $tech = $this->makeTechnician();
        $this->makeSchedule($tech->id, '2026-08-02', [
            ['start' => '09:00', 'end' => '11:00'], // 槽位 09:00、10:00
        ]);
        // 10:30 的预约占用 10:00 槽位；09:00 的预约占用 09:00 槽位
        $this->makeOrder($tech->id, '2026-08-02 10:30:00', Order::STATUS_CONFIRMED);
        $this->makeOrder($tech->id, '2026-08-02 09:00:00', Order::STATUS_PAID);
        // 已取消/已完成/商品订单不计入
        $this->makeOrder($tech->id, '2026-08-02 09:30:00', Order::STATUS_CANCELLED);
        $this->makeOrder($tech->id, '2026-08-02 10:00:00', Order::STATUS_COMPLETED);
        $product = Order::create([
            'order_no'        => 'ORD_CAL_PROD_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => $tech->id,
            'order_type'      => Order::ORDER_TYPE_PRODUCT,
            'total_amount'    => 50.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 50.0,
            'service_time'    => '2026-08-02 09:30:00',
            'status'          => Order::STATUS_PAID,
        ]);
        $this->orderIds[] = $product->id;
        // 其他技师同日订单不影响
        $other = $this->makeTechnician();
        $this->makeOrder($other->id, '2026-08-02 09:30:00', Order::STATUS_CONFIRMED);

        $request = $this->makeRequest('month=2026-08');
        $body = $this->body((new CalendarController())->month($this->encodeId($tech->id), $request));

        $this->assertSame(0, $body['code']);
        $day = $body['data'][1]; // 2026-08-02
        $this->assertSame('2026-08-02', $day['date']);
        $this->assertFalse($day['available']);
        $this->assertSame([], $day['slots']);
        $this->assertSame(2, $day['booked']);
    }

    #[Test] public function month_partially_booked_day_keeps_free_slots(): void
    {
        $tech = $this->makeTechnician();
        $this->makeSchedule($tech->id, '2026-08-05', [
            ['start' => '09:00', 'end' => '13:00'], // 槽位 09:00..12:00
        ]);
        $this->makeOrder($tech->id, '2026-08-05 10:15:00', Order::STATUS_SERVING);

        $request = $this->makeRequest('month=2026-08');
        $body = $this->body((new CalendarController())->month($this->encodeId($tech->id), $request));

        $this->assertSame(0, $body['code']);
        $day = $body['data'][4]; // 2026-08-05
        $this->assertTrue($day['available']);
        $this->assertSame(['09:00', '11:00', '12:00'], $day['slots']); // 10:00 被占用
        $this->assertSame(1, $day['booked']);
    }

    // ── 月历：技师校验 ──

    #[Test] public function month_rejects_invalid_hashid_and_missing_technician(): void
    {
        // 非法 hashid
        $bad = $this->body((new CalendarController())->month('not-a-valid-hashid', $this->makeRequest('month=2026-08')));
        $this->assertSame(404, $bad['code']);
        $this->assertSame('技师不存在', $bad['message']);

        // 合法 hashid 但技师不存在
        $ghost = $this->encodeId('99000000000009999');
        $missing = $this->body((new CalendarController())->month($ghost, $this->makeRequest('month=2026-08')));
        $this->assertSame(404, $missing['code']);
        $this->assertSame('技师不存在', $missing['message']);
    }

    #[Test] public function month_rejects_unapproved_technician(): void
    {
        $tech = $this->makeTechnician('pending');

        $body = $this->body((new CalendarController())->month($this->encodeId($tech->id), $this->makeRequest('month=2026-08')));

        $this->assertSame(404, $body['code']);
        $this->assertSame('技师不存在', $body['message']);
    }

    // ── 月历：参数校验 ──

    #[Test] public function month_rejects_invalid_month_format(): void
    {
        $tech = $this->makeTechnician();
        $controller = new CalendarController();
        $hashId = $this->encodeId($tech->id);

        foreach (['', '2026-08-01', '2026-13', 'abc', '2026-8'] as $badMonth) {
            $body = $this->body($controller->month($hashId, $this->makeRequest('month=' . urlencode($badMonth))));
            $this->assertSame(422, $body['code'], "month=$badMonth");
            $this->assertSame('月份格式不正确', $body['message']);
        }
    }

    // ── 单日接口 ──

    #[Test] public function day_returns_slots_and_excludes_booked(): void
    {
        $tech = $this->makeTechnician();
        $this->makeSchedule($tech->id, '2026-08-03', [
            ['start' => '09:00', 'end' => '12:00'],
            ['start' => '14:00', 'end' => '15:00'], // 槽位 14:00
        ]);
        $this->makeOrder($tech->id, '2026-08-03 14:20:00', Order::STATUS_CONFIRMED);

        $request = $this->makeRequest('date=2026-08-03');
        $body = $this->body((new CalendarController())->day($this->encodeId($tech->id), $request));

        $this->assertSame(0, $body['code']);
        $this->assertSame('2026-08-03', $body['data']['date']);
        $this->assertTrue($body['data']['available']);
        $this->assertSame(['09:00', '10:00', '11:00'], $body['data']['slots']);
        $this->assertSame(1, $body['data']['booked']);
    }

    #[Test] public function day_returns_unavailable_when_no_schedule(): void
    {
        $tech = $this->makeTechnician();

        $request = $this->makeRequest('date=2026-08-03');
        $body = $this->body((new CalendarController())->day($this->encodeId($tech->id), $request));

        $this->assertSame(0, $body['code']);
        $this->assertFalse($body['data']['available']);
        $this->assertSame([], $body['data']['slots']);
        $this->assertSame(0, $body['data']['booked']);
    }

    #[Test] public function day_rejects_invalid_date(): void
    {
        $tech = $this->makeTechnician();
        $controller = new CalendarController();
        $hashId = $this->encodeId($tech->id);

        foreach (['', '2026-08', '2026-08-32', 'abc', '2026-02-30'] as $badDate) {
            $body = $this->body($controller->day($hashId, $this->makeRequest('date=' . urlencode($badDate))));
            $this->assertSame(422, $body['code'], "date=$badDate");
            $this->assertSame('日期格式不正确', $body['message']);
        }
    }
}
