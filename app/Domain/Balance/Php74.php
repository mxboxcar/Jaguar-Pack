<?php

namespace App\Domain\Balance;

/**
 * พฤติกรรม PHP 7.4 ที่ไฟล์เดิมของกลุ่ม balance พึ่งพาโดยไม่ตั้งใจ (CONVENTIONS ข้อ 13)
 *
 *  eq()  = `==` ของ 7.4   (เช่น $txtSearch[1] == $row['id'])
 *  cmp() = `<`, `>`, `<=` ของ 7.4 (เช่น $wantcash > getCurrentCash(), $amount > 0)
 *  num() = ค่าที่ 7.4 ใช้ตอนเอา string ไปคำนวณ ("" → 0, "12abc" → 12) — 8.4 โยน TypeError / เทียบเป็น string
 */
final class Php74
{
    /** string ที่ 7.4 นับว่าเป็นตัวเลข (ไม่ยอมช่องว่างท้าย ต่างจาก 8.x) */
    public static function isNumeric(mixed $v): bool
    {
        if (is_int($v) || is_float($v)) {
            return true;
        }

        return is_string($v) && is_numeric($v) && ! preg_match('/\s$/', $v);
    }

    /** ค่าตัวเลขแบบที่ 7.4 ใช้ในนิพจน์คำนวณ */
    public static function num(mixed $v): int|float
    {
        if (is_int($v) || is_float($v)) {
            return $v;
        }
        if ($v === null || is_bool($v)) {
            return (int) $v;
        }
        if (! preg_match('/^[ \t\n\r\v\f]*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?/', (string) $v, $m)) {
            return 0;
        }
        $t = ltrim($m[0], " \t\n\r\v\f");
        if (preg_match('/^[+-]?\d+$/', $t)) {
            $i = (int) $t;

            return (string) $i === ltrim($t, '+') ? $i : (float) $t;
        }

        return (float) $t;
    }

    /**
     * การเทียบของ 7.4 คืน -1/0/1 (scalar/null เท่านั้น)
     * string กับ string: ตัวเลขทั้งคู่ → เทียบตัวเลข ไม่งั้นเทียบ string; มีฝั่งใดเป็นตัวเลข → แปลงอีกฝั่งเป็นตัวเลข
     */
    public static function cmp(mixed $a, mixed $b): int
    {
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a <=> (bool) $b;
        }
        if ($a === null && is_string($b)) {
            $a = '';
        }
        if ($b === null && is_string($a)) {
            $b = '';
        }
        if ($a === null || $b === null) {
            return (bool) $a <=> (bool) $b;
        }
        if (is_string($a) && is_string($b)) {
            if (self::isNumeric($a) && self::isNumeric($b)) {
                return (float) $a <=> (float) $b;
            }

            return strcmp($a, $b) <=> 0;
        }

        return self::num($a) <=> self::num($b);
    }

    public static function eq(mixed $a, mixed $b): bool
    {
        return self::cmp($a, $b) === 0;
    }

    /** echo ของตัวเลขแบบ 7.4 (precision=14) */
    public static function str(int|float $v): string
    {
        if (is_int($v)) {
            return (string) $v;
        }
        $old = ini_get('precision');
        ini_set('precision', '14');
        $s = (string) $v;
        ini_set('precision', (string) $old);

        return $s;
    }

    /** MakePaging(): "LIMIT (10*page-10) , 10" — ค่าที่ทำให้ SQL เดิมพัง (ติดลบ/ทศนิยม) = null (เดิมไม่มีแถว) */
    public static function offset(mixed $page, int $perPage = 10): ?int
    {
        $start = ($perPage * self::num($page)) - $perPage;
        if ($start < 0 || (is_float($start) && floor($start) != $start)) {
            return null;
        }

        return (int) $start;
    }
}
