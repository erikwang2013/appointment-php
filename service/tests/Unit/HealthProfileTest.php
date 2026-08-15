<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\User;
use app\model\UserHealthProfile;
use app\user\v1\controller\HealthProfileController;
use Webman\Http\Request;

/**
 * 用户健康档案与服务偏好测试（真实 DB）
 *
 * 覆盖：
 * - 首次 PUT 创建档案（无行 → 插行）
 * - 再次 PUT 只更提供的字段，不重复建行
 * - GET 返回本人档案；他人 GET 只见空结构（字段隔离）
 * - preferred_technician_id 不存在 422
 * - 超长文本 422
 * - DELETE 清空档案（删行）
 */
class HealthProfileTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            UserHealthProfile::where('user_id', $id)->delete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->userIds = [];
    }

    /** 造用户（user_type 由调用方指定） */
    private function makeUser(string $userType = 'customer'): User
    {
        $user = User::create([
            'phone'     => '188' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '健康档案测试用户',
            'user_type' => $userType,
            'status'    => 1,
        ]);
        $this->userIds[] = (string) $user->id;
        return $user;
    }

    /** 造 JSON 请求（user_id 由 Auth 中间件注入，测试直接赋值） */
    private function makeRequest(string $method, string $userId, array $body = []): Request
    {
        $encoded = json_encode($body);
        $head    = "Host: localhost\r\n"
            . "Content-Type: application/json\r\n"
            . "Content-Length: " . strlen($encoded) . "\r\n";
        $request = new Request($method . " / HTTP/1.1\r\n" . $head . "\r\n" . $encoded);
        $request->user_id = $userId;
        return $request;
    }

    /** 调控制器并解码响应 */
    private function callController(string $method, string $userId, array $body = []): array
    {
        $controller = new HealthProfileController();
        $response   = match ($method) {
            'PUT'    => $controller->upsert($this->makeRequest('PUT', $userId, $body)),
            'GET'    => $controller->show($this->makeRequest('GET', $userId)),
            'DELETE' => $controller->destroy($this->makeRequest('DELETE', $userId)),
        };
        return json_decode($response->rawBody(), true);
    }

    // ── 首次 PUT 创建 ──

    #[Test] public function first_put_creates_profile_row(): void
    {
        $user    = $this->makeUser();
        $tech    = $this->makeUser('technician');
        $techId  = (string) $tech->id;

        $resp = $this->callController('PUT', (string) $user->id, [
            'allergies'              => '花粉、青霉素',
            'chronic_diseases'       => '高血压',
            'preferred_technician_id' => $techId,
            'preferred_time'         => '14:00-17:00',
            'notes'                  => '力度轻一点',
        ]);

        $this->assertSame(0, $resp['code']);
        $this->assertTrue($resp['data']['set']);
        $this->assertSame('花粉、青霉素', $resp['data']['allergies']);
        $this->assertNotEmpty($resp['data']['preferred_technician_id']);

        $rows = UserHealthProfile::where('user_id', $user->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame($techId, (string) $rows->first()->preferred_technician_id);
        $this->assertSame('高血压', $rows->first()->chronic_diseases);
        $this->assertSame('14:00-17:00', $rows->first()->preferred_time);
    }

    // ── 再次 PUT 更新不重复建行，只更提供的字段 ──

    #[Test] public function second_put_updates_only_provided_fields_without_new_row(): void
    {
        $user = $this->makeUser();
        $tech = $this->makeUser('technician');

        $this->callController('PUT', (string) $user->id, [
            'allergies' => '花粉',
            'notes'     => '原备注',
        ]);
        $resp = $this->callController('PUT', (string) $user->id, [
            'notes' => '新备注',
        ]);

        $this->assertSame(0, $resp['code']);
        $this->assertSame('新备注', $resp['data']['notes']);

        $rows = UserHealthProfile::where('user_id', $user->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('花粉', $rows->first()->allergies, '未提供的字段应保持不变');
        $this->assertSame('新备注', $rows->first()->notes);
    }

    // ── GET 本人档案 / 他人无权限（字段隔离）──

    #[Test] public function get_returns_own_profile_and_other_user_gets_empty(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();

        $this->callController('PUT', (string) $userA->id, ['allergies' => '海鲜过敏']);

        $respA = $this->callController('GET', (string) $userA->id);
        $this->assertSame(0, $respA['code']);
        $this->assertTrue($respA['data']['set']);
        $this->assertSame('海鲜过敏', $respA['data']['allergies']);

        $respB = $this->callController('GET', (string) $userB->id);
        $this->assertSame(0, $respB['code']);
        $this->assertFalse($respB['data']['set']);
        $this->assertSame('', $respB['data']['allergies']);
        $this->assertNull($respB['data']['preferred_technician_id']);
    }

    // ── preferred_technician_id 不存在 422 ──

    #[Test] public function nonexistent_technician_rejected_422(): void
    {
        $user  = $this->makeUser();
        $ghost = \support\Model::generateId();

        $resp = $this->callController('PUT', (string) $user->id, [
            'preferred_technician_id' => $ghost,
        ]);

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, UserHealthProfile::where('user_id', $user->id)->count());
    }

    // ── 超长文本 422 ──

    #[Test] public function oversized_text_rejected_422(): void
    {
        $user = $this->makeUser();

        $resp = $this->callController('PUT', (string) $user->id, [
            'allergies' => str_repeat('敏', 501),
        ]);

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, UserHealthProfile::where('user_id', $user->id)->count());

        $respTime = $this->callController('PUT', (string) $user->id, [
            'preferred_time' => str_repeat('1', 51),
        ]);
        $this->assertSame(422, $respTime['code']);
    }

    // ── DELETE 清空档案 ──

    #[Test] public function delete_clears_profile(): void
    {
        $user = $this->makeUser();
        $this->callController('PUT', (string) $user->id, ['allergies' => '花生']);

        $resp = $this->callController('DELETE', (string) $user->id);

        $this->assertSame(0, $resp['code']);
        $this->assertSame(0, UserHealthProfile::where('user_id', $user->id)->count());

        $after = $this->callController('GET', (string) $user->id);
        $this->assertFalse($after['data']['set']);
    }
}
