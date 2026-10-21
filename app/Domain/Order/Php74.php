<?php

namespace App\Domain\Order;

/**
 * พฤติกรรม PHP 7.4 ที่ไฟล์เดิมของกลุ่ม order (สำเนาจาก mod/tracking — ถ้ารวม branch แล้วย้ายไปใช้ที่เดียวกันได้) พึ่งพาโดยไม่ตั้งใจ (CONVENTIONS ข้อ 13)
 *
 *  eq()  = `==` ของ 7.4 (ตัวเลขเทียบกับ string ที่ไม่ใช่ตัวเลข → แปลง string เป็นตัวเลข: 0 == "abc" เป็น true)
 *  num() = ค่าที่ 7.4 ใช้ตอนเอา string ไปคำนวณ ("" → 0, "12abc" → 12, "1.5" → 1.5) — 8.4 โยน TypeError
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

    /** `$a == $b` แบบ PHP 7.4 สำหรับค่า scalar/null */
    public static function eq(mixed $a, mixed $b): bool
    {
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }
        if ($a === null || $b === null) {
            if ($a === null && $b === null) {
                return true;
            }
            $o = $a === null ? $b : $a;

            return is_string($o) ? $o === '' : ! $o;
        }
        $aNum = is_int($a) || is_float($a);
        $bNum = is_int($b) || is_float($b);
        if ($aNum && $bNum) {
            return $a == $b;
        }
        if ($aNum || $bNum) {
            // ตัวเลข vs string: 7.4 แปลง string เป็นตัวเลขเสมอ
            [$n, $s] = $aNum ? [$a, (string) $b] : [$b, (string) $a];

            return $n == self::num($s);
        }
        $a = (string) $a;
        $b = (string) $b;
        if (self::isNumeric($a) && self::isNumeric($b)) {
            return (float) $a == (float) $b;
        }

        return $a === $b;
    }

    public static function neq(mixed $a, mixed $b): bool
    {
        return ! self::eq($a, $b);
    }

    /** `$a > $b` แบบ 7.4 (ใช้ใน CombineTrack: amount > '') */
    public static function gt(mixed $a, mixed $b): bool
    {
        if ($a === null) {
            $a = '';
        }
        if ($b === null) {
            $b = '';
        }
        if (is_string($a) && is_string($b) && ! (self::isNumeric($a) && self::isNumeric($b))) {
            return strcmp($a, $b) > 0;
        }

        return self::num($a) > self::num($b);
    }

    /** ค่าตัวเลขแบบที่ 7.4 ใช้ในนิพจน์คำนวณ (int ถ้าเป็นจำนวนเต็มเหมือนเดิม) */
    public static function num(mixed $v): int|float
    {
        if (is_int($v) || is_float($v)) {
            return $v;
        }
        if ($v === null || is_bool($v)) {
            return (int) $v;
        }
        $s = (string) $v;
        if (! preg_match('/^\s*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?/', $s, $m)) {
            return 0;
        }
        $t = trim($m[0]);
        if (preg_match('/^[+-]?\d+$/', $t)) {
            $i = (int) $t;
            // เกินช่วง int → float เหมือนเดิม
            return (string) $i === ltrim($t, '+') ? $i : (float) $t;
        }

        return (float) $t;
    }

    /**
     * round($v, $places) ของ PHP 7.4 (_php_math_round แบบมี "pre-rounding", PHP_ROUND_HALF_UP)
     * — PHP 8.4 เลิก pre-rounding: round(2234.385, 2) = 2234.39 (7.4) แต่ 2234.38 (8.4)
     * (พอร์ตจาก _php_math_round ของ PHP 7.4 — เดิมเคยมีสำเนาใน Reports\Receipt ซึ่ง PACK ตัดออกแล้ว)
     */
    public static function round(mixed $v, int $places = 0): float
    {
        $value = (float) self::num($v);
        if (! is_finite($value) || $value == 0.0) {
            return $value;
        }
        $pow = fn (int $p): float => $p < 0 || $p > 22 ? 10.0 ** $p : (float) (10 ** $p);
        $helper = fn (float $x): float => $x >= 0.0 ? floor($x + 0.5) : ceil($x - 0.5);
        $precisionPlaces = 14 - (int) floor(log10(abs($value)));
        $f1 = $pow(abs($places));
        if ($precisionPlaces > $places && $precisionPlaces - 15 < $places) {
            $use = max($precisionPlaces, -60);
            $tmp = $helper($use >= 0 ? $value * $pow(abs($use)) : $value / $pow(abs($use)));
            $use = max(-60, $places - $use);
            $tmp = $tmp / $pow(abs($use));
        } else {
            $tmp = $places >= 0 ? $value * $f1 : $value / $f1;
            if (abs($tmp) >= 1e15) {
                return $value;
            }
        }
        $tmp = $helper($tmp);

        return $places > 0 ? $tmp / $f1 : $tmp * $f1;
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

    /** id จาก POST ที่ไฟล์เดิมฝังลง `id IN (...)` ตรงๆ: คืน list ของ int หรือ null ถ้าเดิมจะเป็น SQL error */
    public static function idList(string $csv): ?array
    {
        $parts = explode(',', $csv);
        $ids = [];
        foreach ($parts as $p) {
            $p = trim($p);
            // เดิมค่าที่ส่งมาอาจมี '' ครอบ (save_combine สร้าง ,'1','2')
            if (preg_match("/^'(-?\d+)'$/", $p, $m)) {
                $p = $m[1];
            }
            if (! preg_match('/^-?\d+$/', $p)) {
                return null;
            }
            $ids[] = (int) $p;
        }

        return $ids;
    }

    /**
     * แถวแบบ mysqli_fetch_array เดิม: ทุกค่าเป็น string (หรือ null) — PDO คืน int เป็น int ทำให้ `==` แบบ 7.4 ต่างไป
     * (เช่น tracking.no = 0: เดิม "0" == '' เป็นเท็จ แต่ 0 == '' เป็นจริง)
     *
     * @return array<string, string|null>
     */
    public static function row(object|array|null $row): array
    {
        $out = [];
        foreach ((array) ($row ?? []) as $k => $v) {
            $out[$k] = ($v === null || is_string($v)) ? $v : (is_bool($v) ? ($v ? '1' : '') : (string) $v);
        }

        return $out;
    }
}
