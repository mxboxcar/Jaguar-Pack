<?php

namespace App\Domain\Tracking;

/**
 * พฤติกรรม PHP 7.4 ที่ไฟล์เดิมของกลุ่ม tracking พึ่งพาโดยไม่ตั้งใจ (CONVENTIONS ข้อ 13)
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

    /**
     * round($v, $places) ของ PHP 7.4 (_php_math_round: pre-round ที่ 15 หลักสำคัญ แล้ว PHP_ROUND_HALF_UP)
     * 8.4 เลิก pre-rounding → ต่างที่หลักสุดท้ายได้ (เช่น round(1.69 / 5.2, 2): 7.4 = 0.33, 8.4 = 0.32)
     */
    public static function round(int|float $v, int $places = 0): float
    {
        $value = (float) $v;
        if (! is_finite($value) || $value == 0.0) {
            return $value;
        }
        $pow = fn (int $p): float => $p > 22 ? 10.0 ** $p : (float) (10 ** $p);
        $basic = fn (float $x, int $p): float => $p >= 0 ? $x * $pow($p) : $x / $pow(-$p);
        $helper = fn (float $x): float => $x >= 0.0 ? floor($x + 0.5) : ceil($x - 0.5);
        $precisionPlaces = 14 - (int) floor(log10(abs($value)));
        $f1 = $pow(abs($places));
        if ($precisionPlaces > $places && $precisionPlaces - 15 < $places) {
            $use = max($precisionPlaces, -60);
            $tmp = $helper($basic($value, $use));
            $tmp = $tmp / $pow(abs(max(-60, $places - $use)));
        } else {
            $tmp = $places >= 0 ? $value * $f1 : $value / $f1;
            if (abs($tmp) >= 1e15) {
                return $value;
            }
        }
        if (abs($tmp - $helper($tmp)) >= 1e-15) {
            $tmp = $helper($tmp);
        }

        return $places > 0 ? $tmp / $f1 : $tmp * $f1;
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
}
