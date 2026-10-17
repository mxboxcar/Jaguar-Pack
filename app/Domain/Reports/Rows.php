<?php

namespace App\Domain\Reports;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Collection;

/**
 * ตัวช่วยของกลุ่ม reports ที่ทำให้ผลเท่ากับไฟล์เดิม (mysqli คืนทุกค่าเป็น string, MakePaging, getPrefix ต่อ request)
 */
final class Rows
{
    /** @var array<string, string> */
    private static array $prefix = [];

    public static function prefix(string $module): string
    {
        return self::$prefix[$module] ??= (string) Lookup::getPrefix($module);
    }

    public static function flush(): void
    {
        self::$prefix = [];
    }

    /** แถวจาก PDO (int เป็นชนิดจริง) → ค่าแบบ mysqli เดิม (ทุกค่าเป็น string, NULL คงเป็น null) */
    public static function str(?object $row): ?object
    {
        if ($row === null) {
            return null;
        }
        foreach (get_object_vars($row) as $k => $v) {
            if ($v !== null && ! is_string($v)) {
                $row->{$k} = is_float($v) ? Php74::str($v) : (string) $v;
            }
        }

        return $row;
    }

    /** @return Collection<int, object> */
    public static function strAll(iterable $rows): Collection
    {
        $out = [];
        foreach ($rows as $r) {
            $out[] = self::str($r);
        }

        return collect($out);
    }

    /**
     * MakePaging($rows, $page) เดิม = "(10*$page - 10), 10" ฝังใน LIMIT
     * ค่าที่เดิมทำให้ SQL ผิด (ติดลบ/ไม่ใช่จำนวนเต็ม) → null = ไม่มีแถว (เดิม query ล้มเงียบ)
     */
    public static function offset(mixed $page, int $perPage = 10): ?int
    {
        $start = ($perPage * Php74::num($page)) - $perPage;
        if ($start < 0 || (is_float($start) && floor($start) != $start)) {
            return null;
        }

        return (int) $start;
    }

    /** ช่องวันที่ในตาราง grid เดิม: '-<br />-' หรือ getthaidate(วันที่, true).'<br />'.gettime(เวลา) */
    public static function dateCell(mixed $v): string
    {
        if ($v === null || $v === '' || $v === '0000-00-00 00:00:00') {
            return '-<br />-';
        }
        $exp = explode(' ', (string) $v);

        return Format::getthaidate($exp[0], true).'<br />'.Format::gettime($exp[1] ?? '');
    }

    /** GetFancyBox() เดิมกับชื่อไฟล์จาก DB (escape ก่อน — เดิมต่อดิบใน href/src) */
    public static function fancy(mixed $file): string
    {
        return Format::getFancyBox($file === null ? null : e((string) $file));
    }

    /** ค่าตัวเลขของ PHP 7.4 เป็นข้อความ (echo) — null = '' */
    public static function s(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_int($v) || is_float($v)) {
            return Php74::str($v);
        }

        return (string) $v;
    }
}
