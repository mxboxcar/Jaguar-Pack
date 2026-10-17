<?php

namespace Tests\Unit\Lot;

use App\Domain\Lot\Php74;
use PHPUnit\Framework\TestCase;

/** พฤติกรรม PHP 7.4 ที่ไฟล์เดิมของ lot พึ่งพา + การกัน SQL injection ของรายการ id */
class Php74Test extends TestCase
{
    public function test_loose_equality_matches_php74(): void
    {
        $this->assertTrue(Php74::eq(0, 'abc'));    // 7.4: true (8.x: false)
        $this->assertTrue(Php74::eq('', 0));
        $this->assertTrue(Php74::eq(null, ''));
        $this->assertTrue(Php74::eq('1', 1));
        $this->assertTrue(Php74::eq('1.0', '1'));
        $this->assertFalse(Php74::eq('1', 2));
        $this->assertFalse(Php74::eq('abc', 'abd'));
        $this->assertTrue(Php74::neq('137', null));
    }

    public function test_numeric_value_matches_php74_arithmetic(): void
    {
        $this->assertSame(0, Php74::num(''));
        $this->assertSame(0, Php74::num(null));
        $this->assertSame(12, Php74::num('12abc'));
        $this->assertSame(1.5, Php74::num('1.5'));
        $this->assertSame(0, Php74::num('问题件'));
        // save_new_lot: ชื่อล็อตล่าสุด + 1
        $this->assertSame('1', Php74::str(Php74::num('เรือ AC') + 1));
        $this->assertSame('1001', Php74::str(Php74::num('1000') + 1));
    }

    public function test_id_list_accepts_only_integers(): void
    {
        $this->assertSame([1, 2, 3], Php74::idList('1,2,3'));
        $this->assertSame([5], Php74::idList("'5'"));
        $this->assertSame([7], Php74::idList(' 7 '));
    }

    /** payload ที่เคยล้าง tracking.lot_id ทั้งตาราง (save_remove_tracking เดิม) ต้องไม่ผ่าน */
    public function test_id_list_rejects_injection_payloads(): void
    {
        foreach (['910719) OR (1=1', '1 OR 1=1', '1,2) OR (1', "1' OR '1'='1", '1;DROP TABLE lot', '', ',', '1,', '0x10', '1e3', 'Array'] as $p) {
            $this->assertNull(Php74::idList($p), $p);
        }
    }
}
