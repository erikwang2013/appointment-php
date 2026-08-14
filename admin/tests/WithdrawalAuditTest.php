<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\WithdrawalController;
use app\common\HashidsService;
use app\model\TechnicianProfile;
use app\model\TechnicianWithdrawal;
use app\model\User;
use support\Db;
use support\Request;
use support\Response;

/**
 * 技师提现审核控制器测试（S7 提现管理闭环）
 *
 * 覆盖：
 *   - 审核通过：大额（>=500）Level 1 店长审批 → 等待财务审批（status 仍 pending）
 *   - 审核通过：小额（<500）店长审批自动完成 → DB status 置 approved（无 openid 时转账失败，响应 500）
 *   - 非 pending 状态审核 → 422
 *   - 驳回：备注必填；成功置 rejected
 *   - 不存在记录 → 404；无效 hashid → 422
 *   - 标记完成：approved → completed；非 approved → 422
 *
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class WithdrawalAuditTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;
    private int $userId;
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

        // 自足 Eloquent 连接：Capsule 静态单例可能被其他测试类用不同 prefix 覆盖，这里显式重建
        $this->bootEloquent();

        Db::beginTransaction();

        $user = new User();
        $user->id = 90000000000002001;
        $user->phone = '138' . substr(uniqid(), -8);
        $user->nickname = '提现测试用户';
        $user->password = password_hash('123456', PASSWORD_DEFAULT);
        $user->status = 1;
        $user->user_type = 'technician';
        $user->save();

        $profile = new TechnicianProfile();
        $profile->id = 90000000000002002;
        $profile->user_id = $user->id;
        $profile->real_name = '提现测试技师';
        $profile->status = 'approved';
        $profile->save();

        $this->userId = (int) $user->id;
        $this->techId = (int) $profile->id;
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
    }

    // ── 工具方法 ──

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

    private function controller(): WithdrawalController
    {
        return new WithdrawalController();
    }

    /** 直接插入一条提现记录（绕过控制器） */
    private function createWithdrawal(float $amount, string $status = 'pending', string $remark = ''): TechnicianWithdrawal
    {
        $fee = round($amount * 0.01, 2);
        $w = new TechnicianWithdrawal();
        $w->id = TechnicianWithdrawal::generateId();
        $w->technician_id = (string) $this->techId;
        $w->withdrawal_no = TechnicianWithdrawal::generateWithdrawalNo();
        $w->amount = $amount;
        $w->actual_amount = round($amount - $fee, 2);
        $w->commission_fee = $fee;
        $w->account_type = 'wechat';
        $w->account_name = '提现测试技师';
        $w->account_no = 'wx_test_0001';
        $w->status = $status;
        if ($remark) {
            $w->audit_remark = $remark;
        }
        $w->save();
        return $w;
    }

    private function hashidOf(TechnicianWithdrawal $w): string
    {
        return HashidsService::encode((int) $w->id);
    }

    // ── 审核通过 ──

    #[Test]
    public function approve_large_amount_level1_waits_finance(): void
    {
        $w = $this->createWithdrawal(800.00);
        $resp = $this->controller()->approve(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/approve'),
            $this->hashidOf($w)
        );
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertStringContainsString('等待财务审批', $data['message']);

        $fresh = TechnicianWithdrawal::find($w->id);
        $this->assertSame('pending', $fresh->status, '大额仅 Level 1 通过，仍待财务审批');
        $this->assertNotNull($fresh->store_approved_at, '应记录店长审批时间');
        $this->assertNull($fresh->finance_approved_at);
    }

    #[Test]
    public function approve_small_amount_marks_approved(): void
    {
        $w = $this->createWithdrawal(100.00);
        $resp = $this->controller()->approve(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/approve'),
            $this->hashidOf($w)
        );

        // 小额店长审批即置 approved；技师未绑定微信 openid 时转账失败 → 响应 500（状态已落库）
        $this->assertSame(500, $this->body($resp)['code']);
        $this->assertStringContainsString('未绑定微信', $this->body($resp)['message']);

        $fresh = TechnicianWithdrawal::find($w->id);
        $this->assertSame('approved', $fresh->status, '小额审批后状态应为 approved');
        $this->assertNotNull($fresh->audited_at);
    }

    #[Test]
    public function approve_non_pending_returns_422(): void
    {
        $w = $this->createWithdrawal(800.00, 'approved');
        $resp = $this->controller()->approve(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/approve'),
            $this->hashidOf($w)
        );
        $this->assertSame(422, $this->body($resp)['code']);
    }

    #[Test]
    public function approve_missing_record_returns_404(): void
    {
        $hashid = HashidsService::encode(90000000000008888);
        $resp = $this->controller()->approve(
            $this->makeRequest('POST', '/admin/withdrawals/' . $hashid . '/approve'),
            $hashid
        );
        $this->assertSame(404, $this->body($resp)['code']);
    }

    #[Test]
    public function invalid_hashid_returns_422(): void
    {
        $resp = $this->controller()->approve(
            $this->makeRequest('POST', '/admin/withdrawals/bogus/approve'),
            'bogus'
        );
        $this->assertSame(422, $this->body($resp)['code']);
    }

    // ── 驳回 ──

    #[Test]
    public function reject_requires_remark(): void
    {
        $w = $this->createWithdrawal(100.00);
        $resp = $this->controller()->reject(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/reject'),
            $this->hashidOf($w)
        );
        $this->assertSame(422, $this->body($resp)['code']);
        $this->assertSame('pending', TechnicianWithdrawal::find($w->id)->status, '无备注不得驳回');
    }

    #[Test]
    public function reject_succeeds(): void
    {
        $w = $this->createWithdrawal(100.00);
        $resp = $this->controller()->reject(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/reject', [
                'remark' => '账户信息有误',
            ]),
            $this->hashidOf($w)
        );
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $fresh = TechnicianWithdrawal::find($w->id);
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('账户信息有误', $fresh->audit_remark);
        $this->assertNotNull($fresh->audited_at);
    }

    #[Test]
    public function reject_non_pending_returns_422(): void
    {
        $w = $this->createWithdrawal(100.00, 'rejected');
        $resp = $this->controller()->reject(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/reject', [
                'remark' => '再次驳回',
            ]),
            $this->hashidOf($w)
        );
        $this->assertSame(422, $this->body($resp)['code']);
    }

    #[Test]
    public function reject_missing_record_returns_404(): void
    {
        $hashid = HashidsService::encode(90000000000008889);
        $resp = $this->controller()->reject(
            $this->makeRequest('POST', '/admin/withdrawals/' . $hashid . '/reject', ['remark' => 'x']),
            $hashid
        );
        $this->assertSame(404, $this->body($resp)['code']);
    }

    // ── 标记完成 ──

    #[Test]
    public function complete_marks_completed(): void
    {
        $w = $this->createWithdrawal(800.00, 'approved');
        $resp = $this->controller()->complete(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/complete'),
            $this->hashidOf($w)
        );
        $this->assertSame(0, $this->body($resp)['code']);

        $fresh = TechnicianWithdrawal::find($w->id);
        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    #[Test]
    public function complete_non_approved_returns_422(): void
    {
        $w = $this->createWithdrawal(800.00, 'pending');
        $resp = $this->controller()->complete(
            $this->makeRequest('POST', '/admin/withdrawals/' . $this->hashidOf($w) . '/complete'),
            $this->hashidOf($w)
        );
        $this->assertSame(422, $this->body($resp)['code']);
    }

    // ── 列表状态筛选（字符串过滤） ──

    #[Test]
    public function index_filters_by_string_status(): void
    {
        $this->createWithdrawal(100.00, 'pending');
        $this->createWithdrawal(200.00, 'rejected');

        $request = new Request("GET /admin/withdrawals HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $request->setGet(['status' => 'rejected']);

        $data = $this->body($this->controller()->index($request));
        $this->assertSame(0, $data['code']);
        $this->assertSame(1, $data['data']['total']);
        $this->assertSame('rejected', $data['data']['list'][0]['status']);
    }
}
