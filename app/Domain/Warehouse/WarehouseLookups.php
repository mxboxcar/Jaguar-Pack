<?php

namespace App\Domain\Warehouse;

use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lookup;

/**
 * ตัวช่วยร่วมของกลุ่ม warehouse (ไฟล์เดิม _goodsconfirm, _admin/_goodsconfirm)
 */
final class WarehouseLookups
{
    /** @var array<string, string> */
    private static array $prefix = [];

    /** getPrefix($module) — cache ต่อ request (grid เดิมเรียกทุกแถว) */
    public static function prefix(string $module): string
    {
        return self::$prefix[$module] ??= (string) Lookup::getPrefix($module);
    }

    /** MakePaging() เดิม: LIMIT (10*page-10), 10 — ค่าติดลบ/ไม่ใช่จำนวนเต็ม = SQL error → ไม่มีแถว (null) */
    public static function offset(mixed $page, int $perPage = 10): ?int
    {
        $start = ($perPage * Php74::num($page)) - $perPage;
        if ($start < 0 || (is_float($start) && floor($start) != $start)) {
            return null;
        }

        return (int) $start;
    }

    /**
     * แถวแบบที่ mysqli คืน (ทุกคอลัมน์เป็น string, NULL เป็น null) — PDO คืน int เป็น int
     *
     * @return array<string, string|null>|null
     */
    public static function strings(object|array|null $row): ?array
    {
        if ($row === null) {
            return null;
        }
        $out = [];
        foreach ((array) $row as $k => $v) {
            $out[$k] = $v === null ? null : (is_float($v) ? Php74::str($v) : (string) $v);
        }

        return $out;
    }

    /** ชื่อไฟล์ใน upload/temp ที่ปลอดภัย (เดิม file_exists('../upload/temp/'.$x) ตรงๆ = path traversal) */
    public static function safeName(string $name): bool
    {
        return $name !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $name) === 1 && ! str_contains($name, '..');
    }

    /** file_exists('upload/temp/'.$name) เดิม — temp อยู่ที่ config legacy.upload.temp_dir */
    public static function tempFileExists(string $name): bool
    {
        if (! self::safeName($name)) {
            return false;
        }

        return is_file(rtrim((string) config('legacy.upload.temp_dir'), '/').'/'.$name);
    }

    /**
     * JS ที่ไฟล์เดิมตอบให้เบราว์เซอร์ไปเรียก _api/blockblob_ajax (ย้ายไฟล์จาก temp ขึ้น storage)
     * ฝั่งนี้ไม่เรียก API ภายนอกเอง — ไฟล์เดิมเป็น CRLF และเยื้องต่างกันทุกไฟล์ ($indent/$inner) ให้ได้ byte เท่าเดิม
     */
    public static function blobJs(string $name, string $indent, string $inner): string
    {
        $i = $indent;

        return "$.ajax({ \r\n{$i}type: 'POST', \r\n{$i}async: true, \r\n{$i}data : 'path=../upload/temp/".Alerts::js($name)."', \r\n"
            ."{$i}url: rooturl + '_api/blockblob_ajax', \r\n{$i}success: function(data) { \r\n{$inner}\r\n{$i}} });";
    }
}
