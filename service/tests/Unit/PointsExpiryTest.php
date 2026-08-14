<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\CheckIn;
use app\model\Notification;
use app\model\User;
use app\model\UserPoints;
use app\process\PointsExpiryTimer;
use app\user\v1\controller\CheckInController;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 积分过期闭环测试
 *
 * 覆盖：earn 积分落库带 expires_at（=now+365 天）、过期扫描写扣减行、
 * 未到期不扣、幂等（重复扫描不重复扣）、expire 行计入 SUM 余额、站内通知、
 * consume 行 expires_at 为空。
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 */
class PointsExpiryTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理积分/通知/签到/用户 */
    private array $userIds = [];

    /** @var string|null 原 points.expiry_days 配置值（null=原本不存在），tearDown 恢复 */
    private ?string $savedExpiryDays = null;

    protected function setUp(): void
    {
        parent::setUp();
        // 固定有效期配置保证断言确定性（原值 tearDown 恢复）
        $this->savedExpiryDays = Db::table('erik_system_config')
            ->where('group', 'points')
            ->where('key', 'expiry_days')
            ->value('value');
        Db::table('erik_system_config')->updateOrInsert(
            ['group' => 'points', 'key' => 'expiry_days'],
            ['value' => '365', 'type' => 'int', 'description' => '积分有效期（天）']
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserPoints::where('user_id', $uid)->delete();
            Notification::where('user_id', $uid)->delete();
            CheckIn::where('user_id', $uid)->delete();
            User::where('id', $uid)->forceDelete();
        }
        $this->userIds = [];

        if ($this->savedExpiryDays === null) {
            Db::table('erik_system_config')
                ->where('group', 'points')
                ->where('key', 'expiry_days')
                ->delete();
        } else {
            Db::table('erik_system_config')
                ->where('group', 'points')
                ->where('key', 'expiry_days')
                ->update(['value' => $this->savedExpiryDays]);
        }
        $this->savedExpiryDays = null;
    }

    #[Test]
    public function check_in_earn_row_has_expires_at_now_plus_365_days(): void
    {
        $userId = $this->makeUser();
        $body = $this->body($this->callCheckIn($userId));

        $this->assertSame(0, $body['code']);

        $row = UserPoints::where('user_id', $userId)
            ->where('type', 'earn')
            ->where('source', 'check_in')
            ->first();
        $this->assertNotNull($row, '签到 earn 流水应存在');
        $this->assertNotNull($row->expires_at, 'earn 积分应带 expires_at');

        // expires_at = 发放时间 + 365 天（容忍 120s 执行偏移）
        $expected = time() + 365 * 86400;
        $this->assertLessThan(120, abs(strtotime($row->expires_at) - $expected));
    }

    #[Test]
    public function consume_row_has_null_expires_at(): void
    {
        $userId = $this->makeUser();
        $this->createPointsRow($userId, 'consume', -50, null);

        $row = UserPoints::where('user_id', $userId)->where('type', 'consume')->first();
        $this->assertNull($row->expires_at, 'consume 行 expires_at 应为空（永不过期语义）');
    }

    #[Test]
    public function expired_earn_row_writes_expire_deduction_and_notification(): void
    {
        $userId = $this->makeUser();
        $earn = $this->createPointsRow($userId, 'earn', 100, date('Y-m-d H:i:s', time() - 3600));

        $this->scanAndExpire();

        $expire = UserPoints::where('user_id', $userId)
            ->where('type', 'expire')
            ->where('order_id', $earn->id)
            ->first();
        $this->assertNotNull($expire, '过期后应写入 expire 扣减行');
        $this->assertSame(-100, (int) $expire->points);
        $this->assertSame('expiry', $expire->source);
        $this->assertSame(0, (int) $expire->balance, 'balance 快照：100 - 100 = 0');

        $notify = Notification::where('user_id', $userId)
            ->where('type', 'points_expiry')
            ->first();
        $this->assertNotNull($notify, '应写入站内通知');
        $this->assertStringContainsString('100', (string) $notify->content);
    }

    #[Test]
    public function unexpired_earn_row_is_not_deducted(): void
    {
        $userId = $this->makeUser();
        $this->createPointsRow($userId, 'earn', 100, date('Y-m-d H:i:s', time() + 365 * 86400));

        $this->scanAndExpire();

        $this->assertSame(0, UserPoints::where('user_id', $userId)
            ->where('type', 'expire')->count());
        $this->assertSame(0, Notification::where('user_id', $userId)->count());
    }

    #[Test]
    public function scan_is_idempotent_on_repeat_runs(): void
    {
        $userId = $this->makeUser();
        $earn = $this->createPointsRow($userId, 'earn', 100, date('Y-m-d H:i:s', time() - 3600));

        $this->scanAndExpire();
        $this->scanAndExpire();

        $this->assertSame(1, UserPoints::where('user_id', $userId)
            ->where('type', 'expire')
            ->where('order_id', $earn->id)
            ->count(), '重复扫描不得产生第二条 expire 扣减行');
        $this->assertSame(1, Notification::where('user_id', $userId)
            ->where('type', 'points_expiry')->count(), '重复扫描不得重复通知');
        $this->assertSame(-100, (int) UserPoints::where('user_id', $userId)
            ->where('type', 'expire')->sum('points'));
    }

    #[Test]
    public function expire_rows_are_counted_in_sum_balance(): void
    {
        $userId = $this->makeUser();
        $this->createPointsRow($userId, 'earn', 100, date('Y-m-d H:i:s', time() - 3600));
        $this->assertSame(100, $this->sumAvailable($userId), '过期前可用 = 100');

        $this->scanAndExpire();

        // 与 OrderController/PointsExchangeController 同口径：earn + consume/use + expire
        $this->assertSame(-100, (int) UserPoints::where('user_id', $userId)
            ->where('type', 'expire')->sum('points'), 'expire 行计入 SUM（负值）');
        $this->assertSame(0, $this->sumAvailable($userId), '过期后可用 = 0（SUM 自然含负的 expire 行）');
    }

    /** 造用户（phone 随机唯一，wx_openid 留空） */
    private function makeUser(): string
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $userId = (string) $user->id;
        $this->userIds[] = $userId;
        return $userId;
    }

    /** 调 CheckInController::store（$request->user_id 直接赋值，与既有测试一致） */
    private function callCheckIn(string $userId): Response
    {
        $req = $this->makeRequest();
        $req->user_id = $userId;
        return (new CheckInController())->store($req);
    }

    private function makeRequest(): Request
    {
        return new Request("POST / HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    /** 直造积分流水（expiresAt 传 null 表示不设到期时间） */
    private function createPointsRow(string $userId, string $type, int $points, ?string $expiresAt): UserPoints
    {
        return UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $userId,
            'type'        => $type,
            'points'      => $points,
            'balance'     => $points,
            'source'      => $type === 'expire' ? 'expiry' : 'check_in',
            'description' => '积分过期测试流水',
            'expires_at'  => $expiresAt,
        ]);
    }

    /** 反射实例化进程（构造函数注册 Workerman Timer，CLI 单测下不可用） */
    private function scanAndExpire(): void
    {
        $timer = (new \ReflectionClass(PointsExpiryTimer::class))->newInstanceWithoutConstructor();
        $timer->scanAndExpire();
    }

    /** 与 OrderController/PointsExchangeController 相同的 SUM 口径可用余额 */
    private function sumAvailable(string $userId): int
    {
        $earned   = (int) UserPoints::where('user_id', $userId)->where('type', 'earn')->sum('points');
        $consumed = (int) UserPoints::where('user_id', $userId)->whereIn('type', ['consume', 'use'])->sum('points');
        $expired  = (int) UserPoints::where('user_id', $userId)->where('type', 'expire')->sum('points');
        return $earned + $consumed + $expired;
    }
}
