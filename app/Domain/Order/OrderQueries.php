<?php

namespace App\Domain\Order;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * query ของหน้า list รายการสั่งซื้อ (bill_product) — _order/{grid,load_paging,load_total_order}.php และของแอดมิน
 *
 * เดิม: LEFT JOIN derived table 2 ตัว (นับ tracking ที่ยังไม่มีเลข / ยังไม่เข้าโกดัง) ซึ่ง GROUP BY ทั้งตาราง (~4 วินาที/ครั้ง)
 *       + query จำนวนร้านต่อแถว (N+1) + getPrefix ต่อแถว
 * ใหม่: ผลเท่าเดิม — derived table คืน NULL เมื่อไม่มีแถว (ไม่เคยเป็น 0 เพราะ COUNT(t3.id) มีเงื่อนไขบน t3)
 *       จึงเทียบได้กับ correlated COUNT = 0 → ใช้ correlated เฉพาะตอนต้องกรองด้วย HAVING, ไม่งั้นนับเฉพาะ 10 แถวของหน้า
 */
final class OrderQueries
{
    public const NO_TRACK = "(SELECT COUNT(t3.id) FROM bill_product_detail AS t LEFT JOIN bill_product_on_tracking AS t2 ON t2.bill_product_detail_id = t.id LEFT JOIN tracking AS t3 ON t3.id = t2.tracking_id WHERE t3.tracking = '' AND t3.tracking != '-2' AND t3.is_delete = 0 AND t.bill_product_id = bill_product.id)";

    public const NO_WAREHOUSE = "(SELECT COUNT(t3.id) FROM bill_product_detail AS t LEFT JOIN bill_product_on_tracking AS t2 ON t2.bill_product_detail_id = t.id LEFT JOIN tracking AS t3 ON t3.id = t2.tracking_id WHERE t3.date_warehouse = '0000-00-00 00:00:00' AND t3.is_delete = 0 AND t3.tracking != '-2' AND t.bill_product_id = bill_product.id)";

    /** HAVING ของเดิม (ค่า NULL ของ derived = 0 ของ correlated) */
    private const HAVING = [
        '3' => 'no_track != 0 AND no_warehouse != 0',
        '5' => 'no_track = 0 AND no_warehouse != 0',
        '6' => 'no_warehouse = 0',
        // load_paging ของลูกค้าเดิมเขียน "no_track = 0" กับ derived table (ค่าเป็น NULL) → ไม่มีแถวผ่านเลย
        'never' => '1 = 0',
    ];

    public static function base(): Builder
    {
        return DB::table('bill_product')->leftJoin('user', 'bill_product.user', '=', 'user.id');
    }

    /**
     * ตัวกรอง ship/status ของ grid เดิม ($txtSearch[1], $txtSearch[2]) — คืน key ของ HAVING หรือ null
     *
     * @param  bool  $brokenHaving  true = load_paging ของลูกค้า (5/6 เดิมไม่มีแถวผ่าน)
     */
    public static function applyShipStatus(Builder $q, mixed $ship, mixed $status, bool $brokenHaving = false): ?string
    {
        if ($ship !== null && Php74::neq($ship, '-2') && Php74::neq($ship, '')) {
            $q->where('bill_product.ship', $ship);
        }
        if ($status === null) {
            return null;
        }
        $notAll = Php74::neq($status, '-2');
        if ($notAll && Php74::neq($status, '') && Php74::neq($status, '3') && Php74::neq($status, '5') && Php74::neq($status, '6')) {
            $q->where('bill_product.status', $status);
        }
        $having = null;
        if ($notAll && Php74::eq($status, '3')) {
            $q->where('bill_product.status', $status);
            $having = '3';
        }
        if ($notAll && Php74::eq($status, '5')) {
            $q->where('bill_product.status', '!=', '-1');
            $having = $brokenHaving ? 'never' : '5';
        }
        if ($notAll && Php74::eq($status, '6')) {
            $q->where('bill_product.status', '!=', '-1');
            $having = $brokenHaving ? 'never' : '6';
        }

        return $having;
    }

    /** ตัวกรองของ load_total_order (ref ของปุ่มสถานะ) — คืน key ของ HAVING หรือ null */
    public static function applyRef(Builder $q, mixed $ref): ?string
    {
        $having = null;
        // เดิม $ref <= 2 (string เทียบ int แบบ 7.4: "abc" → 0, "" → 0, null → true)
        if (Php74::num($ref) <= 2) {
            $q->where('bill_product.status', $ref ?? '');
        }
        if (Php74::eq($ref, '3')) {
            $q->where('bill_product.status', $ref);
            $having = '3';
        }
        if (Php74::eq($ref, '4')) {
            $q->where('bill_product.status', '-1');
        }
        if (Php74::eq($ref, '5')) {
            $q->where('bill_product.status', '!=', '-1');
            $having = '5';
        }
        if (Php74::eq($ref, '6')) {
            $q->where('bill_product.status', '!=', '-1');
            $having = '6';
        }

        return $having;
    }

    /** ใส่คอลัมน์นับ + HAVING (ต้องเรียกหลังตั้ง select แล้ว) */
    public static function withHaving(Builder $q, string $having): Builder
    {
        return $q->selectRaw(self::NO_TRACK.' AS no_track')
            ->selectRaw(self::NO_WAREHOUSE.' AS no_warehouse')
            ->havingRaw(self::HAVING[$having]);
    }

    /** จำนวนแถว (mysqli_num_rows ของ query เดิม) */
    public static function count(Builder $q, ?string $having): int
    {
        if ($having === 'never') {
            return 0;
        }
        if ($having === null) {
            return (clone $q)->count();
        }
        $sub = self::withHaving((clone $q)->select('bill_product.id'), $having);

        return DB::query()->fromSub($sub, 'x')->count();
    }

    /** readRedis/writeRedis เดิม (10 นาที) → cache ของ Laravel; key = SQL + ค่าที่ bind */
    public static function cachedCount(string $tag, Builder $q, ?string $having): int
    {
        $sql = $q->toSql().'|'.json_encode($q->getBindings()).'|'.$having;

        return (int) Cache::remember('order:'.$tag.':'.sha1($sql), 600, fn () => self::count($q, $having));
    }

    /**
     * แถวของหน้า grid: bill_product.* + user.code + no_track/no_warehouse (null = ไม่มี) + shop_count
     *
     * @return list<object>
     */
    public static function page(Builder $q, ?string $having, int $offset): array
    {
        $q = (clone $q)->select('bill_product.*', 'user.code');
        if ($having === 'never') {
            return [];
        }
        if ($having !== null) {
            self::withHaving($q, $having);
        }
        $rows = $q->orderByDesc('bill_product.id')->offset($offset)->limit(10)->get()->all();
        $ids = array_map(fn ($r) => (int) $r->id, $rows);
        $counts = $having === null ? self::trackCounts($ids) : [];
        $shops = self::shopCounts($ids);
        foreach ($rows as $r) {
            if ($having === null) {
                $r->no_track = $counts[(int) $r->id]['no_track'] ?? null;
                $r->no_warehouse = $counts[(int) $r->id]['no_warehouse'] ?? null;
            } else {
                $r->no_track = (int) $r->no_track === 0 ? null : $r->no_track;
                $r->no_warehouse = (int) $r->no_warehouse === 0 ? null : $r->no_warehouse;
            }
            $r->shop_count = $shops[(int) $r->id] ?? 0;
        }

        return $rows;
    }

    /**
     * derived table เดิม จำกัดเฉพาะ id ที่แสดง
     *
     * @param  list<int>  $ids
     * @return array<int, array{no_track?: string, no_warehouse?: string}>
     */
    public static function trackCounts(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $base = fn () => DB::table('bill_product_detail as t')
            ->leftJoin('bill_product_on_tracking as t2', 't2.bill_product_detail_id', '=', 't.id')
            ->leftJoin('tracking as t3', 't3.id', '=', 't2.tracking_id')
            ->whereIn('t.bill_product_id', $ids)
            ->groupBy('t.bill_product_id')
            ->select('t.bill_product_id')->selectRaw('COUNT(t3.id) as ct');
        $out = [];
        foreach ($base()->where('t3.tracking', '')->where('t3.tracking', '!=', '-2')->where('t3.is_delete', 0)->get() as $r) {
            $out[(int) $r->bill_product_id]['no_track'] = $r->ct;
        }
        foreach ($base()->where('t3.date_warehouse', '0000-00-00 00:00:00')->where('t3.is_delete', 0)->where('t3.tracking', '!=', '-2')->get() as $r) {
            $out[(int) $r->bill_product_id]['no_warehouse'] = $r->ct;
        }

        return $out;
    }

    /**
     * จำนวนร้าน = mysqli_num_rows(SELECT id ... WHERE is_delete = 0 GROUP BY shop_name) ต่อแถว
     * (GROUP BY นับ NULL เป็น 1 กลุ่ม แต่ COUNT(DISTINCT) ไม่นับ → บวกเพิ่มเอง)
     *
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    public static function shopCounts(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $rows = DB::table('bill_product_detail')->where('is_delete', 0)->whereIn('bill_product_id', $ids)
            ->groupBy('bill_product_id')->select('bill_product_id')
            ->selectRaw('COUNT(DISTINCT shop_name) + MAX(shop_name IS NULL) as c')->get();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->bill_product_id] = (int) $r->c;
        }

        return $out;
    }

    /** LIKE '%x%' แบบ bind (เดิมต่อ string → % และ _ ของผู้ใช้ทำงานเป็น wildcard — ที่นี่ค้นตามตัวอักษร) */
    public static function like(string $s): string
    {
        return '%'.addcslashes($s, '%_\\').'%';
    }
}
