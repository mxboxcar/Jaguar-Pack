<?php

namespace App\Domain\Order;

use App\Support\Legacy\Lookup;

/**
 * ค่าที่ไฟล์เดิมของกลุ่ม order เรียกซ้ำทุกแถว (getPrefix('order') ในลูป = N+1) → อ่านครั้งเดียวต่อ request
 */
final class OrderLookups
{
    /** @var array<string, mixed> */
    private static array $prefix = [];

    /** @var array<string, mixed> */
    private static array $prefixId = [];

    public static function flush(): void
    {
        self::$prefix = [];
        self::$prefixId = [];
    }

    /** getPrefix($module) — ไม่มีแถว = null (echo เป็น '') */
    public static function prefix(string $module): string
    {
        if (! array_key_exists($module, self::$prefix)) {
            self::$prefix[$module] = Lookup::getPrefix($module);
        }

        return (string) self::$prefix[$module];
    }

    public static function prefixId(string $module): mixed
    {
        if (! array_key_exists($module, self::$prefixId)) {
            self::$prefixId[$module] = Lookup::getPrefixID($module);
        }

        return self::$prefixId[$module];
    }

    /**
     * MakePaging()/MakePagingFixed() เดิม: "start , 10" ต่อท้าย LIMIT — page ว่าง/ติดลบ = LIMIT ติดลบ = SQL error
     * (mysqli คืน false → grid แสดง "ไม่มีข้อมูล") → คืน null ให้ผู้เรียกแสดงแบบเดียวกันโดยไม่ query
     */
    public static function offset(mixed $page, int $perPage = 10): ?int
    {
        $start = ($perPage * Php74::num($page)) - $perPage;
        if ($start < 0 || (is_float($start) && floor($start) != $start)) {
            return null;
        }

        return (int) $start;
    }
}
