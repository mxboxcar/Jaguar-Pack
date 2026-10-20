<?php

namespace Tests\Feature\Reports;

use App\Domain\Reports\Receipt;
use Tests\TestCase;

/**
 * SR: สูตรใบเสร็จ/ภาษีขาย (App\Domain\Reports\Receipt) — ค่าที่คาดหวังได้จากเว็บเดิม (PHP 7.4, :8111) ด้วยข้อมูลเดียวกัน (ไม่แตะ DB)
 */
class ReceiptCalcTest extends TestCase
{
    /** calvat.php เดิม: urldecode(json_encode([...])) — byte ต่อ byte กับ :8111 */
    public function test_calvat_matches_legacy_json(): void
    {
        $this->assertSame('{"a":"1234.56","b":1320.9792,"c":"1234.56","d":"86.42","e":1320.98,"th":"'.$this->u('หนึ่งพันสามร้อยยี่สิบบาทเก้าสิบแปดสตางค์').'"}',
            Receipt::calvat('1', '1234.56'));
        $this->assertSame('{"a":"1234.50","b":1320.915,"c":"1234.50","d":"86.42","e":1320.92,"th":"'.$this->u('หนึ่งพันสามร้อยยี่สิบบาทเก้าสิบสองสตางค์').'"}',
            Receipt::calvat('1', '1,234.5'));
        $this->assertSame('{"a":"0.01","b":0.010700000000000001,"c":"0.01","d":"0.00","e":0.01,"th":"'.$this->u('หนึ่งสตางค์').'"}',
            Receipt::calvat(1, '0.01'));
        $this->assertSame('{"a":1153.8,"b":"1234.56","c":1153.8,"d":80.76560747663551,"e":"1234.56","th":"'.$this->u('หนึ่งพันสองร้อยสามสิบสี่บาทห้าสิบหกสตางค์').'"}',
            Receipt::calvat('2', '1234.56'));
        $this->assertSame('{"a":0.01,"b":"0.01","c":0.01,"d":0.0006542056074766356,"e":"0.01","th":"'.$this->u('หนึ่งสตางค์').'"}',
            Receipt::calvat('2', '0.01'));
    }

    public function test_thai_baht_words_like_legacy_read_number(): void
    {
        $this->assertSame('หนึ่งร้อยเจ็ดบาทถ้วน', Receipt::thaiBaht(107));
        $this->assertSame('ยี่สิบเอ็ดบาทสิบเอ็ดสตางค์', Receipt::thaiBaht('21.11'));
        $this->assertSame('สองล้านเอ็ดบาทเจ็ดสตางค์', Receipt::thaiBaht(2000001.07));
        // จุดบกพร่องเดิม: 1,000,000 พอดีไม่เข้าเงื่อนไข > 1000000 → "แสน"
        $this->assertSame('แสนบาทถ้วน', Receipt::thaiBaht(1000000));
        $this->assertSame('ถ้วน', Receipt::thaiBaht(null));
        $this->assertSame('ถ้วน', Receipt::thaiBaht('abc'));
    }

    /** round()/number_format() แบบ PHP 7.4 (มี pre-rounding) */
    public function test_round_and_number_format_like_php74(): void
    {
        $this->assertSame(1.01, Receipt::round(1.005, 2));
        $this->assertSame(2.68, Receipt::round(2.675, 2));
        $this->assertSame(-1.01, Receipt::round(-1.005, 2));
        $this->assertSame(20675.28, Receipt::round(20675.27654205607, 2));
        $this->assertSame('1,307.83', Receipt::numberFormat(1307.8282, 2));
        $this->assertSame('12.00', Receipt::numberFormat('12abc', 2, ''));
        $this->assertNull(Receipt::numberFormat('abc', 2));
    }

    /** แถวรายงานภาษีขาย (printtax / grid 97): รวม VAT แล้วใช้ number_format มี comma (≥ 1,000 → คำนวณต่อได้ 1 แบบเดิม) */
    public function test_tax_rows(): void
    {
        $vat = Receipt::taxRow((object) ['receipt_is_vat' => '1', 'receipt_total' => '100.13']);
        $this->assertSame('100.13', $vat['e']);
        $this->assertSame('7.01', $vat['d']);
        $incl = Receipt::taxRow((object) ['receipt_is_vat' => '0', 'receipt_total' => '20000.07']);
        $this->assertSame('1,308.42', $incl['d']);
        $this->assertSame(20001.07, $incl['total']);
    }

    /** ฟอร์มใบเสร็จ: receipt_total 0.00 = ช่องว่าง (receipt_1) / "0" (receipt_2) แบบเดิม */
    public function test_receipt_form_inputs(): void
    {
        $zero = (object) ['receipt_total' => '0.00'];
        $this->assertSame('', Receipt::form($zero, true)['input']);
        $this->assertSame('0', Receipt::form($zero, false)['input']);
        $set = (object) ['receipt_total' => '1500.15'];
        $this->assertSame('1500.15', Receipt::form($set, true)['input']);
        $this->assertSame('105.01', Receipt::form($set, true)['d']);
        $this->assertSame('98.14', Receipt::form($set, false)['d']);
    }

    private function u(string $s): string
    {
        return trim((string) json_encode($s), '"');
    }
}
