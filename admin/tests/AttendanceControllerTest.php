<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\AttendanceController;
use app\common\HashidsService;
use app\model\TechnicianAttendance;
use app\model\TechnicianProfile;
use support\Db;
use support\Request;
use support\Response;

/**
 * 技师考勤管理测试
 *
 * 覆盖：列表按月筛选（含技师姓名搜索、hashid 编码）、
 * 统计聚合（出勤天数 / 总工时 / 平均工时）、非法月份 422。
 * 策略与 TicketSatisfactionTest 一致：真实库 + 事务回滚，不留脏数据。
 */
class AttendanceControllerTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;

    protected function setUp(): void
    {
        if (!self::$dbChecked) {
            self::$dbChecked = true;
            try {
                Db::select('SELECT 1');
                self::$dbReady = true;
            } catch (\Throwable) {
                self::$dbReady = false;
            }
        }
        if (!self::$dbReady) {
            $this->markTestSkipped('数据库不可用');
        }

        $this->bootEloquent();
        Db::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
    }

    /** 重建全局 Eloquent 连接（prefix 空，模型 $table 已内嵌 appointment_ 前缀） */
    private function bootEloquent(): void
    {
        $dbConfig = config('database.connections.default');
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver'    => $dbConfig['driver'] ?? 'mysql',
            'host'      => $dbConfig['host'] ?? getenv('DB_HOST') ?: '127.0.0.1',
            'port'      => $dbConfig['port'] ?? getenv('DB_PORT') ?: '3306',
            'database'  => $dbConfig['database'] ?? getenv('DB_DATABASE') ?: 'appointment',
            'username'  => $dbConfig['username'] ?? getenv('DB_USERNAME') ?: 'root',
            'password'  => $dbConfig['password'] ?? getenv('DB_PASSWORD') ?: '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    private function makeTechnician(string $name): int
    {
        $profile = new TechnicianProfile();
        $profile->id = (int)(8000000000000000 + random_int(1, 9999999));
        $profile->user_id = (string)(9900000000000000 + random_int(1, 999999));
        $profile->real_name = $name;
        $profile->status = 'approved';
        $profile->save();
        return (int)$profile->id;
    }

    private function makeAttendance(int $technicianId, string $date, ?string $checkOutTime = null): void
    {
        $record = new TechnicianAttendance();
        $record->id = (int)(8100000000000000 + random_int(1, 9999999));
        $record->technician_id = $technicianId;
        $record->date = $date;
        $record->check_in_at = $date . ' 09:00:00';
        $record->check_out_at = $checkOutTime !== null ? $date . ' ' . $checkOutTime : null;
        $record->save();
    }

    private function fetchIndex(string $query): array
    {
        $request = new Request("GET /admin/attendance?{$query} HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $response = (new AttendanceController())->index($request);
        return $this->body($response);
    }

    private function fetchStats(string $query): array
    {
        $request = new Request("GET /admin/attendance/stats?{$query} HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $response = (new AttendanceController())->stats($request);
        return $this->body($response);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    #[Test]
    public function index_filters_by_month_and_name(): void
    {
        $zhang = $this->makeTechnician('张打卡');
        $li = $this->makeTechnician('李考勤');
        $this->makeAttendance($zhang, '2026-06-10', '12:00:00');
        $this->makeAttendance($li, '2026-06-11', '11:00:00');
        $this->makeAttendance($zhang, '2026-07-10', '12:00:00'); // 月外，不应出现

        $resp = $this->fetchIndex('date=2026-06');
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(2, $resp['data']['total'], '仅当月 2 条');
        $names = array_column($resp['data']['list'], 'real_name');
        sort($names);
        $this->assertSame(['张打卡', '李考勤'], $names, '应 join 出技师姓名');

        $resp2 = $this->fetchIndex('date=2026-06&name=' . urlencode('张'));
        $this->assertSame(1, $resp2['data']['total'], '姓名搜索应命中 1 条');
        $this->assertSame('张打卡', $resp2['data']['list'][0]['real_name']);
        $this->assertNotNull($resp2['data']['list'][0]['technician_id'], '技师ID应为hashid编码');
        $this->assertSame($zhang, HashidsService::decode((string)$resp2['data']['list'][0]['technician_id']), '技师ID hashid 可还原');
    }

    #[Test]
    public function stats_aggregates_work_days_and_hours(): void
    {
        $zhang = $this->makeTechnician('张打卡');
        $li = $this->makeTechnician('李考勤');
        // 张：2 天，6/10 工时 3h、6/11 工时 2h → 总 5h 平均 2.5h
        $this->makeAttendance($zhang, '2026-06-10', '12:00:00');
        $this->makeAttendance($zhang, '2026-06-11', '11:00:00');
        // 李：1 天，仅上班未下班 → 总工时 0
        $this->makeAttendance($li, '2026-06-12');
        // 月外不计入
        $this->makeAttendance($zhang, '2026-07-01', '12:00:00');

        $resp = $this->fetchStats('date=2026-06');
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('2026-06', $resp['data']['date']);
        $this->assertCount(2, $resp['data']['list'], '仅当月 2 名技师');

        $byName = [];
        foreach ($resp['data']['list'] as $row) {
            $byName[$row['real_name']] = $row;
        }
        $this->assertSame(2, $byName['张打卡']['work_days']);
        $this->assertEquals(5.0, $byName['张打卡']['total_hours']);
        $this->assertEquals(2.5, $byName['张打卡']['avg_hours']);
        $this->assertSame(1, $byName['李考勤']['work_days']);
        $this->assertEquals(0.0, $byName['李考勤']['total_hours']);
        $this->assertEquals(0.0, $byName['李考勤']['avg_hours']);
    }

    #[Test]
    public function invalid_month_returns_422(): void
    {
        $resp = $this->fetchIndex('date=2026-13');
        $this->assertSame(422, $resp['code']);

        $resp2 = $this->fetchStats('date=abc');
        $this->assertSame(422, $resp2['code']);
    }
}
