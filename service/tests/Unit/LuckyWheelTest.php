<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\WheelController;
use app\model\LuckyWheel;
use app\model\UserPoints;
use app\model\WheelRecord;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 积分幸运转盘测试
 *
 * 覆盖：奖品列表只返回上架且隐藏权重/库存、抽奖扣积分+写记录、
 * 积分不足 422、中奖发放（权重 100 确定性中「100积分」验证积分流水）、
 * 谢谢参与仅记录不发放、记录分页。
 * 基建与 FlashSaleOrderTest 一致（真实 DB + tearDown 清理）。
 */
class LuckyWheelTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理积分流水 */
    private array $userIds = [];

    /** @var string[] 用例奖品 ID，tearDown 统一清理 */
    private array $prizeIds = [];

    /** @var string[] 用例抽奖记录 ID，tearDown 统一清理 */
    private array $recordIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            UserPoints::where('user_id', $id)->delete();
            WheelRecord::where('user_id', $id)->delete();
        }
        foreach ($this->prizeIds as $id) {
            LuckyWheel::where('id', $id)->delete();
        }
        foreach ($this->recordIds as $id) {
            WheelRecord::where('id', $id)->delete();
        }
        // 恢复演示种子奖品上架状态（测试期间置为下架以隔离权重池）
        LuckyWheel::whereIn('id', ['10000000000001001', '10000000000001002'])
            ->where('status', 0)
            ->update(['status' => 1]);
        $this->userIds = [];
        $this->prizeIds = [];
        $this->recordIds = [];
    }

    private function makeRequest(string $method, array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("$method / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function newUserId(): string
    {
        $id = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $id;
        return $id;
    }

    /** 直接插入一条积分流水（earn，作为可用余额） */
    private function makePoints(string $userId, int $points): void
    {
        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $userId,
            'type'        => 'earn',
            'points'      => $points,
            'balance'     => $points,
            'source'      => 'test',
            'description' => '测试积分',
        ]);
    }

    /** 直接插入一个奖品（默认上架 weight 100 权重池唯一） */
    private function makePrize(array $overrides = []): LuckyWheel
    {
        $prize = LuckyWheel::create(array_merge([
            'id'          => LuckyWheel::generateId(),
            'name'        => '测试奖品',
            'cost_points' => 10,
            'prize_type'  => 'points',
            'prize_value' => 100.00,
            'weight'      => 100,
            'stock'       => -1,
            'sort'        => 1,
            'status'      => 1,
        ], $overrides));
        $this->prizeIds[] = $prize->id;
        return $prize;
    }

    private function availablePoints(string $userId): int
    {
        $earned   = (int) UserPoints::where('user_id', $userId)->where('type', 'earn')->sum('points');
        $consumed = (int) UserPoints::where('user_id', $userId)->whereIn('type', ['consume', 'use'])->sum('points');
        $expired  = (int) UserPoints::where('user_id', $userId)->where('type', 'expire')->sum('points');
        return $earned + $consumed + $expired;
    }

    /** 测试期间隐藏演示种子奖品，保证权重池仅含用例奖品（确定性抽奖） */
    private function hideSeedPrizes(): void
    {
        LuckyWheel::whereIn('id', ['10000000000001001', '10000000000001002'])
            ->update(['status' => 0]);
    }

    #[Test]
    public function prizesOnlyListsOnShelf(): void
    {
        $this->hideSeedPrizes();
        $onShelf = $this->makePrize(['name' => '上架奖品']);
        $this->makePrize(['name' => '下架奖品', 'status' => 0]);

        $request = $this->makeRequest('GET');
        $request->user_id = $this->newUserId();
        $data = $this->body((new WheelController())->prizes($request))['data'];

        $names = array_column($data['list'], 'name');
        $this->assertSame(['上架奖品'], $names);
        // 权重/库存/排序等博弈信息不下发
        $this->assertArrayNotHasKey('weight', $data['list'][0]);
        $this->assertArrayNotHasKey('stock', $data['list'][0]);
        $this->assertSame(10, $data['list'][0]['cost_points']);
        $this->assertSame('points', $data['list'][0]['prize_type']);
        // JSON 数字无小数时解码为 int，fixture 值固定 100.00
        $this->assertSame(100, $data['list'][0]['prize_value']);
    }

    #[Test]
    public function spinDeductsPointsAndWritesRecord(): void
    {
        $this->hideSeedPrizes();
        $userId = $this->newUserId();
        $this->makePoints($userId, 100);
        $prize = $this->makePrize(['name' => '100积分返还']);

        $request = $this->makeRequest('POST');
        $request->user_id = $userId;
        $data = $this->body((new WheelController())->spin($request))['data'];

        $this->assertSame('win', $data['result']);
        $this->assertSame('100积分返还', $data['name']);
        $this->assertSame(10, $data['cost_points']);

        // 扣积分流水：-10，余额快照 100 - 10 = 90
        $consume = UserPoints::where('user_id', $userId)
            ->where('type', 'consume')
            ->where('source', 'lucky_wheel')
            ->first();
        $this->assertNotNull($consume);
        $this->assertSame(-10, (int) $consume->points);
        $this->assertSame(90, (int) $consume->balance);

        $record = WheelRecord::where('user_id', $userId)->first();
        $this->assertNotNull($record);
        $this->assertSame('win', (string) $record->result);
        $this->assertSame((string) $prize->id, (string) $record->wheel_id);
        $this->recordIds[] = $record->id;
    }

    #[Test]
    public function spinInsufficientPointsReturns422(): void
    {
        $this->hideSeedPrizes();
        $userId = $this->newUserId();
        $this->makePoints($userId, 5);
        $this->makePrize();

        $request = $this->makeRequest('POST');
        $request->user_id = $userId;
        $response = $this->body((new WheelController())->spin($request));

        $this->assertSame(422, $response['code']);
        $this->assertSame('积分不足', $response['message']);
        $this->assertNull(WheelRecord::where('user_id', $userId)->first());
    }

    #[Test]
    public function spinPointsPrizeGrantsEarnFlow(): void
    {
        $this->hideSeedPrizes();
        $userId = $this->newUserId();
        $this->makePoints($userId, 100);
        $this->makePrize(['name' => '100积分返还', 'prize_type' => 'points', 'prize_value' => 100]);

        $request = $this->makeRequest('POST');
        $request->user_id = $userId;
        $data = $this->body((new WheelController())->spin($request))['data'];

        $this->assertSame('win', $data['result']);
        $this->assertSame('granted', $data['grant']['status']);
        $this->assertSame(100, $data['grant']['points']);

        // 中奖返还积分流水（type=earn source=lucky_wheel，含过期时间）
        $earn = UserPoints::where('user_id', $userId)
            ->where('type', 'earn')
            ->where('source', 'lucky_wheel')
            ->first();
        $this->assertNotNull($earn);
        $this->assertSame(100, (int) $earn->points);
        $this->assertNotNull($earn->expires_at);
        // 100 - 10(消耗) + 100(返还) = 190
        $this->assertSame(190, $this->availablePoints($userId));
    }

    #[Test]
    public function spinNonePrizeOnlyRecordsNoGrant(): void
    {
        $this->hideSeedPrizes();
        $userId = $this->newUserId();
        $this->makePoints($userId, 100);
        $this->makePrize(['name' => '谢谢参与', 'prize_type' => 'none', 'prize_value' => 0]);

        $request = $this->makeRequest('POST');
        $request->user_id = $userId;
        $response = $this->body((new WheelController())->spin($request));

        $this->assertSame(0, $response['code']);
        $this->assertSame('谢谢参与', $response['message']);
        $this->assertSame('lose', $response['data']['result']);
        $this->assertNull($response['data']['grant']);

        // 仅记录，无任何发放流水
        $record = WheelRecord::where('user_id', $userId)->first();
        $this->assertNotNull($record);
        $this->assertSame('lose', (string) $record->result);
        $this->assertSame('none', (string) $record->prize_type);
        $this->recordIds[] = $record->id;
        $this->assertNull(UserPoints::where('user_id', $userId)
            ->where('source', 'lucky_wheel')
            ->where('type', 'earn')
            ->first());
    }

    #[Test]
    public function recordsPaginated(): void
    {
        $userId = $this->newUserId();
        for ($i = 0; $i < 3; $i++) {
            $record = WheelRecord::create([
                'id'          => WheelRecord::generateId(),
                'user_id'     => $userId,
                'wheel_id'    => '10000000000001001',
                'prize_type'  => 'none',
                'prize_value' => 0,
                'result'      => 'lose',
            ]);
            $this->recordIds[] = $record->id;
        }

        $request = $this->makeRequest('GET', ['per_page' => 2]);
        $request->user_id = $userId;
        $response = $this->body((new WheelController())->records($request));

        $this->assertSame(0, $response['code']);
        $this->assertCount(2, $response['data']);
        $this->assertSame(3, $response['meta']['total']);
        $this->assertSame('谢谢参与', $response['data'][0]['prize_name']);
        $this->assertSame('lose', $response['data'][0]['result']);
    }
}
