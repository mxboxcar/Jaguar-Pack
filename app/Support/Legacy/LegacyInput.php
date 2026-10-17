<?php

namespace App\Support\Legacy;

use Illuminate\Http\Request;

/**
 * ค่าที่รับจากฟอร์มให้ "เหมือนที่เว็บเดิมเขียนลง DB" (system_config.php บรรทัด ~73-95)
 *
 * เว็บเดิมแปลงทุก request ก่อนถึงโมดูล:
 *   $_POST: ' → ''  แล้วลบ \ ทิ้งทั้งหมด     (ค่าในตัวแปร PHP)
 *   $_GET : addslashes(strip_tags(trim()))   และ id ถูก (int)
 * จากนั้นโมดูลฝังค่าใน SQL ตรงๆ → MySQL อ่าน '' / \' กลับเป็น ' → ค่าที่ลง DB จริงคือ:
 *   POST: ค่าดิบที่ตัด \ ออก                → LegacyInput::post()   (ใช้กับ query builder / Eloquent)
 *   GET : strip_tags(trim(ค่าดิบ))           → LegacyInput::query()
 * ถ้าโมดูลเอาค่าไปใช้ฝั่ง PHP (เช่น encrypt, แสดงผล, เทียบ) ให้ใช้ phpValue() ซึ่ง ' ยังเป็น ''
 */
final class LegacyInput
{
    /** ค่าที่ลง DB จริงของ $_POST[$key] */
    public static function post(Request $request, string $key, mixed $default = ''): mixed
    {
        $v = $request->request->all()[$key] ?? $default;

        return self::mapStrings($v, fn (string $s) => self::sqlValue($s));
    }

    /** ค่าในตัวแปร PHP เดิมของ $_POST[$key] (' ถูกแปลงเป็น '') */
    public static function postPhp(Request $request, string $key, mixed $default = ''): mixed
    {
        $v = $request->request->all()[$key] ?? $default;

        return self::mapStrings($v, fn (string $s) => self::phpValue($s));
    }

    /** ค่าที่ลง DB จริงของ $_GET[$key] (id เป็น int เสมอเหมือนเดิม) */
    public static function query(Request $request, string $key, mixed $default = ''): mixed
    {
        $v = $request->query->all()[$key] ?? $default;
        if ($key === 'id' && $v !== '' && $v !== null) {
            return (int) $v;
        }

        return self::mapStrings($v, fn (string $s) => strip_tags(trim($s)));
    }

    public static function sqlValue(string $s): string
    {
        return str_replace('\\', '', $s);
    }

    public static function phpValue(string $s): string
    {
        return str_replace('\\', '', str_replace("'", "''", $s));
    }

    private static function mapStrings(mixed $v, callable $fn): mixed
    {
        if (is_array($v)) {
            return array_map(fn ($x) => self::mapStrings($x, $fn), $v);
        }

        return is_string($v) ? $fn($v) : $v;
    }
}
