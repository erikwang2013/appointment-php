<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\OrderReview;
use app\model\User;
use app\order\v1\controller\ReviewController;
use support\Container;
use Webman\Http\Request;

/**
 * 评价图片审核状态流转测试（真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 隐藏后技师公开评价列表不再返回该条（byTechnician 端到端）
 * - 恢复后重新可见
 * - 带图评价 images JSON round-trip（审核列表 images 非空前提）
 * - 无图评价仍正常公开（隐藏/恢复不影响公开可见性之外的维度）
 *
 * 管理端隐藏/恢复控制器见 admin/tests/ReviewAuditControllerTest.php。
 */
class ReviewAuditTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例评价 ID */
    private array $reviewIds = [];

    protected function tearDown(): void
    {
        foreach ($this->reviewIds as $id) {
            OrderReview::where('id', $id)->delete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->userIds  = [];
        $this->reviewIds = [];
    }

    /** 造用户 */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '188' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '评价审核测试用户',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造带图评价（status 由调用方控制） */
    private function makeReview(int $status, string $technicianId): OrderReview
    {
        $review = OrderReview::create([
            'id'            => OrderReview::generateId(),
            'order_id'      => OrderReview::generateId(),
            'user_id'       => $this->makeUser()->id,
            'technician_id' => $technicianId,
            'rating'        => 5,
            'content'       => '评价图片审核测试内容',
            'images'        => ['https://example.com/a.jpg'],
            'status'        => $status,
        ]);
        $this->reviewIds[] = $review->id;
        return $review;
    }

    /** 调用 byTechnician 并返回响应中的评价 hashid id 集合 */
    private function publicReviewIds(string $technicianId): array
    {
        $controller = new ReviewController();
        $request    = new Request("GET / HTTP/1.1\r\nHost: localhost\r\n\r\n");
        $response   = $controller->byTechnician($request, $this->hashid((int) $technicianId));
        $data       = json_decode($response->rawBody(), true);
        return array_column($data['data'] ?? [], 'id');
    }

    private function hashid(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    #[Test]
    public function hidden_review_excluded_from_public_list(): void
    {
        $techId = '90000000000001001';
        $review = $this->makeReview(OrderReview::STATUS_VISIBLE, $techId);

        $this->assertContains($this->hashid((int) $review->id), $this->publicReviewIds($techId));

        $review->status = OrderReview::STATUS_HIDDEN;
        $review->save();

        $this->assertNotContains($this->hashid((int) $review->id), $this->publicReviewIds($techId));
    }

    #[Test]
    public function restore_makes_review_visible_again(): void
    {
        $techId = '90000000000001002';
        $review = $this->makeReview(OrderReview::STATUS_HIDDEN, $techId);

        $this->assertNotContains($this->hashid((int) $review->id), $this->publicReviewIds($techId));

        $review->status = OrderReview::STATUS_VISIBLE;
        $review->save();

        $this->assertContains($this->hashid((int) $review->id), $this->publicReviewIds($techId));
    }

    #[Test]
    public function images_stored_as_json_and_cast_back_to_array(): void
    {
        $review = $this->makeReview(OrderReview::STATUS_VISIBLE, '90000000000001003');
        $fresh  = OrderReview::find($review->id);

        $this->assertIsArray($fresh->images);
        $this->assertSame(['https://example.com/a.jpg'], $fresh->images);
    }

    #[Test]
    public function review_without_images_still_public(): void
    {
        $techId = '90000000000001004';
        $review = OrderReview::create([
            'id'            => OrderReview::generateId(),
            'order_id'      => OrderReview::generateId(),
            'user_id'       => $this->makeUser()->id,
            'technician_id' => $techId,
            'rating'        => 4,
            'content'       => '纯文字评价',
            'images'        => [],
            'status'        => OrderReview::STATUS_VISIBLE,
        ]);
        $this->reviewIds[] = $review->id;

        $this->assertContains($this->hashid((int) $review->id), $this->publicReviewIds($techId));
    }
}
