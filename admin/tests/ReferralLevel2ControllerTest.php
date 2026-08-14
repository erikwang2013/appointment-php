<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\ReferralLevel2Controller;
use app\model\Order;
use app\model\ReferralLevel2Reward;
use app\model\User;
use support\Db;
use support\Request;
use support\Response;

/**
 * 二级返佣记录管理测试（只读列表）
 *
 * 覆盖：分页返回记录、字段完整（二级推荐人/被推荐人昵称/订单号/金额）、关键词筛选。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class ReferralLevel2ControllerTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;
    private string $topId;
    private string $referredId;

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

        // 固定 id：同毫秒连续调用 snowflake 可能生成相同 id，测试用固定值避免主键冲突
        $this->topId = '90000000000003101';
        $this->referredId = '90000000000003102';
        $middleId = '90000000000003103';

        $top = new User();
        $top->id = $this->topId;
        $top->phone = '137' . substr(uniqid(), -8);
        $top->nickname = '二级推荐人';
        $top->password = '';
        $top->status = 1;
        $top->user_type = 'user';
        $top->save();

        $referred = new User();
        $referred->id = $this->referredId;
        $referred->phone = '136' . substr(uniqid(), -8);
        $referred->nickname = '二级被推荐人';
        $referred->password = '';
        $referred->status = 1;
        $referred->user_type = 'user';
        $referred->save();

        $middle = new User();
        $middle->id = $middleId;
        $middle->phone = '135' . substr(uniqid(), -8);
        $middle->nickname = '一级推荐人';
        $middle->password = '';
        $middle->status = 1;
        $middle->user_type = 'user';
        $middle->save();

        // 测试环境无 webman Initializer 自动生成 id，须显式指定（同 ReferralRewardControllerTest 策略）
        $order = new Order();
        $order->id = '90000000000003105';
        $order->order_no = 'ORD_L2_ADMIN_' . uniqid();
        $order->user_id = $this->referredId;
        $order->technician_id = $middleId;
        $order->order_type = Order::ORDER_TYPE_APPOINTMENT;
        $order->total_amount = 100.0;
        $order->discount_amount = 0.0;
        $order->paid_amount = 100.0;
        $order->status = Order::STATUS_COMPLETED;
        $order->save();

        $reward = new ReferralLevel2Reward();
        $reward->id = '90000000000003104';
        $reward->order_id = (string) $order->id;
        $reward->referred_user_id = $this->referredId;
        $reward->referrer_id = $this->topId;
        $reward->amount = 2.00;
        $reward->status = 1;
        $reward->save();
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

    private function request(array $params = []): Request
    {
        $request = new Request("GET /admin/referral-level2 HTTP/1.1\r\nHost: localhost\r\n\r\n");
        if ($params) {
            $request->setGet($params);
        }
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    #[Test] public function index_returns_level2_records_with_fields(): void
    {
        $resp = $this->body((new ReferralLevel2Controller())->index($this->request()));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $data = $resp['data'];
        $this->assertSame(1, $data['total']);
        $this->assertCount(1, $data['list']);
        $this->assertSame('二级推荐人', $data['list'][0]['referrer_nickname']);
        $this->assertSame('二级被推荐人', $data['list'][0]['referred_nickname']);
        $this->assertSame(2.0, (float) $data['list'][0]['amount']);
        $this->assertNotEmpty($data['list'][0]['order_no']);
        $this->assertNotEmpty($data['list'][0]['created_at']);
        // id/order_id 字段已 hashid 编码
        $this->assertNotSame(
            $data['list'][0]['id'],
            ReferralLevel2Reward::first()->id
        );
    }

    #[Test] public function index_filters_by_keyword(): void
    {
        // 按被推荐人昵称命中
        $resp = $this->body((new ReferralLevel2Controller())->index($this->request(['keyword' => '被推荐人'])));
        $this->assertSame(1, $resp['data']['total']);

        // 无命中关键词
        $resp = $this->body((new ReferralLevel2Controller())->index($this->request(['keyword' => '不存在的昵称xyz'])));
        $this->assertSame(0, $resp['data']['total']);
    }
}
