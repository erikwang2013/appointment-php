<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\Money;

/**
 * Money bcmath 封装边界用例（R32 bcmath 改造配套）
 *
 * 覆盖：
 *   - add/sub/mul：浮点盲区精确值（0.1+0.2 族、1.1×1.1 族）、大数超 2^53、scale=0 截断
 *   - div：scale 截断（非四舍五入）、除 0（含 '0.0' 形态）抛 InvalidArgumentException
 *   - round：半进位远离零（1.005/负值/进位链 0.995、9.999@1）、scale=0
 *   - toFen：半分之差修正（0.005→1、0.075→8 浮点陷阱旧 (int)round(0.075*100)=7）、负数
 *   - cmp：相等 0/大小、按 scale 截断比较语义
 *   - 非法入参（'abc'、scale<0）统一 ValueError（PHP 8 bcmath 原生拒绝）
 *
 * 语义基线：PHP 8.3.7 bcmath 对超 scale 位数一律向零截断（非舍入），
 * round() 依赖「加半进位 + 截断」实现 round-half-away-from-zero，与本文件断言绑定。
 */
class MoneyTest extends TestCase
{
    // ── add / sub：浮点盲区精确值 ──

    #[Test] public function add_sub_exact_where_float_would_drift(): void
    {
        $this->assertSame('0.3000', Money::add('0.1', '0.2'));   // float: 0.30000000000000004
        $this->assertSame('0.2000', Money::sub('0.3', '0.1'));   // float: 0.19999999999999998
        $this->assertSame('0.0000', Money::add('0.00004', '-0.00004'));
        $this->assertSame('-1.0000', Money::sub('0', '1'));
    }

    #[Test] public function add_accepts_int_and_float_inputs(): void
    {
        $this->assertSame('3.0000', Money::add(1, 2));
        $this->assertSame('0.3000', Money::add(0.1, 0.2));       // (string) 转换后仍是精确十进制
        $this->assertSame('19.0000', Money::sub(19.99, 0.99));
        $this->assertSame('0.3000', Money::sub(0.5, 0.2));
    }

    #[Test] public function add_scale0_truncates_toward_zero(): void
    {
        $this->assertSame('2', Money::add('1.4', '1.4', 0));
        $this->assertSame('-2', Money::add('-1.4', '-1.4', 0));
    }

    #[Test] public function add_survives_beyond_float_2_53(): void
    {
        // 2^53+1：float 会坍缩回 9007199254740992，bcmath 保真
        $this->assertSame('9007199254740994.0000', Money::add('9007199254740993', '1'));
        $this->assertSame('9007199254740992.0000', Money::sub('9007199254740993', '1'));
    }

    // ── mul：浮点盲区与大数 ──

    #[Test] public function mul_exact_where_float_would_drift(): void
    {
        $this->assertSame('0.0200', Money::mul('0.1', '0.2'));   // float: 0.020000000000000004
        $this->assertSame('1.2100', Money::mul('1.1', '1.1'));   // float: 1.2100000000000002
        $this->assertSame('0.2500', Money::mul('-0.5', '-0.5'));
        $this->assertSame('-0.2500', Money::mul('0.5', '-0.5'));
    }

    #[Test] public function mul_big_number_exact(): void
    {
        $this->assertSame('19999999999999998.0000', Money::mul('9999999999999999', '2'));
        $this->assertSame('370370367037037034.0000', Money::mul('123456789012345678', '3'));
    }

    // ── div：scale 截断 + 除 0 守卫 ──

    #[Test] public function div_truncates_at_scale_not_rounds(): void
    {
        $this->assertSame('0.3333', Money::div('1', '3'));
        $this->assertSame('0.66', Money::div('2', '3', 2));      // 0.666… → '0.66' 截断
        $this->assertSame('2.5000', Money::div('10', '4'));
        $this->assertSame('0.5000', Money::div('-1', '-2'));
    }

    #[Test] public function div_zero_throws(): void
    {
        foreach (['0', '0.0', '0.00', '-0'] as $zero) {
            try {
                Money::div('1', $zero);
                $this->fail("div('1', '$zero') 应抛 InvalidArgumentException");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('除数为 0', $e->getMessage());
            }
        }
    }

    // ── round：半进位远离零 ──

    #[Test] public function round_half_up_away_from_zero(): void
    {
        $this->assertSame('1.01', Money::round('1.005', 2));
        $this->assertSame('-1.01', Money::round('-1.005', 2));
        $this->assertSame('1.00', Money::round('1.0049', 2));    // 非半分不进位
        $this->assertSame('-1.00', Money::round('-1.0049', 2));
        $this->assertSame('1.00', Money::round('0.995', 2));     // 进位链跨整数位
        $this->assertSame('0.00', Money::round('0.004', 2));
    }

    #[Test] public function round_scale1_carries_into_whole_part(): void
    {
        $this->assertSame('2.0', Money::round('1.999', 1));
        $this->assertSame('10.0', Money::round('9.999', 1));     // 跨十位进位
        $this->assertSame('-2.0', Money::round('-1.999', 1));
    }

    #[Test] public function round_scale0_half_away(): void
    {
        $this->assertSame('1', Money::round('1.4', 0));
        $this->assertSame('-1', Money::round('-1.4', 0));
        $this->assertSame('2', Money::round('1.5', 0));
        $this->assertSame('-2', Money::round('-1.5', 0));
        $this->assertSame('1', Money::round('0.5', 0));
    }

    // ── toFen：元→分（round 到分后 ×100）──

    #[Test] public function toFen_rounds_half_cent_before_scaling(): void
    {
        $this->assertSame(1, Money::toFen('0.005'));
        $this->assertSame(0, Money::toFen('0.0049'));
        $this->assertSame(-1, Money::toFen('-0.005'));
        $this->assertSame(1235, Money::toFen('12.345'));
        $this->assertSame(1999, Money::toFen('19.99'));
        $this->assertSame(0, Money::toFen('0'));
        $this->assertSame(-1, Money::toFen('-0.01'));
    }

    #[Test] public function toFen_escapes_float_100x_trap(): void
    {
        // 旧写法 (int) round(0.075 * 100)：0.075 二进制为 0.07499999…，×100=7.4999… → 7
        // bcmath 精确十进制半进位：0.075 → 0.08 → 8 分
        $this->assertSame(8, Money::toFen('0.075'));
        $this->assertSame(8, Money::toFen(0.075));                // float 入参经 (string) 仍为 '0.075'
        $this->assertSame(2000, Money::toFen(20));
    }

    // ── cmp：bccomp 语义 ──

    #[Test] public function cmp_equal_zero_and_ordering(): void
    {
        $this->assertSame(0, Money::cmp('1.01', '1.01'));
        $this->assertSame(-1, Money::cmp('0.1', '0.2'));
        $this->assertSame(1, Money::cmp('500.01', '500.00'));
        $this->assertSame(-1, Money::cmp('499.99', '500.00'));
        $this->assertSame(1, Money::cmp('1.01', '1.00', 2));
    }

    #[Test] public function cmp_compares_at_scale_truncation(): void
    {
        // scale=2 时第 3 位小数不进比较：1.005 与 1.004 视为相等（值与审核阈值等 2dp 域比较自洽）
        $this->assertSame(0, Money::cmp('1.005', '1.004', 2));
        $this->assertSame(0, Money::cmp('1.004', '1.005', 2));
        $this->assertSame(-1, Money::cmp('1.005', '1.006', 3));   // scale=3 才见第 3 位
    }

    // ── 非法入参：PHP 8 bcmath 原生 ValueError ──

    #[Test] public function invalid_inputs_throw_value_error(): void
    {
        $cases = [
            'add 非法字符串'   => fn () => Money::add('abc', '1'),
            'round 非法字符串' => fn () => Money::round('abc'),
            'div 非法被除数'   => fn () => Money::div('abc', '2'),
            'toFen 非法入参'   => fn () => Money::toFen('abc'),
            'scale 越界'       => fn () => Money::add('1', '2', -1),
        ];
        foreach ($cases as $name => $fn) {
            try {
                $fn();
                $this->fail("$name 应抛 ValueError");
            } catch (\ValueError $e) {
                $this->assertTrue(true);
            }
        }
    }
}
