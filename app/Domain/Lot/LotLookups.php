<?php

namespace App\Domain\Lot;

use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\DB;

/**
 * ตัวช่วยของกลุ่ม lot: prefix แบบ cache ต่อ request, LIMIT ของ MakePaging, แถวแบบ mysqli_fetch_array (สำหรับ JSON เดิม)
 * และข้อมูลประกอบต่อแถวที่ไฟล์เดิมยิง query ทีละแถว (N+1) → ดึงครั้งเดียวด้วย IN (...)
 */
final class LotLookups
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

    /** PACK: ผู้ใช้ที่แก้ล็อตได้ในหน้า (form.php / load_lot_detail.php / index.php เดิม: checkUser()['id'] == 5 ฮาร์ดโค้ด) */
    public const EDITOR_USER_ID = 5;

    /**
     * PACK: เดิมฟอร์มเพิ่ม/แก้ tracking, ช่องแก้หัวล็อต, ปุ่มตั้งวันที่/พิมพ์, ช่องเลือก/แก้/ลบในตาราง และปุ่มสร้างล็อต แสดงเฉพาะ user id 5
     * (เฉพาะหน้าจอ — endpoint เขียนยังตรวจสิทธิ์ admin_lot ตามเดิม)
     */
    public static function allowEdit(): bool
    {
        return (int) Legacy::userId() === self::EDITOR_USER_ID;
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
     * ค่าแบบที่ mysqli คืน (ทุกคอลัมน์เป็น string, NULL เป็น null) — PDO คืน int เป็น int
     *
     * @return array<string, string|null>
     */
    public static function strings(object|array|null $row): ?array
    {
        if ($row === null) {
            return null;
        }
        $out = [];
        foreach ((array) $row as $k => $v) {
            $out[$k] = $v === null ? null : (string) $v;
        }

        return $out;
    }

    /**
     * mysqli_fetch_array() (MYSQLI_BOTH): ทุกคอลัมน์ได้ทั้ง key ตัวเลขและชื่อ เรียง 0, ชื่อ0, 1, ชื่อ1, ...
     * json_encode ของค่านี้ = object {"0":"..","id":"..",...} แบบที่ JS เดิมอ่าน (data[0].code ฯลฯ)
     * (ใช้กับ query ที่ไม่มีชื่อคอลัมน์ซ้ำเท่านั้น)
     *
     * @return array<int|string, string|null>|null
     */
    public static function both(object|array|null $row): ?array
    {
        $s = self::strings($row);
        if ($s === null) {
            return null;
        }
        $out = [];
        $i = 0;
        foreach ($s as $k => $v) {
            $out[$i++] = $v;
            $out[$k] = $v;
        }

        return $out;
    }

    /**
     * SR: รูปพัสดุ (tracking_image ที่ยังไม่ลบ) ของหลาย tracking ใน query เดียว — ไฟล์เดิม SELECT ทีละ tracking (ไม่มี ORDER BY = ลำดับ id)
     *
     * @param  list<int|string>  $ids
     * @return array<int, list<string>>
     */
    public static function trackingImages(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn ($v) => $v > 0));
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach (DB::table('tracking_image')->whereIn('tracking_id', $ids)->where('is_delete', '0')->orderBy('id')->get(['tracking_id', 'attachment']) as $r) {
            $out[(int) $r->tracking_id][] = (string) $r->attachment;
        }

        return $out;
    }

    /**
     * tracking ลูกที่ header = id แม่ (ไฟล์เดิม: SELECT tracking FROM tracking WHERE header = 'id' ทีละแถว)
     *
     * @param  list<int|string>  $ids
     * @return array<string, list<string>>
     */
    public static function childTrackings(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        $rows = DB::table('tracking')
            ->whereIn('header', array_map(fn ($v) => (string) $v, $ids))
            ->orderBy('id')
            ->get(['header', 'tracking']);
        foreach ($rows as $r) {
            $out[(string) $r->header][] = (string) $r->tracking;
        }

        return $out;
    }
}
