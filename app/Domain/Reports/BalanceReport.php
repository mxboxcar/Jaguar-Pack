<?php

namespace App\Domain\Reports;

use App\Support\Legacy\Format;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * รายงานตามประเภทรายการเงิน (_admin/_report/grid.php, else.php, refill_5/6/7.php, shipping.php)
 *
 * type 99 = ค่าบริการโกดังไทย (bill_payment.price_thai_storage), 98 = ค่าจัดส่งในไทย (price_thai_shipping),
 * 97 (SR) = รายงานภาษีขาย (bill_payment.receipt_*),
 * อื่นๆ = แถว balance ของ balance_type นั้น
 *
 * ข้อมูลประกอบต่อแถว (type 3/5/6/7) เดิม query ทีละแถว (N+1) → extras() โหลดเป็นชุดด้วย whereIn (id เป็น PK = ผลเท่าเดิม)
 */
final class BalanceReport
{
    /** ช่วงวันที่แบบเดิม: getshortdate(วันที่).เวลา — ไม่มีช่องว่างคั่น (ค่าที่ datepicker ส่งมาปกติไม่มีเวลา) */
    public static function range(string $start, string $end): array
    {
        $a = explode('|', $start);
        $b = explode('|', $end);

        return [Format::getshortdate($a[0]).($a[1] ?? ''), Format::getshortdate($b[0]).($b[1] ?? '')];
    }

    /** SELECT ของรายการ (ยังไม่ ORDER/LIMIT) — $range = null คือไม่กรองวันที่ */
    public static function list(mixed $type, ?array $range, bool $joinTransfer): Builder
    {
        if (Php74::eq($type, 99) || Php74::eq($type, 98)) {
            $col = Php74::eq($type, 99) ? 'price_thai_storage' : 'price_thai_shipping';
            $q = DB::table('bill_payment')
                ->leftJoin('user', 'user.id', '=', 'bill_payment.user')
                ->where('bill_payment.is_delete', '0')
                ->select(['bill_payment.'.$col.' as amount', 'bill_payment.id', 'user.code', 'bill_payment.create_date',
                    DB::raw("CONCAT('รายการชำระเงินค่าขนส่งสินค้า PAY-', bill_payment.id) as detail")]);
            if ($range !== null) {
                $q->whereBetween('bill_payment.create_date', $range);
            }

            return $q;
        }
        if (self::isTax($type)) {
            // SR: รายงานภาษีขาย — bill_payment ที่ออกใบเสร็จแล้ว (receipt_id != ''), กรองวันที่ด้วย receipt_date
            $q = DB::table('bill_payment')
                ->leftJoin('user', 'user.id', '=', 'bill_payment.user')
                ->where('bill_payment.receipt_id', '!=', '')->where('bill_payment.is_delete', '0')
                ->select(['bill_payment.*', 'bill_payment.id', 'user.code', 'bill_payment.create_date', 'bill_payment.receipt_total as amount',
                    DB::raw("CONCAT('รายการชำระเงินค่าขนส่งสินค้า PAY-', bill_payment.id, ' - ', receipt_id) as detail")]);
            if ($range !== null) {
                $q->whereBetween('bill_payment.receipt_date', $range);
            }

            return $q;
        }
        $q = DB::table('balance')
            ->leftJoin('balance_type', 'balance.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->leftJoin('user', 'user.id', '=', 'balance.user')
            ->where('balance.is_delete', '0')
            ->where('balance.type', (string) $type)
            ->select(['balance.*', 'balance_type.name', 'prefix.link_admin as link', 'user.code']);
        if ($joinTransfer) {
            $q->leftJoin('bill_transfer', 'bill_transfer.id', '=', 'balance.prefix_ref_id')->addSelect('bill_transfer.status');
        }
        if ($range !== null) {
            $q->whereBetween('balance.create_date', $range);
        }

        return $q;
    }

    /** SR: type 97 = รายงานภาษีขาย (เดิม `$txtSearch[0] == '97'` ตรวจหลัง 99/98) */
    public static function isTax(mixed $type): bool
    {
        return ! Php74::eq($type, 99) && ! Php74::eq($type, 98) && Php74::eq($type, '97');
    }

    /** ตารางหลักของ list() (ใช้ ORDER BY <ตาราง>.id) */
    public static function table(mixed $type): string
    {
        return Php74::eq($type, 99) || Php74::eq($type, 98) || self::isTax($type) ? 'bill_payment' : 'balance';
    }

    /** SELECT SUM(...) as sum, COUNT(id) as count ของ grid.php */
    public static function sum(mixed $type, ?array $range): object
    {
        if (Php74::eq($type, 99) || Php74::eq($type, 98)) {
            $col = Php74::eq($type, 99) ? 'price_thai_storage' : 'price_thai_shipping';
            $q = DB::table('bill_payment')->where('is_delete', '0');
            if ($range !== null) {
                $q->whereBetween('create_date', $range);
            }

            return Rows::str($q->selectRaw('SUM(`'.$col.'`) as sum, COUNT(id) as count')->first());
        }
        if (self::isTax($type)) {
            $q = DB::table('bill_payment')->where('is_delete', '0')->where('receipt_id', '!=', '');
            if ($range !== null) {
                $q->whereBetween('receipt_date', $range);
            }

            return Rows::str($q->selectRaw('SUM(receipt_total) as sum, COUNT(id) as count')->first());
        }
        $q = DB::table('balance')->where('is_delete', '0')->where('type', (string) $type);
        if ($range !== null) {
            $q->whereBetween('create_date', $range);
        }

        return Rows::str($q->selectRaw('SUM(amount) as sum, COUNT(id) as count')->first());
    }

    /**
     * ข้อมูลประกอบตาม prefix_ref_id ของแต่ละแถว: [ref_id => object] (ไม่มี = ไม่อยู่ใน array → ค่าว่างแบบ mysqli_fetch_array ได้ null)
     *  3: bill_payment + shipping_type (price_shipping, price_thai_storage, price_thai_shipping, ship_name)
     *  5: bill_transfer (total, rate) · 6: bill_alipay (total, rate) · 7: taobao (usecash, rate, charge) + SUM(taobao_detail.price) as sum
     *
     * @return array<string, object>
     */
    public static function extras(mixed $type, Collection $rows): array
    {
        $ids = $rows->pluck('prefix_ref_id')->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (string) $v)->unique()->values()->all();
        if ($ids === []) {
            return [];
        }
        $out = [];
        if (Php74::eq($type, 3)) {
            $q = DB::table('bill_payment as p')->leftJoin('shipping_type as t', 't.id', '=', 'p.shipping_type')
                ->whereIn('p.id', $ids)->get(['p.id', 't.name as ship_name', 'p.price_shipping', 'p.price_thai_storage', 'p.price_thai_shipping']);
        } elseif (Php74::eq($type, 5)) {
            $q = DB::table('bill_transfer')->whereIn('id', $ids)->get(['id', 'total', 'rate']);
        } elseif (Php74::eq($type, 6)) {
            $q = DB::table('bill_alipay')->whereIn('id', $ids)->get(['id', 'total', 'rate']);
        } elseif (Php74::eq($type, 7)) {
            $q = DB::table('taobao')->whereIn('id', $ids)->get(['id', 'usecash', 'rate', 'charge']);
            $sums = DB::table('taobao_detail')->whereIn('taobao_id', $ids)->where('is_delete', 0)
                ->groupBy('taobao_id')->get(['taobao_id', DB::raw('SUM(price) as sum')])->keyBy(fn ($r) => (string) $r->taobao_id);
            foreach ($q as $r) {
                $out[(string) $r->id] = Rows::str($r);
            }
            // SUM ของ taobao ที่ไม่มีแถวในตาราง taobao ก็ยังได้ค่า (เดิมเป็น 2 query แยกกัน)
            foreach ($ids as $id) {
                $o = $out[$id] ?? (object) ['usecash' => null, 'rate' => null, 'charge' => null];
                $o->sum = isset($sums[$id]) ? Rows::s($sums[$id]->sum) : null;
                $out[$id] = $o;
            }

            return $out;
        } else {
            return [];
        }
        foreach ($q as $r) {
            $out[(string) $r->id] = Rows::str($r);
        }

        return $out;
    }

    /** ค่าของ $data_in[$k] เดิม (แถวไม่มี = null) */
    public static function ex(array $extras, mixed $refId, string $k): mixed
    {
        $o = $extras[(string) $refId] ?? null;

        return $o->{$k} ?? null;
    }
}
