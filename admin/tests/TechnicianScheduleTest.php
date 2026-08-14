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
use app\model\Order;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use app\model\User;
use support\Db;
use support\Request;
use support\Response;

/**
 * 技师排班管理控制器测试（S7 排班管理闭环）
 *
 * 覆盖：
 *   - 创建排班（store）成功与字段校验
 *   - 同技师同日期重复提交 → 幂等更新（UNIQUE(technician_id,date)）
 *   - 1062 唯一键冲突判定（isDuplicateEntry 私有方法，反射）
 *   - 删除排班（仅删排班行）
 *   - 设为休息（status=0）
 *   - 列表联查当日预约占用（bookings）
 *
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class TechnicianScheduleTest extends TestCase
{
    private const ADMIN_ID = 10000000000000001;

    private static bool $dbReady = false;
    private static bool $dbChecked = false;
    private string $date = '2030-01-15';
    private string $occupiedDate = '2030-01-16';
    private int $userId;
    private string $techHashid;
    private int $techId;

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

        // 自足 Eloquent 连接：Capsule 静态单例可能被其他测试类（含并行开发中的用例）
        // 用不同 prefix 覆盖，这里显式重建 —— 模型 $table 已内嵌 erik_ 前缀，prefix 必须留空
        $this->bootEloquent();

        Db::beginTransaction();

        // 测试用户 + 技师档案（real_name 走 Encryptable 加密，正常赋值即可）
        $user = new User();
        $user->id = 90000000000001001;
        $user->phone = '139' . substr(uniqid(), -8);
        $user->nickname = '排班测试用户';
        $user->password = password_hash('123456', PASSWORD_DEFAULT);
        $user->status = 1;
        $user->user_type = 'technician';
        $user->save();

        $profile = new TechnicianProfile();
        $profile->id = 90000000000001002;
        $profile->user_id = $user->id;
        $profile->real_name = '排班测试技师';
        $profile->status = 'approved';
        $profile->save();

        $this->userId = (int) $user->id;
        $this->techId = (int) $profile->id;
        $this->techHashid = HashidsService::encode($this->techId);
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
    }

    // ── 工具方法 ──

    /** 重建全局 Eloquent 连接（prefix 空，模型 $table 已内嵌 erik_ 前缀） */
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

    private function makeRequest(string $method, string $path, array $post = [], array $get = []): Request
    {
        $request = new Request("$method $path HTTP/1.1\r\nHost: localhost\r\n\r\n");
        if ($post) {
            $request->setPost($post);
        }
        if ($get) {
            $request->setGet($get);
        }
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

    /** 直接插入一条排班（绕过控制器，用于幂等/占用场景） */
    private function createScheduleRow(int $techId, string $date, array $slots = [], int $status = 1): TechnicianSchedule
    {
        $schedule = new TechnicianSchedule();
        $schedule->id = TechnicianSchedule::generateId();
        $schedule->technician_id = (string) $techId;
        $schedule->date = $date;
        $schedule->time_slots = $slots ?: [['start' => '09:00', 'end' => '12:00']];
        $schedule->status = $status;
        $schedule->save();
        return $schedule;
    }

    /** 直接插入一条预约订单（占位） */
    private function createOrderRow(int $techId, string $date, string $serviceTime, string $status = 'paid'): Order
    {
        $order = new Order();
        $order->id = Order::generateId();
        $order->order_no = 'TEST' . uniqid();
        $order->user_id = (string) $this->userId;
        $order->technician_id = (string) $techId;
        $order->order_type = Order::ORDER_TYPE_APPOINTMENT;
        $order->status = $status;
        $order->service_time = $serviceTime;
        $order->save();
        return $order;
    }

    // ── 创建 ──

    #[Test]
    public function store_creates_schedule(): void
    {
        $request = $this->makeRequest('POST', '/admin/schedules', [
            'technician_id' => $this->techHashid,
            'date'          => $this->date,
            'time_slots'    => [['start' => '09:00', 'end' => '12:00'], ['start' => '14:00', 'end' => '18:00']],
        ]);
        $resp = $this->controller()->store($request);
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame($this->date, $data['data']['date']);
        $this->assertSame(1, $data['data']['status']);
        $this->assertCount(2, $data['data']['time_slots']);

        $this->assertSame(
            1,
            TechnicianSchedule::where('technician_id', $this->techId)
                ->where('date', $this->date)->count(),
            'UNIQUE(technician_id,date) 单日应仅一条'
        );
    }

    #[Test]
    public function store_upsert_is_idempotent(): void
    {
        $this->createScheduleRow($this->techId, $this->date, [['start' => '09:00', 'end' => '12:00']]);

        // 同一技师同一日期重复提交 → 更新而非新增（幂等）
        $request = $this->makeRequest('POST', '/admin/schedules', [
            'technician_id' => $this->techHashid,
            'date'          => $this->date,
            'time_slots'    => [['start' => '10:00', 'end' => '11:00']],
            'status'        => 0,
        ]);
        $resp = $this->controller()->store($request);
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame(0, $data['data']['status']);
        $this->assertSame([['start' => '10:00', 'end' => '11:00']], $data['data']['time_slots']);

        $this->assertSame(
            1,
            TechnicianSchedule::where('technician_id', $this->techId)
                ->where('date', $this->date)->count(),
            '重复提交不得产生第二行'
        );
    }

    #[Test]
    public function store_validates_inputs(): void
    {
        $c = $this->controller();

        // 无效技师 ID
        $badTech = $this->makeRequest('POST', '/admin/schedules', [
            'technician_id' => 'not-a-hashid', 'date' => $this->date, 'time_slots' => [],
        ]);
        $this->assertSame(422, $this->body($c->store($badTech))['code']);

        // 技师不存在（合法 hashid 解码后无记录）
        $ghost = HashidsService::encode(90000000000009999);
        $noTech = $this->makeRequest('POST', '/admin/schedules', [
            'technician_id' => $ghost, 'date' => $this->date, 'time_slots' => [],
        ]);
        $this->assertSame(404, $this->body($c->store($noTech))['code']);

        // 日期格式错误
        $badDate = $this->makeRequest('POST', '/admin/schedules', [
            'technician_id' => $this->techHashid, 'date' => '2030/01/15', 'time_slots' => [],
        ]);
        $this->assertSame(422, $this->body($c->store($badDate))['code']);

        // time_slots 格式错误（缺 end / end <= start）
        $badSlots = $this->makeRequest('POST', '/admin/schedules', [
            'technician_id' => $this->techHashid, 'date' => $this->date,
            'time_slots'    => [['start' => '09:00']],
        ]);
        $this->assertSame(422, $this->body($c->store($badSlots))['code']);

        $inverted = $this->makeRequest('POST', '/admin/schedules', [
            'technician_id' => $this->techHashid, 'date' => $this->date,
            'time_slots'    => [['start' => '12:00', 'end' => '09:00']],
        ]);
        $this->assertSame(422, $this->body($c->store($inverted))['code']);
    }

    #[Test]
    public function is_duplicate_entry_detects_1062(): void
    {
        $reflection = new \ReflectionMethod(TechnicianScheduleController::class, 'isDuplicateEntry');
        $reflection->setAccessible(true);

        // 真实 MySQL 报错信息形态：SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry ...
        $pdo = new \PDOException(
            "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '900-2030-01-15' for key 'uk_tech_date'",
            23000
        );
        $this->assertTrue($reflection->invoke(new TechnicianScheduleController(), $pdo));

        $other = new \RuntimeException('boom');
        $this->assertFalse($reflection->invoke(new TechnicianScheduleController(), $other));
    }

    // ── 删除 ──

    #[Test]
    public function destroy_removes_only_schedule_row(): void
    {
        $schedule = $this->createScheduleRow($this->techId, $this->date);
        // 同日订单保留，删除排班不得影响订单
        $order = $this->createOrderRow($this->techId, $this->date, $this->date . ' 10:00:00');

        $hashid = HashidsService::encode((int) $schedule->id);
        $resp = $this->controller()->destroy($this->makeRequest('DELETE', '/admin/schedules/' . $hashid), $hashid);
        $this->assertSame(0, $this->body($resp)['code']);

        $this->assertNull(TechnicianSchedule::find($schedule->id), '排班行应已删除');
        $this->assertNotNull(Order::find($order->id), '订单不得被连带删除');
    }

    #[Test]
    public function destroy_missing_schedule_returns_404(): void
    {
        $hashid = HashidsService::encode(90000000000007777);
        $resp = $this->controller()->destroy($this->makeRequest('DELETE', '/admin/schedules/' . $hashid), $hashid);
        $this->assertSame(404, $this->body($resp)['code']);
    }

    // ── 设为休息 ──

    #[Test]
    public function set_rest_marks_status_zero(): void
    {
        $schedule = $this->createScheduleRow($this->techId, $this->date, [['start' => '09:00', 'end' => '18:00']], 1);

        $hashid = HashidsService::encode((int) $schedule->id);
        $resp = $this->controller()->setRest($this->makeRequest('PUT', '/admin/schedules/' . $hashid . '/rest'), $hashid);
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame(0, $data['data']['status']);
        $this->assertNotEmpty($data['data']['time_slots'], '设为休息应保留时间段');
    }

    // ── 列表 + 预约占用闭环 ──

    #[Test]
    public function index_returns_bookings_for_occupied_day(): void
    {
        $this->createScheduleRow($this->techId, $this->date);
        $this->createScheduleRow($this->techId, $this->occupiedDate);

        // 当日占位订单：有效（paid）计入
        $this->createOrderRow($this->techId, $this->occupiedDate, $this->occupiedDate . ' 10:00:00', 'paid');
        // 已取消订单：不计入占位
        $this->createOrderRow($this->techId, $this->occupiedDate, $this->occupiedDate . ' 11:00:00', 'cancelled');
        // 无排班日的订单：不归属任何排班日
        $this->createOrderRow($this->techId, $this->date, '2030-01-17 10:00:00', 'paid');

        // 限定本测试技师，避免全库既有排班数据干扰计数
        $request = $this->makeRequest('GET', '/admin/schedules', [], ['technician_id' => $this->techHashid]);
        $resp = $this->controller()->index($request);
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame(2, $data['data']['total']);
        $byDate = [];
        foreach ($data['data']['list'] as $row) {
            $byDate[$row['date']] = $row;
        }

        $this->assertArrayHasKey($this->date, $byDate);
        $this->assertArrayHasKey($this->occupiedDate, $byDate);

        // 占用日：1 条有效占位（cancelled 不计）
        $bookings = $byDate[$this->occupiedDate]['bookings'];
        $this->assertCount(1, $bookings);
        $this->assertSame('paid', $bookings[0]['status']);
        $this->assertSame('排班测试技师', $byDate[$this->occupiedDate]['technician_name']);
        $this->assertArrayHasKey('order_no', $bookings[0]);

        // 未占用日：bookings 为空数组
        $this->assertSame([], $byDate[$this->date]['bookings']);
    }

    #[Test]
    public function index_filters_by_technician_and_date(): void
    {
        $this->createScheduleRow($this->techId, $this->date);
        $this->createScheduleRow($this->techId, $this->occupiedDate);

        // 按技师筛选 → 2 条
        $resp = $this->controller()->index($this->makeRequest('GET', '/admin/schedules', [], [
            'technician_id' => $this->techHashid,
        ]));
        $this->assertSame(2, $this->body($resp)['data']['total']);

        // 按日期区间 + 技师筛选 → 1 条
        $resp2 = $this->controller()->index($this->makeRequest('GET', '/admin/schedules', [], [
            'date_start'   => $this->date,
            'date_end'     => $this->date,
            'technician_id' => $this->techHashid,
        ]));
        $data2 = $this->body($resp2);
        $this->assertSame(1, $data2['data']['total']);
        $this->assertSame($this->date, $data2['data']['list'][0]['date']);
        $this->assertSame($this->techHashid, $data2['data']['list'][0]['technician_id'], 'technician_id 应编码返回');
    }

    #[Test]
    public function index_accepts_invalid_technician_hashid_with_422(): void
    {
        $request = $this->makeRequest('GET', '/admin/schedules?technician_id=bogus', []);
        $resp = $this->controller()->index($request);
        $this->assertSame(422, $this->body($resp)['code']);
    }
}
