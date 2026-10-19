<?php

namespace App\Support\Legacy;

/**
 * ฟังก์ชันจัดรูปแบบของ system_config.php เดิม — ผลลัพธ์ต้องเหมือนเดิมทุกตัวอักษร (รวม HTML ที่ติดมา)
 * ข้อสังเกตที่ต้องรักษาไว้:
 *  - numberformat*: "ตัด" ทศนิยม ไม่ปัดเศษ และไม่ใส่ comma (JS makeCurrency() ใส่ให้ฝั่งเบราว์เซอร์)
 *  - getthaidate: แยกด้วย '-' เท่านั้น ถ้าส่ง datetime มา ส่วนวันจะติดเวลาไปด้วย (เหมือนเดิม)
 */
final class Format
{
    private const MONTH_KEYS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

    private const EN_MONTHS = ['January', 'Feburary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    /** ส่วนกลางของ numberformat/numberformatpoint/numberformatone/cubicformat */
    private static function digits(string $value, ?int $decimals): string
    {
        $value = str_replace([' ', ','], '', $value);
        $exp = explode('.', $value);
        $v = $exp[0];
        if ($decimals === null) {
            return $v;
        }
        $back = $exp[1] ?? '';
        $v .= '.';
        for ($i = 0; $i < $decimals; $i++) {
            $c = $back[$i] ?? '';
            $v .= $c === '' ? '0' : $c;
        }

        return $v;
    }

    private static function str(mixed $value): string
    {
        // (string) ของ float ใน PHP 7.4 ใช้ precision=14 เหมือนกัน (ini precision เดิม)
        return is_float($value) ? self::phpFloat($value) : (string) $value;
    }

    /** (string)$float แบบ PHP 7.4 (precision=14) — ใช้ตอนส่งตัวเลขลง SQL ให้ได้ค่าเท่าเดิม */
    public static function phpFloat(float $f): string
    {
        $old = ini_get('precision');
        ini_set('precision', '14');
        $s = (string) $f;
        ini_set('precision', (string) $old);

        return $s;
    }

    public static function numberformat(mixed $value, bool $isDiv = true, bool $isUnit = true, bool $isChina = false): string
    {
        $v = self::digits(self::str($value), 2);
        if (! $isDiv) {
            return $v;
        }
        if (! $isUnit) {
            return '<span class="currency">'.$v.'</span>';
        }

        return $isChina
            ? Lang::get('cn_currency').'&nbsp;<span class="currency">'.$v.'</span>'
            : '<span class="currency">'.$v.'</span>&nbsp;'.Lang::get('thai_currency');
    }

    public static function numberformatforsubmit(mixed $value): string
    {
        return self::numberformat($value, false, false, false);
    }

    public static function numbercal(mixed $value): float
    {
        return round((float) $value, 2);
    }

    public static function numberformatpoint(mixed $value, bool $isDiv = true, bool $isUnit = true): string
    {
        $v = self::digits(self::str($value), 2);
        if (! $isDiv) {
            return $v;
        }

        return $isUnit ? '<span class="currency">'.$v.'</span>&nbsp;p' : '<span class="currency">'.$v.'</span>';
    }

    public static function numberformatzero(mixed $value, bool $isDiv = true): string
    {
        $v = self::digits(self::str($value), null);

        return $isDiv ? '<span class="currency">'.$v.'</span>' : $v;
    }

    public static function numberformatone(mixed $value, bool $isDiv = true): string
    {
        $v = self::digits(self::str($value), 1);

        return $isDiv ? '<span class="currency">'.$v.'</span>' : $v;
    }

    public static function cubicformat(mixed $value, bool $isDiv = true): string
    {
        $v = self::digits(number_format((float) $value, 10), 4);

        return $isDiv ? '<span class="currency">'.$v.'</span>' : $v;
    }

    public static function numformat(mixed $value): string
    {
        return str_replace(',', '', (string) $value);
    }

    public static function getZero(mixed $value): mixed
    {
        // เดิมเทียบแบบหลวม (==) กับ "0.00" / "0": ค่าตัวเลขหรือ string ตัวเลขที่เท่ากับศูนย์ ("0.000", "00") ก็นับ
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (float) $value == 0 ? '-' : $value;
        }
        $spans = ['<span class="currency">0.00</span>', '<span class="currency">0</span>', '<span class="currency">0.0</span>'];

        return in_array((string) $value, $spans, true) ? '-' : $value;
    }

    public static function getBold(mixed $value): string
    {
        return '<strong>'.$value.'</strong>';
    }

    public static function nStr(mixed $value): string
    {
        return str_replace("'", "''", (string) $value);
    }

    public static function cleanSql(mixed $s): string
    {
        $s = (string) $s;
        return (string) preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $s));
    }

    public static function utf8_strlen(mixed $s): int
    {
        $s = (string) $s;
        $l = 0;
        $c = strlen($s);
        for ($i = 0; $i < $c; $i++) {
            if ((ord($s[$i]) & 0xC0) !== 0x80) {
                $l++;
            }
        }

        return $l;
    }

    /** bankformat เดิม (ทำงานระดับ byte เหมือนเดิม: เลขบัญชีเป็น ASCII) */
    public static function bankformat(mixed $source, bool $isHide = true): string
    {
        $source = (string) $source; // ค่าจาก DB อาจเป็น null/int (เดิมไม่มี type)
        $str = '';
        $n = self::utf8_strlen($source);
        for ($i = 0; $i < $n; $i++) {
            if ($isHide) {
                if ($i < 4) {
                    $source[$i] = 'x';
                }
                if ($i === self::utf8_strlen($source) - 1) {
                    $source[$i] = 'x';
                }
            }
            $ch = $source[$i] ?? '';
            $str .= ($i === 2 || $i === 3 || $i === 8) ? $ch.'-' : $ch;
        }

        return $str;
    }

    public static function booltoint(mixed $b): int
    {
        return $b == 'true' ? 1 : 0;
    }

    public static function randd(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKMNOPQRSTUVWXYZ';
        $s = '';
        for ($i = 0; $i < $length; $i++) {
            $s .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $s;
    }

    public static function randomColor(): string
    {
        $s = '#';
        for ($i = 0; $i < 3; $i++) {
            $s .= dechex(random_int(170, 255));
        }

        return $s;
    }

    public static function hex2rgb(string $hex, float|string $opacity = 0.6): string
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 3) {
            $r = hexdec($hex[0].$hex[0]);
            $g = hexdec($hex[1].$hex[1]);
            $b = hexdec($hex[2].$hex[2]);
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return 'rgba('.$r.','.$g.','.$b.','.$opacity.')';
    }

    public static function gettime(mixed $date): string
    {
        return "<span style='font-size: 9px!important;'>".$date.'</span>';
    }

    public static function getToday(): string
    {
        return date('Y-m-d');
    }

    /** getthaidate('2024-01-31') → "<span ...>31 ม.ค. 2024</span>" (ภาษาตาม cookie) */
    public static function getthaidate(mixed $date, bool $short = false, bool $isDiv = false): string
    {
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d H:i:s');
        }
        $p = explode('-', (string) $date);
        $mm = $p[1] ?? '';
        if ($mm === '00') {
            return '-';
        }
        $idx = array_search($mm, ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'], true);
        $month = $idx === false ? '' : Lang::get(self::MONTH_KEYS[$idx]);
        $monthS = $idx === false ? '' : $mm;
        $d = $p[2] ?? '';
        $y = $p[0];
        $text = $short ? $d.'/'.$monthS.'/'.$y : $d.' '.$month.' '.$y;

        return $isDiv ? $text : "<span style='font-size: 9px!important;'>".$text.'</span>';
    }

    /** "31 ม.ค. 2024" (ชื่อเดือนตามภาษา) → "2024-01-31" */
    public static function getshortdate(string $date): string
    {
        $p = explode(' ', $date);

        return ($p[2] ?? '').'-'.self::monthFromName($p[1] ?? '', true).'-'.self::padDay($p[0]);
    }

    /** = getshortdate (เดิมเป็นสำเนาโค้ดกัน) */
    public static function getdatesubmit(string $date): string
    {
        return self::getshortdate($date);
    }

    /** "31 ม.ค. 2024" → "31 January 2024" */
    public static function getendate(string $date): string
    {
        $p = explode(' ', $date);

        return self::padDay($p[0]).' '.self::monthFromName($p[1] ?? '', false).' '.($p[2] ?? '');
    }

    private static function padDay(string $d): string
    {
        return strlen($d) === 1 ? '0'.$d : $d;
    }

    private static function monthFromName(string $name, bool $numeric): string
    {
        // เดิม: switch($x) case ($x == $resource['jan']) … → เคสแรกที่เทียบแล้วจริง
        if ($name === '') {
            return '';
        }
        foreach (self::MONTH_KEYS as $i => $k) {
            if ($name == Lang::get($k)) {
                return $numeric ? sprintf('%02d', $i + 1) : self::EN_MONTHS[$i];
            }
        }

        return '';
    }

    public static function getChinaSymbol(): string
    {
        return '¥';
    }

    public static function getThaiSymbol(): string
    {
        return ' ฿';
    }

    /** MakePaging/MakePagingFixed เดิม: คืน "start , 10" สำหรับ LIMIT (ใช้กับ offset()/limit() แทน) */
    public static function pagingOffset(int $page, int $perPage = 10): int
    {
        return ($perPage * $page) - $perPage;
    }

    /** @return list<string> */
    public static function uploadImageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png'];
    }

    /** @return list<string> */
    public static function uploadAllowedExtensions(): array
    {
        return [...self::uploadImageExtensions(), 'pdf'];
    }

    public static function uploadIsImage(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::uploadImageExtensions(), true);
    }

    public static function getFancyBox(?string $image): string
    {
        if ((string) $image === '') {
            return '-';
        }
        $u = Legacy::hrefBlob($image);

        return '<a class="fancybox" href="'.$u.'"><img src="'.$u.'" style="max-width: 40px;max-height: 40px;"></a>';
    }

    public static function goLogImg(?string $image): string
    {
        $u = Legacy::hrefBlob((string) $image);

        return '<a class="fancybox" href="'.$u.'"><img src="'.$u.'" style="max-width: 60px;max-height: 60px;"></a>';
    }
}
