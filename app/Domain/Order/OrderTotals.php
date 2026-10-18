<?php

namespace App\Domain\Order;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\DB;

/**
 * คำนวณยอดรวมของใบสั่งซื้อใหม่ — โค้ดชุดเดียวกันที่คัดซ้ำไว้ใน save_amount / save_price / save_shipping / save_vat เดิม
 *
 * total = Σ(price × amount × exchange_rate) ของทุกรายการ (ไม่กรอง is_delete เหมือนเดิม)
 *       + Σ(tracking.price_shipping × exchange_rate) ของทุก bill_product_on_tracking ของรายการเหล่านั้น
 * (เดิม: ไม่มีรายการเลย → "IN ()" = SQL error → ไม่บวกค่าส่ง)
 */
final class OrderTotals
{
    public static function sum(mixed $billProductId): int|float
    {
        $total = 0;
        $details = DB::table('bill_product_detail')->where('bill_product_id', $billProductId ?? '')
            ->get(['id', 'price', 'amount', 'exchange_rate']);
        foreach ($details as $row) {
            $total = $total + ((Php74::num($row->price) * Php74::num($row->amount)) * Php74::num($row->exchange_rate));
        }
        $ids = $details->pluck('id')->all();
        if ($ids === []) {
            return $total;
        }
        $rows = DB::table('bill_product_on_tracking')
            ->leftJoin('tracking', 'bill_product_on_tracking.tracking_id', '=', 'tracking.id')
            ->leftJoin('bill_product_detail', 'bill_product_on_tracking.bill_product_detail_id', '=', 'bill_product_detail.id')
            ->whereIn('bill_product_detail_id', $ids)
            ->get(['tracking.price_shipping', 'bill_product_detail.exchange_rate']);
        foreach ($rows as $row) {
            $total = $total + (Php74::num($row->price_shipping) * Php74::num($row->exchange_rate));
        }

        return $total;
    }

    /** UPDATE bill_product SET total = '$total' (ค่า float แปลงเป็น string แบบ PHP 7.4) */
    public static function saveTotal(mixed $billProductId): void
    {
        $total = self::sum($billProductId);
        DB::table('bill_product')->where('id', $billProductId ?? '')->update(['total' => Php74::str($total)]);
    }

    /**
     * ส่วน VAT ของ save_vat.php / ท้าย save_shipping.php: $withVat = เปิด VAT หรือไม่
     */
    public static function applyVat(mixed $id, bool $withVat): void
    {
        $total = self::sum($id);
        $v = Lookup::getSetting('vat');
        $numvat = Format::numberformatforsubmit((Php74::num($v) * $total) / 100);
        $vat = Format::numberformatforsubmit(Php74::num($numvat) + $total);
        if ($withVat) {
            DB::table('bill_product')->where('id', $id ?? '')->update([
                'usecash' => $vat, 'total' => $vat, 'is_vat' => '1', 'total_vat' => $numvat, 'vat' => $v ?? '',
            ]);
        } else {
            $total = Php74::str(self::sum($id));
            DB::table('bill_product')->where('id', $id ?? '')->update([
                'usecash' => $total, 'total' => $total, 'is_vat' => '0', 'total_vat' => '0', 'vat' => '0',
            ]);
        }
    }
}
