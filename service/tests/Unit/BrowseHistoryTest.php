<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\BrowseHistoryController;
use app\api\v1\controller\ServiceController;
use app\model\BrowseHistory;
use app\model\Service;
use app\model\User;
use support\Container;
use support\Db;
use Webman\Http\Request;

/**
 * 用户浏览足迹测试（真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 登录用户浏览详情后列表出现足迹
 * - 重复浏览不重复插入，只刷新 viewed_at
 * - 未登录浏览不记录
 * - 列表 join 服务名称/封面/价格/原价，item_id 为 hashid
 * - 删除单条仅限本人
 * - 清空仅限本人
 */
class BrowseHistoryTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例服务 ID */
    private array $serviceIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            BrowseHistory::where('user_id', $id)->delete();
        }
        foreach ($this->serviceIds as $id) {
            Db::table('appointment_service')->where('id', $id)->delete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->userIds    = [];
        $this->serviceIds = [];
    }

    /** 造用户 */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '188' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '浏览足迹测试用户',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造服务（直接写库，避免 Scout 同步 OpenSearch） */
    private function makeService(string $name = '推拿理疗', float $price = 99.9, float $originalPrice = 129.9): Service
    {
        $id = Service::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_service')->insert([
            'id'             => $id,
            'name'           => $name,
            'cover_image'    => 'https://example.com/cover.jpg',
            'price'          => $price,
            'original_price' => $originalPrice,
            'status'         => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
        $this->serviceIds[] = $id;
        return Service::find($id);
    }

    /** 造请求（user_id 由 Auth 中间件注入，测试直接赋值；null 模拟未登录） */
    private function makeRequest(string $method, ?string $userId = null): Request
    {
        $request = new Request($method . " / HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: 0\r\n\r\n");
        if ($userId !== null) {
            $request->user_id = $userId;
        }
        return $request;
    }

    /** hashid 编码 */
    private function hashidOf(int|string $id): string
    {
        return (string) Container::get('hashids')->encode((int) $id);
    }

    /** 模拟用户浏览服务详情 */
    private function viewDetail(?User $user, Service $service): array
    {
        $controller = new ServiceController();
        $response = $controller->detail(
            $this->hashidOf($service->id),
            $this->makeRequest('GET', $user ? (string) $user->id : null)
        );
        return json_decode($response->rawBody(), true);
    }

    /** 调浏览足迹控制器 */
    private function callController(string $method, User $user, ?string $pathItemId = null): array
    {
        $controller = new BrowseHistoryController();
        $request    = $this->makeRequest($method, (string) $user->id);
        $response   = match ($method) {
            'LIST'    => $controller->index($request),
            'DESTROY' => $controller->destroy($request, (string) $pathItemId),
            default   => $controller->clear($request),
        };
        return json_decode($response->rawBody(), true);
    }

    // ── 详情浏览后记录足迹 ──

    #[Test] public function view_detail_records_browse_history(): void
    {
        $user    = $this->makeUser();
        $service = $this->makeService();

        $resp = $this->viewDetail($user, $service);

        $this->assertSame(0, $resp['code']);

        $row = BrowseHistory::where('user_id', $user->id)
            ->where('item_id', $service->id)
            ->first();
        $this->assertNotNull($row);
        $this->assertNotEmpty($row->viewed_at);
    }

    // ── 重复浏览不重复插入，只更新时间 ──

    #[Test] public function repeat_view_updates_time_not_duplicates(): void
    {
        $user    = $this->makeUser();
        $service = $this->makeService();

        $this->viewDetail($user, $service);

        // 将首次浏览时间改为过去，验证重复浏览仅刷新 viewed_at
        BrowseHistory::where('user_id', $user->id)
            ->where('item_id', $service->id)
            ->update(['viewed_at' => '2020-01-01 00:00:00']);

        $this->viewDetail($user, $service);

        $rows = BrowseHistory::where('user_id', $user->id)
            ->where('item_id', $service->id)
            ->get();
        $this->assertCount(1, $rows);
        $this->assertNotSame('2020-01-01 00:00:00', $rows->first()->viewed_at->format('Y-m-d H:i:s'));
    }

    // ── 未登录浏览不记录 ──

    #[Test] public function anonymous_view_not_recorded(): void
    {
        $service = $this->makeService();

        $resp = $this->viewDetail(null, $service);

        $this->assertSame(0, $resp['code']);
        $this->assertSame(0, BrowseHistory::where('item_id', $service->id)->count());
    }

    // ── 列表 join 服务信息 ──

    #[Test] public function index_joins_service_info(): void
    {
        $user    = $this->makeUser();
        $first   = $this->makeService('拔罐刮痧', 66.6, 88.8);
        $second  = $this->makeService('推拿理疗', 99.9, 129.9);

        // 先看 first 再看 second，列表应按 viewed_at 倒序 second 在前
        $this->viewDetail($user, $first);
        $this->viewDetail($user, $second);

        $resp = $this->callController('LIST', $user);

        $this->assertSame(0, $resp['code']);
        $this->assertSame(2, $resp['meta']['total']);
        $this->assertCount(2, $resp['data']);

        $top = $resp['data'][0];
        $this->assertSame($this->hashidOf($second->id), $top['item_id']);
        $this->assertSame('推拿理疗', $top['name']);
        $this->assertSame('https://example.com/cover.jpg', $top['cover_image']);
        $this->assertSame(99.9, $top['price']);
        $this->assertSame(129.9, $top['original_price']);
        $this->assertNotEmpty($top['viewed_at']);

        $this->assertSame($this->hashidOf($first->id), $resp['data'][1]['item_id']);
        $this->assertSame('拔罐刮痧', $resp['data'][1]['name']);
    }

    // ── 删除单条仅限本人 ──

    #[Test] public function destroy_only_own_record(): void
    {
        $owner    = $this->makeUser();
        $other    = $this->makeUser();
        $service  = $this->makeService();

        $this->viewDetail($owner, $service);

        // 他人删除返回 404，不影响本人记录
        $resp = $this->callController('DESTROY', $other, $this->hashidOf($service->id));
        $this->assertSame(404, $resp['code']);
        $this->assertSame(1, BrowseHistory::where('user_id', $owner->id)->count());

        // 非法 hashid 返回 404
        $resp = $this->callController('DESTROY', $owner, 'invalid');
        $this->assertSame(404, $resp['code']);

        // 本人删除成功
        $resp = $this->callController('DESTROY', $owner, $this->hashidOf($service->id));
        $this->assertSame(0, $resp['code']);
        $this->assertSame(0, BrowseHistory::where('user_id', $owner->id)->count());
    }

    // ── 清空仅限本人 ──

    #[Test] public function clear_only_own_records(): void
    {
        $owner    = $this->makeUser();
        $other    = $this->makeUser();
        $serviceA = $this->makeService();
        $serviceB = $this->makeService();

        $this->viewDetail($owner, $serviceA);
        $this->viewDetail($owner, $serviceB);
        $this->viewDetail($other, $serviceA);

        $resp = $this->callController('CLEAR', $owner);

        $this->assertSame(0, $resp['code']);
        $this->assertSame(0, BrowseHistory::where('user_id', $owner->id)->count());
        $this->assertSame(1, BrowseHistory::where('user_id', $other->id)->count());
    }
}
