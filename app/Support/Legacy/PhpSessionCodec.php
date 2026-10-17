<?php

namespace App\Support\Legacy;

/**
 * อ่าน/เขียนข้อมูล session รูปแบบ serialize_handler = php (ค่าเริ่มต้นของ PHP)
 *   user_id|s:44:"....";user_role|s:44:"....";
 * PHP 8 ไม่มี session_decode() ใช้นอก session จริง จึงต้อง parse เอง
 */
final class PhpSessionCodec
{
    /** @return array<string, mixed> */
    public static function decode(?string $data): array
    {
        $data = (string) $data;
        $out = [];
        $pos = 0;
        $len = strlen($data);
        while ($pos < $len) {
            $bar = strpos($data, '|', $pos);
            if ($bar === false) {
                break;
            }
            $key = substr($data, $pos, $bar - $pos);
            $end = self::valueEnd($data, $bar + 1);
            if ($end === null) {
                break; // ข้อมูลเสีย: เก็บเท่าที่อ่านได้ (PHP เดิมก็ทิ้งส่วนที่เหลือ)
            }
            $raw = substr($data, $bar + 1, $end - $bar - 1);
            $value = @unserialize($raw, ['allowed_classes' => false]);
            if ($value === false && $raw !== 'b:0;') {
                break;
            }
            $out[$key] = $value;
            $pos = $end;
        }

        return $out;
    }

    /** @param array<string, mixed> $values */
    public static function encode(array $values): string
    {
        $s = '';
        foreach ($values as $k => $v) {
            if (str_contains((string) $k, '|')) {
                continue; // PHP เองก็เขียน key ที่มี | ไม่ได้
            }
            $s .= $k.'|'.serialize($v);
        }

        return $s;
    }

    /** หาตำแหน่งหลังจบค่า serialize ที่เริ่มที่ $p (null = รูปแบบไม่ถูกต้อง) */
    private static function valueEnd(string $d, int $p): ?int
    {
        $t = $d[$p] ?? '';
        switch ($t) {
            case 'N':
                return ($d[$p + 1] ?? '') === ';' ? $p + 2 : null;
            case 'b':
            case 'i':
            case 'd':
                $semi = strpos($d, ';', $p);

                return $semi === false ? null : $semi + 1;
            case 's':
                if (! preg_match('/\Gs:(\d+):"/', $d, $m, 0, $p)) {
                    return null;
                }
                $start = $p + strlen($m[0]);
                $end = $start + (int) $m[1];
                if (substr($d, $end, 2) !== '";') {
                    return null;
                }

                return $end + 2;
            case 'a':
                if (! preg_match('/\Ga:(\d+):\{/', $d, $m, 0, $p)) {
                    return null;
                }
                $q = $p + strlen($m[0]);
                for ($i = 0; $i < (int) $m[1] * 2; $i++) {
                    $q = self::valueEnd($d, $q);
                    if ($q === null) {
                        return null;
                    }
                }

                return ($d[$q] ?? '') === '}' ? $q + 1 : null;
            case 'O':
                if (! preg_match('/\GO:\d+:"[^"]*":(\d+):\{/', $d, $m, 0, $p)) {
                    return null;
                }
                $q = $p + strlen($m[0]);
                for ($i = 0; $i < (int) $m[1] * 2; $i++) {
                    $q = self::valueEnd($d, $q);
                    if ($q === null) {
                        return null;
                    }
                }

                return ($d[$q] ?? '') === '}' ? $q + 1 : null;
            default:
                return null;
        }
    }
}
