<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\common\Money;

/**
 * Money bcmath 封装冒烟（admin 副本）
 *
 * admin 端 Money 与 service 端同签名同实现（双份），本文件只做加载态冒烟——
 * 完整边界用例见 service/tests/Unit/MoneyTest.php（16 用例 / 63 断言）。
 * 覆盖 admin 控制器实际用法：RefundWorkflowController 的 ≥500 审核阈值 cmp、
 * ReportController net_revenue 减法链、WithdrawalController 金额比对。
 */
class MoneyTest extends TestCase
{
    #[Test] public function admin_copy_rounds_and_converts_like_service(): void
    {
        $this->assertSame('1.01', Money::round('1.005', 2));
        $this->assertSame('-1.01', Money::round('-1.005', 2));
        $this->assertSame('0.3000', Money::add('0.1', '0.2'));
        $this->assertSame('66.68', Money::round(Money::sub('100.01', '33.33'), 2));
        $this->assertSame(1, Money::toFen('0.005'));
        $this->assertSame(8, Money::toFen('0.075'));   // 浮点陷阱：(int) round(0.075*100) = 7
    }

    #[Test] public function admin_threshold_comparisons_use_cmp(): void
    {
        // RefundWorkflowController：退款金额 ≥ 500.00 才走两级审批
        $this->assertTrue(Money::cmp('500.00', '500.00') >= 0);
        $this->assertTrue(Money::cmp('500.01', '500.00') >= 0);
        $this->assertFalse(Money::cmp('499.99', '500.00') >= 0);
        // 2dp DECIMAL 值域内 cmp 与金额域自洽（半分之差不可达）
        $this->assertSame(-1, Money::cmp('0.01', '0.02'));
    }

    #[Test] public function admin_div_zero_guarded(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::div('1', '0');
    }
}
