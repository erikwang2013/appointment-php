<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Notification;
use app\model\Order;
use app\model\OrderReview;
use app\model\TechnicianProfile;
use app\model\User;
use app\technician\v1\controller\ReviewController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 技师回复评价测试
 *
 * 覆盖：技师本人回复成功并落站内通知（type=review_reply）、
 * 非本人 404、重复回复 422、评价不存在 404、回复内容为空 422。
 * 基建与 TierRatingTest 一致（真实 DB + tearDown 清理）。
 */
class ReviewReplyTest extends TestCase
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
            'real_name' => '回复测试技师' . substr((string) $user->id, -4),
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

    /** 造已完成订单 + 评价 */
    private function makeReview(TechnicianProfile $technician, User $user): OrderReview
    {
        $order = Order::create([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_REV_' . uniqid(),
            'user_id'         => $user->id,
            'technician_id'   => $technician->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => Order::STATUS_COMPLETED,
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

    private function makeRequest(string $reply): Request
    {
        $body = http_build_query(['reply' => $reply]);
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

    private function reply(string $technicianId, OrderReview $review, string $content): array
    {
        $request = $this->makeRequest($content);
        $request->technician_id = $technicianId;
        return $this->body((new ReviewController())->reply($request, $this->encodeId((int) $review->order_id)));
    }

    // ── 技师本人回复成功 + 站内通知 ──

    #[Test] public function technician_reply_succeeds_and_notifies_user(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $resp = $this->reply($technician->id, $review, '感谢您的评价，欢迎再来');

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('回复成功', $resp['message']);

        $review->refresh();
        $this->assertSame('感谢您的评价，欢迎再来', $review->reply);
        $this->assertNotNull($review->replied_at, '回复应写入 replied_at');

        $notice = Notification::where('user_id', $user->id)
            ->where('type', 'review_reply')
            ->orderBy('created_at', 'desc')
            ->first();
        $this->assertNotNull($notice, '回复后应生成站内通知');
        $this->assertSame((int) $review->order_id, (int) $notice->order_id);
        $this->assertStringContainsString('感谢您的评价', (string) $notice->content);
    }

    // ── 非本人技师回复 404 ──

    #[Test] public function other_technician_gets_404(): void
    {
        $technician = $this->makeTechnician();
        $other = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $resp = $this->reply($other->id, $review, '我是别的技师');

        $this->assertSame(404, $resp['code'], json_encode($resp));
        $review->refresh();
        $this->assertSame('', (string) $review->reply, '非本人回复不应落库');
    }

    // ── 重复回复 422 ──

    #[Test] public function duplicate_reply_gets_422(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $this->reply($technician->id, $review, '第一次回复');
        $resp = $this->reply($technician->id, $review, '第二次回复');

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $review->refresh();
        $this->assertSame('第一次回复', $review->reply, '重复回复不应覆盖原内容');
    }

    // ── 评价不存在 404 ──

    #[Test] public function missing_review_gets_404(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        // 删除评价后再回复
        OrderReview::where('id', $review->id)->delete();

        $resp = $this->reply($technician->id, $review, '评价已删除');

        $this->assertSame(404, $resp['code'], json_encode($resp));
    }

    // ── 回复内容为空 422 ──

    #[Test] public function empty_reply_gets_422(): void
    {
        $technician = $this->makeTechnician();
        $user = $this->makeUser();
        $review = $this->makeReview($technician, $user);

        $resp = $this->reply($technician->id, $review, '   ');

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $review->refresh();
        $this->assertSame('', (string) $review->reply);
    }
}
