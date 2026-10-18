<?php

namespace App\Domain\Shop;

use App\Support\Legacy\Format;

/**
 * เลขคณิตแบบ PHP 7.4 ที่โค้ดเดิมพึ่งพา (CONVENTIONS ข้อ 13)
 *   - string ที่ไม่ใช่ตัวเลข ("", "abc") ในการคำนวณ = 0 (PHP 8.4 โยน TypeError)
 *   - string ที่ขึ้นต้นด้วยตัวเลข ("12abc") = 12
 *   - (string) ของ float = precision 14
 */
final class Php74
{
    /** ค่าตัวเลขของ $v แบบที่ PHP 7.4 ใช้ในนิพจน์เลขคณิต */
    public static function num(mixed $v): int|float
    {
        if (is_int($v) || is_float($v)) {
            return $v;
        }
        if ($v === null || is_bool($v)) {
            return (int) $v;
        }
        if (is_array($v)) {
            return 0;
        }
        $s = (string) $v;
        if (is_numeric($s)) {
            return $s + 0;
        }
        if (preg_match('/^[ \t\n\r\v\f]*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?/', $s, $m)) {
            return trim($m[0]) + 0;
        }

        return 0;
    }

    /** (string) ของผลคำนวณแบบ PHP 7.4 (ใช้ตอนต่อ string / ส่งลง DB ให้ค่าเท่าเดิม) */
    public static function str(mixed $v): string
    {
        return is_float($v) ? Format::phpFloat($v) : (string) $v;
    }

    /** $a == $b ของ PHP 7.4 ระหว่าง int/string (เช่น 0 == "" เป็น true) */
    public static function looseEq(mixed $a, mixed $b): bool
    {
        if ((is_int($a) || is_float($a)) && is_string($b)) {
            return is_numeric($b) ? $a == $b + 0 : $a == self::num($b);
        }
        if ((is_int($b) || is_float($b)) && is_string($a)) {
            return self::looseEq($b, $a);
        }

        return $a == $b;
    }
}
