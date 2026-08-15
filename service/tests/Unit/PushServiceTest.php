<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\AppPushService;
use app\model\PushLog;
use support\Db;

/**
 * APP 推送服务测试（真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 未启用（push.enabled=0）返回 false 且不写 erik_push_log
 * - 启用后写 status=sent 记录且 payload 结构正确（含 provider）
 * - isEnabled 跟随配置开关变化
 * - 异常场景（超大 payload 触发落库异常）不抛出
 * - 支付成功推送（挂接块等价调用）返回 true 且落库
 */
class PushServiceTest extends TestCase
{
    private const CONFIG_GROUP = 'push';

    /** @var string[] 用例用户 ID，tearDown 统一清理推送日志 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            PushLog::where('user_id', $id)->delete();
        }
        Db::table('erik_system_config')->where('group', self::CONFIG_GROUP)->delete();
        $this->userIds = [];
    }

    /** 造一个随机用户 ID（推送日志仅记录用，无需真实用户行） */
    private function makeUserId(): int
    {
        $id = \support\Model::generateId();
        $this->userIds[] = (string) $id;
        return (int) $id;
    }

    /** 写入推送配置（upsert，tearDown 统一删除；id 与迁移种子一致避免主键冲突） */
    private function setConfig(string $key, string $value): void
    {
        $ids = ['enabled' => 91000000000000025, 'provider' => 91000000000000026];
        Db::table('erik_system_config')->upsert(
            [[
                'id'          => $ids[$key],
                'group'       => self::CONFIG_GROUP,
                'key'         => $key,
                'value'       => $value,
                'type'        => 'string',
                'description' => '测试配置',
            ]],
            ['group', 'key'],
            ['value']
        );
    }

    #[Test]
    public function testDisabledPushReturnsFalseWithoutRecord(): void
    {
        $this->setConfig('enabled', '0');

        $userId = $this->makeUserId();
        $result = AppPushService::pushToUser($userId, '支付成功', '测试内容', ['type' => 'order_paid']);

        $this->assertFalse($result, '未启用时应返回 false（静默降级）');
        $this->assertFalse(AppPushService::isEnabled());
        $this->assertSame(0, PushLog::where('user_id', $userId)->count(), '未启用时不应写推送记录');
    }

    #[Test]
    public function testEnabledPushWritesSentRecordWithPayload(): void
    {
        $this->setConfig('enabled', '1');
        $this->setConfig('provider', 'jpush');

        $userId = $this->makeUserId();
        $result = AppPushService::pushToUser(
            $userId,
            '服务即将开始',
            '您的服务将在 2026-08-15 10:00 开始',
            ['type' => 'service_reminder', 'order_id' => '123456', 'order_no' => 'APP20260815001']
        );

        $this->assertTrue($result, '启用时应返回 true');
        $log = PushLog::where('user_id', $userId)->first();
        $this->assertNotNull($log, '启用后应写入推送记录');
        $this->assertSame(AppPushService::STATUS_SENT, $log->status);
        $this->assertSame('jpush', $log->provider);
        $this->assertSame('服务即将开始', $log->title);

        $payload = is_array($log->payload) ? $log->payload : json_decode((string) $log->payload, true);
        $this->assertSame('service_reminder', $payload['type'] ?? null);
        $this->assertSame('123456', $payload['order_id'] ?? null);
        $this->assertSame('APP20260815001', $payload['order_no'] ?? null);
    }

    #[Test]
    public function testIsEnabledReflectsConfigToggle(): void
    {
        $this->setConfig('enabled', '0');
        $this->assertFalse(AppPushService::isEnabled());

        $this->setConfig('enabled', '1');
        $this->assertTrue(AppPushService::isEnabled());

        $this->setConfig('enabled', '0');
        $this->assertFalse(AppPushService::isEnabled());
    }

    #[Test]
    public function testPushDoesNotThrowOnPersistFailure(): void
    {
        $this->setConfig('enabled', '1');

        // 超大 payload（约 200KB）超出 content/payload 列容量，落库必失败——
        // 推送链路应捕获记录异常并仍返回 true，绝不抛出
        $userId = $this->makeUserId();
        $result = AppPushService::pushToUser(
            $userId,
            '大 payload 测试',
            '测试内容',
            ['data' => str_repeat('x', 200 * 1024)]
        );

        $this->assertTrue($result, '落库失败不应影响推送链路返回值');
    }

    #[Test]
    public function testPaymentSuccessPushHook(): void
    {
        // 模拟 WechatPayService::markOrderPaid 挂接块的等价调用
        $this->setConfig('enabled', '1');

        $userId = $this->makeUserId();
        $result = AppPushService::pushToUser(
            $userId,
            '支付成功',
            '您的订单 APP20260815002 已支付成功，预约已确认',
            ['type' => 'order_paid', 'order_id' => '223344', 'order_no' => 'APP20260815002']
        );

        $this->assertTrue($result);
        $log = PushLog::where('user_id', $userId)->first();
        $this->assertNotNull($log);
        $this->assertSame('支付成功', $log->title);
        $this->assertSame(AppPushService::STATUS_SENT, $log->status);

        $payload = is_array($log->payload) ? $log->payload : json_decode((string) $log->payload, true);
        $this->assertSame('order_paid', $payload['type'] ?? null);
        $this->assertSame('223344', $payload['order_id'] ?? null);
    }
}
