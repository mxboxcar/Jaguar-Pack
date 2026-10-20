<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * คำนวณราคาของ system_status_function.php เดิม (ใช้ร่วมกันหลายกลุ่ม: tracking, lot, payment)
 * query ทุกตัว bind ค่า; ตัวเลขที่เขียนลง DB แปลงแบบ PHP 7.4 (Format::phpFloat) ให้ได้ค่าเท่าเดิม
 * ฟังก์ชันที่เขียน DB: CalPrice, CalPriceAutoDiff — โมดูลที่เรียกต้องห่อ transaction เอง
 */
final class Pricing
{
    private static function n(mixed $v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private static function sqlNum(float $v): string
    {
        return Format::phpFloat($v);
    }

    /**
     * แถว tracking + ราคา user_type_detail (kg, cubic, pamount) [+ main_kg/main_cubic ของ product_type]
     * $productTypeId = ค่าแทน tracking.product_type_id (อ่านแบบ "หลังแก้ประเภท" โดยไม่เขียน DB — getCalPrice($id, false))
     */
    private static function trackingRate(mixed $trackingId, bool $withMain, ?int $productTypeId = null): ?object
    {
        $q = DB::table('tracking')
            ->leftJoin('user as t1', 'tracking.user', '=', 't1.id')
            ->leftJoin('user_type_detail as t2', function ($j) use ($productTypeId) {
                $j->on('t1.type', '=', 't2.user_type_id');
                $productTypeId === null
                    ? $j->on('tracking.product_type_id', '=', 't2.product_type_id')
                    : $j->where('t2.product_type_id', '=', $productTypeId);
            })
            ->where('tracking.id', $trackingId)
            ->select('tracking.*', 't2.kg', 't2.cubic', 't2.amount as pamount');
        if ($withMain) {
            $q->leftJoin('product_type as t3', fn ($j) => $productTypeId === null
                ? $j->on('tracking.product_type_id', '=', 't3.id')
                : $j->where('t3.id', '=', $productTypeId))
                ->addSelect('t3.kg as main_kg', 't3.cubic as main_cubic');
        }
        $d = $q->first();
        if ($d && $productTypeId !== null) {
            $d->product_type_id = $productTypeId;
        }

        return $d;
    }

    /** @return array{0: float, 1: float, 2: float} [kg, cubic, amount] */
    private static function calc(?object $d): array
    {
        $amount = self::n($d->amount ?? null);
        $kg = (self::n($d->weight ?? null) * self::n($d->kg ?? null)) * $amount;
        $cubic = (((self::n($d->width ?? null) * self::n($d->long ?? null) * self::n($d->height ?? null)) / 1000000) * self::n($d->cubic ?? null)) * $amount;
        $pa = $amount * self::n($d->pamount ?? null);

        return [$kg, $cubic, $pa];
    }

    /**
     * SR: แก้ product_type ของ tracking ให้ตรงประเภทล็อต (เดิม "fix bug shipping type") แล้วอ่านแถวใหม่
     * - $onlyWithLot = true (CalPriceAutoDiff): ทำเมื่อ lot_id != "0" เท่านั้น
     * - $onlyWithLot = false (GetCalPrice / CalPrice ของ SR): ทำเสมอ — ไม่มีล็อต = type ว่าง == 0 → หา product_type ชื่อเดียวกันของรถ (type 0)
     *   ไม่เจอ = เขียน '' (non-strict → 0) เหมือนเดิม
     * ใช้ราคาหลักของ product_type เมื่อ user_type_detail ไม่มีราคา (kg/cubic ว่าง)
     */
    private static function syncLotTypeAndLoad(mixed $trackingId, bool $onlyWithLot, bool $persist = true): ?object
    {
        $d = self::trackingRate($trackingId, true);
        if (! $onlyWithLot || ($d && (string) $d->lot_id !== '0')) {
            $lotType = $d ? DB::table('lot')->where('id', $d->lot_id)->value('type') : null;
            $typeName = $d ? DB::table('product_type')->where('id', $d->product_type_id)->value('name') : null;
            $newId = DB::table('product_type')->where('name', (string) $typeName)
                ->where('type', (string) $lotType === '0' || $lotType === null ? 0 : 1)->value('id');
            if ($persist) {
                DB::table('tracking')->where('id', $trackingId)->update(['product_type_id' => $newId ?? '']);
                $d = self::trackingRate($trackingId, true);
            } else {
                // ไม่เขียน: อ่านเหมือนแถวที่ถูกแก้แล้ว ('' ลงคอลัมน์ int แบบ non-strict = 0)
                $d = $d ? self::trackingRate($trackingId, true, (int) ($newId ?? 0)) : null;
            }
        }
        if ($d) {
            if ((string) ($d->kg ?? '') === '') {
                $d->kg = $d->main_kg;
            }
            if ((string) ($d->cubic ?? '') === '') {
                $d->cubic = $d->main_cubic;
            }
        }

        return $d;
    }

    /**
     * GetCalPrice($tracking) → "kg|cubic|amount" — SR: เขียน product_type_id ด้วย (ผู้เรียกห่อ transaction เอง)
     * $persist = false: ผลเท่ากันแต่ไม่เขียน DB (ผู้มีสิทธิ์ *_view อย่างเดียว = อ่านอย่างเดียว — เช่น _admin/_lot/load_tracking)
     */
    public static function getCalPrice(mixed $trackingId, bool $persist = true): string
    {
        [$kg, $cubic, $amount] = self::calc(self::syncLotTypeAndLoad($trackingId, false, $persist));

        return self::sqlNum($kg).'|'.self::sqlNum($cubic).'|'.self::sqlNum($amount);
    }

    /** CalPrice($tracking, $choose_price): 1 = ตามน้ำหนัก, 2 = ตามคิว, อื่นๆ = น้ำหนักเมื่อมากกว่าคิว (เท่ากัน → คิว แบบ SR) */
    public static function calPrice(mixed $trackingId, mixed $choosePrice): void
    {
        $d = self::syncLotTypeAndLoad($trackingId, false);
        if (! $d) {
            return; // เดิม: UPDATE ... WHERE id = '' → ไม่มีแถวถูกแก้
        }
        [$kg, $cubic] = self::calc($d);
        $byKg = $choosePrice == 1 || ($choosePrice != 2 && $kg > $cubic);
        self::writePrice($d->id, $byKg, $kg, $cubic);
    }

    /** CalPriceAutoDiff($tracking): แก้ product_type ให้ตรงประเภทล็อต (มีล็อต) แล้วเลือกราคาที่มากกว่า (เท่ากัน → คิว) */
    public static function calPriceAutoDiff(mixed $trackingId): void
    {
        $d = self::syncLotTypeAndLoad($trackingId, true);
        if (! $d) {
            return;
        }
        [$kg, $cubic] = self::calc($d);
        self::writePrice($d->id, $kg > $cubic, $kg, $cubic);
    }

    private static function writePrice(mixed $id, bool $byKg, float $kg, float $cubic): void
    {
        $set = $byKg
            ? ['price_cubic' => '0.00', 'price_amount' => '0.00', 'price_kg' => self::sqlNum($kg)]
            : ['price_kg' => '0.00', 'price_amount' => '0.00', 'price_cubic' => self::sqlNum($cubic)];
        $set['mod_date'] = DB::raw('NOW()');
        $set['mod_by'] = (int) Legacy::userId();
        DB::table('tracking')->where('id', $id)->update($set);
    }

    /**
     * getPrice($user, $type, ...) เดิม: ราคาจาก member_rate หรือ product_type
     *
     * ตาราง `member` / `member_rate` ไม่มีใน DB (เดิม mysqli ไม่โยน error → query ล้มเงียบ ได้ค่าว่าง → ใช้ product_type เสมอ)
     * จึงอ่าน product_type อย่างเดียว ผลเท่าเดิมทุกกรณี ($user ไม่มีผล)
     *
     * @return array{kg: float, cubic: float, amount: float}
     */
    public static function getPrice(mixed $user, mixed $type, mixed $weight, mixed $width, mixed $height, mixed $long, mixed $amount): array
    {
        $rate = DB::table('product_type')->where('id', $type)->where('is_delete', 0)->first(); // ไม่มีคอลัมน์ per_amount (เดิม undefined index → 0)

        return [
            'kg' => self::n($weight) * self::n($rate->kg ?? null),
            // SR: cubicformat(คิว, false) ก่อนคูณราคา (jl ใช้คิวดิบ)
            'cubic' => self::n(Format::cubicformat(((self::n($width) * self::n($height) * self::n($long)) / 1000000), false)) * self::n($rate->cubic ?? null),
            'amount' => self::n($amount) * self::n($rate->per_amount ?? null),
        ];
    }

    /**
     * getShippingTypePrice($id, $payment_ref, $sender) — ตอนสร้างบิล (รายการจาก bill_payment_detail_temp.random)
     *
     * @return array<string, mixed>|null แถว shipping_type (+ price_thai_shipping ตาม rate, + ค่าเปลี่ยนผู้ส่ง)
     */
    public static function getShippingTypePrice(mixed $id, mixed $paymentRef, mixed $sender): ?array
    {
        $rows = DB::table('tracking as t')
            ->leftJoin('user', 'user.id', '=', 't.user')
            ->leftJoin('bill_payment_detail_temp as t2', 't.id', '=', 't2.shipping_id')
            ->where('t2.random', $paymentRef)
            ->get(['t.weight', 't.width', 't.height', 't.long', 't.amount', 'user.code']);

        return self::shippingType($id, $rows, $sender);
    }

    /** getShippingTypePrice_EditSender($id, $payment_id, $sender) — บิลที่สร้างแล้ว (bill_payment_detail) */
    public static function getShippingTypePriceEditSender(mixed $id, mixed $paymentId, mixed $sender): ?array
    {
        // เดิม join t.id = t2.tracking_id แต่ bill_payment_detail.tracking_id คือ PK (AUTO_INCREMENT) ของตารางนี้เอง
        // ส่วน tracking.id อยู่ในคอลัมน์ id → น้ำหนัก/ขนาดที่ใช้คิดค่าส่งแบบ rate (และ VAT) มาจาก tracking ผิดแถว
        // แก้ (เจ้าของอนุมัติ 2026-10-18): join t.id = t2.id
        $rows = DB::table('tracking as t')
            ->leftJoin('user', 'user.id', '=', 't.user')
            ->leftJoin('bill_payment_detail as t2', 't.id', '=', 't2.id')
            ->where('t2.payment_id', $paymentId)
            ->get(['t.weight', 't.width', 't.height', 't.long', 't.amount', 'user.code']);

        return self::shippingType($id, $rows, $sender);
    }

    /** @param iterable<object> $rows */
    private static function shippingType(mixed $id, iterable $rows, mixed $sender): ?array
    {
        $w = 0.0;
        $c = 0.0;
        $code = '';
        foreach ($rows as $r) {
            $w += self::n($r->weight) * self::n($r->amount);
            $c += ((self::n($r->width) * self::n($r->height) * self::n($r->long)) / 1000000) * self::n($r->amount);
            if ($code === '') {
                $code = (string) ($r->code ?? '');
            }
        }
        $row = DB::table('shipping_type')->where('id', $id)->where('is_delete', 0)->first();
        $data = $row ? (array) $row : null;
        $useRate = $data['is_use_rate'] ?? null;
        if ($useRate == 1 || $useRate == 2) {
            $total = $useRate == 1 ? $w : $c;
            // เดิมเทียบ `min` <= 'ค่า' แบบ string ใน SQL (MySQL แปลงเป็นตัวเลขให้) → ส่งเป็นตัวเลขแบบ 7.4
            $data['price_thai_shipping'] = DB::table('shipping_type_rate')
                ->where('shipping_type_id', $id)->where('type', $useRate)->where('is_delete', 0)
                ->where('min', '<=', self::sqlNum($total))
                ->orderByDesc('max')->limit(1)->value('value');
        }
        if ($sender == 1) {
            // SR: ค่าเปลี่ยนผู้ส่งต่อรหัสลูกค้า setting charge_sender_edit = "CODE|ค่า,CODE|ค่า" (ตัวท้ายที่ตรงชนะ, เทียบแบบ == เดิม)
            $fee = Lookup::getSetting('change_sender');
            foreach (explode(',', (string) Lookup::getSetting('charge_sender_edit')) as $item) {
                $p = explode('|', $item);
                if ($p[0] == $code) {
                    $fee = $p[1] ?? null;
                }
            }
            $data['price_thai_storage'] = self::n($data['price_thai_storage'] ?? null) + self::n($fee);
        }

        return $data;
    }
}
