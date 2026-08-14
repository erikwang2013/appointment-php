<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Notification;
use app\model\User;
use app\model\UserWallet;
use app\model\WalletTransfer;
use app\model\WalletTxn;
use app\wallet\v1\controller\WalletTransferController;
use support\Container;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 用户间余额转账测试
 *
 * 基建与 WalletTest 一致（真实 DB + tearDown 清理）。
 * 覆盖：转账成功（双方钱包+双流水+转账记录+接收方通知）、余额不足 422、
 * 转自己 422、接收人不存在 404、单日限额 422、单笔限额 422、
 * client_token 幂等、Redis 锁并发只成功一次、记录分页（发出+收到）。
 * 涉及 Redis 的用例在 Redis 不可用时 skip。
 */
class WalletTransferTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例 Redis key，tearDown 统一清理 */
    private array $redisKeys = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            WalletTransfer::where('from_user_id', $uid)->orWhere('to_user_id', $uid)->delete();
            WalletTxn::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
            Notification::where('user_id', $uid)->delete();
            User::where('id', $uid)->forceDelete();
        }
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }
        $this->userIds = [];
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

    private function authRequest(string $userId, array $post = []): Request
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function newUserId(): string
    {
        $uid = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $uid;
        return $uid;
    }

    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    private function makeWallet(string $userId, float $balance): UserWallet
    {
        return UserWallet::create([
            'user_id'         => $userId,
            'balance'         => $balance,
            'total_recharge'  => 0.00,
            'total_consume'   => 0.00,
        ]);
    }

    private function encodeId(int $id): string
    {
        return (string) Container::get('hashids')->encode($id);
    }

    private function redisAvailable(): bool
    {
        try {
            $probe = 'test:probe:' . uniqid();
            $this->redisKeys[] = $probe;
            Redis::setex($probe, 5, '1');
            return Redis::get($probe) === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    private function trackRedisKey(string $key): void
    {
        if (!in_array($key, $this->redisKeys, true)) {
            $this->redisKeys[] = $key;
        }
    }

    // ── 转账成功 ──

    #[Test] public function transfer_success_credits_receiver_and_writes_records(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $this->makeWallet($sender->id, 1000.0);
        $this->makeWallet($receiver->id, 0.0);

        $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => $this->encodeId((int) $receiver->id),
            'amount'     => 200,
            'remark'     => '测试转账',
        ])));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(800.0, (float) $resp['data']['balance']);
        // to_user_id 响应已 hashid 编码
        $this->assertSame((int) $receiver->id, Container::get('hashids')->decode((string) $resp['data']['to_user_id'])[0]);

        $this->assertSame(800.0, (float) UserWallet::where('user_id', $sender->id)->first()->balance);
        $this->assertSame(200.0, (float) UserWallet::where('user_id', $receiver->id)->first()->balance);

        $out = WalletTxn::where('user_id', $sender->id)->where('type', WalletTxn::TYPE_TRANSFER_OUT)->first();
        $this->assertNotNull($out);
        $this->assertSame(200.0, (float) $out->amount);
        $this->assertSame(800.0, (float) $out->balance_after);

        $in = WalletTxn::where('user_id', $receiver->id)->where('type', WalletTxn::TYPE_TRANSFER_IN)->first();
        $this->assertNotNull($in);
        $this->assertSame(200.0, (float) $in->amount);
        $this->assertSame(200.0, (float) $in->balance_after);

        $transfer = WalletTransfer::where('from_user_id', $sender->id)->first();
        $this->assertNotNull($transfer);
        $this->assertSame(WalletTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertSame((string) $receiver->id, (string) $transfer->to_user_id);
        $this->assertSame(200.0, (float) $transfer->amount);
        $this->assertSame('测试转账', $transfer->remark);

        $notice = Notification::where('user_id', $receiver->id)->where('type', 'balance_received')->first();
        $this->assertNotNull($notice);
        $this->assertStringContainsString('200.00', (string) $notice->content);
    }

    // ── 余额不足 ──

    #[Test] public function transfer_insufficient_balance_returns_422(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $this->makeWallet($sender->id, 50.0);
        $this->makeWallet($receiver->id, 0.0);

        $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => $this->encodeId((int) $receiver->id),
            'amount'     => 100,
        ])));

        $this->assertSame(422, $resp['code']);
        $this->assertSame('余额不足', $resp['message']);
        $this->assertSame(50.0, (float) UserWallet::where('user_id', $sender->id)->first()->balance);
        $this->assertSame(0, WalletTxn::where('user_id', $sender->id)->count());
        $this->assertSame(0, WalletTransfer::where('from_user_id', $sender->id)->count());
    }

    // ── 转自己 ──

    #[Test] public function transfer_to_self_returns_422(): void
    {
        $sender = $this->makeUser();
        $this->makeWallet($sender->id, 100.0);

        $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => $this->encodeId((int) $sender->id),
            'amount'     => 10,
        ])));

        $this->assertSame(422, $resp['code']);
        $this->assertSame('不能转账给自己', $resp['message']);
        $this->assertSame(0, WalletTransfer::where('from_user_id', $sender->id)->count());
    }

    // ── 接收人不存在 ──

    #[Test] public function transfer_receiver_not_found_returns_404(): void
    {
        $sender = $this->makeUser();
        $this->makeWallet($sender->id, 100.0);

        // 不存在的用户 hashid
        $ghost = (int) (9900000000000000 + random_int(1000000, 9999999));
        $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => $this->encodeId($ghost),
            'amount'     => 10,
        ])));
        $this->assertSame(404, $resp['code']);
        $this->assertSame('接收人不存在', $resp['message']);

        // 非法 hashid 字符串
        $resp2 = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => 'not-a-hashid',
            'amount'     => 10,
        ])));
        $this->assertSame(404, $resp2['code']);

        $this->assertSame(0, WalletTransfer::where('from_user_id', $sender->id)->count());
    }

    // ── 单日限额 ──

    #[Test] public function transfer_exceeds_daily_limit_returns_422(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $this->makeWallet($sender->id, 10000.0);

        // 今日已累计 4900（+本次 200 = 5100 > 5000）
        WalletTransfer::create([
            'from_user_id' => $sender->id,
            'to_user_id'   => $receiver->id,
            'amount'       => 4900.00,
            'status'       => WalletTransfer::STATUS_COMPLETED,
            'remark'       => '历史转账',
        ]);

        $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => $this->encodeId((int) $receiver->id),
            'amount'     => 200,
        ])));

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('今日累计转账已达上限', (string) $resp['message']);
        $this->assertSame(10000.0, (float) UserWallet::where('user_id', $sender->id)->first()->balance);
        $this->assertSame(1, WalletTransfer::where('from_user_id', $sender->id)->count());
        $this->assertSame(0, WalletTxn::where('user_id', $sender->id)->count());
    }

    // ── 单笔限额 ──

    #[Test] public function transfer_rejects_amount_out_of_single_limit(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $this->makeWallet($sender->id, 10000.0);

        foreach ([0, 0.5, 0.99] as $tooSmall) {
            $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
                'to_user_id' => $this->encodeId((int) $receiver->id),
                'amount'     => $tooSmall,
            ])));
            $this->assertSame(422, $resp['code'], "amount={$tooSmall} 应低于单笔下限");
        }
        foreach ([1000.01, 5000] as $tooBig) {
            $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
                'to_user_id' => $this->encodeId((int) $receiver->id),
                'amount'     => $tooBig,
            ])));
            $this->assertSame(422, $resp['code'], "amount={$tooBig} 应超单笔上限");
        }
        $this->assertSame(0, WalletTransfer::where('from_user_id', $sender->id)->count());
        $this->assertSame(0, WalletTxn::where('user_id', $sender->id)->count());
    }

    // ── client_token 幂等 ──

    #[Test] public function transfer_with_same_client_token_only_succeeds_once(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过 client_token 用例');
        }
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $this->makeWallet($sender->id, 1000.0);
        $token = 'client_token_' . uniqid();
        $this->trackRedisKey('wallet_transfer_token:' . $token);

        $post = [
            'to_user_id'   => $this->encodeId((int) $receiver->id),
            'amount'       => 100,
            'client_token' => $token,
        ];

        $r1 = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, $post)));
        $this->assertSame(0, $r1['code'], json_encode($r1));

        $r2 = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, $post)));
        $this->assertSame(422, $r2['code']);
        $this->assertSame('请勿重复提交', $r2['message']);

        $this->assertSame(1, WalletTransfer::where('from_user_id', $sender->id)->count());
        $this->assertSame(900.0, (float) UserWallet::where('user_id', $sender->id)->first()->balance);
        $this->assertSame(1, WalletTxn::where('user_id', $sender->id)->count());
    }

    // ── 并发只成功一次（Redis 锁）──

    #[Test] public function transfer_concurrent_lock_allows_only_one(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过并发锁用例');
        }
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $this->makeWallet($sender->id, 1000.0);

        // 模拟另一请求已持有转账锁：并发请求应被拒绝
        $lockKey = 'wallet_transfer:' . $sender->id;
        $this->trackRedisKey($lockKey);
        Redis::connection()->set($lockKey, 'other-token', 'EX', 30, 'NX');

        $resp = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => $this->encodeId((int) $receiver->id),
            'amount'     => 100,
        ])));
        $this->assertSame(400, $resp['code']);
        $this->assertSame('操作处理中，请稍后再试', $resp['message']);
        $this->assertSame(0, WalletTransfer::where('from_user_id', $sender->id)->count());
        $this->assertSame(1000.0, (float) UserWallet::where('user_id', $sender->id)->first()->balance);

        // 锁释放后可正常转账
        Redis::connection()->del($lockKey);
        $resp2 = $this->body((new WalletTransferController())->transfer($this->authRequest($sender->id, [
            'to_user_id' => $this->encodeId((int) $receiver->id),
            'amount'     => 100,
        ])));
        $this->assertSame(0, $resp2['code'], json_encode($resp2));
        $this->assertSame(1, WalletTransfer::where('from_user_id', $sender->id)->count());
    }

    // ── 转账记录分页（发出+收到）──

    #[Test] public function transfers_list_contains_out_and_in_records_only_for_participant(): void
    {
        $me = $this->makeUser();
        $other = $this->makeUser();
        $stranger = $this->makeUser();

        // 我发出 1 笔 + 收到 1 笔 + 与我无关 1 笔
        WalletTransfer::create(['from_user_id' => $me->id, 'to_user_id' => $other->id, 'amount' => 100, 'status' => WalletTransfer::STATUS_COMPLETED, 'remark' => '']);
        WalletTransfer::create(['from_user_id' => $other->id, 'to_user_id' => $me->id, 'amount' => 50, 'status' => WalletTransfer::STATUS_COMPLETED, 'remark' => '']);
        WalletTransfer::create(['from_user_id' => $stranger->id, 'to_user_id' => $other->id, 'amount' => 30, 'status' => WalletTransfer::STATUS_COMPLETED, 'remark' => '']);

        $resp = $this->body((new WalletTransferController())->transfers($this->authRequest($me->id)));

        $this->assertSame(2, $resp['meta']['total']);
        $directions = array_column($resp['data'], 'direction');
        sort($directions);
        $this->assertSame(['in', 'out'], $directions);
        // 记录详情仅参与者可见
        $mine = WalletTransfer::where('from_user_id', $me->id)->first();
        $detail = $this->body((new WalletTransferController())->show($this->authRequest($me->id), $this->encodeId((int) $mine->id)));
        $this->assertSame(0, $detail['code']);
        $secret = WalletTransfer::where('from_user_id', $stranger->id)->first();
        $forbidden = $this->body((new WalletTransferController())->show($this->authRequest($me->id), $this->encodeId((int) $secret->id)));
        $this->assertSame(404, $forbidden['code']);
    }
}
