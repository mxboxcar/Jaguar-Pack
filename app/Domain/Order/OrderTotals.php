<?php

namespace App\Domain\Order;

use Illuminate\Support\Facades\DB;

/**
 * คำนวณยอดรวมของใบสั่งซื้อใหม่ — โค้ดชุดเดียวกันที่คัดซ้ำไว้ใน save_amount / save_price / save_shipping / save_newrate (SR) เดิม
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
}
