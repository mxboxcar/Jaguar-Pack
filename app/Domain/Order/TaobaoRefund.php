<?php

namespace App\Domain\Order;

/**
 * ยอดคืนเงินเมื่อแอดมินยกเลิกรายการฝากจ่ายเถาเปา 1 รายการ (เจ้าของสั่งแก้ 2026-10-18)
 *
 * ตอนลูกค้าสร้างใบ (_taobao/save): ราคาหยวนรวมถูกตัดจากกระเป๋าหยวน (balance_cn) ก่อน = usecash_cn
 * ส่วนที่เหลือ × rate + ค่าบริการ (charge ต่อ 1 ออเดอร์ × จำนวนออเดอร์) ตัดจากบาท (balance) = usecash
 * → คืนในสกุลเดิม: หยวนก่อน (เท่าที่ใบนี้ยังถูกตัดหยวนค้างอยู่) ที่เหลือเป็นบาทตาม rate ของใบ + ค่าบริการ 1 ออเดอร์
 *   และรวมแล้วไม่เกินยอดที่ใบนี้ยังถูกตัดค้างอยู่ในแต่ละสกุล (ยอดสุทธิของสมุดเงินของใบ)
 */
final class TaobaoRefund
{
    /**
     * @param  float  $netCn  ยอดสุทธิ balance_cn ของใบ (ติดลบ = ยังถูกตัดค้าง)
     * @param  float  $netThb  ยอดสุทธิ balance ของใบ
     * @return array{cny: float, thb: float, charge: float}
     */
    public static function forCancel(float $price, float $charge, float $rate, float $netCn, float $netThb): array
    {
        $price = max(0.0, $price);
        $cnyHeld = max(0.0, -$netCn);
        $thbHeld = max(0.0, -$netThb);

        $cny = round(min($price, $cnyHeld), 2);
        $chargeBack = round(min(max(0.0, $charge), $thbHeld), 2);
        $thb = round(min(max(0.0, ($price - $cny) * $rate), $thbHeld - $chargeBack), 2);

        return ['cny' => $cny, 'thb' => max(0.0, $thb), 'charge' => $chargeBack];
    }
}
