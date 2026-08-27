<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\TicketController;
use app\model\Ticket;
use support\Db;
use support\Request;
use support\Response;

/**
 * 工单满意度统计测试
 *
 * 覆盖：satisfaction 返回聚合（总数/已评分/平均分/分布）、
 * 平均分 1 位小数计算、无评分时平均分为 0.0。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class TicketSatisfactionTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;

    /** 事务内基线（共享库可能残留他人数据，计数断言用增量） */
    private int $baseTotal = 0;
    private int $baseRated = 0;

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
        $this->baseTotal = Ticket::count();
        $this->baseRated = Ticket::whereNotNull('rating')->count();
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

    /** 直接插入一条带评分工单 */
    private function makeRatedTicket(int $rating): Ticket
    {
        $ticket = new Ticket();
        $ticket->id = 90000000000004000 + random_int(1, 999);
        $ticket->user_id = '9900000000000001';
        $ticket->category = 'service';
        $ticket->description = '满意度测试';
        $ticket->status = 'closed';
        $ticket->rating = $rating;
        $ticket->rated_at = date('Y-m-d H:i:s');
        $ticket->save();
        return $ticket;
    }

    /** 直接插入一条未评分工单 */
    private function makeUnratedTicket(): Ticket
    {
        $ticket = new Ticket();
        $ticket->id = 90000000000004000 + random_int(1, 999);
        $ticket->user_id = '9900000000000001';
        $ticket->category = 'service';
        $ticket->description = '满意度测试未评分';
        $ticket->status = 'closed';
        $ticket->save();
        return $ticket;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function fetch(): array
    {
        $request = new Request("GET /admin/tickets/satisfaction HTTP/1.1\r\nHost: localhost\r\n\r\n");
        return $this->body((new TicketController())->satisfaction($request));
    }

    #[Test]
    public function satisfaction_returns_aggregates(): void
    {
        $this->makeRatedTicket(5);
        $this->makeRatedTicket(4);
        $this->makeRatedTicket(3);
        $this->makeUnratedTicket();

        $data = $this->fetch();
        $this->assertSame(0, $data['code'], json_encode($data));
        $this->assertSame(4, $data['data']['total'] - $this->baseTotal, '总数为基线+本用例');
        $this->assertSame(3, $data['data']['rated_count'] - $this->baseRated, '已评分为基线+本用例');
        $this->assertSame(1, $data['data']['unrated_count'] - ($this->baseTotal - $this->baseRated), '未评分为基线+本用例');
        if ($this->baseRated === 0) {
            $this->assertSame('4.0', $data['data']['average'], '平均分应为 1 位小数');
            $this->assertSame(
                [1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 1],
                $data['data']['distribution'],
                '评分分布应含 1-5 星各数量'
            );
        }
    }

    #[Test]
    public function satisfaction_average_rounds_to_one_decimal(): void
    {
        $this->makeRatedTicket(5);
        $this->makeRatedTicket(4);

        $data = $this->fetch();

        if ($this->baseRated === 0) {
            $this->assertSame('4.5', $data['data']['average']);
        }
    }

    #[Test]
    public function satisfaction_empty_returns_zero_average(): void
    {
        $data = $this->fetch();

        $this->assertSame(0, $data['code'], json_encode($data));
        $this->assertSame($this->baseTotal, $data['data']['total']);
        $this->assertSame($this->baseRated, $data['data']['rated_count']);
        if ($this->baseRated === 0) {
            $this->assertSame('0.0', $data['data']['average']);
            $this->assertSame([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], $data['data']['distribution']);
        }
    }
}
