<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\InvoiceController;
use app\common\HashidsService;
use app\model\Invoice;
use support\Db;
use support\Request;
use support\Response;

/**
 * 电子发票管理控制器测试（开票/驳回流转闭环）
 *
 * 覆盖：
 *   - 开票：pending → issued，issued_no/issued_at 写入
 *   - 开票缺发票号 → 422；非 pending 状态开票 → 422
 *   - 驳回：pending → rejected，remark 写入
 *   - 驳回缺原因 → 422；非 pending 状态驳回 → 422
 *   - 不存在记录 → 404；无效 hashid → 422
 *
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class InvoiceControllerTest extends TestCase
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

        // 自足 Eloquent 连接：Capsule 静态单例可能被其他测试类用不同 prefix 覆盖，这里显式重建
        $this->bootEloquent();

        Db::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
    }

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

    private function makeRequest(string $method, string $path, array $post = []): Request
    {
        $request = new Request("$method $path HTTP/1.1\r\nHost: localhost\r\n\r\n");
        if ($post) {
            $request->setPost($post);
        }
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true);
    }

    private function controller(): InvoiceController
    {
        return new InvoiceController();
    }

    /** 直接插入一条待开票记录（绕过控制器） */
    private function createPendingInvoice(string $orderType = 'service', string $status = 'pending'): Invoice
    {
        $invoice = new Invoice();
        $invoice->id            = Invoice::generateId();
        $invoice->user_id       = '90000000000003001';
        $invoice->order_id      = '90000000000003002';
        $invoice->order_type    = $orderType;
        $invoice->title_type    = 'personal';
        $invoice->invoice_title = '发票测试抬头';
        $invoice->amount        = 100.00;
        $invoice->status        = $status;
        $invoice->save();
        return $invoice;
    }

    private function hashidOf(Invoice $invoice): string
    {
        return HashidsService::encode((int) $invoice->id);
    }

    // ── 开票 ──

    #[Test]
    public function issue_sets_issued_and_issued_at(): void
    {
        $invoice = $this->createPendingInvoice();
        $resp = $this->controller()->issue(
            $this->makeRequest('POST', '/admin/invoices/' . $this->hashidOf($invoice) . '/issue', ['issued_no' => 'INV-2026-0001']),
            $this->hashidOf($invoice)
        );
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame('issued', $data['data']['status']);
        $this->assertSame('INV-2026-0001', $data['data']['issued_no']);
        $this->assertNotEmpty($data['data']['issued_at']);

        $fresh = Invoice::find($invoice->id);
        $this->assertSame('issued', $fresh->status);
        $this->assertSame('INV-2026-0001', $fresh->issued_no);
        $this->assertNotNull($fresh->issued_at);
    }

    #[Test]
    public function issue_requires_issued_no(): void
    {
        $invoice = $this->createPendingInvoice();
        $resp = $this->controller()->issue(
            $this->makeRequest('POST', '/admin/invoices/' . $this->hashidOf($invoice) . '/issue'),
            $this->hashidOf($invoice)
        );

        $this->assertSame(422, $this->body($resp)['code']);
        $this->assertSame('pending', Invoice::find($invoice->id)->status);
    }

    #[Test]
    public function issue_non_pending_rejected(): void
    {
        $invoice = $this->createPendingInvoice('service', 'issued');
        $resp = $this->controller()->issue(
            $this->makeRequest('POST', '/admin/invoices/' . $this->hashidOf($invoice) . '/issue', ['issued_no' => 'INV-2026-0002']),
            $this->hashidOf($invoice)
        );

        $this->assertSame(422, $this->body($resp)['code']);
    }

    // ── 驳回 ──

    #[Test]
    public function reject_sets_rejected_with_remark(): void
    {
        $invoice = $this->createPendingInvoice();
        $resp = $this->controller()->reject(
            $this->makeRequest('POST', '/admin/invoices/' . $this->hashidOf($invoice) . '/reject', ['remark' => '抬头信息有误']),
            $this->hashidOf($invoice)
        );
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame('rejected', $data['data']['status']);
        $this->assertSame('抬头信息有误', $data['data']['remark']);

        $fresh = Invoice::find($invoice->id);
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('抬头信息有误', $fresh->remark);
    }

    #[Test]
    public function reject_requires_remark(): void
    {
        $invoice = $this->createPendingInvoice();
        $resp = $this->controller()->reject(
            $this->makeRequest('POST', '/admin/invoices/' . $this->hashidOf($invoice) . '/reject'),
            $this->hashidOf($invoice)
        );

        $this->assertSame(422, $this->body($resp)['code']);
        $this->assertSame('pending', Invoice::find($invoice->id)->status);
    }

    #[Test]
    public function reject_non_pending_rejected(): void
    {
        $invoice = $this->createPendingInvoice('service', 'rejected');
        $resp = $this->controller()->reject(
            $this->makeRequest('POST', '/admin/invoices/' . $this->hashidOf($invoice) . '/reject', ['remark' => 'x']),
            $this->hashidOf($invoice)
        );

        $this->assertSame(422, $this->body($resp)['code']);
    }

    // ── 异常路径 ──

    #[Test]
    public function issue_missing_invoice_returns_404(): void
    {
        $resp = $this->controller()->issue(
            $this->makeRequest('POST', '/admin/invoices/90000000000009999/issue', ['issued_no' => 'x']),
            HashidsService::encode(90000000000009999)
        );

        $this->assertSame(404, $this->body($resp)['code']);
    }

    #[Test]
    public function issue_invalid_hashid_returns_422(): void
    {
        $resp = $this->controller()->issue(
            $this->makeRequest('POST', '/admin/invoices/not-a-hashid/issue', ['issued_no' => 'x']),
            'not-a-hashid'
        );

        $this->assertSame(422, $this->body($resp)['code']);
    }
}
