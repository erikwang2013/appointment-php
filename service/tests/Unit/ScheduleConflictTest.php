<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\TechnicianSchedule;
use app\technician\v1\controller\ScheduleController;
use support\Model;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 技师排班冲突检测 + 批量排班测试（ScheduleController）
 *
 * 覆盖：
 * - 单日 upsert：提交时间段内部重叠 → 422；不重叠/边界相接可并存
 * - 批量排班：日期段逐日创建；某天已有排班 → 跳过该天；日期段超 7 天 → 422
 *
 * 依赖真实 DB（与 TechnicianWorkTest 同基建）。
 */
class ScheduleConflictTest extends TestCase
{
    /** @var string[] 用例创建的技师 ID，tearDown 统一清理其排班行 */
    private array $technicianIds = [];

    protected function tearDown(): void
    {
        foreach ($this->technicianIds as $id) {
            TechnicianSchedule::where('technician_id', $id)->delete();
        }
        $this->technicianIds = [];
    }

    private function makeRequest(array $post = [], string $method = 'PUT'): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("$method / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    /** 造一个独立技师 ID（无需档案行，排班表无外键依赖） */
    private function makeTechnicianId(): string
    {
        $id = (string) Model::generateId();
        $this->technicianIds[] = $id;
        return $id;
    }

    // ── 单日 upsert 冲突检测 ──

    #[Test] public function update_rejects_overlapping_slots_with_422(): void
    {
        $request = $this->makeRequest([
            'date' => '2026-08-20',
            'time_slots' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '10:00', 'end' => '13:00'], // 与上一条重叠
            ],
        ]);
        $request->technician_id = $this->makeTechnicianId();

        $resp = $this->body((new ScheduleController())->update($request));

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('与已有排班时间冲突：09:00-12:00', $resp['message']);
    }

    #[Test] public function update_allows_non_overlapping_and_adjacent_slots(): void
    {
        $technicianId = $this->makeTechnicianId();
        $request = $this->makeRequest([
            'date' => '2026-08-20',
            'time_slots' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '12:00', 'end' => '14:00'], // 边界相接，不冲突
                ['start' => '14:30', 'end' => '18:00'],
            ],
        ]);
        $request->technician_id = $technicianId;

        $resp = $this->body((new ScheduleController())->update($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $slots = TechnicianSchedule::where('technician_id', $technicianId)
            ->where('date', '2026-08-20')->first()->time_slots;
        $this->assertCount(3, $slots);
    }

    // ── 批量排班 ──

    #[Test] public function batch_creates_schedule_for_each_day_in_range(): void
    {
        $technicianId = $this->makeTechnicianId();
        $request = $this->makeRequest([
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'time_slots' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '14:00', 'end' => '18:00'],
            ],
        ], 'POST');
        $request->technician_id = $technicianId;

        $resp = $this->body((new ScheduleController())->batch($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertCount(3, $resp['data']['created']);
        $this->assertSame([], $resp['data']['skipped']);
        $this->assertSame(3, TechnicianSchedule::where('technician_id', $technicianId)->count());
    }

    #[Test] public function batch_skips_days_with_existing_schedule(): void
    {
        $technicianId = $this->makeTechnicianId();
        TechnicianSchedule::create([
            'id' => TechnicianSchedule::generateId(),
            'technician_id' => $technicianId,
            'date' => '2026-08-21', // 中间一天已有排班
            'time_slots' => [['start' => '09:00', 'end' => '12:00']],
            'status' => 1,
        ]);

        $request = $this->makeRequest([
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'time_slots' => [['start' => '09:00', 'end' => '12:00']],
        ], 'POST');
        $request->technician_id = $technicianId;

        $resp = $this->body((new ScheduleController())->batch($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(['2026-08-20', '2026-08-22'], $resp['data']['created']);
        $this->assertSame([['date' => '2026-08-21', 'reason' => '该日期已有排班']], $resp['data']['skipped']);
        $this->assertStringContainsString('成功 2 天，跳过 1 天', $resp['message']);
    }

    #[Test] public function batch_rejects_date_range_over_7_days(): void
    {
        $request = $this->makeRequest([
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-27', // 8 天，超上限
            'time_slots' => [['start' => '09:00', 'end' => '12:00']],
        ], 'POST');
        $request->technician_id = $this->makeTechnicianId();

        $resp = $this->body((new ScheduleController())->batch($request));

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('最多支持 7 天', $resp['message']);
    }
}
