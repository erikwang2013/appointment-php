<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Hashids\Hashids;

class HashidsEncodingTest extends TestCase
{
    private Hashids $hashids;

    protected function setUp(): void
    {
        $this->hashids = new Hashids('appointment-service-test', 8);
    }

    #[Test] public function encodes_positive_integers(): void
    {
        $hash = $this->hashids->encode(123);
        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    #[Test] public function encode_decode_roundtrip(): void
    {
        $encoded = $this->hashids->encode(9876543210);
        $decoded = $this->hashids->decode($encoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals(9876543210, $decoded[0]);
    }

    #[Test] public function different_ids_different_hashes(): void
    {
        $h1 = $this->hashids->encode(100);
        $h2 = $this->hashids->encode(200);
        $this->assertNotEquals($h1, $h2);
    }

    #[Test] public function same_id_same_hash(): void
    {
        $this->assertEquals($this->hashids->encode(42), $this->hashids->encode(42));
    }
}
