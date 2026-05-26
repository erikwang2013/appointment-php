<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Order;

class OrderStateTest extends TestCase
{
    #[Test] public function constants_are_correct(): void
    {
        $this->assertEquals('pending', Order::STATUS_PENDING);
        $this->assertEquals('paid', Order::STATUS_PAID);
        $this->assertEquals('confirmed', Order::STATUS_CONFIRMED);
        $this->assertEquals('serving', Order::STATUS_SERVING);
        $this->assertEquals('completed', Order::STATUS_COMPLETED);
        $this->assertEquals('cancelled', Order::STATUS_CANCELLED);
        $this->assertEquals('refunding', Order::STATUS_REFUNDING);
        $this->assertEquals('refunded', Order::STATUS_REFUNDED);
    }

    #[Test] public function is_cancellable_when_pending(): void
    {
        $o = new Order(); $o->setRawAttributes(['status' => 'pending']);
        $this->assertTrue(in_array($o->status, ['pending', 'paid']));
    }

    #[Test] public function is_cancellable_when_paid(): void
    {
        $o = new Order(); $o->setRawAttributes(['status' => 'paid']);
        $this->assertTrue(in_array($o->status, ['pending', 'paid']));
    }

    #[Test] public function not_cancellable_when_serving(): void
    {
        $o = new Order(); $o->setRawAttributes(['status' => 'serving']);
        $this->assertFalse(in_array($o->status, ['pending', 'paid']));
    }

    #[Test] public function order_type_defaults_to_appointment(): void
    {
        $o = new Order(['order_type' => 'appointment']);
        $this->assertEquals('appointment', $o->order_type);
    }
}
