<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Notification;
use app\model\Order;
use app\model\OrderReview;
use app\model\TechnicianProfile;
use app\model\User;
use app\order\v1\controller\ReviewController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 评价追评测试
 *
 * 覆盖：本人追评成功并落站内通知（type=review_append）且响应透出 append 字段、
 * 非本人 404、重复追评 422、空内容 422、非 completed 订单 422。
 * 基建与 ReviewReplyTest 一致（真实 DB + tearDown 清理）。
 */
class ReviewAppendTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    /** @var string[] 用例订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例评价 ID，tearDown 统一清理 */
    private array $reviewIds = [];

    protected function tearDown(): void
    {
        if ($this->userIds) {
            Notification::whereIn('user_id', $this->userIds)->delete();
        }
        if ($this->reviewIds) {
            OrderReview::whereIn('id', $this->reviewIds)->delete();
        }
        if ($this->orderIds) {
            Order::whereIn('id', $this->orderIds)->delete();
        }
        if ($this->profileIds) {
            TechnicianProfile::whereIn('id', $this->profileIds)->delete();
        }
        if ($this->userIds) {
            User::whereIn('id', $this->userIds)->delete();
        }
        $this->userIds = $this->profileIds = $this->orderIds = $this->reviewIds = [];
    }

    /** 造用户 + 技师档案 */
    private function makeTechnician(): TechnicianProfile
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = (string) $user->id;

        $profile = TechnicianProfile::create([
            'id'        => TechnicianProfile::generateId(),
            'user_id'   => $user->id,
            'real_name' => '追评测试技师' . substr((string) $user->id, -4),
            'status'    => 'approved',
        ]);
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    /** 造评价用户 */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = (string) $user->id;
        return $user;
    }

    /** 造订单 + 评价（订单状态可指定，默认已完成） */
    private function makeReview(TechnicianProfile $technician, User $user, string $status = Order::STATUS_COMPLETED): OrderReview
    {
        $order = Order::create([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_APP_' . uniqid(),
            'user_id'         => $user->id,
            'technician_id'   => $technician->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => $status,
            'service_time'    => date('Y-m-d H:i:s', time() + 43200),
        ]);
        $this->orderIds[] = $order->id;

        $review = OrderReview::create([
            'id'            => OrderReview::generateId(),
            'order_id'      => $order->id,
            'user_id'       => $user->id,
            'technician_id' => $technician->id,
            'rating'        => 5,
            'content'       => '服务很好',
            'images'        => [],
            'status'        => OrderReview::STATUS_VISIBLE,
        ]);
        $this->reviewIds[] = $review->id;
        return $review;
    }

    private function makeRequest(string $content, string $images = ''): Request
    {
        $params = ['content' => $content];
        if ($images !== '') {
            $params['images'] = $images;
        }
        $body = http_build_query($params);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function encodeId(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    private function append(string $userId, OrderReview $review, string $content, string $images = ''): array
    {
        $request = $this->makeRequest($content, $images);
        $request->user_id = $userId;
        return $this->body((new ReviewController())->append($request, $this->encodeId((int) $review->order_id)));
    }

    // ── 本人追评成功 + 通知技师 + 响应透出 ──

    #[Test] public function user_appends_review_succeeds_and_notifies_technician(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $resp = $this->append((string) $user->id, $review, '补充：手法很专业', 'https://cdn.example.com/a.jpg, https://cdn.example.com/b.jpg');

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('追评成功', $resp['message']);

        // 响应透出 append 字段
        $this->assertSame('补充：手法很专业', $resp['data']['append_content'] ?? null, '响应应透出 append_content');
        $this->assertSame(
            ['https://cdn.example.com/a.jpg', 'https://cdn.example.com/b.jpg'],
            $resp['data']['append_images'] ?? null,
            '响应应透出 append_images（去空格后的数组）'
        );
        $this->assertNotEmpty($resp['data']['append_at'] ?? null, '响应应透出 append_at');

        $review->refresh();
        $this->assertSame('补充：手法很专业', $review->append_content);
        $this->assertSame(['https://cdn.example.com/a.jpg', 'https://cdn.example.com/b.jpg'], $review->append_images);
        $this->assertNotNull($review->append_at, '追评应写入 append_at');

        $notice = Notification::where('user_id', $technician->user_id)
            ->where('type', 'review_append')
            ->orderBy('created_at', 'desc')
            ->first();
        $this->assertNotNull($notice, '追评后应生成站内通知');
        $this->assertSame((int) $review->order_id, (int) $notice->order_id);
        $this->assertStringContainsString('手法很专业', (string) $notice->content);
    }

    // ── 非本人追评 404 ──

    #[Test] public function other_user_gets_404(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $other = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $resp = $this->append((string) $other->id, $review, '别人想追评');

        $this->assertSame(404, $resp['code'], json_encode($resp));
        $review->refresh();
        $this->assertNull($review->append_content, '非本人追评不应落库');
    }

    // ── 重复追评 422 ──

    #[Test] public function duplicate_append_gets_422(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $this->append((string) $user->id, $review, '第一次追评');
        $resp = $this->append((string) $user->id, $review, '第二次追评');

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $review->refresh();
        $this->assertSame('第一次追评', $review->append_content, '重复追评不应覆盖原内容');
    }

    // ── 空内容 422 ──

    #[Test] public function empty_content_gets_422(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $resp = $this->append((string) $user->id, $review, '   ');

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $review->refresh();
        $this->assertNull($review->append_content);
    }

    // ── 非 completed 订单 422 ──

    #[Test] public function non_completed_order_gets_422(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user, Order::STATUS_PAID);

        $resp = $this->append((string) $user->id, $review, '订单未完成也能追评吗');

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $review->refresh();
        $this->assertNull($review->append_content);
    }

    // ── 用户提交评价（POST /api/v1/order/review/{order_id}，第 19 轮补注册路由）──

    private function store(string $userId, Order $order, array $params = []): array
    {
        $body = http_build_query(array_merge(['rating' => 5, 'content' => '服务很好'], $params));
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        $request = new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
        $request->user_id = $userId;
        return $this->body((new ReviewController())->store($request, $this->encodeId((int) $order->id)));
    }

    #[Test] public function user_submits_review_succeeds(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);
        // 已有评价，先清掉以便测试首次提交
        $review->delete();
        $this->reviewIds = array_diff($this->reviewIds, [$review->id]);

        $order = Order::find($review->order_id);
        $resp = $this->store((string) $user->id, $order, ['rating' => 4, 'content' => '手法不错']);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('评价成功', $resp['message']);
        $saved = OrderReview::findByOrderId((string) $order->id);
        $this->assertNotNull($saved, '评价应落库');
        $this->assertSame(4, (int) $saved->rating);
        $this->assertSame('手法不错', $saved->content);
        $this->reviewIds[] = $saved->id;
    }

    #[Test] public function submit_review_non_owner_gets_404(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $other = $this->makeUser();
        $review = $this->makeReview($technician, $user);
        $review->delete();
        $this->reviewIds = array_diff($this->reviewIds, [$review->id]);

        $order = Order::find($review->order_id);
        $resp = $this->store((string) $other->id, $order);

        $this->assertSame(404, $resp['code'], json_encode($resp));
        $this->assertNull(OrderReview::findByOrderId((string) $order->id), '非本人不应落库');
    }

    #[Test] public function duplicate_review_gets_400(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $order = Order::find($review->order_id);
        $resp = $this->store((string) $user->id, $order);

        $this->assertSame(400, $resp['code'], json_encode($resp));
        $this->assertSame('该订单已评价', $resp['message']);
        $this->assertSame((int) $review->id, (int) OrderReview::findByOrderId((string) $order->id)->id, '不应覆盖原评价');
    }
}
