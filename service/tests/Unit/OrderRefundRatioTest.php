<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Order;
use Carbon\Carbon;

class OrderRefundRatioTest extends TestCase
{
    private function make(array $attrs): Order
    {
        $o = new Order();
        $o->setRawAttributes(array_merge(['created_at' => Carbon::now(), 'updated_at' => Carbon::now()], $attrs));
        return $o;
    }

    #[Test] public function refund_full_15min(): void
    {
        $o = $this->make(['status' => 'paid', 'created_at' => Carbon::now(), 'service_time' => Carbon::now()->addHours(12)]);
        $this->assertEquals(1.00, $o->calcRefundRatio());
    }

    #[Test] public function refund_full_over_6h(): void
    {
        $o = $this->make(['status' => 'paid', 'created_at' => Carbon::now()->subHours(2), 'service_time' => Carbon::now()->addHours(10)]);
        $this->assertEquals(1.00, $o->calcRefundRatio());
    }

    #[Test] public function refund_90_within_6h(): void
    {
        $o = $this->make(['status' => 'paid', 'created_at' => Carbon::now()->subHours(2), 'service_time' => Carbon::now()->addHours(3)]);
        $this->assertEquals(0.90, $o->calcRefundRatio());
    }

    #[Test] public function refund_zero_serving(): void
    {
        // M8 规则：核销即开始服务则不可退（serving 0.80 → 0），与 isRefundable()/refund() 保持一致
        $o = $this->make(['status' => 'serving', 'created_at' => Carbon::now()->subHours(3), 'service_start_at' => Carbon::now()->subMinutes(10)]);
        $this->assertEquals(0.0, $o->calcRefundRatio());
    }

    #[Test] public function refund_zero_completed(): void
    {
        $o = $this->make(['status' => 'completed', 'created_at' => Carbon::now()->subHours(4), 'service_start_at' => Carbon::now()->subHours(1)]);
        $this->assertEquals(0.0, $o->calcRefundRatio());
    }

    #[Test] public function paid_refundable(): void
    {
        $this->assertTrue($this->make(['status' => 'paid'])->isRefundable());
    }

    #[Test] public function pending_not_refundable(): void
    {
        $this->assertFalse($this->make(['status' => 'pending'])->isRefundable());
    }

    #[Test] public function completed_not_refundable(): void
    {
        $this->assertFalse($this->make(['status' => 'completed'])->isRefundable());
    }
}
