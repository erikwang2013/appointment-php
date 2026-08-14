<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\marketing\v1\controller\CardController;
use app\model\MemberCard;
use app\model\MemberCardUsage;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\Service;
use app\model\UserMemberCard;
use support\Container;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 次卡核销闭环测试
 *
 * 覆盖：我的次卡列表（归属/剩余次数/状态）、核销成功扣次并生成已完成订单
 * （pay_type=card）、剩余次数不足 422、过期 400、无效 hashid 422、
 * 非本人次卡 404、Redis 幂等锁防短时间重复提交。
 * 基建与 WalletTest 一致（真实 DB + tearDown 清理）；Redis 用例不可用时 skip。
 */
class CardUsageTest extends TestCase
{
    /** @var string[] 用例次卡记录 ID，tearDown 统一清理 */
    private array $cardIds = [];

    /** @var string[] 用例用户次卡 ID，tearDown 统一清理 */
    private array $userCardIds = [];

    /** @var string[] 用例服务 ID，tearDown 统一清理 */
    private array $serviceIds = [];

    /** @var string[] 用例使用记录 ID，tearDown 统一清理 */
    private array $usageIds = [];

    /** @var string[] 用例订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例 Redis key，tearDown 统一清理 */
    private array $redisKeys = [];

    protected function tearDown(): void
    {
        foreach ($this->usageIds as $id) {
            MemberCardUsage::where('id', $id)->delete();
        }
        foreach ($this->orderIds as $id) {
            OrderItem::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->userCardIds as $id) {
            UserMemberCard::where('id', $id)->delete();
        }
        foreach ($this->cardIds as $id) {
            MemberCard::where('id', $id)->delete();
        }
        foreach ($this->serviceIds as $id) {
            Service::where('id', $id)->delete();
        }
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }

        $this->cardIds = [];
        $this->userCardIds = [];
        $this->serviceIds = [];
        $this->usageIds = [];
        $this->orderIds = [];
        $this->redisKeys = [];
    }

    // ── 工具 ──

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
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    private function makeCard(int $totalTimes = 10): MemberCard
    {
        $card = new MemberCard();
        $card->id = MemberCard::generateId();
        $card->name = '测试次卡' . substr($card->id, -4);
        $card->type = 'times';
        $card->price = 99.00;
        $card->duration_days = 0;
        $card->total_times = $totalTimes;
        $card->services = [];
        $card->status = 1;
        $card->save();
        $this->cardIds[] = $card->id;
        return $card;
    }

    private function makeUserCard(string $userId, MemberCard $card, int $usedTimes = 0, ?string $endAt = null): UserMemberCard
    {
        $uc = new UserMemberCard();
        $uc->id = UserMemberCard::generateId();
        $uc->user_id = $userId;
        $uc->card_id = $card->id;
        $uc->start_at = date('Y-m-d H:i:s', time() - 86400);
        $uc->end_at = $endAt ?? date('Y-m-d H:i:s', time() + 86400 * 30);
        $uc->total_times = $card->total_times;
        $uc->used_times = $usedTimes;
        $uc->status = $usedTimes >= $card->total_times ? 'used_up' : 'active';
        $uc->save();
        $this->userCardIds[] = $uc->id;
        return $uc;
    }

    private function makeService(): Service
    {
        $svc = new Service();
        $svc->id = Service::generateId();
        $svc->category_id = 0;
        $svc->name = '测试服务' . substr($svc->id, -4);
        $svc->price = 88.00;
        $svc->duration = 60;
        $svc->status = 1;
        $svc->save();
        $this->serviceIds[] = $svc->id;
        return $svc;
    }

    private function hashId(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    private function redisAvailable(): bool
    {
        try {
            $probe = 'test:card:probe:' . uniqid();
            $this->redisKeys[] = $probe;
            Redis::setex($probe, 5, '1');
            return Redis::get($probe) === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    // ── 用例 ──

    #[Test]
    public function myReturnsOwnedCardsWithRemainingAndStatus(): void
    {
        $uid = $this->newUserId();
        $other = $this->newUserId();
        $card1 = $this->makeCard(5);
        $card2 = $this->makeCard(3);
        $uc1 = $this->makeUserCard($uid, $card1, 1);   // 剩 4 次
        $uc2 = $this->makeUserCard($uid, $card2, 3);   // 剩 0 次 → used_up
        $this->makeUserCard($other, $card1);           // 别人的卡不返回

        $resp = $this->body((new CardController())->my($this->authRequest($uid)));
        $this->assertSame(0, $resp['code']);
        $data = $resp['data'];
        $this->assertCount(2, $data);

        $byId = [];
        foreach ($data as $item) {
            $byId[$item['id']] = $item;
        }
        $this->assertArrayHasKey($this->hashId((int) $uc1->id), $byId);
        $this->assertArrayHasKey($this->hashId((int) $uc2->id), $byId);

        $item1 = $byId[$this->hashId((int) $uc1->id)];
        $this->assertSame(5, $item1['total_times']);
        $this->assertSame(1, $item1['used_times']);
        $this->assertSame(4, $item1['remaining_times']);
        $this->assertSame('active', $item1['status']);
        $this->assertSame($card1->name, $item1['name']);

        $item2 = $byId[$this->hashId((int) $uc2->id)];
        $this->assertSame(0, $item2['remaining_times']);
        $this->assertSame('used_up', $item2['status']);
    }

    #[Test]
    public function useDeductsTimesAndCreatesCompletedCardOrder(): void
    {
        $uid = $this->newUserId();
        $card = $this->makeCard(3);
        $uc = $this->makeUserCard($uid, $card);
        $svc = $this->makeService();

        $resp = $this->body((new CardController())->use($this->authRequest($uid, [
            'user_card_id' => $this->hashId((int) $uc->id),
            'service_id' => $this->hashId((int) $svc->id),
            'remark' => '前台核销',
        ])));

        $this->assertSame(0, $resp['code']);
        $this->assertSame(2, $resp['data']['remaining_times']);

        // 次数已扣
        $this->assertSame(1, (int) UserMemberCard::find($uc->id)->used_times);

        // 使用记录
        $usage = MemberCardUsage::where('user_card_id', $uc->id)
            ->where('service_id', $svc->id)
            ->first();
        $this->assertNotNull($usage);
        $this->assertSame('active', $usage->status);
        $this->usageIds[] = $usage->id;

        // 已完成订单 + pay_type=card 支付记录 + 订单明细
        $order = Order::find($usage->order_id);
        $this->assertNotNull($order);
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame((string) $usage->id, (string) $order->member_card_usage_id);
        $this->assertSame('前台核销', $order->remark);
        $this->assertNotEmpty($order->order_no);
        $this->orderIds[] = $order->id;

        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('card', $payment->pay_type);
        $this->assertSame(OrderPayment::STATUS_SUCCESS, $payment->status);

        $item = OrderItem::where('order_id', $order->id)->first();
        $this->assertNotNull($item);
        $this->assertSame((string) $svc->id, (string) $item->target_id);
        $this->assertSame($svc->name, $item->name);
    }

    #[Test]
    public function useReturns422WhenTimesExhausted(): void
    {
        $uid = $this->newUserId();
        $card = $this->makeCard(1);
        $uc = $this->makeUserCard($uid, $card, 1); // 已用完
        $svc = $this->makeService();

        $resp = $this->body((new CardController())->use($this->authRequest($uid, [
            'user_card_id' => $this->hashId((int) $uc->id),
            'service_id' => $this->hashId((int) $svc->id),
        ])));

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('不足', $resp['message']);
        // 未产生使用记录
        $this->assertSame(0, MemberCardUsage::where('user_card_id', $uc->id)->count());
    }

    #[Test]
    public function useReturns400WhenCardExpired(): void
    {
        $uid = $this->newUserId();
        $card = $this->makeCard(5);
        $uc = $this->makeUserCard($uid, $card, 0, date('Y-m-d H:i:s', time() - 3600)); // 已过期
        $svc = $this->makeService();

        $resp = $this->body((new CardController())->use($this->authRequest($uid, [
            'user_card_id' => $this->hashId((int) $uc->id),
            'service_id' => $this->hashId((int) $svc->id),
        ])));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('过期', $resp['message']);
    }

    #[Test]
    public function useReturns422ForInvalidHashids(): void
    {
        $uid = $this->newUserId();

        // 两个 ID 都是无效 hashid
        $resp = $this->body((new CardController())->use($this->authRequest($uid, [
            'user_card_id' => 'garbage-id',
            'service_id' => 'garbage-id',
        ])));
        $this->assertSame(422, $resp['code']);

        // user_card_id 有效但 service_id 无效
        $card = $this->makeCard(5);
        $uc = $this->makeUserCard($uid, $card);
        $resp = $this->body((new CardController())->use($this->authRequest($uid, [
            'user_card_id' => $this->hashId((int) $uc->id),
            'service_id' => 'garbage-id',
        ])));
        $this->assertSame(422, $resp['code']);
    }

    #[Test]
    public function useReturns404ForOthersCard(): void
    {
        $uid = $this->newUserId();
        $other = $this->newUserId();
        $card = $this->makeCard(5);
        $uc = $this->makeUserCard($other, $card);
        $svc = $this->makeService();

        $resp = $this->body((new CardController())->use($this->authRequest($uid, [
            'user_card_id' => $this->hashId((int) $uc->id),
            'service_id' => $this->hashId((int) $svc->id),
        ])));

        $this->assertSame(404, $resp['code']);
        $this->assertStringContainsString('不存在', $resp['message']);
    }

    #[Test]
    public function useRejectsDuplicateSubmissionWithinLockWindow(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过幂等锁用例');
        }

        $uid = $this->newUserId();
        $card = $this->makeCard(5);
        $uc = $this->makeUserCard($uid, $card);
        $svc = $this->makeService();

        // 模拟同一 user_card_id+service_id 在锁窗口内的重复提交
        $lockKey = 'card_use:' . $uc->id . ':' . $svc->id;
        $this->redisKeys[] = $lockKey;
        Redis::setex($lockKey, 60, (string) $uid);

        $resp = $this->body((new CardController())->use($this->authRequest($uid, [
            'user_card_id' => $this->hashId((int) $uc->id),
            'service_id' => $this->hashId((int) $svc->id),
        ])));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('频繁', $resp['message']);
        // 未扣次、未产生使用记录
        $this->assertSame(0, (int) UserMemberCard::find($uc->id)->used_times);
        $this->assertSame(0, MemberCardUsage::where('user_card_id', $uc->id)->count());
    }
}
