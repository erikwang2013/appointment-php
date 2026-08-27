<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\ReportController;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\TechnicianProfile;
use support\Request;
use support\Response;

/**
 * 数据报表接口测试（admin 端 ReportController）
 *
 * 说明：admin tests/bootstrap.php 不初始化 Eloquent，本用例自建 Capsule
 * （与 OrderVerificationControllerTest 同基建），连接真实 MySQL。
 *
 * 覆盖：
 * - orders：      汇总键齐全（总订单/支付订单/支付金额/退款金额/净营收）+ 按天趋势 list/total
 * - technicians： TOP10 结构（hashid/姓名/单量/营收/评分）按营收降序
 * - distribution：支付渠道（wechat/alipay/balance）+ 订单状态分布
 *
 * 报表结果 Redis 缓存 5 分钟，缓存键含日期；每次运行随机基础日期保证键唯一。
 */
class ReportControllerTest extends TestCase
{
    /** @var string 本次运行随机基础日期，保证 Redis 缓存键唯一 */
    private string $baseDate;

    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    /** @var string[] 用例创建的支付/退款/钱包流水 ID，tearDown 统一清理 */
    private array $paymentIds = [];
    private array $refundIds = [];
    private array $txnUserIds = [];

    protected function setUp(): void
    {
        $this->baseDate = date('Y-m-d', strtotime('2019-01-01 +' . random_int(0, 2000) . ' days'));

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
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function tearDown(): void
    {
        foreach ($this->txnUserIds as $uid) {
            \Illuminate\Database\Capsule\Manager::table('appointment_wallet_txn')->where('user_id', $uid)->delete();
        }
        foreach ($this->refundIds as $id) {
            OrderRefund::where('id', $id)->delete();
        }
        foreach ($this->paymentIds as $id) {
            OrderPayment::where('id', $id)->delete();
        }
        foreach ($this->orderIds as $id) {
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        $this->orderIds = $this->profileIds = $this->paymentIds = $this->refundIds = $this->txnUserIds = [];
    }

    private function makeRequest(array $query = []): Request
    {
        $queryString = http_build_query($query);
        return new Request("GET /admin/reports/orders?{$queryString} HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function makeTechnician(string $name = '测试技师', float $rating = 5.0): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id        = TechnicianProfile::generateId();
        $profile->user_id   = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = $name;
        $profile->rating    = $rating;
        $profile->status    = 'approved';
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    /**
     * 造订单（forceCreate：id 非 fillable 且本环境无全局 creating 事件）
     */
    private function makeOrder(string $technicianId, string $status, float $paidAmount): Order
    {
        $order = Order::forceCreate([
            'id'            => Order::generateId(),
            'order_no'      => 'ORD_RPT_' . uniqid(),
            'user_id'       => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id' => $technicianId,
            'order_type'    => 'appointment',
            'total_amount'  => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'   => $paidAmount,
            'status'        => $status,
            'created_at'    => $this->baseDate . ' 10:00:00',
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function makePayment(Order $order, string $payType, float $amount): OrderPayment
    {
        $payment = new OrderPayment();
        $payment->id          = OrderPayment::generateId();
        $payment->order_id    = $order->id;
        $payment->payment_no  = 'PAY_RPT_' . uniqid();
        $payment->pay_type    = $payType;
        $payment->amount      = $amount;
        $payment->status      = OrderPayment::STATUS_SUCCESS;
        $payment->created_at  = $this->baseDate . ' 10:05:00';
        $payment->save();
        $this->paymentIds[] = $payment->id;
        return $payment;
    }

    private function makeRefund(Order $order, OrderPayment $payment, float $amount): OrderRefund
    {
        $refund = new OrderRefund();
        $refund->id          = OrderRefund::generateId();
        $refund->order_id    = $order->id;
        $refund->payment_id  = $payment->id;
        $refund->refund_no   = 'RFD_RPT_' . uniqid();
        $refund->amount      = $amount;
        $refund->status      = OrderRefund::STATUS_SUCCESS;
        $refund->refunded_at = $this->baseDate . ' 11:00:00';
        $refund->created_at  = $this->baseDate . ' 11:00:00';
        $refund->save();
        $this->refundIds[] = $refund->id;
        return $refund;
    }

    private function makeWalletTxn(string $orderId, float $amount): void
    {
        $userId = (string) (9900000000000000 + random_int(1, 999999));
        $this->txnUserIds[] = $userId;
        \Illuminate\Database\Capsule\Manager::table('appointment_wallet_txn')->insert([
            'id'             => \app\common\SnowflakeService::generate(),
            'user_id'        => $userId,
            'type'           => 'consume',
            'amount'         => $amount,
            'balance_after'  => 0.0,
            'order_id'       => $orderId,
            'remark'         => 'test',
            'created_at'     => $this->baseDate . ' 10:10:00',
        ]);
    }

    #[Test] public function orders_returns_summary_and_daily_trend(): void
    {
        $tech = $this->makeTechnician();
        $paid = $this->makeOrder($tech->id, 'completed', 200.0);
        $this->makeOrder($tech->id, 'pending', 100.0);
        $payment = $this->makePayment($paid, 'wechat', 200.0);
        $this->makeRefund($paid, $payment, 50.0);

        $resp = (new ReportController())->orders($this->makeRequest([
            'start_date' => $this->baseDate,
            'end_date'   => $this->baseDate,
        ]));
        $data = $this->body($resp)['data'];
        $this->assertSame(0, $this->body($resp)['code']);

        // 汇总：1 已支付订单（200）+ 1 待支付（100），退款 50，净营收 150
        // 注：金额用 assertEquals —— PHP json_encode 会把整数浮点（200.0）序列化为 200
        $this->assertSame(2, $data['summary']['total_orders']);
        $this->assertSame(1, $data['summary']['paid_orders']);
        $this->assertEquals(200.0, $data['summary']['payment_amount']);
        $this->assertEquals(50.0, $data['summary']['refund_amount']);
        $this->assertEquals(150.0, $data['summary']['net_revenue']);

        // 趋势：单日 list 一条，键齐全
        $this->assertSame(1, $data['total']);
        $this->assertCount(1, $data['list']);
        $row = $data['list'][0];
        foreach (['date', 'order_count', 'payment_amount', 'refund_amount', 'net_revenue'] as $key) {
            $this->assertArrayHasKey($key, $row);
        }
        $this->assertSame($this->baseDate, $row['date']);
        $this->assertSame(2, $row['order_count']);
    }

    #[Test] public function technicians_returns_top10_ranked_by_revenue(): void
    {
        $techA = $this->makeTechnician('甲技师', 4.8);
        $techB = $this->makeTechnician('乙技师', 5.0);
        $this->makeOrder($techA->id, 'completed', 300.0);
        $this->makeOrder($techA->id, 'completed', 200.0);
        $this->makeOrder($techB->id, 'completed', 100.0);

        $resp = (new ReportController())->technicians($this->makeRequest([
            'start_date' => $this->baseDate,
            'end_date'   => $this->baseDate,
            'sort_by'    => 'revenue',
        ]));
        $data = $this->body($resp)['data'];
        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame(2, $data['total']);
        $this->assertCount(2, $data['list']);

        $first = $data['list'][0];
        // 营收高者在前（A=500 > B=100）
        $this->assertEquals(500.0, $first['revenue']);
        $this->assertSame(2, $first['order_count']);
        // 技师 ID 已编码为 hashid
        $this->assertNotSame($techA->id, $first['technician_id']);
        foreach (['technician_id', 'technician_name', 'order_count', 'revenue', 'rating'] as $key) {
            $this->assertArrayHasKey($key, $first);
        }
        // 第二名为乙技师，评分 5.0
        $this->assertEquals(100.0, $data['list'][1]['revenue']);
        $this->assertEquals(5.0, $data['list'][1]['rating']);
    }

    #[Test] public function distribution_returns_pay_type_and_status(): void
    {
        $tech = $this->makeTechnician();
        $orderA = $this->makeOrder($tech->id, 'completed', 200.0);
        $orderB = $this->makeOrder($tech->id, 'pending', 100.0);
        $this->makePayment($orderA, 'wechat', 200.0);
        $this->makePayment($orderB, 'alipay', 100.0);
        $this->makeWalletTxn($orderA->id, 50.0);

        $resp = (new ReportController())->distribution($this->makeRequest([
            'start_date' => $this->baseDate,
            'end_date'   => $this->baseDate,
        ]));
        $data = $this->body($resp)['data'];
        $this->assertSame(0, $this->body($resp)['code']);

        // 支付渠道：微信/支付宝各 1 笔，余额 1 笔（钱包消费流水）
        $types = array_column($data['pay_type'], 'type');
        $this->assertSame(['wechat', 'alipay', 'balance'], $types);
        $byType = array_column($data['pay_type'], null, 'type');
        $this->assertSame(1, $byType['wechat']['count']);
        $this->assertEquals(200.0, $byType['wechat']['amount']);
        $this->assertSame(1, $byType['balance']['count']);
        $this->assertEquals(50.0, $byType['balance']['amount']);

        // 状态分布：completed=1, pending=1
        $statusCounts = array_column($data['status'], 'count', 'status');
        $this->assertSame(1, $statusCounts['completed']);
        $this->assertSame(1, $statusCounts['pending']);
    }
}
