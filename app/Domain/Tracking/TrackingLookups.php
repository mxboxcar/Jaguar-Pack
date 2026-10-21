<?php

namespace App\Domain\Tracking;

use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลประกอบต่อแถวของหน้า list tracking — ไฟล์เดิมยิง query ทีละแถว (N+1, 3–4 query ต่อแถว)
 * ที่นี่ดึงครั้งเดียวต่อหน้าด้วย IN (...) แล้วเลือกแถวตามกติกาเดิม (แถวแรก / ล่าสุด)
 */
final class TrackingLookups
{
    /** @var array<string, mixed> */
    private static array $prefix = [];

    /** getPrefix() แบบ cache ต่อ request (ไฟล์เดิมเรียกทุกแถว) */
    public static function prefix(string $module): string
    {
        if (! array_key_exists($module, self::$prefix)) {
            self::$prefix[$module] = Lookup::getPrefix($module);
        }

        return (string) self::$prefix[$module];
    }

    public static function prefixId(string $module): mixed
    {
        return Lookup::getPrefixID($module);
    }

    public static function flush(): void
    {
        self::$prefix = [];
    }

    /**
     * เลขออเดอร์ในระบบต่อ tracking (เดิม: tracking LEFT JOIN bill_product_on_tracking LEFT JOIN bill_product_detail ทีละแถว)
     * เดิมข้ามเฉพาะค่า NULL (mysqli คืน string: "0" != "" เป็นจริง)
     *
     * @param  list<int>  $ids
     * @return array<int, list<int|string>>
     */
    public static function orderIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        $rows = DB::table('bill_product_on_tracking as t')
            ->leftJoin('bill_product_detail as t2', 't.bill_product_detail_id', '=', 't2.id')
            ->whereIn('t.tracking_id', $ids)
            ->orderBy('t.id')
            ->get(['t.tracking_id', 't2.bill_product_id']);
        foreach ($rows as $r) {
            if ($r->bill_product_id !== null && $r->bill_product_id !== '') {
                $out[(int) $r->tracking_id][] = $r->bill_product_id;
            }
        }

        return $out;
    }

    /**
     * bill_product.admin_quote ของแถวแรกที่ join ได้ (เดิม mysqli_fetch_array ครั้งเดียว)
     *
     * @param  list<int>  $ids
     * @return array<int, mixed>
     */
    public static function adminQuotes(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        $rows = DB::table('bill_product_on_tracking as t')
            ->leftJoin('bill_product_detail as t2', 't2.id', '=', 't.bill_product_detail_id')
            ->leftJoin('bill_product as t3', 't2.bill_product_id', '=', 't3.id')
            ->whereIn('t.tracking_id', $ids)
            ->orderBy('t.id')
            ->get(['t.tracking_id', 't3.admin_quote']);
        foreach ($rows as $r) {
            $k = (int) $r->tracking_id;
            if (! array_key_exists($k, $out)) {
                $out[$k] = $r->admin_quote;
            }
        }

        return $out;
    }

    /**
     * goodsconfirm แถวแรกที่ status != -1 ต่อ tracking
     *
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    public static function goodsconfirmIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        $rows = DB::table('goodsconfirm')
            ->whereIn('tracking_id', $ids)
            ->where('status', '!=', -1)
            ->orderBy('id')
            ->get(['id', 'tracking_id']);
        foreach ($rows as $r) {
            $k = (int) $r->tracking_id;
            if (! array_key_exists($k, $out)) {
                $out[$k] = $r->id;
            }
        }

        return $out;
    }

    /**
     * SR: รูปพัสดุ (tracking_image) ต่อ tracking ใน grid — เดิม SELECT ทีละแถว WHERE tracking_id = x AND is_delete = 0 (ไม่มี ORDER BY → id)
     *
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    public static function images(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach (DB::table('tracking_image')->whereIn('tracking_id', $ids)->where('is_delete', '0')->orderBy('id')
            ->get(['tracking_id', 'attachment']) as $r) {
            $out[(int) $r->tracking_id][] = (string) $r->attachment;
        }

        return $out;
    }

    /**
     * SR: รูปของ tracking เดียว (หน้า form) — เดิม AND tracking_id != '0' (หน้าสร้างใหม่ = ไม่มีรูป)
     *
     * @return list<string>
     */
    public static function imageList(int $trackingId): array
    {
        return $trackingId === 0 ? [] : (self::images([$trackingId])[$trackingId] ?? []);
    }

    /**
     * MakePaging()/MakePagingFixed() เดิม: LIMIT (10*page-10), 10 — ค่าติดลบ/ไม่ใช่จำนวนเต็ม = SQL error → ไม่มีแถว (null)
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
