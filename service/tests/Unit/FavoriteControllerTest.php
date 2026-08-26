<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\user\v1\controller\FavoriteController;
use app\model\Service;
use app\model\TechnicianProfile;
use app\model\User;
use app\model\UserFavorite;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 收藏控制器测试
 *
 * 覆盖：无效收藏类型 400 / 空目标 400 / 收藏服务成功 / 重复收藏 400 /
 * 收藏技师 favorite_count 自增 / 列表关联目标详情与孤儿收藏 / 取消收藏自减与 404。
 */
class FavoriteControllerTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例收藏 ID */
    private array $favoriteIds = [];

    /** @var int[] 用例服务 ID */
    private array $serviceIds = [];

    /** @var int[] 用例技师档案 ID */
    private array $technicianIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserFavorite::where('user_id', $uid)->delete();
            User::where('id', $uid)->forceDelete();
        }
        if ($this->favoriteIds) {
            UserFavorite::whereIn('id', $this->favoriteIds)->delete();
        }
        if ($this->serviceIds) {
            Service::whereIn('id', $this->serviceIds)->forceDelete();
        }
        if ($this->technicianIds) {
            TechnicianProfile::whereIn('id', $this->technicianIds)->forceDelete();
        }
        $this->userIds = [];
        $this->favoriteIds = [];
        $this->serviceIds = [];
        $this->technicianIds = [];
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
            'phone'     => '196' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $uid;
        return $uid;
    }

    private function makeService(): Service
    {
        $s = Service::create([
            'id'          => Service::generateId(),
            'category_id' => 0,
            'name'        => '肩颈按摩',
            'cover_image' => 's.png',
            'price'       => 99.00,
            'sales_volume' => 12,
            'status'      => 1,
        ]);
        $this->serviceIds[] = (int) $s->id;
        return $s;
    }

    private function makeTechnician(): TechnicianProfile
    {
        $user = $this->newUserId();
        $t = TechnicianProfile::create([
            'user_id'    => $user,
            'real_name'  => '王师傅',
            'avatar'     => 't.png',
            'rating'     => 4.8,
            'order_count' => 30,
            'status'     => 'active',
        ]);
        $this->technicianIds[] = (int) $t->id;
        return $t;
    }

    private function withUser(string $userId, array $post = []): Request
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $request;
    }

    #[Test] public function store_favorites_service_success(): void
    {
        $uid = $this->newUserId();
        $service = $this->makeService();

        $resp = $this->body((new FavoriteController())->store($this->withUser($uid, [
            'target_type' => 'service', 'target_id' => (string) $service->id,
        ])));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('收藏成功', $resp['message']);
        $row = UserFavorite::where('user_id', $uid)->first();
        $this->assertNotNull($row);
        $this->assertSame('service', $row->target_type);
        $this->favoriteIds[] = $row->id;
    }

    #[Test] public function store_rejects_invalid_target_type_and_empty_target(): void
    {
        $uid = $this->newUserId();

        $badType = $this->body((new FavoriteController())->store($this->withUser($uid, [
            'target_type' => 'store', 'target_id' => '1',
        ])));
        $this->assertSame(400, $badType['code']);
        $this->assertStringContainsString('无效的收藏类型', (string) $badType['message']);

        $empty = $this->body((new FavoriteController())->store($this->withUser($uid, [
            'target_type' => 'service', 'target_id' => '',
        ])));
        $this->assertSame(400, $empty['code']);
        $this->assertStringContainsString('请指定收藏目标', (string) $empty['message']);
        $this->assertSame(0, UserFavorite::where('user_id', $uid)->count());
    }

    #[Test] public function store_duplicate_favorite_rejected(): void
    {
        $uid = $this->newUserId();
        $service = $this->makeService();
        $controller = new FavoriteController();
        $controller->store($this->withUser($uid, ['target_type' => 'service', 'target_id' => (string) $service->id]));

        $dup = $this->body($controller->store($this->withUser($uid, ['target_type' => 'service', 'target_id' => (string) $service->id])));

        $this->assertSame(400, $dup['code']);
        $this->assertStringContainsString('已收藏', (string) $dup['message']);
        $this->assertSame(1, UserFavorite::where('user_id', $uid)->count());
    }

    #[Test] public function store_technician_increments_favorite_count(): void
    {
        $uid = $this->newUserId();
        $tech = $this->makeTechnician();

        $resp = $this->body((new FavoriteController())->store($this->withUser($uid, [
            'target_type' => 'technician', 'target_id' => (string) $tech->id,
        ])));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(1, (int) TechnicianProfile::find($tech->id)->favorite_count, '技师被收藏数 +1');
    }

    #[Test] public function index_joins_target_details_and_keeps_orphan_favorites(): void
    {
        $uid = $this->newUserId();
        $service = $this->makeService();
        $tech = $this->makeTechnician();
        $controller = new FavoriteController();
        $controller->store($this->withUser($uid, ['target_type' => 'service', 'target_id' => (string) $service->id]));
        $controller->store($this->withUser($uid, ['target_type' => 'technician', 'target_id' => (string) $tech->id]));
        // 收藏一个已删除的服务 → 列表保留条目但无 target
        $ghost = $this->makeService();
        $controller->store($this->withUser($uid, ['target_type' => 'service', 'target_id' => (string) $ghost->id]));
        $ghost->forceDelete();

        $resp = $this->body($controller->index($this->withUser($uid)));

        $this->assertSame(0, $resp['code']);
        $this->assertCount(3, $resp['data']);
        $targets = array_column(array_filter($resp['data'], fn ($i) => isset($i['target'])), 'target');
        $this->assertCount(2, $targets, '两条有关联目标');
        $this->assertSame('肩颈按摩', $targets[0]['name']);
        $this->assertSame(12, $targets[0]['sales_volume']);
        $this->assertSame('王师傅', $targets[1]['real_name']);
        $this->assertSame(4.8, (float) $targets[1]['rating']);
        $orphan = array_values(array_filter($resp['data'], fn ($i) => !isset($i['target'])));
        $this->assertCount(1, $orphan, '目标已删除仍保留收藏条目');
        $this->assertSame('service', $orphan[0]['target_type']);
    }

    #[Test] public function destroy_removes_favorite_and_decrements_count(): void
    {
        $uid = $this->newUserId();
        $tech = $this->makeTechnician();
        $controller = new FavoriteController();
        $controller->store($this->withUser($uid, ['target_type' => 'technician', 'target_id' => (string) $tech->id]));
        $fav = UserFavorite::where('user_id', $uid)->first();
        $this->favoriteIds[] = $fav->id;
        $this->assertSame(1, (int) TechnicianProfile::find($tech->id)->favorite_count);

        $resp = $this->body($controller->destroy($this->withUser($uid), (string) $fav->id));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNull(UserFavorite::find($fav->id));
        $this->assertSame(0, (int) TechnicianProfile::find($tech->id)->favorite_count, '被收藏数 -1');
    }

    #[Test] public function destroy_returns_404_for_missing_favorite(): void
    {
        $uid = $this->newUserId();
        $service = $this->makeService();
        $controller = new FavoriteController();
        $controller->store($this->withUser($uid, ['target_type' => 'service', 'target_id' => (string) $service->id]));
        $fav = UserFavorite::where('user_id', $uid)->first();

        $resp = $this->body($controller->destroy($this->withUser($uid), (string) $fav->id));
        $this->assertSame(0, $resp['code']);
        $resp2 = $this->body($controller->destroy($this->withUser($uid), (string) $fav->id));
        $this->assertSame(404, $resp2['code']);
    }
}
