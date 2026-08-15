<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\ReturnCustomerController;
use app\model\Order;
use app\model\SystemConfig;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\User;
use support\Db;
use support\Request;
use support\Response;

/**
 * 回头客奖励管理测试（R24：30 天内二次消费奖金）
 *
 * 覆盖：配置查看默认值、配置更新落库、奖励记录列表（含技师/订单/用户信息）。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class ReturnCustomerTest extends TestCase
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
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function saveConfig(string $key, string $value): void
    {
        SystemConfig::updateOrCreate(
            ['group' => 'return_customer', 'key' => $key],
            ['value' => $value, 'type' => 'string', 'description' => '测试配置']
        );
    }

    private function makeReward(): TechnicianEarning
    {
        $user = new User();
        $user->id = User::generateId();
        $user->phone = '197' . substr((string) random_int(10000000, 99999999), 0, 8);
        $user->wx_openid = '';
        $user->user_type = 'user';
        $user->status = 1;
        $user->save();

        $techUser = new User();
        $techUser->id = User::generateId();
        $techUser->phone = '196' . substr((string) random_int(10000000, 99999999), 0, 8);
        $techUser->wx_openid = '';
        $techUser->user_type = 'user';
        $techUser->status = 1;
        $techUser->save();

        $technician = new TechnicianProfile();
        $technician->id = TechnicianProfile::generateId();
        $technician->user_id = $techUser->id;
        $technician->real_name = '回头客测试技师';
        $technician->status = 'active';
        $technician->audited_at = date('Y-m-d H:i:s');
        $technician->save();

        $order = new Order();
        $order->id = Order::generateId();
        $order->order_no = 'RC' . time() . random_int(1000, 9999);
        $order->user_id = $user->id;
        $order->technician_id = $technician->id;
        $order->order_type = 'appointment';
        $order->paid_amount = 100.0;
        $order->status = 'completed';
        $order->service_time = date('Y-m-d H:i:s');
        $order->save();

        $earning = new TechnicianEarning();
        $earning->id = TechnicianEarning::generateId();
        $earning->technician_id = $technician->id;
        $earning->order_id = $order->id;
        $earning->type = 'return_customer';
        $earning->amount = 5.0;
        $earning->description = '回头客奖励（订单 ' . $order->order_no . '，30天内二次消费）';
        $earning->status = 'pending';
        $earning->save();

        return $earning;
    }

    // ── 配置 ──

    #[Test]
    public function config_returns_seeded_defaults_when_absent(): void
    {
        // 事务内删除配置 → 接口应回退默认值（enabled=1, ratio=0.05）
        SystemConfig::where('group', 'return_customer')->delete();

        $response = (new ReturnCustomerController())->config($this->makeRequest('GET', '/admin/return-customer/config'));
        $data = $this->body($response);

        $this->assertSame(0, $data['code']);
        $this->assertSame(1, $data['data']['enabled']);
        $this->assertSame(0.05, (float) $data['data']['ratio']);
    }

    #[Test]
    public function update_config_validates_and_persists(): void
    {
        $controller = new ReturnCustomerController();

        // 非法比例 → 422
        $bad = $this->body($controller->updateConfig($this->makeRequest('PUT', '/admin/return-customer/config', [
            'enabled' => '1',
            'ratio'   => '2',
        ])));
        $this->assertSame(422, $bad['code']);

        // 合法更新 → 落库
        $ok = $this->body($controller->updateConfig($this->makeRequest('PUT', '/admin/return-customer/config', [
            'enabled' => '0',
            'ratio'   => '0.1',
        ])));
        $this->assertSame(0, $ok['code']);
        $this->assertSame(0, $ok['data']['enabled']);
        $this->assertSame(0.1, (float) $ok['data']['ratio']);

        $this->assertSame('0', (string) SystemConfig::where('group', 'return_customer')->where('key', 'enabled')->value('value'));
        $this->assertSame('0.1000', (string) SystemConfig::where('group', 'return_customer')->where('key', 'ratio')->value('value'));

        // 再读回
        $again = $this->body($controller->config($this->makeRequest('GET', '/admin/return-customer/config')));
        $this->assertSame(0, $again['data']['enabled']);
        $this->assertSame(0.1, (float) $again['data']['ratio']);
    }

    // ── 记录列表 ──

    #[Test]
    public function rewards_lists_only_return_customer_earnings_with_relations(): void
    {
        $this->saveConfig('enabled', '1');
        $reward = $this->makeReward();

        $response = (new ReturnCustomerController())->rewards($this->makeRequest('GET', '/admin/return-customer/rewards'));
        $data = $this->body($response);

        $this->assertSame(0, $data['code']);
        $this->assertGreaterThanOrEqual(1, $data['data']['total']);
        $item = collect($data['data']['list'])->firstWhere('order_no', $reward->order->order_no);
        $this->assertNotNull($item, '奖励记录应出现在列表中');
        $this->assertSame(5.0, (float) $item['amount']);
        $this->assertSame('回头客测试技师', $item['technician_name']);
        $this->assertSame($reward->order->order_no, $item['order_no']);
        $this->assertSame('pending', $item['status']);
        $this->assertArrayHasKey('user_nickname', $item);
    }

    #[Test]
    public function rewards_filters_by_keyword(): void
    {
        $this->saveConfig('enabled', '1');
        $reward = $this->makeReward();
        $orderNo = $reward->order->order_no;

        $response = (new ReturnCustomerController())->rewards($this->makeRequest('GET', '/admin/return-customer/rewards?keyword=' . urlencode($orderNo)));
        $data = $this->body($response);

        $this->assertSame(0, $data['code']);
        $this->assertGreaterThanOrEqual(1, $data['data']['total']);

        $response2 = (new ReturnCustomerController())->rewards($this->makeRequest('GET', '/admin/return-customer/rewards?keyword=不存在的关键词xyz'));
        $data2 = $this->body($response2);
        $this->assertSame(0, $data2['code']);
        $this->assertSame(0, $data2['data']['total']);
    }
}
