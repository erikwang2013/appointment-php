<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\OrderVerificationController;
use app\model\Order;
use app\model\OrderVerification;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use support\Request;
use support\Response;

/**
 * 核销记录管理接口测试（admin 端 OrderVerificationController）
 *
 * 说明：admin tests/bootstrap.php 不初始化 Eloquent，本用例自建 Capsule
 * （与 service 端 tests/bootstrap.php 同基建），连接真实 MySQL。
 *
 * 覆盖：
 * - 列表仅返回已核销记录（whereNotNull verified_at）
 * - 按订单号 / 技师 / 核销方式筛选
 * - 详情返回编码后的 hashid ID
 */
class OrderVerificationControllerTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    protected function setUp(): void
    {
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'appointment'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            // 表前缀已内嵌在模型 $table（如 appointment_order_verification），与 admin/config/database.php 一致，此处不再配置 prefix
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            TechnicianEarning::where('order_id', $id)->delete();
            OrderVerification::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->profileIds = [];
    }

    private function makeRequest(array $query = []): Request
    {
        $queryString = http_build_query($query);
        $request = new Request("GET /admin/order-verifications?{$queryString} HTTP/1.1\r\nHost: localhost\r\n\r\n");
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    /** 造已审核技师档案 */
    private function makeTechnician(): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id        = TechnicianProfile::generateId();
        $profile->user_id   = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = '测试技师';
        $profile->status    = 'approved';
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    /**
     * 造订单 + 核销码记录
     *
     * @param bool $verified 是否已核销（决定列表是否展示）
     */
    private function makeOrderWithVerification(string $technicianId, bool $verified = true): array
    {
        // forceCreate：id 非 fillable 且本环境无全局 creating 事件，需显式写入 snowflake 主键
        $order = Order::forceCreate([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_ADM_VER_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => $technicianId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => $verified ? Order::STATUS_SERVING : Order::STATUS_PAID,
        ]);
        $this->orderIds[] = $order->id;

        $code = bin2hex(random_bytes(16));
        $verification = new OrderVerification();
        $verification->id = OrderVerification::generateId();
        $verification->order_id = $order->id;
        $verification->code = $code;
        if ($verified) {
            $verification->verified_by = $order->user_id;
            $verification->verify_type = OrderVerification::VERIFY_TYPE_SCAN;
            $verification->location = '门店A';
            $verification->verified_at = date('Y-m-d H:i:s');
        }
        $verification->save();

        return [$order, $verification];
    }

    #[Test] public function index_returns_only_verified_records(): void
    {
        $technician = $this->makeTechnician();
        [$verifiedOrder, $verifiedRec] = $this->makeOrderWithVerification($technician->id, true);
        $this->makeOrderWithVerification($technician->id, false); // 未核销不应出现在列表

        $resp = (new OrderVerificationController())->index($this->makeRequest());

        $data = $this->body($resp)['data'];
        $this->assertSame(1, $data['total']);
        $this->assertCount(1, $data['list']);
        $this->assertSame($verifiedOrder->order_no, $data['list'][0]['order']['order_no']);
        // ID 已编码为 hashid
        $this->assertNotSame($verifiedRec->id, $data['list'][0]['id']);
    }

    #[Test] public function index_filters_by_order_no_and_verify_type(): void
    {
        $technician = $this->makeTechnician();
        [$order, $verification] = $this->makeOrderWithVerification($technician->id, true);
        $this->makeOrderWithVerification($technician->id, true);

        // 按订单号筛选
        $resp = (new OrderVerificationController())->index($this->makeRequest([
            'order_no' => $order->order_no,
        ]));
        $data = $this->body($resp)['data'];
        $this->assertSame(1, $data['total']);
        $this->assertSame($order->order_no, $data['list'][0]['order']['order_no']);

        // 按核销方式筛选（scan 有记录，self 无记录）
        $resp2 = (new OrderVerificationController())->index($this->makeRequest([
            'verify_type' => 'self',
        ]));
        $this->assertSame(0, $this->body($resp2)['data']['total']);
    }

    #[Test] public function index_filters_by_technician_and_date(): void
    {
        $technicianA = $this->makeTechnician();
        $technicianB = $this->makeTechnician();
        [$orderA] = $this->makeOrderWithVerification($technicianA->id, true);
        $this->makeOrderWithVerification($technicianB->id, true);

        // 按技师筛选（订单归属技师）
        $resp = (new OrderVerificationController())->index($this->makeRequest([
            'technician_id' => $technicianA->id,
        ]));
        $data = $this->body($resp)['data'];
        $this->assertSame(1, $data['total']);
        $this->assertSame($orderA->order_no, $data['list'][0]['order']['order_no']);

        // 按核销日期筛选（今天有记录，明天无记录）
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $resp2 = (new OrderVerificationController())->index($this->makeRequest([
            'date_start' => $today,
            'date_end'   => $today,
        ]));
        $this->assertSame(2, $this->body($resp2)['data']['total']);

        $resp3 = (new OrderVerificationController())->index($this->makeRequest([
            'date_start' => $tomorrow,
            'date_end'   => $tomorrow,
        ]));
        $this->assertSame(0, $this->body($resp3)['data']['total']);
    }

    #[Test] public function show_returns_encoded_detail(): void
    {
        $technician = $this->makeTechnician();
        [, $verification] = $this->makeOrderWithVerification($technician->id, true);

        $controller = new OrderVerificationController();
        $hashid = \app\common\HashidsService::encode((int) $verification->id);

        $resp = $controller->show($this->makeRequest(), $hashid);
        $data = $this->body($resp)['data'];
        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame($hashid, $data['id']);
        $this->assertSame(OrderVerification::VERIFY_TYPE_SCAN, $data['verify_type']);
        $this->assertNotNull($data['verified_at']);
    }
}
