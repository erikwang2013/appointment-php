<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CaptchaTest extends TestCase
{
    protected function setUp(): void
    {
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
            $dotenv->safeLoad();
        }
    }

    #[Test]
    public function captcha_generate_returns_valid_structure(): void
    {
        $result = captcha_create('click', ['difficulty' => 'medium']);

        $this->assertArrayHasKey('key', $result, '应包含 key');
        $this->assertArrayHasKey('image', $result, '应包含 image');
        $this->assertArrayHasKey('type', $result, '应包含 type');
        $this->assertEquals('click', $result['type']);
        $this->assertArrayHasKey('extra', $result, '应包含 extra');
        $this->assertArrayHasKey('texts', $result['extra'], 'extra 应包含 texts');

        $this->assertNotEmpty($result['key']);
        $this->assertNotEmpty($result['image']);
        $this->assertGreaterThanOrEqual(2, count($result['extra']['texts']), '应至少有 2 个目标字');
    }

    #[Test]
    public function captcha_texts_have_required_fields(): void
    {
        $result = captcha_create('click', ['difficulty' => 'easy']);

        $this->assertCount(2, $result['extra']['texts'], 'easy 难度应有 2 个目标字');

        foreach ($result['extra']['texts'] as $text) {
            $this->assertArrayHasKey('text', $text);
            $this->assertArrayHasKey('order', $text);
            $this->assertIsString($text['text']);
            $this->assertIsInt($text['order']);
            $this->assertGreaterThanOrEqual(1, $text['order']);
        }
    }

    #[Test]
    public function captcha_difficulty_controls_text_count(): void
    {
        $easy = captcha_create('click', ['difficulty' => 'easy']);
        $medium = captcha_create('click', ['difficulty' => 'medium']);
        $hard = captcha_create('click', ['difficulty' => 'hard']);

        $this->assertCount(2, $easy['extra']['texts'], 'easy 应为 2 个目标字');
        $this->assertCount(5, $medium['extra']['texts'], 'medium（默认）应为 5 个目标字');
        $this->assertCount(4, $hard['extra']['texts'], 'hard 应为 4 个目标字');
    }

    #[Test]
    public function captcha_verify_wrong_clicks_fails(): void
    {
        $result = captcha_create('click', ['difficulty' => 'easy']);

        // [x, y] 坐标对 — 客户端提交用户点击位置
        $clicks = [[0, 0], [999, 999]];
        $valid = captcha_verify($result['key'], 'click', $clicks);

        $this->assertFalse($valid, '错误坐标应验证失败');
    }

    #[Test]
    public function captcha_key_persists_after_failed_attempt(): void
    {
        $result = captcha_create('click', ['difficulty' => 'easy']);

        // 错误验证不应删除 key（未达最大尝试次数）
        $clicks = [[0, 0], [999, 999]];
        $first = captcha_verify($result['key'], 'click', $clicks);
        $this->assertFalse($first);

        // 同一 key 仍可继续尝试验证
        $second = captcha_verify($result['key'], 'click', $clicks);
        $this->assertFalse($second);
    }

    #[Test]
    public function captcha_generates_unique_keys(): void
    {
        $r1 = captcha_create('click');
        $r2 = captcha_create('click');

        $this->assertNotEquals($r1['key'], $r2['key'], '每次生成的 key 应不同');
    }
}
