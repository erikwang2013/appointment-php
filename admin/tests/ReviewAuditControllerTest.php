<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\ReviewAuditController;
use app\common\HashidsService;
use app\model\OrderReview;
use support\Db;
use support\Request;
use support\Response;

/**
 * 评价图片审核控制器测试（带图评价隐藏/恢复闭环）
 *
 * 覆盖：
 *   - 列表仅返回 images 非空 JSON 数组的评价
 *   - 隐藏：visible → hidden；隐藏后再隐藏 → 422
 *   - 恢复：hidden → visible；可见状态下恢复 → 422
 *   - 不存在记录 → 404
 *
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class ReviewAuditControllerTest extends TestCase
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

    private function controller(): ReviewAuditController
    {
        return new ReviewAuditController();
    }

    /** 直接插入一条评价（status/images 由调用方控制） */
    private function createReview(int $status, array $images): OrderReview
    {
        $review = new OrderReview();
        $review->id            = OrderReview::generateId();
        $review->order_id      = OrderReview::generateId();
        $review->user_id       = '90000000000003002';
        $review->technician_id = '90000000000003003';
        $review->rating        = 5;
        $review->content       = '审核测试评价内容';
        $review->images        = $images;
        $review->status        = $status;
        $review->save();
        return $review;
    }

    private function hashidOf(OrderReview $review): string
    {
        return HashidsService::encode((int) $review->id);
    }

    #[Test]
    public function list_returns_only_reviews_with_nonempty_images(): void
    {
        $withImages = $this->createReview(OrderReview::STATUS_VISIBLE, ['https://example.com/a.jpg']);
        $noImages   = $this->createReview(OrderReview::STATUS_VISIBLE, []);

        $resp = $this->controller()->index($this->makeRequest('GET', '/admin/review-audit'));
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $ids = array_column($data['data']['list'], 'id');
        $this->assertContains($this->hashidOf($withImages), $ids);
        $this->assertNotContains($this->hashidOf($noImages), $ids);
        $this->assertGreaterThanOrEqual(1, $data['data']['total']);
    }

    #[Test]
    public function list_filters_by_status(): void
    {
        $hidden = $this->createReview(OrderReview::STATUS_HIDDEN, ['https://example.com/h.jpg']);
        $visible = $this->createReview(OrderReview::STATUS_VISIBLE, ['https://example.com/v.jpg']);

        $resp = $this->controller()->index($this->makeRequest('GET', '/admin/review-audit?status=1'));
        $ids  = array_column($this->body($resp)['data']['list'], 'id');

        $this->assertContains($this->hashidOf($visible), $ids);
        $this->assertNotContains($this->hashidOf($hidden), $ids);
    }

    #[Test]
    public function hide_sets_hidden_status(): void
    {
        $review = $this->createReview(OrderReview::STATUS_VISIBLE, ['https://example.com/a.jpg']);

        $resp = $this->controller()->hide(
            $this->makeRequest('POST', '/admin/review-audit/' . $this->hashidOf($review) . '/hide'),
            $this->hashidOf($review)
        );
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame(OrderReview::STATUS_HIDDEN, $data['data']['status']);
        $this->assertSame(OrderReview::STATUS_HIDDEN, (int) OrderReview::find($review->id)->status);
    }

    #[Test]
    public function restore_sets_visible_status(): void
    {
        $review = $this->createReview(OrderReview::STATUS_HIDDEN, ['https://example.com/a.jpg']);

        $resp = $this->controller()->restore(
            $this->makeRequest('POST', '/admin/review-audit/' . $this->hashidOf($review) . '/restore'),
            $this->hashidOf($review)
        );
        $data = $this->body($resp);

        $this->assertSame(0, $data['code']);
        $this->assertSame(OrderReview::STATUS_VISIBLE, $data['data']['status']);
        $this->assertSame(OrderReview::STATUS_VISIBLE, (int) OrderReview::find($review->id)->status);
    }

    #[Test]
    public function status_change_in_wrong_direction_returns_422(): void
    {
        // 已隐藏的评价再次隐藏 → 422
        $hidden = $this->createReview(OrderReview::STATUS_HIDDEN, ['https://example.com/a.jpg']);
        $resp = $this->controller()->hide(
            $this->makeRequest('POST', '/admin/review-audit/' . $this->hashidOf($hidden) . '/hide'),
            $this->hashidOf($hidden)
        );
        $this->assertSame(422, $this->body($resp)['code']);

        // 可见的评价恢复 → 422
        $visible = $this->createReview(OrderReview::STATUS_VISIBLE, ['https://example.com/b.jpg']);
        $resp = $this->controller()->restore(
            $this->makeRequest('POST', '/admin/review-audit/' . $this->hashidOf($visible) . '/restore'),
            $this->hashidOf($visible)
        );
        $this->assertSame(422, $this->body($resp)['code']);
    }

    #[Test]
    public function missing_review_returns_404(): void
    {
        $hashid = HashidsService::encode(90000000000009999);
        $resp = $this->controller()->hide(
            $this->makeRequest('POST', '/admin/review-audit/' . $hashid . '/hide'),
            $hashid
        );
        $this->assertSame(404, $this->body($resp)['code']);
    }
}
