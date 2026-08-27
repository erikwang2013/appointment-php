<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\GuestController;
use app\model\Service;
use app\model\ServiceCategory;
use app\model\TechnicianProfile;
use app\model\User;
use support\Container;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 游客模式测试（未登录只读浏览，真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 未带 token 直接请求各端点返回 200 及数据形状
 * - 服务分类筛选 / 分页
 * - 服务详情 hashid 解码；无效 hashid 与不存在均 404 不泄露
 * - 技师列表含评分、按 service_id 筛选
 * - 带 token（user_id 注入）请求同样兼容
 */
class GuestTest extends TestCase
{
    /** @var array<string, string[]> 用例插入行，tearDown 按表硬删清理 */
    private array $cleanup = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $table => $ids) {
            if (!empty($ids)) {
                Db::table($table)->whereIn('id', $ids)->delete();
            }
        }
        $this->cleanup = [];
    }

    private function track(string $table, string $id): void
    {
        $this->cleanup[$table][] = $id;
    }

    /** 造请求（不设 user_id 模拟未登录；$userId 非空模拟已认证兼容性） */
    private function makeRequest(string $uri, ?string $userId = null): Request
    {
        $request = new Request("GET {$uri} HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: 0\r\n\r\n");
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

    private function guest(): GuestController
    {
        return new GuestController();
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true);
    }

    /** 造服务分类 */
    private function makeCategory(string $name = '游客测试分类'): ServiceCategory
    {
        $id = ServiceCategory::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_service_category')->insert([
            'id' => $id, 'name' => $name, 'icon' => '', 'parent_id' => 0,
            'sort' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->track('appointment_service_category', $id);
        return ServiceCategory::find($id);
    }

    /** 造服务（直接写库，避免 Scout 同步 OpenSearch） */
    private function makeService(int|string $categoryId, string $name = '游客测试服务'): Service
    {
        $id = Service::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_service')->insert([
            'id' => $id, 'category_id' => (string) $categoryId, 'name' => $name,
            'cover_image' => 'https://example.com/cover.jpg', 'price' => 99.9,
            'original_price' => 129.9, 'duration' => 60, 'sales_volume' => 0,
            'sort' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->track('appointment_service', $id);
        return Service::find($id);
    }

    /** 造用户 + 技师档案 */
    private function makeTechnician(): array
    {
        $user = User::create([
            'phone'     => '188' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '游客测试技师',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->track('appointment_user', $user->id);

        $id = TechnicianProfile::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_technician_profile')->insert([
            'id' => $id, 'user_id' => $user->id, 'real_name' => '游客测试技师',
            'gender' => 0, 'id_card' => '', 'id_card_front' => '', 'id_card_back' => '',
            'avatar' => '', 'intro' => '', 'cover_image' => '', 'video_url' => '',
            'rating' => 5.0, 'order_count' => 0, 'favorite_count' => 0,
            'status' => 'approved', 'audit_remark' => '',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->track('appointment_technician_profile', $id);

        return ['user' => $user, 'profile' => TechnicianProfile::find($id)];
    }

    // ── 首页聚合 ──

    #[Test] public function home_returns_aggregated_sections(): void
    {
        $resp = $this->body($this->guest()->home());

        $this->assertSame(0, $resp['code']);
        foreach (['banners', 'announcements', 'categories', 'hot_services'] as $key) {
            $this->assertArrayHasKey($key, $resp['data']);
            $this->assertIsArray($resp['data'][$key]);
        }
        $this->assertNotEmpty($resp['data']['banners']);
        $this->assertNotEmpty($resp['data']['announcements']);
        $this->assertNotEmpty($resp['data']['categories']);
        $this->assertNotEmpty($resp['data']['hot_services']);
    }

    // ── 服务列表 ──

    #[Test] public function services_returns_paginated_list(): void
    {
        $resp = $this->body($this->guest()->services(
            $this->makeRequest('/api/guest/services?page=1&per_page=5')
        ));

        $this->assertSame(0, $resp['code']);
        $this->assertIsArray($resp['data']);
        $this->assertNotEmpty($resp['data']);
        $this->assertLessThanOrEqual(5, count($resp['data']));
        $this->assertArrayHasKey('total', $resp['meta']);
        $this->assertArrayHasKey('has_more', $resp['meta']);
    }

    #[Test] public function services_filters_by_category(): void
    {
        $category = $this->makeCategory();
        $service  = $this->makeService($category->id, '游客分类专属服务');

        $resp = $this->body($this->guest()->services(
            $this->makeRequest('/api/guest/services?category_id=' . $this->hashidOf($category->id))
        ));

        $this->assertSame(0, $resp['code']);
        $ids = array_column($resp['data'], 'id');
        $this->assertContains($this->hashidOf($service->id), $ids);
        foreach ($resp['data'] as $item) {
            $this->assertIsString($item['id']);
            $this->assertNotEmpty($item['name']);
        }
    }

    // ── 服务详情（404 不泄露）──

    #[Test] public function service_detail_returns_404_for_invalid_hashid(): void
    {
        $resp = $this->body($this->guest()->serviceDetail('not-a-hashid'));

        $this->assertSame(404, $resp['code']);
    }

    #[Test] public function service_detail_returns_404_for_missing_service(): void
    {
        // 有效 hashid 但对应记录不存在
        $resp = $this->body($this->guest()->serviceDetail($this->hashidOf(9007199254740991)));

        $this->assertSame(404, $resp['code']);
    }

    #[Test] public function service_detail_returns_service_with_hashid_ids(): void
    {
        $category = $this->makeCategory();
        $service  = $this->makeService($category->id, '游客详情服务');

        $resp = $this->body($this->guest()->serviceDetail($this->hashidOf($service->id)));

        $this->assertSame(0, $resp['code']);
        $this->assertSame($this->hashidOf($service->id), $resp['data']['id']);
        $this->assertSame('游客详情服务', $resp['data']['name']);
        $this->assertSame($this->hashidOf($category->id), $resp['data']['category_id']);
    }

    // ── 门店 ──

    #[Test] public function stores_returns_list_with_coordinates(): void
    {
        $resp = $this->body($this->guest()->stores());

        $this->assertSame(0, $resp['code']);
        $this->assertNotEmpty($resp['data']);
        foreach ($resp['data'] as $store) {
            $this->assertIsString($store['id']);
            $this->assertArrayHasKey('name', $store);
            $this->assertArrayHasKey('lat', $store);
            $this->assertArrayHasKey('lng', $store);
            $this->assertArrayHasKey('status', $store);
        }
    }

    // ── 技师 ──

    #[Test] public function technicians_returns_list_with_rating(): void
    {
        $resp = $this->body($this->guest()->technicians(
            $this->makeRequest('/api/guest/technicians?per_page=5')
        ));

        $this->assertSame(0, $resp['code']);
        $this->assertNotEmpty($resp['data']);
        foreach ($resp['data'] as $tech) {
            $this->assertIsString($tech['id']);
            $this->assertArrayHasKey('name', $tech);
            $this->assertArrayHasKey('avatar', $tech);
            $this->assertArrayHasKey('rating', $tech);
            $this->assertArrayHasKey('order_count', $tech);
        }
    }

    #[Test] public function technicians_filters_by_service(): void
    {
        $category = $this->makeCategory();
        $service  = $this->makeService($category->id);
        $linked   = $this->makeTechnician();
        $unlinked = $this->makeTechnician();

        $tsId = Service::generateId();
        $now  = date('Y-m-d H:i:s');
        Db::table('appointment_technician_service')->insert([
            'id' => $tsId, 'technician_id' => $linked['profile']->id,
            'service_id' => $service->id, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->track('appointment_technician_service', $tsId);

        $resp = $this->body($this->guest()->technicians(
            $this->makeRequest('/api/guest/technicians?service_id=' . $this->hashidOf($service->id))
        ));

        $this->assertSame(0, $resp['code']);
        $ids = array_column($resp['data'], 'id');
        $this->assertContains($this->hashidOf($linked['profile']->id), $ids);
        $this->assertNotContains($this->hashidOf($unlinked['profile']->id), $ids);
    }

    // ── 带 token 兼容性 ──

    #[Test] public function endpoints_compatible_with_token(): void
    {
        // 带 token（user_id 注入）请求同样可用——游客模式与登录态并存
        $request = $this->makeRequest('/api/guest/services?page=1', '12345');
        $resp    = $this->body($this->guest()->services($request));

        $this->assertSame(0, $resp['code']);
        $this->assertIsArray($resp['data']);
        $this->assertNotEmpty($resp['data']);
    }
}
