<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\admin\controller\ReferralRewardController;
use app\model\User;
use app\model\UserReferral;
use support\Db;
use support\Request;
use support\Response;

/**
 * 分销返佣记录管理测试（只读列表）
 *
 * 覆盖：仅返回已发放记录、分页字段完整、关键词筛选推荐人/被推荐人。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class ReferralRewardControllerTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;
    private string $referrerId;
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
        $this->referrerId = '90000000000003001';
        $this->referredId = '90000000000003002';

        $referrer = new User();
        $referrer->id = $this->referrerId;
        $referrer->phone = '138' . substr(uniqid(), -8);
        $referrer->nickname = '返佣推荐人';
        $referrer->password = '';
        $referrer->status = 1;
        $referrer->user_type = 'user';
        $referrer->save();

        $referred = new User();
        $referred->id = $this->referredId;
        $referred->phone = '139' . substr(uniqid(), -8);
        $referred->nickname = '返佣被推荐人';
        $referred->password = '';
        $referred->status = 1;
        $referred->user_type = 'user';
        $referred->save();

        // 一条已发放 + 一条未发放（应被过滤）
        $now = date('Y-m-d H:i:s');
        $rewarded = new UserReferral();
        $rewarded->id = '90000000000003003';
        $rewarded->referrer_id = $this->referrerId;
        $rewarded->referred_user_id = $this->referredId;
        $rewarded->registered_at = $now;
        $rewarded->reward_type = 'balance';
        $rewarded->reward_amount = '5.00';
        $rewarded->rewarded_at = $now;
        $rewarded->first_order_at = $now;
        $rewarded->save();

        $unrewarded = new UserReferral();
        $unrewarded->id = '90000000000003004';
        $unrewarded->referrer_id = $this->referrerId;
        $unrewarded->referred_user_id = $this->referredId;
        $unrewarded->registered_at = $now;
        $unrewarded->save();
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
    }

    /** 重建全局 Eloquent 连接（prefix 空，模型 $table 已内嵌 appointment_ 前缀） */
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
        $request = new Request("GET /admin/referral-rewards HTTP/1.1\r\nHost: localhost\r\n\r\n");
        if ($params) {
            $request->setGet($params);
        }
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    #[Test] public function index_returns_only_rewarded_records(): void
    {
        $resp = $this->body((new ReferralRewardController())->index($this->request()));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $data = $resp['data'];
        $this->assertSame(1, $data['total']);
        $this->assertCount(1, $data['list']);
        $this->assertSame('返佣推荐人', $data['list'][0]['referrer_nickname']);
        $this->assertSame('返佣被推荐人', $data['list'][0]['referred_nickname']);
        $this->assertSame(5.0, (float) $data['list'][0]['reward_amount']);
        $this->assertNotEmpty($data['list'][0]['rewarded_at']);
        // id 字段已 hashid 编码
        $this->assertNotSame($data['list'][0]['id'], UserReferral::where('rewarded_at', '!=', null)->first()->id);
    }

    #[Test] public function index_filters_by_keyword(): void
    {
        // 按被推荐人昵称命中
        $resp = $this->body((new ReferralRewardController())->index($this->request(['keyword' => '被推荐人'])));
        $this->assertSame(1, $resp['data']['total']);

        // 无命中关键词
        $resp = $this->body((new ReferralRewardController())->index($this->request(['keyword' => '不存在的昵称xyz'])));
        $this->assertSame(0, $resp['data']['total']);
    }
}
