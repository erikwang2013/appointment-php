<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\ReviewController;
use app\common\HashidsService;
use app\model\Order;
use app\model\OrderReview;
use app\model\TechnicianProfile;
use app\model\User;
use support\Db;
use support\Request;
use support\Response;

/**
 * 评价管理控制器测试（S7 评价管理闭环）
 *
 * 覆盖：
 *   - 列表（index）：分页 + rating / status / keyword 筛选
 *   - 详情（show）：含用户、订单、技师档案关联
 *   - 审核（audit）：hide / show 置位 status（0/1），非法 action 拒绝
 *   - 删除（destroy）：删行
 *
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class ReviewControllerTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;
    private int $userId;
    private int $techId;
    private int $reviewId;
    private string $reviewHashid;

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

        // 测试用户 + 技师档案
        $user = new User();
        $user->id = 90000000000002001;
        $user->phone = '138' . substr(uniqid(), -8);
        $user->nickname = '评价测试用户';
        $user->password = password_hash('123456', PASSWORD_DEFAULT);
        $user->status = 1;
        $user->user_type = 'technician';
        $user->save();

        $profile = new TechnicianProfile();
        $profile->id = 90000000000002002;
        $profile->user_id = $user->id;
        $profile->real_name = '评价测试技师';
        $profile->status = 'approved';
        $profile->save();

        // 订单
        $order = new Order();
        $order->id = Order::generateId();
        $order->order_no = 'RVT' . uniqid();
        $order->user_id = (string) $user->id;
        $order->technician_id = (string) $user->id;
        $order->order_type = Order::ORDER_TYPE_APPOINTMENT;
        $order->status = 'completed';
        $order->service_time = date('Y-m-d H:i:s');
        $order->save();

        // 评价
        $review = new OrderReview();
        $review->id = OrderReview::generateId();
        $review->order_id = (string) $order->id;
        $review->user_id = (string) $user->id;
        $review->technician_id = (string) $user->id;
        $review->rating = 5;
        $review->content = '服务很好，非常专业';
        $review->images = [];
        $review->status = OrderReview::STATUS_VISIBLE;
        $review->save();

        $this->userId = (int) $user->id;
        $this->techId = (int) $profile->id;
        $this->reviewId = (int) $review->id;
        $this->reviewHashid = HashidsService::encode($this->reviewId);
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
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

    private function controller(): ReviewController
    {
        return new ReviewController();
    }

    // ── 列表 ──

    #[Test]
    public function index_returns_paginated_list(): void
    {
        $resp = $this->body($this->controller()->index($this->makeRequest('GET', '/admin/reviews')));
        $this->assertSame(0, $resp['code']);
        $this->assertArrayHasKey('list', $resp['data']);
        $this->assertArrayHasKey('total', $resp['data']);
        $this->assertGreaterThanOrEqual(1, $resp['data']['total']);
        $this->assertSame(1, $resp['data']['page']);
    }

    #[Test]
    public function index_filters_by_rating_status_keyword(): void
    {
        // rating 筛选
        $resp = $this->body($this->controller()->index(
            $this->makeRequest('GET', '/admin/reviews', [], ['rating' => 5])
        ));
        $this->assertSame(0, $resp['code']);
        foreach ($resp['data']['list'] as $item) {
            $this->assertSame(5, $item['rating']);
        }

        // status 筛选（隐藏的应不出现）
        $resp = $this->body($this->controller()->index(
            $this->makeRequest('GET', '/admin/reviews', [], ['status' => OrderReview::STATUS_HIDDEN])
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertSame(0, $resp['data']['total']);

        // keyword 内容筛选
        $resp = $this->body($this->controller()->index(
            $this->makeRequest('GET', '/admin/reviews', [], ['keyword' => '专业'])
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertGreaterThanOrEqual(1, $resp['data']['total']);
    }

    // ── 详情 ──

    #[Test]
    public function show_returns_detail_with_relations(): void
    {
        $resp = $this->body($this->controller()->show(
            $this->makeRequest('GET', '/admin/reviews/' . $this->reviewHashid), $this->reviewHashid
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertSame(5, $resp['data']['rating']);
        $this->assertArrayHasKey('order', $resp['data']);
        $this->assertArrayHasKey('user', $resp['data']);
        $this->assertArrayHasKey('technician', $resp['data']);
        $this->assertSame('评价测试技师', $resp['data']['technician']['real_name']);
    }

    #[Test]
    public function show_returns_404_for_missing(): void
    {
        $missingHashid = HashidsService::encode(99999999999999);
        $resp = $this->body($this->controller()->show(
            $this->makeRequest('GET', '/admin/reviews/' . $missingHashid), $missingHashid
        ));
        $this->assertSame(404, $resp['code']);
    }

    // ── 审核 ──

    #[Test]
    public function audit_hide_and_show_toggle_status(): void
    {
        // hide → 0
        $resp = $this->body($this->controller()->audit(
            $this->makeRequest('PUT', "/admin/reviews/{$this->reviewHashid}/audit", ['action' => 'hide']),
            $this->reviewHashid
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertSame(OrderReview::STATUS_HIDDEN, $resp['data']['status']);
        $this->assertSame(0, OrderReview::find($this->reviewId)->status);

        // show → 1
        $resp = $this->body($this->controller()->audit(
            $this->makeRequest('PUT', "/admin/reviews/{$this->reviewHashid}/audit", ['action' => 'show']),
            $this->reviewHashid
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertSame(OrderReview::STATUS_VISIBLE, $resp['data']['status']);
        $this->assertSame(1, OrderReview::find($this->reviewId)->status);
    }

    #[Test]
    public function audit_rejects_invalid_action(): void
    {
        $resp = $this->body($this->controller()->audit(
            $this->makeRequest('PUT', "/admin/reviews/{$this->reviewHashid}/audit", ['action' => 'delete']),
            $this->reviewHashid
        ));
        $this->assertSame(422, $resp['code']);
    }

    // ── 回复查看 ──

    #[Test]
    public function reply_returns_empty_when_not_replied(): void
    {
        $resp = $this->body($this->controller()->reply(
            $this->makeRequest('GET', "/admin/reviews/{$this->reviewHashid}/reply"), $this->reviewHashid
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertSame('', $resp['data']['reply']);
        $this->assertArrayHasKey('replied_at', $resp['data']);
    }

    #[Test]
    public function reply_returns_content_when_replied(): void
    {
        $review = OrderReview::find($this->reviewId);
        $review->reply = '感谢您的评价，欢迎再来';
        $review->replied_at = date('Y-m-d H:i:s');
        $review->save();

        $resp = $this->body($this->controller()->reply(
            $this->makeRequest('GET', "/admin/reviews/{$this->reviewHashid}/reply"), $this->reviewHashid
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertSame('感谢您的评价，欢迎再来', $resp['data']['reply']);
        $this->assertNotNull($resp['data']['replied_at']);
    }

    #[Test]
    public function reply_returns_404_for_missing(): void
    {
        $missingHashid = HashidsService::encode(99999999999999);
        $resp = $this->body($this->controller()->reply(
            $this->makeRequest('GET', "/admin/reviews/{$missingHashid}/reply"), $missingHashid
        ));
        $this->assertSame(404, $resp['code']);
    }

    // ── 删除 ──

    #[Test]
    public function destroy_removes_review(): void
    {
        $resp = $this->body($this->controller()->destroy(
            $this->makeRequest('DELETE', '/admin/reviews/' . $this->reviewHashid), $this->reviewHashid
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertNull(OrderReview::find($this->reviewId));
    }
}
