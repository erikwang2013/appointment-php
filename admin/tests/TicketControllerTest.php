<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\TicketController;
use app\common\HashidsService;
use app\middleware\AdminPermission;
use app\model\Notification;
use app\model\Ticket;
use support\Db;
use support\Redis;
use support\Request;
use support\Response;

/**
 * 客服工单管理控制器测试
 *
 * 覆盖：
 *   - 权限中间件：无匹配权限 → 403，有权限放行
 *   - 回复：默认置 processing、指定 resolved；回复内容必填；已结束工单不可回复
 *   - 回复成功后用户收到站内通知 type=ticket_reply
 *
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class TicketControllerTest extends TestCase
{
    private const ADMIN_ID = 777;
    private const CACHE_KEY = 'perm:777';

    private static bool $dbReady = false;
    private static bool $dbChecked = false;
    private int $ticketId;
    private string $ticketHashid;
    private string $userId;
    private array $redisKeys = [];

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

        $this->userId = '9900000000000001';
        $ticket = new Ticket();
        $ticket->id = 90000000000003001;
        $ticket->user_id = $this->userId;
        $ticket->category = 'refund';
        $ticket->description = '退款进度咨询';
        $ticket->status = 'pending';
        $ticket->save();

        $this->ticketId = (int) $ticket->id;
        $this->ticketHashid = HashidsService::encode($this->ticketId);
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }
        $this->redisKeys = [];
    }

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
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function controller(): TicketController
    {
        return new TicketController();
    }

    /** 带登录管理员的回复请求 */
    private function replyRequest(array $post): Request
    {
        $request = $this->makeRequest('POST', '/admin/tickets/' . $this->ticketHashid . '/reply', $post);
        $request->adminId = self::ADMIN_ID;
        return $request;
    }

    private function redisAvailable(): bool
    {
        try {
            $probe = 'test:probe:' . uniqid();
            $this->trackKey($probe);
            Redis::setex($probe, 5, '1');
            return Redis::get($probe) === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    private function trackKey(string $key): void
    {
        if (!in_array($key, $this->redisKeys, true)) {
            $this->redisKeys[] = $key;
        }
    }

    // ── 权限 ──

    #[Test]
    public function index_denied_without_permission(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $request = $this->makeRequest('GET', '/admin/tickets');
        $request->adminId = self::ADMIN_ID;
        $this->trackKey(self::CACHE_KEY);
        Redis::setex(self::CACHE_KEY, 60, json_encode(['get.admin/reviews']));

        $resp = (new AdminPermission())->process($request, fn () => json(['code' => 0]));
        $data = $this->body($resp);
        $this->assertSame(403, $data['code']);
        $this->assertSame('无权限访问', $data['message']);
    }

    #[Test]
    public function index_allowed_with_ticket_permission(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $request = $this->makeRequest('GET', '/admin/tickets');
        $request->adminId = self::ADMIN_ID;
        $this->trackKey(self::CACHE_KEY);
        Redis::setex(self::CACHE_KEY, 60, json_encode(['get.admin/tickets']));

        $resp = (new AdminPermission())->process($request, fn () => json(['code' => 0]));
        $this->assertSame(0, $this->body($resp)['code']);
    }

    // ── 回复 ──

    #[Test]
    public function reply_defaults_to_processing_and_notifies_user(): void
    {
        $resp = $this->controller()->reply(
            $this->replyRequest([
                'reply_content' => '已为您核实，退款将在 1-3 个工作日到账',
            ]),
            $this->ticketHashid
        );

        $data = $this->body($resp);
        $this->assertSame(0, $data['code'], json_encode($data));
        $this->assertSame('processing', $data['data']['status'], '未指定 status 默认 processing');

        $fresh = Ticket::find($this->ticketId);
        $this->assertSame('processing', $fresh->status);
        $this->assertSame('已为您核实，退款将在 1-3 个工作日到账', $fresh->reply_content);
        $this->assertNotNull($fresh->replied_at);
        $this->assertSame((string) self::ADMIN_ID, (string) $fresh->admin_id, '应记录处理管理员');

        // 用户收到站内通知
        $notify = Notification::where('user_id', $this->userId)
            ->where('type', 'ticket_reply')
            ->first();
        $this->assertNotNull($notify, '回复后应生成站内通知');
        $this->assertStringContainsString('工单', (string) $notify->content);
    }

    #[Test]
    public function reply_with_resolved_status_marks_resolved(): void
    {
        $resp = $this->controller()->reply(
            $this->replyRequest([
                'reply_content' => '问题已解决，感谢您的反馈',
                'status'        => 'resolved',
            ]),
            $this->ticketHashid
        );

        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame('resolved', (string) Ticket::find($this->ticketId)->status);
    }

    #[Test]
    public function reply_requires_content(): void
    {
        $resp = $this->controller()->reply(
            $this->replyRequest(['reply_content' => '   ']),
            $this->ticketHashid
        );

        $this->assertSame(422, $this->body($resp)['code']);
        $this->assertSame('pending', (string) Ticket::find($this->ticketId)->status, '空回复不得变更状态');
    }

    #[Test]
    public function reply_rejects_closed_ticket(): void
    {
        Ticket::find($this->ticketId)->update(['status' => 'closed']);

        $resp = $this->controller()->reply(
            $this->replyRequest(['reply_content' => '继续回复']),
            $this->ticketHashid
        );

        $this->assertSame(422, $this->body($resp)['code']);
    }

    #[Test]
    public function reply_missing_record_returns_404(): void
    {
        $hashid = HashidsService::encode(90000000000009999);
        $resp = $this->controller()->reply(
            $this->makeRequest('POST', "/admin/tickets/{$hashid}/reply", ['reply_content' => 'x']),
            $hashid
        );
        $this->assertSame(404, $this->body($resp)['code']);
    }

    // ── 列表 ──

    #[Test]
    public function index_returns_paginated_list_with_filter(): void
    {
        $ticket2 = new Ticket();
        $ticket2->id = 90000000000003002;
        $ticket2->user_id = $this->userId;
        $ticket2->category = 'technician';
        $ticket2->description = '技师态度问题';
        $ticket2->status = 'resolved';
        $ticket2->save();

        $request = $this->makeRequest('GET', '/admin/tickets', [], []);
        $request->setGet(['status' => 'resolved', 'page' => 1, 'limit' => 10]);
        $data = $this->body($this->controller()->index($request));

        $this->assertSame(0, $data['code'], json_encode($data));
        $this->assertSame(1, $data['data']['total']);
        $this->assertSame('resolved', $data['data']['list'][0]['status']);
        $this->assertArrayHasKey('user', $data['data']['list'][0], '列表应带用户关联');
    }
}
