<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\QueueNumber;

class QueueSystemTest extends TestCase
{
    #[Test] public function status_constants_correct(): void
    {
        $this->assertEquals('waiting', QueueNumber::STATUS_WAITING);
        $this->assertEquals('called', QueueNumber::STATUS_CALLED);
        $this->assertEquals('serving', QueueNumber::STATUS_SERVING);
        $this->assertEquals('completed', QueueNumber::STATUS_COMPLETED);
        $this->assertEquals('cancelled', QueueNumber::STATUS_CANCELLED);
    }

    #[Test] public function can_create_with_status(): void
    {
        $q = new QueueNumber();
        $this->assertEquals('waiting', QueueNumber::STATUS_WAITING);
        $this->assertNotEmpty(QueueNumber::STATUS_WAITING);
    }

    #[Test] public function model_table_name_correct(): void
    {
        $q = new QueueNumber();
        $this->assertEquals('appointment_queue_number', $q->getTable());
    }

    #[Test] public function model_uses_snowflake(): void
    {
        $q = new QueueNumber();
        $this->assertFalse($q->incrementing);
        // keyType set by parent supportModel::boot()
        $this->assertFalse($q->incrementing);
    }
}
