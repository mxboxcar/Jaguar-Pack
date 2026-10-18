<?php

namespace Tests\Feature\Money;

use App\Domain\Money\Money;
use Tests\TestCase;

/**
 * กติกาเงินที่แก้จากเว็บเดิม (รายงานข้อ 9–15) — ส่วนที่ไม่แตะ DB
 */
class MoneyRulesTest extends TestCase
{
    public function test_status_matching_is_numeric_and_exact(): void
    {
        $this->assertTrue(Money::statusIn('1', [Money::PENDING]));
        $this->assertTrue(Money::statusIn(1, ['1', '2']));
        $this->assertTrue(Money::statusIn('-1', [Money::CANCELLED]));
        $this->assertTrue(Money::statusIn('2', [2]));
        $this->assertFalse(Money::statusIn('-1', [Money::PENDING, Money::DONE]));
        $this->assertFalse(Money::statusIn(null, [0]));
        $this->assertFalse(Money::statusIn('', [0]), 'ค่าว่างไม่ใช่สถานะ 0 (เดิม "" == 0 ใน 7.4)');
        $this->assertFalse(Money::statusIn('1.5', [1]));
        $this->assertFalse(Money::statusIn('abc', [0]));
    }

    public function test_refundable_never_exceeds_what_is_still_held(): void
    {
        $this->assertSame(100.0, Money::refundable(100, -100), 'ถูกตัด 100 คืน 100');
        $this->assertSame(0.0, Money::refundable(100, 0), 'คืนไปแล้ว = ไม่คืนซ้ำ');
        $this->assertSame(0.0, Money::refundable(100, 50), 'คืนเกินไปแล้ว = ไม่คืน');
        $this->assertSame(40.0, Money::refundable(100, -40), 'ถูกตัดค้าง 40 คืน 40');
        $this->assertSame(100.0, Money::refundable(100, -250), 'ไม่เกินยอดของรายการ');
        $this->assertSame(900.0, Money::refundable(PHP_FLOAT_MAX, -900));
    }

    public function test_refill_amount_must_be_positive_money(): void
    {
        foreach (['1', '100', '1,000', '1,000.50', '0.01', ' 25.5 '] as $ok) {
            $this->assertTrue(Money::validRefillAmount($ok), $ok);
        }
        foreach (['', '0', '0.00', '-1', '-0.01', 'abc', '5abc', '1e3', '10.123', '.5', '+5', '1 000'] as $bad) {
            $this->assertFalse(Money::validRefillAmount($bad), $bad);
        }
    }

    public function test_money_format_matches_decimal_column(): void
    {
        $this->assertSame('1234.50', Money::fmt(1234.5));
        $this->assertSame('0.10', Money::fmt(0.1 + 0.0000001));
        $this->assertSame('-3.00', Money::fmt(-3));
    }

    public function test_refuse_is_legacy_error_box(): void
    {
        $this->assertSame("ErrorBox('x', 'CloseLoading()');", Money::refuse('x'));
    }
}
