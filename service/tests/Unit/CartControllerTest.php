<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\order\v1\controller\CartController;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 购物车控制器测试（Redis 存储 cart:{user_id}）
 *
 * 覆盖：整体覆盖保存 + 字段规范化（白名单/quantity 下限 1/丢弃脏条目）、
 * items 非数组 400、读空车/读已存车、清空。
 */
class CartControllerTest extends TestCase
{
    /** @var string[] 用例写入的 Redis key，tearDown 统一清理 */
    private array $redisKeys = [];

    protected function tearDown(): void
    {
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }
        $this->redisKeys = [];
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

    private function makeUserRequest(array $post = []): Request
    {
        $uid = (string) random_int(8800000000000000, 8899999999999999);
        $request = $this->makeRequest($post);
        $request->user_id = $uid;
        $this->redisKeys[] = "cart:{$uid}";
        return $request;
    }

    #[Test] public function store_normalizes_items_and_persists_cart(): void
    {
        $request = $this->makeUserRequest([
            'items' => [
                ['id' => 'srv_1', 'name' => '肩颈按摩', 'price' => '99.5', 'cover_image' => 'a.png', 'quantity' => '2', 'junk' => 'drop'],
                ['id' => 'srv_2', 'name' => 'SPA', 'price' => '0', 'quantity' => '0'],
                'not-an-array',
            ],
        ]);

        $resp = $this->body((new CartController())->store($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('购物车已保存', $resp['message']);
        $this->assertCount(2, $resp['data'], '非数组条目被丢弃');
        $this->assertSame('srv_1', $resp['data'][0]['id']);
        $this->assertSame(99.5, $resp['data'][0]['price']);
        $this->assertSame(2, $resp['data'][0]['quantity']);
        $this->assertArrayNotHasKey('junk', $resp['data'][0], '白名单外字段被丢弃');
        $this->assertSame(1, $resp['data'][1]['quantity'], 'quantity 下限为 1');

        // 持久化到 Redis，index 可读回
        $read = $this->body((new CartController())->index($request));
        $this->assertSame($resp['data'], $read['data']);
    }

    #[Test] public function store_rejects_non_array_items(): void
    {
        $request = $this->makeUserRequest(['items' => 'not-an-array']);

        $resp = $this->body((new CartController())->store($request));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('格式错误', (string) $resp['message']);
    }

    #[Test] public function index_returns_empty_cart_for_new_user(): void
    {
        $request = $this->makeUserRequest();

        $resp = $this->body((new CartController())->index($request));

        $this->assertSame(0, $resp['code']);
        $this->assertSame([], $resp['data']);
    }

    #[Test] public function destroy_clears_cart(): void
    {
        $request = $this->makeUserRequest([
            'items' => [['id' => 'srv_1', 'name' => 'x', 'price' => '10', 'cover_image' => '', 'quantity' => '1']],
        ]);
        (new CartController())->store($request);

        $resp = $this->body((new CartController())->destroy($request));

        $this->assertSame(0, $resp['code']);
        $read = $this->body((new CartController())->index($request));
        $this->assertSame([], $read['data'], '清空后购物车为空');
    }
}
