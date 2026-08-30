<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\FinanceController;
use app\admin\controller\SalesStatsController;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use Illuminate\Database\Capsule\Manager as DB;
use support\Redis;
use support\Request;
use support\Response;

/**
 * 销售/财务统计接口测试（admin 端 SalesStatsController + FinanceController）
 *
 * 与 ReportControllerTest 同基建：自建 Capsule 连接真实 MySQL。
 * 覆盖：
 * - sales_stats：汇总/每日合计 + 缓存键 svc:sales_stats:{start}_{end} 写入（无前缀键不写）
 * - finance_stats：收入/退款/提现/佣金 + 缓存键 svc:finance_stats:{start}_{end} 写入（无前缀键不写）
 * - 非法日期回退默认范围（不拼入缓存键）
 *
 * 每次运行随机基础日期（2019-2024），保证 Redis 缓存键唯一。
 */
class StatsControllerTest extends TestCase
{
    /** @var string 本次运行随机基础日期，保证 Redis 缓存键唯一 */
    private string $baseDate;

    /** @var string[] 用例创建记录 ID，tearDown 统一清理 */
    private array $orderIds = [];
    private array $paymentIds = [];
    private array $refundIds = [];
    private array $withdrawalIds = [];
    private array $earningIds = [];

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
        foreach ($this->earningIds as $id) {
            DB::table('appointment_technician_earnings')->where('id', $id)->delete();
        }
        foreach ($this->withdrawalIds as $id) {
            DB::table('appointment_technician_withdrawal')->where('id', $id)->delete();
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
        $this->orderIds = $this->paymentIds = $this->refundIds = $this->withdrawalIds = $this->earningIds = [];
    }

    private function makeRequest(array $query = []): Request
    {
        $queryString = http_build_query($query);
        return new Request("GET /admin/sales-stats?{$queryString} HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function makeOrder(string $status, float $paidAmount): Order
    {
        $order = Order::forceCreate([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_STS_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => 'appointment',
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => $status,
            'created_at'      => $this->baseDate . ' 10:00:00',
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function makePayment(Order $order, float $amount): OrderPayment
    {
        $payment = new OrderPayment();
        $payment->id          = OrderPayment::generateId();
        $payment->order_id    = $order->id;
        $payment->payment_no  = 'PAY_STS_' . uniqid();
        $payment->pay_type    = 'wechat';
        $payment->amount      = $amount;
        $payment->status      = OrderPayment::STATUS_SUCCESS;
        $payment->paid_at     = $this->baseDate . ' 10:05:00';
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
        $refund->refund_no   = 'RFD_STS_' . uniqid();
        $refund->amount      = $amount;
        $refund->status      = OrderRefund::STATUS_SUCCESS;
        $refund->refunded_at = $this->baseDate . ' 11:00:00';
        $refund->created_at  = $this->baseDate . ' 11:00:00';
        $refund->save();
        $this->refundIds[] = $refund->id;
        return $refund;
    }

    private function makeWithdrawal(float $amount): void
    {
        $id = \app\common\SnowflakeService::generate();
        DB::table('appointment_technician_withdrawal')->insert([
            'id'             => $id,
            'technician_id'  => (string) (9900000000000000 + random_int(1, 999999)),
            'withdrawal_no'  => 'WDL_STS_' . uniqid(),
            'amount'         => $amount,
            'actual_amount'  => $amount,
            'commission_fee' => 0.0,
            'status'         => 'completed',
            'completed_at'   => $this->baseDate . ' 12:00:00',
            'created_at'     => $this->baseDate . ' 12:00:00',
        ]);
        $this->withdrawalIds[] = $id;
    }

    private function makeEarning(float $amount): void
    {
        $id = \app\common\SnowflakeService::generate();
        DB::table('appointment_technician_earnings')->insert([
            'id'            => $id,
            'technician_id' => (string) (9900000000000000 + random_int(1, 999999)),
            'order_id'      => 0,
            'type'          => 'commission',
            'amount'        => $amount,
            'status'        => 'settled',
            'created_at'    => $this->baseDate . ' 13:00:00',
        ]);
        $this->earningIds[] = $id;
    }

    #[Test] public function sales_stats_returns_summary_and_writes_svc_cache_key(): void
    {
        $this->makeOrder('completed', 200.0);
        $this->makeOrder('pending', 100.0);

        $resp = (new SalesStatsController())->index($this->makeRequest([
            'date_start' => $this->baseDate,
            'date_end'   => $this->baseDate,
        ]));
        $data = $this->body($resp)['data'];
        $this->assertSame(0, $this->body($resp)['code']);

        // 汇总：1 已支付订单（200），1 待支付不计入
        $this->assertSame(1, $data['summary']['total_orders']);
        $this->assertEquals(200.0, $data['summary']['total_revenue']);
        // 每日合计一条，日期为请求范围
        $this->assertCount(1, $data['daily_totals']);
        $this->assertSame($this->baseDate, $data['daily_totals'][0]['date']);

        // 缓存键 svc:sales_stats:{start}_{end} 写入，无前缀键不写
        $key = "svc:sales_stats:{$this->baseDate}_{$this->baseDate}";
        $this->assertNotEmpty(Redis::get($key));
        $this->assertNull(Redis::get("sales_stats:{$this->baseDate}_{$this->baseDate}"));
    }

    #[Test] public function finance_stats_returns_totals_and_writes_svc_cache_key(): void
    {
        $order = $this->makeOrder('completed', 200.0);
        $payment = $this->makePayment($order, 200.0);
        $this->makeRefund($order, $payment, 50.0);
        $this->makeWithdrawal(100.0);
        $this->makeEarning(30.0);

        $resp = (new FinanceController())->stats($this->makeRequest([
            'date_start' => $this->baseDate,
            'date_end'   => $this->baseDate,
        ]));
        $data = $this->body($resp)['data'];
        $this->assertSame(0, $this->body($resp)['code']);

        // 收入 200 - 退款 50 - 提现 100 / 佣金 30
        $this->assertEquals(200.0, $data['revenue']);
        $this->assertEquals(50.0, $data['refunds']);
        $this->assertEquals(100.0, $data['withdrawals']);
        $this->assertEquals(30.0, $data['commissions']);
        $this->assertEquals(120.0, $data['net_revenue']);
        $this->assertEquals(50.0, $data['net_income']);

        // 缓存键 svc:finance_stats:{start}_{end} 写入，无前缀键不写
        $key = "svc:finance_stats:{$this->baseDate}_{$this->baseDate}";
        $this->assertNotEmpty(Redis::get($key));
        $this->assertNull(Redis::get("finance_stats:{$this->baseDate}_{$this->baseDate}"));
    }

    #[Test] public function invalid_dates_fall_back_to_default_range(): void
    {
        $cases = [
            'sales'   => fn(Request $r) => (new SalesStatsController())->index($r),
            'finance' => fn(Request $r) => (new FinanceController())->stats($r),
        ];
        foreach ($cases as $name => $call) {
            $resp = $call($this->makeRequest(['date_start' => '../../etc/passwd', 'date_end' => 'x']));
            $data = $this->body($resp)['data'];
            $this->assertSame(0, $this->body($resp)['code'], $name);
            // 非法日期回退默认近 30 天，不拼入缓存键
            $this->assertSame(date('Y-m-d', strtotime('-30 days')), $data['date_start'], $name);
            $this->assertSame(date('Y-m-d'), $data['date_end'], $name);
            $this->assertNull(Redis::get("svc:sales_stats:../../etc/passwd_x"), $name);
            $this->assertNull(Redis::get("svc:finance_stats:../../etc/passwd_x"), $name);
        }
    }

    #[Test] public function finance_stats_route_is_registered(): void
    {
        // 财务统计端点需可经 HTTP 路由到达（README 宣称 svc:finance_stats 特性）
        \Webman\Route::load([config_path()]);
        $r = \Webman\Route::dispatch('GET', '/admin/finances/stats');
        $this->assertSame(1, $r[0]);
        $this->assertSame('stats', $r[1]['callback'][1]);
    }
}
