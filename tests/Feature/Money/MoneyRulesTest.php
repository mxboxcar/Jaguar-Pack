<?php

namespace Tests\Feature\Money;

use App\Domain\Money\Money;
use App\Domain\Order\TaobaoRefund;
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

    public function test_taobao_refund_uses_the_currency_actually_paid(): void
    {
        // จ่ายหยวนทั้งหมด (ใบถูกตัดหยวน 150 บาท 20 = ค่าบริการ 2 ออเดอร์)
        $this->assertSame(['cny' => 100.0, 'thb' => 0.0, 'charge' => 10.0], TaobaoRefund::forCancel(100, 10, 5, -150, -20));
        // จ่ายบาททั้งหมด: ราคา × rate ของใบ + ค่าบริการ
        $this->assertSame(['cny' => 0.0, 'thb' => 500.0, 'charge' => 10.0], TaobaoRefund::forCancel(100, 10, 5, 0, -1020));
        // ผสม: หยวนค้าง 20 → หยวน 20 + บาท (30 × 5)
        $this->assertSame(['cny' => 20.0, 'thb' => 150.0, 'charge' => 10.0], TaobaoRefund::forCancel(50, 10, 5, -20, -170));
        // ไม่คืนเกินที่ใบนี้ยังถูกตัดอยู่ (เช่น เว็บเดิมคืนไปแล้วบางส่วน)
        $this->assertSame(['cny' => 0.0, 'thb' => 5.0, 'charge' => 10.0], TaobaoRefund::forCancel(50, 10, 5, 10, -15));
        $this->assertSame(['cny' => 0.0, 'thb' => 0.0, 'charge' => 0.0], TaobaoRefund::forCancel(50, 10, 5, 0, 0));
        // ราคา/ค่าบริการติดลบจากข้อมูลเก่า = ไม่คืนติดลบ (ไม่หักเงินตอนยกเลิก)
        $this->assertSame(['cny' => 0.0, 'thb' => 0.0, 'charge' => 0.0], TaobaoRefund::forCancel(-50, -10, 5, -100, -100));
    }

    public function test_admin_amount_accepts_only_plain_money(): void
    {
        foreach (['1000' => '1000', '1,000' => '1000', ' 500' => '500', '+25' => '25', '-20.5' => '-20.5', '-1,000.50' => '-1000.50',
            '0' => '0', '999999999999999999.99' => '999999999999999999.99'] as $in => $out) {
            $this->assertSame($out, Money::adminAmount((string) $in), (string) $in);
        }
        // เดิม MySQL non-strict: "abc"/"" → 0.00, "1,00" → 1.00, ยาวเกิน → 999999999999999999.99
        foreach (['', 'abc', '1,00', '1.234', '1e3', '--5', '99999999999999999999', '1 000', '.5', '0x10', '5-'] as $bad) {
            $this->assertNull(Money::adminAmount($bad), $bad);
        }
    }

    public function test_refuse_is_legacy_error_box(): void
    {
        $this->assertSame("ErrorBox('x', 'CloseLoading()');", Money::refuse('x'));
    }
}
