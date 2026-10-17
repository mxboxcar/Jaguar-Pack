<?php

namespace App\Domain\Payment;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลประกอบต่อแถวของหน้า payment — ไฟล์เดิมยิง query ทีละแถว (N+1: เลขออเดอร์ + tracking ลูกของ header)
 * ที่นี่ดึงครั้งเดียวต่อหน้าด้วย IN (...) แล้วเลือกแถวตามกติกาเดิม
 */
final class PaymentLookups
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

    public static function flush(): void
    {
        self::$prefix = [];
    }

    /**
     * เลขออเดอร์ (bill_product_detail.bill_product_id) ของ tracking แต่ละตัว — เดิม:
     *   SELECT bill_product_detail.bill_product_id FROM bill_product_on_tracking LEFT JOIN bill_product_detail ...
     *   WHERE tracking_id = X AND bill_product_on_tracking.is_delete = 0  → mysqli_fetch_array แถวแรก (ทีละแถว)
     * ไม่มีแถว / join ไม่เจอ = null (เดิม $data_in['bill_product_id'] = null → '')
     *
     * @param  iterable<int|string>  $trackingIds
     * @return array<int, mixed>
     */
    public static function orderIds(iterable $trackingIds): array
    {
        $ids = self::ints($trackingIds);
        if ($ids === []) {
            return [];
        }
        $out = [];
        $rows = DB::table('bill_product_on_tracking')
            ->leftJoin('bill_product_detail', 'bill_product_detail.id', '=', 'bill_product_on_tracking.bill_product_detail_id')
            ->whereIn('bill_product_on_tracking.tracking_id', $ids)
            ->where('bill_product_on_tracking.is_delete', 0)
            ->orderBy('bill_product_on_tracking.id')
            ->get(['bill_product_on_tracking.tracking_id', 'bill_product_detail.bill_product_id']);
        foreach ($rows as $r) {
            $k = (int) $r->tracking_id;
            if (! array_key_exists($k, $out)) {
                $out[$k] = $r->bill_product_id;
            }
        }

        return $out;
    }

    /**
     * tracking ลูก (header = id) — เดิม SELECT tracking FROM tracking WHERE header = X ทีละแถว (ไม่กรอง is_delete)
     * header เป็น TEXT (มี index) → ส่ง id เป็น string ให้ใช้ index ได้
     *
     * @param  iterable<int|string>  $headerIds
     * @return array<int, list<mixed>>
     */
    public static function children(iterable $headerIds): array
    {
        $ids = self::strings($headerIds);
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach (DB::table('tracking')->whereIn('header', $ids)->orderBy('id')->get(['header', 'tracking']) as $r) {
            $out[(int) $r->header][] = $r->tracking;
        }

        return $out;
    }

    /**
     * ใส่ header_count ให้ทุกแถว — เดิม (SELECT count(id) FROM tracking as t2 WHERE t2.header = tracking.id) ในทุกแถว
     * ซึ่งเทียบ TEXT กับ int → ใช้ index ไม่ได้ = สแกน tracking ทั้งตารางต่อแถว (หน้า load_create ของลูกค้าที่มีพัสดุมาก ~46 วินาที)
     * ที่นี่นับครั้งเดียวด้วย header IN ('id', ...) (ค่า header ในระบบเป็นตัวเลขล้วน → ผลเท่ากัน)
     *
     * @param  Collection<int, object>  $items
     * @return Collection<int, object>
     */
    public static function withHeaderCount(Collection $items): Collection
    {
        $ids = self::strings($items->pluck('id'));
        $counts = $ids === [] ? [] : DB::table('tracking')->whereIn('header', $ids)->groupBy('header')
            ->pluck(DB::raw('count(id) as c'), 'header')->all();
        foreach ($items as $i) {
            $i->header_count = (string) ($counts[(string) $i->id] ?? 0);
        }

        return $items;
    }

    /** @return list<string> */
    private static function strings(iterable $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[] = (string) (int) $id;
        }

        return array_values(array_unique($out));
    }

    /** "รถ/เรือ lot/no" หรือ MALL (lot_id = no = -99) */
    public static function lotLabel(object $item, bool $mall = true): string
    {
        $str = Php74::eq($item->lot_type ?? null, 0) ? Lang::get('car') : Lang::get('ship');
        if ($mall && Php74::eq($item->lot_id ?? null, -99) && Php74::eq($item->no ?? null, -99)) {
            return 'MALL';
        }

        return $str.' '.($item->lot_name ?? '').'/'.($item->no ?? '');
    }

    /**
     * บวกค่าคอลัมน์ของแถวจากซ้ายไปขวาแบบ PHP 7.4 ($start + $item[k1] + $item[k2] ...)
     * ลำดับการบวก float มีผลกับผลลัพธ์ → ส่ง key ตามลำดับเดียวกับไฟล์เดิมเสมอ
     *
     * @param  list<string>  $keys
     */
    public static function add(object $item, array $keys, int|float $start = 0): int|float
    {
        $v = $start;
        foreach ($keys as $k) {
            $v = $v + Php74::num($item->{$k} ?? null);
        }

        return $v;
    }

    /** คิวของแถว ((w*h*l)/1e6)*amount */
    public static function cubic(object $item): int|float
    {
        return ((Php74::num($item->width ?? null) * Php74::num($item->height ?? null) * Php74::num($item->long ?? null)) / 1000000)
            * Php74::num($item->amount ?? null);
    }

    /** @return list<int> */
    private static function ints(iterable $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[] = (int) $id;
        }

        return array_values(array_unique($out));
    }

    /** @param Collection<int, object> $items */
    public static function idsOf(Collection $items): array
    {
        return $items->pluck('id')->all();
    }

    /**
     * MakePaging() เดิม: LIMIT (10*page-10), 10 — ค่าติดลบ/ไม่ใช่จำนวนเต็ม = SQL error → ไม่มีแถว (null)
     */
    public static function offset(mixed $page, int $perPage = 10): ?int
    {
        $start = ($perPage * Php74::num($page)) - $perPage;
        if ($start < 0 || (is_float($start) && floor($start) != $start)) {
            return null;
        }

        return (int) $start;
    }

    /**
     * แถวจาก PDO (int/float เป็นชนิดจริง) → ค่าแบบ mysqli เดิม (ทุกค่าเป็น string, NULL คงเป็น null)
     * ให้การเทียบแบบ PHP 7.4 (Php74::eq) และการแสดงผลตรงกับไฟล์เดิม
     */
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

    /**
     * @param  iterable<object>  $rows
     * @return Collection<int, object>
     */
    public static function strAll(iterable $rows): Collection
    {
        $out = [];
        foreach ($rows as $r) {
            $out[] = self::str($r);
        }

        return collect($out);
    }

    /** ค่าตัวเลขแบบที่ PHP 7.4 echo / ฝังใน SQL ('$x') — null = '' */
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

    /**
     * ช่องวันที่ของ grid: ว่าง/ศูนย์ → "-<br />-"
     *   $admin = false: getthaidate(วัน)."<br />".str_replace(":00", "", เวลา)     (_payment/grid.php)
     *   $admin = true : getthaidate(วัน, true)."<br />".gettime(เวลา)              (_admin/_payment/grid.php, _alipay/grid.php)
     */
    public static function dateCell(mixed $v, bool $admin): string
    {
        if ($v === null || $v === '' || $v === '0000-00-00 00:00:00') {
            return '-<br />-';
        }
        $exp = explode(' ', (string) $v);
        $time = $exp[1] ?? '';

        return $admin
            ? Format::getthaidate($exp[0], true).'<br />'.Format::gettime($time)
            : Format::getthaidate($exp[0]).'<br />'.str_replace(':00', '', $time);
    }
}
