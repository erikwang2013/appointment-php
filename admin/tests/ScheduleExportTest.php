<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\TechnicianScheduleController;
use app\common\HashidsService;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use app\model\User;
use support\Db;
use support\Request;
use support\Response;

/**
 * 技师排班 CSV 导出测试（R24 排班导出）
 *
 * 覆盖：
 *   - 导出成功：BOM + 表头 + 时间段明细解析（time_slots JSON）
 *   - 参数校验：缺日期 / 非法日期 / 跨度 >31 天 / end<start / 非法技师 hashid → 422
 *   - 按技师筛选导出
 *
 * 策略：真实库 + 事务回滚，不留脏数据；导出临时文件在 tearDown 删除；
 * DB 不可用时整体跳过。
 */
class ScheduleExportTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;

    private string $dateStart = '2030-02-01';
    private string $dateEnd   = '2030-02-28';
    private int $techId;
    private string $techHashid;
    private int $otherTechId;
    /** @var string[] 本次测试生成的导出文件，tearDown 清理 */
    private array $tmpFiles = [];

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

        // 自足 Eloquent 连接：Capsule 静态单例可能被其他测试类覆盖，
        // 这里显式重建 —— 模型 $table 已内嵌 appointment_ 前缀，prefix 必须留空
        $this->bootEloquent();

        Db::beginTransaction();

        $user = new User();
        $user->id = 90000000000001201;
        $user->phone = '139' . substr(uniqid(), -8);
        $user->nickname = '导出测试用户';
        $user->password = password_hash('123456', PASSWORD_DEFAULT);
        $user->status = 1;
        $user->user_type = 'technician';
        $user->save();

        $profile = new TechnicianProfile();
        $profile->id = 90000000000001202;
        $profile->user_id = $user->id;
        $profile->real_name = '导出测试技师';
        $profile->status = 'approved';
        $profile->save();

        $user2 = new User();
        $user2->id = 90000000000001203;
        $user2->phone = '138' . substr(uniqid(), -8);
        $user2->nickname = '导出测试用户B';
        $user2->password = password_hash('123456', PASSWORD_DEFAULT);
        $user2->status = 1;
        $user2->user_type = 'technician';
        $user2->save();

        $profile2 = new TechnicianProfile();
        $profile2->id = 90000000000001204;
        $profile2->user_id = $user2->id;
        $profile2->real_name = '导出测试技师B';
        $profile2->status = 'approved';
        $profile2->save();

        $this->techId = (int) $profile->id;
        $this->techHashid = HashidsService::encode($this->techId);
        $this->otherTechId = (int) $profile2->id;
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
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

    private function makeRequest(string $method, string $path, array $get = []): Request
    {
        $request = new Request("$method $path HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $request->setGet($get);
        $request->user_id = 10000000000000001;
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true);
    }

    private function controller(): TechnicianScheduleController
    {
        return new TechnicianScheduleController();
    }

    private function createScheduleRow(int $techId, string $date, array $slots): TechnicianSchedule
    {
        $schedule = new TechnicianSchedule();
        $schedule->id = TechnicianSchedule::generateId();
        $schedule->technician_id = (string) $techId;
        $schedule->date = $date;
        $schedule->time_slots = $slots;
        $schedule->status = 1;
        $schedule->save();
        return $schedule;
    }

    /**
     * 调用 export 并读取落盘的 CSV 内容（download 响应体在 workerman 中为文件流）
     */
    private function exportCsv(array $get): array
    {
        $resp = $this->controller()->export($this->makeRequest('GET', '/admin/technician-schedule/export', $get));

        $files = glob(runtime_path() . '/tmp/schedules_*.csv') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $latest = $files[0] ?? null;
        if ($latest !== null) {
            $this->tmpFiles[] = $latest;
        }

        return [$resp, $latest];
    }

    // ── 导出成功 ──

    #[Test]
    public function export_returns_bom_header_and_slot_rows(): void
    {
        $this->createScheduleRow($this->techId, '2030-02-10', [
            ['start' => '09:00', 'end' => '12:00'],
            ['start' => '14:00', 'end' => '18:00'],
        ]);
        $this->createScheduleRow($this->techId, '2030-02-11', [
            ['start' => '10:00', 'end' => '11:00'],
        ]);

        [$resp, $file] = $this->exportCsv(['start_date' => $this->dateStart, 'end_date' => $this->dateEnd]);
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertNotNull($file, '应生成 CSV 临时文件');
        $this->assertStringContainsString(
            'schedules_' . date('Ymd'),
            basename($file),
            '文件名应为 schedules_YYYYmmddHHMMSS.csv'
        );
        $disposition = '';
        foreach ($resp->getHeaders() as $name => $value) {
            if (strcasecmp((string) $name, 'Content-Disposition') === 0) {
                $disposition = (string) $value;
                break;
            }
        }
        $this->assertStringContainsString('schedules_', $disposition);

        $content = file_get_contents($file);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'CSV 应带 UTF-8 BOM');

        $lines = array_map('str_getcsv', explode("\n", trim(substr($content, 3))));
        $this->assertSame(['技师ID', '技师姓名', '日期', '时间段明细'], $lines[0], '表头列');

        $this->assertSame((string) $this->techId, $lines[1][0], '技师ID 列');
        $this->assertSame('导出测试技师', $lines[1][1]);
        $this->assertSame('2030-02-10', $lines[1][2]);
        $this->assertSame('09:00-12:00, 14:00-18:00', $lines[1][3], 'time_slots JSON 应解析为时间段明细');
        $this->assertSame('2030-02-11', $lines[2][2]);
        $this->assertSame('10:00-11:00', $lines[2][3]);
    }

    // ── 参数校验 ──

    #[Test]
    public function export_validates_dates_and_span(): void
    {
        $c = $this->controller();

        // 缺 start_date / end_date
        $noStart = $c->export($this->makeRequest('GET', '/admin/technician-schedule/export', ['end_date' => $this->dateEnd]));
        $this->assertSame(422, $this->body($noStart)['code']);
        $noEnd = $c->export($this->makeRequest('GET', '/admin/technician-schedule/export', ['start_date' => $this->dateStart]));
        $this->assertSame(422, $this->body($noEnd)['code']);

        // 非法日期格式
        $badFormat = $c->export($this->makeRequest('GET', '/admin/technician-schedule/export', [
            'start_date' => '2030/02/01', 'end_date' => $this->dateEnd,
        ]));
        $this->assertSame(422, $this->body($badFormat)['code']);

        // 不存在的日期
        $badDate = $c->export($this->makeRequest('GET', '/admin/technician-schedule/export', [
            'start_date' => '2030-02-31', 'end_date' => $this->dateEnd,
        ]));
        $this->assertSame(422, $this->body($badDate)['code']);

        // end < start
        $inverted = $c->export($this->makeRequest('GET', '/admin/technician-schedule/export', [
            'start_date' => '2030-02-28', 'end_date' => '2030-02-01',
        ]));
        $this->assertSame(422, $this->body($inverted)['code']);

        // 跨度 > 31 天（2月1日 → 3月10日 = 37 天）
        $tooLong = $c->export($this->makeRequest('GET', '/admin/technician-schedule/export', [
            'start_date' => '2030-02-01', 'end_date' => '2030-03-10',
        ]));
        $this->assertSame(422, $this->body($tooLong)['code']);

        // 非法技师 hashid
        $badTech = $c->export($this->makeRequest('GET', '/admin/technician-schedule/export', [
            'start_date' => $this->dateStart, 'end_date' => $this->dateEnd, 'technician_id' => 'bogus',
        ]));
        $this->assertSame(422, $this->body($badTech)['code']);
    }

    // ── 按技师筛选 ──

    #[Test]
    public function export_filters_by_technician(): void
    {
        $this->createScheduleRow($this->techId, '2030-02-10', [['start' => '09:00', 'end' => '12:00']]);
        $this->createScheduleRow($this->otherTechId, '2030-02-11', [['start' => '13:00', 'end' => '17:00']]);

        [$resp, $file] = $this->exportCsv([
            'start_date'    => $this->dateStart,
            'end_date'      => $this->dateEnd,
            'technician_id' => $this->techHashid,
        ]);
        $this->assertSame(200, $resp->getStatusCode());

        $lines = array_map('str_getcsv', explode("\n", trim(substr(file_get_contents($file), 3))));
        $this->assertCount(2, $lines, '表头 + 仅本技师 1 行');
        $this->assertSame('2030-02-10', $lines[1][2]);
        $this->assertSame('导出测试技师', $lines[1][1]);
    }
}
