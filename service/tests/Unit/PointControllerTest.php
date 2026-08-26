<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\marketing\v1\controller\PointController;
use app\model\User;
use app\model\UserPoints;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 用户积分流水控制器测试
 *
 * 覆盖：余额取最新流水快照、分页 meta、type/source 过滤、无记录用户空列表。
 */
class PointControllerTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserPoints::where('user_id', $uid)->delete();
            User::where('id', $uid)->delete();
        }
        $this->userIds = [];
    }

    private function makeRequest(array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function newUserId(): string
    {
        $uid = (string) (9900000000000000 + random_int(1, 999999));
        User::create([
            'id'        => $uid,
            'phone'     => '198' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $uid;
        return $uid;
    }

    /** 造一条积分流水，createdAt 可显式指定以控制余额快照顺序 */
    private function addPoint(string $userId, string $type, int $points, int $balance, string $source, ?string $createdAt = null): void
    {
        $row = UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $userId,
            'type'        => $type,
            'points'      => $points,
            'balance'     => $balance,
            'source'      => $source,
            'description' => '测试流水',
        ]);
        if ($createdAt !== null) {
            $row->created_at = $createdAt;
            $row->save();
        }
    }

    private function index(string $userId, array $post = []): array
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $this->body((new PointController())->index($request));
    }

    #[Test] public function index_returns_latest_balance_with_paginated_records(): void
    {
        $uid = $this->newUserId();
        $this->addPoint($uid, 'earn', 100, 100, 'sign', '2026-08-01 10:00:00');
        $this->addPoint($uid, 'consume', -30, 70, 'exchange', '2026-08-02 10:00:00');
        $this->addPoint($uid, 'earn', 20, 90, 'referral', '2026-08-03 10:00:00');

        $resp = $this->index($uid);

        $this->assertSame(0, $resp['code']);
        $this->assertSame(90, $resp['data']['balance'], '余额 = 最新流水的 balance 快照');
        $this->assertCount(3, $resp['data']['records']);
        $this->assertSame('referral', $resp['data']['records'][0]['source'], '按 created_at 倒序');
        $this->assertSame(3, $resp['meta']['total']);
        $this->assertSame(1, $resp['meta']['current_page']);
        $this->assertFalse($resp['meta']['has_more']);
        $this->assertNotEmpty($resp['data']['records'][0]['id'], '流水 id 已 hashids 编码');
    }

    #[Test] public function index_filters_by_type_and_source(): void
    {
        $uid = $this->newUserId();
        $this->addPoint($uid, 'earn', 100, 100, 'sign', '2026-08-01 10:00:00');
        $this->addPoint($uid, 'earn', 50, 150, 'referral', '2026-08-02 10:00:00');
        $this->addPoint($uid, 'consume', -40, 110, 'exchange', '2026-08-03 10:00:00');

        $byType = $this->index($uid, ['type' => 'consume']);
        $this->assertCount(1, $byType['data']['records']);
        $this->assertSame('consume', $byType['data']['records'][0]['type']);
        $this->assertSame(1, $byType['meta']['total']);

        $bySource = $this->index($uid, ['source' => 'referral']);
        $this->assertCount(1, $bySource['data']['records']);
        $this->assertSame('referral', $bySource['data']['records'][0]['source']);
    }

    #[Test] public function index_respects_per_page(): void
    {
        $uid = $this->newUserId();
        for ($i = 1; $i <= 5; $i++) {
            $this->addPoint($uid, 'earn', 10, $i * 10, 'sign', "2026-08-0{$i} 10:00:00");
        }

        $resp = $this->index($uid, ['per_page' => 2]);

        $this->assertSame(2, $resp['meta']['per_page']);
        $this->assertCount(2, $resp['data']['records']);
        $this->assertSame(5, $resp['meta']['total']);
        $this->assertSame(3, $resp['meta']['last_page']);
        $this->assertTrue($resp['meta']['has_more']);
    }

    #[Test] public function index_returns_zero_balance_for_user_without_records(): void
    {
        $uid = $this->newUserId();

        $resp = $this->index($uid);

        $this->assertSame(0, $resp['code']);
        $this->assertSame(0, $resp['data']['balance']);
        $this->assertSame([], $resp['data']['records']);
        $this->assertSame(0, $resp['meta']['total']);
    }
}
