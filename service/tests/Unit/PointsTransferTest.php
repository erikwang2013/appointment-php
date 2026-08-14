<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Notification;
use app\model\User;
use app\model\UserPoints;
use app\model\UserPointsTransfer;
use app\user\v1\controller\PointsTransferController;
use support\Container;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 用户积分转赠闭环测试
 *
 * 覆盖：转赠成功（双方流水 + 转赠记录 + 站内通知）、余额不足 422、转自己 422、
 * 接收人不存在 404、单日限额 422、重复提交幂等（Redis 锁 + 锁内复验）、
 * 记录分页（发出 + 收到 + 方向 + 对方昵称）。
 * 基建与 PointsExchangeTest 一致（真实 DB + tearDown 清理）。
 */
class PointsTransferTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserPoints::where('user_id', $uid)->delete();
            UserPointsTransfer::where('from_user_id', $uid)->orWhere('to_user_id', $uid)->delete();
            Notification::where('user_id', $uid)->delete();
            User::where('id', $uid)->delete();
            Redis::connection()->del("points_transfer:{$uid}");
        }
        $this->userIds = [];
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

    private function newUserId(string $nickname = ''): string
    {
        // 注意：User::create 的 id 不在 fillable 中（会被静默丢弃），
        // 实际 ID 为自增值，必须读回 $user->id（与 ReferralRewardTest 一致）
        $user = User::create([
            'phone'      => '199' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'wx_openid'  => '',
            'user_type'  => 'user',
            'status'     => 1,
            'nickname'   => $nickname,
        ]);
        $uid = (string) $user->id;
        $this->userIds[] = $uid;
        return $uid;
    }

    private function creditPoints(string $userId, int $points): void
    {
        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $userId,
            'type'        => 'earn',
            'points'      => $points,
            'balance'     => $points,
            'source'      => 'test_seed',
            'description' => '测试预置积分',
        ]);
    }

    private function encodeId(int $id): string
    {
        return (string) Container::get('hashids')->encode($id);
    }

    private function transfer(string $userId, string $toUserIdHash, int $points): array
    {
        $request = $this->makeRequest(['to_user_id' => $toUserIdHash, 'points' => $points]);
        $request->user_id = $userId;
        return $this->body((new PointsTransferController())->transfer($request));
    }

    private function records(string $userId): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return $this->body((new PointsTransferController())->records($request));
    }

    private function decodeId(string $hashid): int
    {
        return (int) Container::get('hashids')->decode($hashid)[0];
    }

    // ── 转赠成功：双方流水 + 转赠记录 + 通知 ──

    #[Test] public function transfer_success_credits_receiver_and_deducts_sender(): void
    {
        $sender = $this->newUserId('发送者');
        $receiver = $this->newUserId('接收者');
        $this->creditPoints($sender, 1000);

        $resp = $this->transfer($sender, $this->encodeId((int) $receiver), 200);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(200, (int) $resp['data']['points']);
        $this->assertNotEmpty($resp['data']['transfer_id']);

        // 发送方扣减流水：type=consume source=points_transfer，负值，balance 快照 = 1000-200
        $sendRow = UserPoints::where('user_id', $sender)->where('source', 'points_transfer')->first();
        $this->assertNotNull($sendRow);
        $this->assertSame('consume', $sendRow->type);
        $this->assertSame(-200, (int) $sendRow->points);
        $this->assertSame(800, (int) $sendRow->balance);

        // 接收方入账流水：type=earn 正值，含过期时间
        $recvRow = UserPoints::where('user_id', $receiver)->where('source', 'points_transfer')->first();
        $this->assertNotNull($recvRow);
        $this->assertSame('earn', $recvRow->type);
        $this->assertSame(200, (int) $recvRow->points);
        $this->assertSame(200, (int) $recvRow->balance);
        $this->assertNotNull($recvRow->expires_at);

        // 转赠记录
        $record = UserPointsTransfer::where('from_user_id', $sender)->where('to_user_id', $receiver)->first();
        $this->assertNotNull($record);
        $this->assertSame(200, (int) $record->points);
        $this->assertSame('completed', $record->status);

        // 站内通知接收方
        $notice = Notification::where('user_id', $receiver)->where('type', 'points_received')->first();
        $this->assertNotNull($notice);
        $this->assertStringContainsString('200', (string) $notice->content);
    }

    // ── 余额不足 ──

    #[Test] public function transfer_rejects_insufficient_points(): void
    {
        $sender = $this->newUserId();
        $receiver = $this->newUserId();
        $this->creditPoints($sender, 50);

        $resp = $this->transfer($sender, $this->encodeId((int) $receiver), 100);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('积分不足', (string) $resp['message']);
        $this->assertSame(0, UserPointsTransfer::where('from_user_id', $sender)->count());
        $this->assertSame(0, UserPoints::where('user_id', $receiver)->where('source', 'points_transfer')->count());
    }

    // ── 转给自己 ──

    #[Test] public function transfer_rejects_self(): void
    {
        $sender = $this->newUserId();
        $this->creditPoints($sender, 1000);

        $resp = $this->transfer($sender, $this->encodeId((int) $sender), 100);

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('不能转赠给自己', (string) $resp['message']);
        $this->assertSame(0, UserPointsTransfer::where('from_user_id', $sender)->count());
    }

    // ── 接收人不存在（无效 hashid / 不存在的用户） ──

    #[Test] public function transfer_rejects_missing_receiver(): void
    {
        $sender = $this->newUserId();
        $this->creditPoints($sender, 1000);

        $respBadHash = $this->transfer($sender, 'invalid_hash', 100);
        $this->assertSame(404, $respBadHash['code']);
        $this->assertStringContainsString('接收人不存在', (string) $respBadHash['message']);

        $respMissing = $this->transfer($sender, $this->encodeId(9800000000000000), 100);
        $this->assertSame(404, $respMissing['code']);
        $this->assertStringContainsString('接收人不存在', (string) $respMissing['message']);

        $this->assertSame(0, UserPointsTransfer::where('from_user_id', $sender)->count());
    }

    // ── 单日累计转赠限额 ──

    #[Test] public function transfer_rejects_daily_limit_exceeded(): void
    {
        $sender = $this->newUserId();
        $receiver = $this->newUserId();
        $this->creditPoints($sender, 50000);

        // 8000 成功
        $r1 = $this->transfer($sender, $this->encodeId((int) $receiver), 8000);
        $this->assertSame(0, $r1['code'], json_encode($r1));

        // 2000 恰好到限额边界（8000 + 2000 = 10000），允许
        $r2 = $this->transfer($sender, $this->encodeId((int) $receiver), 2000);
        $this->assertSame(0, $r2['code'], json_encode($r2));

        // 再转 1 超限（10001 > 10000），拒绝
        $r3 = $this->transfer($sender, $this->encodeId((int) $receiver), 1);
        $this->assertSame(422, $r3['code']);
        $this->assertStringContainsString('单日转赠限额', (string) $r3['message']);

        $this->assertSame(2, UserPointsTransfer::where('from_user_id', $sender)->count());
        $this->assertSame(10000, (int) UserPoints::where('user_id', $receiver)->where('source', 'points_transfer')->sum('points'));
    }

    // ── 并发/重复提交：Redis 锁 + 锁内复验，只成功一次 ──

    #[Test] public function duplicate_submit_only_succeeds_once(): void
    {
        $sender = $this->newUserId();
        $receiver = $this->newUserId();
        $this->creditPoints($sender, 500);

        // 模拟并发：手动持有 Redis NX 锁，重复提交直接拒绝
        Redis::connection()->set("points_transfer:{$sender}", $sender, 'EX', 30, 'NX');
        $locked = $this->transfer($sender, $this->encodeId((int) $receiver), 300);
        $this->assertSame(400, $locked['code']);
        $this->assertStringContainsString('处理中', (string) $locked['message']);
        $this->assertSame(0, UserPointsTransfer::where('from_user_id', $sender)->count());
        Redis::connection()->del("points_transfer:{$sender}");

        // 锁释放后首次转赠成功
        $r1 = $this->transfer($sender, $this->encodeId((int) $receiver), 300);
        $this->assertSame(0, $r1['code'], json_encode($r1));

        // 再次提交：锁内余额复验拦截（余额 200 < 300），不产生第二条记录
        $r2 = $this->transfer($sender, $this->encodeId((int) $receiver), 300);
        $this->assertSame(422, $r2['code']);
        $this->assertStringContainsString('积分不足', (string) $r2['message']);

        $this->assertSame(1, UserPointsTransfer::where('from_user_id', $sender)->count());
        $this->assertSame(300, (int) UserPoints::where('user_id', $receiver)->where('source', 'points_transfer')->sum('points'));
        $this->assertSame(-300, (int) UserPoints::where('user_id', $sender)->where('source', 'points_transfer')->sum('points'));
    }

    // ── 转赠记录：发出 + 收到，方向与对方昵称 ──

    #[Test] public function records_lists_sent_and_received_with_direction(): void
    {
        $userA = $this->newUserId('用户甲');
        $userB = $this->newUserId('用户乙');
        $this->creditPoints($userA, 1000);
        $this->creditPoints($userB, 1000);

        $t1 = $this->transfer($userA, $this->encodeId((int) $userB), 100);
        $t2 = $this->transfer($userB, $this->encodeId((int) $userA), 50);
        $this->assertSame(0, $t1['code'], json_encode($t1));
        $this->assertSame(0, $t2['code'], json_encode($t2));

        $resp = $this->records($userA);
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(2, (int) $resp['meta']['total']);
        $this->assertCount(2, $resp['data']);

        // 最新一条在前：B→A 收到 50
        $first = $resp['data'][0];
        $this->assertSame('received', $first['direction']);
        $this->assertSame(50, (int) $first['points']);
        $this->assertSame('用户乙', $first['nickname']);
        $this->assertSame((string) $userB, (string) $this->decodeId((string) $first['from_user_id']));

        // 第二条：A→B 发出 100
        $second = $resp['data'][1];
        $this->assertSame('sent', $second['direction']);
        $this->assertSame(100, (int) $second['points']);
        $this->assertSame('用户乙', $second['nickname']);
        $this->assertSame((string) $userB, (string) $this->decodeId((string) $second['to_user_id']));
    }
}
