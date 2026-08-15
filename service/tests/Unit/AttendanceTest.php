<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\TechnicianAttendance;
use app\technician\v1\controller\AttendanceController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 技师考勤打卡闭环测试
 *
 * 覆盖：上班打卡落库、重复上班 422、未上班直接下班 422、
 * 正常下班（含迟到标记 + 重复下班 422）、我的考勤月列表与汇总。
 * 基建与 TicketTest 一致（真实 DB + tearDown 清理，身份走 $request->technician_id）。
 */
class AttendanceTest extends TestCase
{
    /** @var int[] 用例创建的考勤记录 ID，tearDown 统一清理 */
    private array $attendanceIds = [];

    protected function tearDown(): void
    {
        foreach ($this->attendanceIds as $id) {
            TechnicianAttendance::where('id', $id)->delete();
        }
        $this->attendanceIds = [];
    }

    private function makeRequest(array $post = [], string $method = 'POST'): Request
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

    private function controller(): AttendanceController
    {
        return new AttendanceController();
    }

    private function newTechnicianId(): int
    {
        return (int)(9000000000000000 + random_int(1, 999999));
    }

    /** 直接插入一条当日考勤记录（绕过控制器） */
    private function makeTodayRecord(int $technicianId, array $extra = []): TechnicianAttendance
    {
        $record = TechnicianAttendance::create(array_merge([
            'id'            => TechnicianAttendance::generateId(),
            'technician_id' => $technicianId,
            'date'          => date('Y-m-d'),
            'check_in_at'   => date('Y-m-d 09:00:00'),
        ], $extra));
        $this->attendanceIds[] = $record->id;
        return $record;
    }

    // ── 上班打卡 ──

    #[Test] public function check_in_creates_record(): void
    {
        $technicianId = $this->newTechnicianId();
        $request = $this->makeRequest();
        $request->technician_id = $technicianId;

        $resp = $this->body($this->controller()->checkIn($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $row = TechnicianAttendance::where('technician_id', $technicianId)->where('date', date('Y-m-d'))->first();
        $this->assertNotNull($row, '应落库一条当日记录');
        $this->attendanceIds[] = $row->id;
        $this->assertSame('normal', $row->status);
        $this->assertNotNull($row->check_in_at);
        $this->assertNull($row->check_out_at);
        $this->assertSame((int)$row->id, (int)Container::get('hashids')->decode((string)$resp['data']['id'])[0], 'id 应为 hashid 且可还原');
    }

    #[Test] public function check_in_duplicate_returns_422(): void
    {
        $technicianId = $this->newTechnicianId();
        $this->makeTodayRecord($technicianId);

        $request = $this->makeRequest();
        $request->technician_id = $technicianId;
        $resp = $this->body($this->controller()->checkIn($request));

        $this->assertSame(422, $resp['code']);
        $this->assertSame(1, TechnicianAttendance::where('technician_id', $technicianId)->where('date', date('Y-m-d'))->count(), '不得重复落库');
    }

    // ── 下班打卡 ──

    #[Test] public function check_out_without_check_in_returns_422(): void
    {
        $technicianId = $this->newTechnicianId();
        $request = $this->makeRequest();
        $request->technician_id = $technicianId;

        $resp = $this->body($this->controller()->checkOut($request));

        $this->assertSame(422, $resp['code']);
        $this->assertSame('请先上班打卡', $resp['message']);
    }

    #[Test] public function check_out_sets_time_and_marks_late(): void
    {
        $technicianId = $this->newTechnicianId();
        $this->makeTodayRecord($technicianId, ['check_in_at' => date('Y-m-d 10:30:00')]);

        $request = $this->makeRequest();
        $request->technician_id = $technicianId;
        $resp = $this->body($this->controller()->checkOut($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $row = TechnicianAttendance::find($this->attendanceIds[0]);
        $this->assertNotNull($row->check_out_at, '下班时间应落库');
        $this->assertSame('late', $row->status, '10:30 上班应标记迟到');

        // 重复下班 → 422
        $resp2 = $this->body($this->controller()->checkOut($request));
        $this->assertSame(422, $resp2['code']);
        $this->assertSame(1, TechnicianAttendance::where('technician_id', $technicianId)->count());
    }

    // ── 我的考勤 ──

    #[Test] public function index_returns_month_list_and_summary(): void
    {
        $technicianId = $this->newTechnicianId();
        // 固定月份构造：6/10 完整工时 3 小时，6/11 仅上班，7/10 落在月外
        $records = [];
        foreach (['2026-06-10' => '12:00:00', '2026-06-11' => null, '2026-07-10' => '11:00:00'] as $date => $checkOut) {
            $records[] = TechnicianAttendance::create([
                'id'            => TechnicianAttendance::generateId(),
                'technician_id' => $technicianId,
                'date'          => $date,
                'check_in_at'   => $date . ' 09:00:00',
                'check_out_at'  => $checkOut ? $date . ' ' . $checkOut : null,
            ]);
            $this->attendanceIds[] = $records[count($records) - 1]->id;
        }

        $request = $this->makeRequest(['month' => '2026-06'], 'GET');
        $request->technician_id = $technicianId;
        $resp = $this->body($this->controller()->index($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('2026-06', $resp['data']['month']);
        $this->assertCount(2, $resp['data']['list'], '仅返回当月记录');
        $this->assertSame(2, $resp['data']['summary']['work_days']);
        $this->assertEquals(3.0, $resp['data']['summary']['total_hours'], '6/10 完整工时 3 小时');
        $this->assertEquals(1.5, $resp['data']['summary']['avg_hours'], '2 天平均 1.5 小时');

        // 非法月份 → 422
        $request2 = $this->makeRequest(['month' => '2026-13'], 'GET');
        $request2->technician_id = $technicianId;
        $resp2 = $this->body($this->controller()->index($request2));
        $this->assertSame(422, $resp2['code']);
    }
}
