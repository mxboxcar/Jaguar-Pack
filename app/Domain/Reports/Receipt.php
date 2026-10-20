<?php

namespace App\Domain\Reports;

/**
 * SR: ใบเสร็จรับเงิน/ใบกำกับภาษี (_admin/_export/receipt*.php, calvat.php, print_receipt.php, _admin/_report/printtax.php,
 * grid.php/else.php type 97) — สูตรคำนวณภาษีและ ThaiBath()/ReadNumber() ที่ไฟล์เดิมคัดลอกไว้ในแต่ละไฟล์ (เหมือนกันทุกไฟล์)
 *
 * ทุกสูตรทำงานกับค่าแบบ PHP 7.4 (string จาก mysqli/POST, `"x" * 7` = ตัวเลขนำหน้า) และใช้ round()/number_format() แบบ 7.4
 * (PHP 8.4 เลิก "pre-rounding" ของ round() → ผลต่างกันได้ที่หลักสุดท้าย เช่น round(1.005, 2): 7.4 = 1.01, 8.4 = 1.0)
 */
final class Receipt
{
    private const POSITION = ['แสน', 'หมื่น', 'พัน', 'ร้อย', 'สิบ', ''];

    private const NUMBER = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];

    private static function n(mixed $v): int|float
    {
        return Php74::num($v);
    }

    /** round($v, $places) ของ PHP 7.4 (_php_math_round แบบมี pre-rounding, PHP_ROUND_HALF_UP) */
    public static function round(mixed $v, int $places = 0): float
    {
        $value = (float) self::n($v);
        if (! is_finite($value) || $value == 0.0) {
            return $value;
        }
        $precisionPlaces = 14 - (int) floor(log10(abs($value)));
        $f1 = self::pow10(abs($places));
        if ($precisionPlaces > $places && $precisionPlaces - 15 < $places) {
            $use = max($precisionPlaces, -60);
            $tmp = self::helper(self::basic($value, $use));
            $use = max(-60, $places - $use);
            $tmp = $tmp / self::pow10(abs($use));
        } else {
            $tmp = $places >= 0 ? $value * $f1 : $value / $f1;
            if (abs($tmp) >= 1e15) {
                return $value;
            }
        }
        $tmp = self::helper($tmp);
        $tmp = $places > 0 ? $tmp / $f1 : $tmp * $f1;

        return $tmp;
    }

    private static function pow10(int $p): float
    {
        return $p < 0 || $p > 22 ? 10.0 ** $p : (float) (10 ** $p);
    }

    private static function basic(float $v, int $places): float
    {
        $f = self::pow10(abs($places));

        return $places >= 0 ? $v * $f : $v / $f;
    }

    private static function helper(float $v): float
    {
        return $v >= 0.0 ? floor($v + 0.5) : ceil($v - 0.5);
    }

    /**
     * number_format($v, $dec, '.', $sep) ของ PHP 7.4 — ค่าที่ไม่ใช่ตัวเลข = null (เดิม warning แล้วคืน NULL),
     * "12abc" = 12 (notice), ปัดด้วย round() แบบ 7.4
     */
    public static function numberFormat(mixed $v, int $dec, string $sep = ','): ?string
    {
        if (is_string($v) && ! is_numeric(trim($v)) && preg_match('/^\s*[+-]?(\d|\.\d)/', $v) !== 1) {
            return null;
        }
        $f = self::round($v, $dec);

        return number_format($f, $dec, '.', $sep);
    }

    /** ThaiBath() เดิม — จำนวนเงินเป็นคำอ่านภาษาไทย */
    public static function thaiBaht(mixed $amount): string
    {
        $amount = self::numberFormat($amount, 2, '');
        $pt = $amount === null ? false : strpos($amount, '.');
        $number = $fraction = '';
        if ($pt === false) {
            $number = (string) $amount;
        } else {
            $number = substr($amount, 0, $pt);
            $fraction = substr($amount, $pt + 1);
        }
        $ret = '';
        $baht = self::readNumber($number);
        if ($baht !== '') {
            $ret .= $baht.'บาท';
        }
        $satang = self::readNumber($fraction);
        if ($satang !== '') {
            $ret .= $satang.'สตางค์';
        } else {
            $ret .= 'ถ้วน';
        }

        return $ret;
    }

    /** ReadNumber() เดิม (รวมจุดบกพร่องเดิม: 1,000,000 พอดี = "แสน") */
    public static function readNumber(mixed $number): string
    {
        $number = self::n($number);
        $ret = '';
        if ($number == 0) {
            return $ret;
        }
        if ($number > 1000000) {
            $ret .= self::readNumber(intval($number / 1000000)).'ล้าน';
            $number = intval(fmod($number, 1000000));
        }
        $divider = 100000;
        $pos = 0;
        while ($number > 0) {
            $d = intval($number / $divider);
            $ret .= ($divider == 10 && $d == 2) ? 'ยี่'
                : (($divider == 10 && $d == 1) ? ''
                : (($divider == 1 && $d == 1 && $ret !== '') ? 'เอ็ด' : (self::NUMBER[$d] ?? '')));
            $ret .= ($d ? (self::POSITION[$pos] ?? '') : '');
            $number = (int) $number % (int) $divider;
            $divider = $divider / 10;
            $pos++;
        }

        return $ret;
    }

    /**
     * ตารางในฟอร์มใบเสร็จ (receipt_1.php = บวก VAT 7%, receipt_2.php = รวม VAT แล้ว)
     * เดิม: total = (receipt_total == "0" ? $a : receipt_total) โดย $a ยังไม่ถูกกำหนด → ยอด 0.00 = ช่องว่าง/ศูนย์
     *
     * @return array{input: mixed, c: mixed, d: mixed, e: mixed}
     */
    public static function form(object $bill, bool $addVat): array
    {
        $rt = $bill->receipt_total ?? null;
        $total = Php74::eq($rt, '0') ? null : $rt;
        if ($addVat) {
            $c = $total;
            $d = self::numberFormat((self::n($total) * 7) / 100, 2, '');
            $e = self::n($total) + self::n($d);
        } else {
            $vats = self::numberFormat((self::n($total) * 7) / 107, 2, '');
            $c = self::n($total) - self::n($vats);
            $d = $vats;
            $e = $total;
        }

        // ช่อง input = (receipt_total == "0" ? $a : receipt_total) — $a ณ ตอนแสดง: receipt_1 = total (null), receipt_2 = total - vats (0)
        $input = Php74::eq($rt, '0') ? ($addVat ? $total : $c) : $rt;

        return ['input' => Rows::s($input), 'c' => $c, 'd' => $d, 'e' => $e];
    }

    /**
     * calvat.php — ผลเป็น JSON แบบเดิม: urldecode(json_encode([...]))
     * type 1 = บวก VAT, อื่นๆ = รวม VAT แล้ว (ใช้ numberformatforsubmit = ตัดทศนิยม 2 ตำแหน่ง)
     */
    public static function calvat(mixed $type, mixed $postTotal): string
    {
        $total = \App\Support\Legacy\Format::numberformatforsubmit($postTotal);
        if (Php74::eq($type, 1)) {
            $a = $total;
            $b = self::n($total) + ((self::n($total) * 7) / 100);
            $c = $total;
            $d = self::numberFormat((self::n($total) * 7) / 100, 2, '');
            $e = self::n($total) + self::n($d);
        } else {
            $sub = fn ($v) => \App\Support\Legacy\Format::numberformatforsubmit($v);
            $vats = $sub((self::n($total) * 7) / 107);
            $vats2 = self::n($sub(self::n($sub($total)) * 7)) / 107;
            $a = self::n($total) - self::n($vats);
            $b = $total;
            $c = self::n($total) - self::n($vats);
            $d = $vats2;
            $e = $total;
        }

        return urldecode(self::json(['a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e, 'th' => self::thaiBaht($e)]));
    }

    /** json_encode ของ PHP 7.4 (serialize_precision -1, escape / และ unicode) */
    private static function json(array $v): string
    {
        return (string) json_encode($v);
    }

    /**
     * print_receipt.php — ยอดในใบเสร็จที่พิมพ์ (ใช้ round() 2 ตำแหน่ง)
     *
     * @return array{a: float, b: float, c: float, d: float, e: float}
     */
    public static function printed(object $bill): array
    {
        $t = $bill->receipt_total ?? null;
        if (Php74::eq($bill->receipt_is_vat ?? null, 1)) {
            $a = self::round($t, 2);
            $b = self::round(self::n($t) + (self::n($t) * 7) / 100, 2);
            $d = self::round((self::n($t) * 7) / 100, 2);

            return ['a' => $a, 'b' => $b, 'c' => $a, 'd' => $d, 'e' => $b];
        }
        $vats = (self::n($t) * 7) / 107;
        $a = self::round(self::n($t) - $vats, 2);
        $b = self::round($t, 2);

        return ['a' => $a, 'b' => $b, 'c' => $a, 'd' => self::round($vats, 2), 'e' => $b];
    }

    /**
     * แถวรายงานภาษีขาย (printtax.php, grid.php / else.php type 97): [e = มูลค่า, d = ภาษี, total]
     * receipt_is_vat = 1: ภาษี = 7% ของยอด (ปรับ ±0.01 เมื่อผลรวมลงท้าย .01/.99 แบบเดิม)
     * อื่นๆ: ภาษี = number_format(ยอด*7/107, 2) — มี comma คั่นหลักพัน (ยอด ≥ 1,000 บาท → "1,234.56" แล้วคำนวณต่อได้ 1 แบบเดิม)
     *
     * @return array{e: mixed, d: mixed, total: int|float}
     */
    public static function taxRow(object $row): array
    {
        $rt = $row->receipt_total ?? null;
        if (Php74::eq($row->receipt_is_vat ?? null, 1)) {
            $d = self::numberFormat((self::n($rt) * 7) / 100, 2, '');
            $e = $rt;
            $total = self::n($d) + self::n($e);
            if (str_contains(Php74::str($total), '.01')) {
                $d = self::n($d) + 0.01;
                $total = $total - 0.01;
            }
            if (str_contains(Php74::str($total), '.99')) {
                $d = self::n($d) - 0.01;
                $total = $total + 0.01;
            }

            return ['e' => $e, 'd' => $d, 'total' => $total];
        }
        $d = self::numberFormat((self::n($rt) * 7) / 107, 2, ',');

        return ['e' => $rt, 'd' => $d, 'total' => self::n($d) + self::n($rt)];
    }
}
