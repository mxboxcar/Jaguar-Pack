<?php

namespace App\Domain\Tracking;

use App\Support\Legacy\Pricing;
use Illuminate\Support\Facades\DB;

/**
 * รวม tracking หลายแถวเป็นแถวเดียว:
 *  - mergeIds()  = _admin/_combine_track/save_combine.php (แอดมินเลือก 2 แถว)
 *  - run()       = CombineTrack($tracking_number, $order_number) ของ system_status_function.php (เรียกจาก _api/submit_order_number_v2.php)
 *
 * กติกาการรวม (เหมือนเดิม): ทีละคอลัมน์ เอาค่าแรกที่ "ไม่ใช่ค่าว่าง" ตามลำดับแถว, amount = ค่ามากสุด
 * การเทียบใช้ == / != / > แบบ PHP 7.4 และค่าที่ได้ถูกส่งเป็น string ('' แทน NULL) เหมือน INSERT ... VALUES('...') เดิม
 *
 * ต่างจากเดิมโดยตั้งใจ (ดู docs/modules/tracking.md):
 *  - save_combine เดิม INSERT 42 ค่าเข้าตาราง 43 คอลัมน์ (ไม่มี date_section) → INSERT ล้มเสมอ แต่ยัง DELETE ทั้ง 2 แถว
 *    และย้าย bill_product_on_tracking ไป tracking_id = 0 = ข้อมูลหาย → ที่นี่ INSERT ครบคอลัมน์ และทำทั้งหมดใน transaction
 *  - CombineTrack เดิมมี loop เช็ค `lot` (คอลัมน์ไม่มีจริง → SQL error → ไม่ทำอะไร) → ตัดทิ้ง ผลเท่าเดิม
 */
final class CombineTrack
{
    /** คอลัมน์ตามลำดับในตาราง (INSERT INTO tracking VALUES(...) เดิม) */
    private const FIELDS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'no', 'tracking', 'order',
        'price_kg', 'price_cubic', 'price_shipping', 'price_amount', 'rate_kg', 'rate_cubic', 'product_type_id', 'lot_id', 'user',
        'weight', 'width', 'height', 'long', 'amount', 'ship', 'payment_id', 'is_craft', 'is_qc', 'quote', 'price_qc', 'price_repack',
        'price_express', 'price_else', 'price_craft', 'date_warehouse', 'date_outofchina', 'date_thai', 'date_finish', 'remark',
        'remark_shop', 'header', 'is_checked', 'checked_by', 'date_section'];

    private const ZERO = '0000-00-00 00:00:00';

    /** คอลัมน์ => ค่าที่ถือว่า "ว่าง" (เทียบด้วย != แบบ 7.4) */
    private const EMPTY = [
        'create_date' => self::ZERO, 'mod_date' => self::ZERO, 'tracking' => '', 'order' => '', 'remark' => '', 'remark_shop' => '',
        'header' => '', 'date_warehouse' => self::ZERO, 'date_outofchina' => self::ZERO, 'date_thai' => self::ZERO,
        'date_finish' => self::ZERO, 'date_section' => self::ZERO,
    ];

    /** คอลัมน์ที่ไม่เคยถูกตั้งค่าในโค้ดเดิม (คงเป็น '') */
    private const NEVER = ['create_by', 'mod_by', 'is_delete', 'id'];

    /**
     * @param  iterable<object>  $rows
     * @param  string  $isCheckedEmpty  save_combine เทียบ is_checked กับ '' / CombineTrack เทียบกับ '0'
     * @return array<string, string>
     */
    public static function merge(iterable $rows, string $isCheckedEmpty): array
    {
        $last = array_fill_keys(self::FIELDS, '');
        foreach ($rows as $r) {
            $r = (array) $r;
            foreach (self::FIELDS as $f) {
                if (in_array($f, self::NEVER, true)) {
                    continue;
                }
                // mysqli เดิมคืนทุกค่าเป็น string (PDO คืน int) — สำคัญกับการเทียบกับ '': "0" != "" แต่ 0 == ""
                $v = isset($r[$f]) ? (string) $r[$f] : null;
                if ($f === 'amount') {
                    if (Php74::gt($v, $last['amount'])) {
                        $last['amount'] = $v;
                    }

                    continue;
                }
                $empty = $f === 'is_checked' ? $isCheckedEmpty : (self::EMPTY[$f] ?? '0');
                if (Php74::neq($v, $empty) && Php74::eq($last[$f], '')) {
                    $last[$f] = $v;
                }
            }
        }

        // '".$last[$item]."' เดิม: null → ''
        return array_map(fn ($v) => $v === null ? '' : (string) $v, $last);
    }

    /** แถวที่รวมแล้ว → INSERT (ไม่ส่ง id: '' ใน AUTO_INCREMENT = ได้ id ใหม่เหมือนเดิม) */
    private static function insertMerged(array $last): int
    {
        unset($last['id']);

        return (int) DB::table('tracking')->insertGetId($last);
    }

    /** ย้ายออเดอร์ที่ผูกกับแถวเดิมไปแถวใหม่ ลบแถวเดิม แล้วคิดราคาใหม่ */
    private static function replace(array $oldIds, int $newId): void
    {
        DB::table('tracking')->whereIn('id', $oldIds)->delete();
        DB::table('bill_product_on_tracking')->whereIn('tracking_id', $oldIds)->update(['tracking_id' => $newId]);
        Pricing::calPriceAutoDiff($newId);
    }

    /** save_combine.php: รวมได้เฉพาะเมื่อ id ที่ส่งมาตรงกับ 2 แถวพอดี — คืน id ใหม่ (null = ไม่ได้รวม) */
    public static function mergeIds(array $ids): ?int
    {
        if ($ids === []) {
            return null;
        }

        return DB::transaction(function () use ($ids) {
            $rows = DB::table('tracking')->whereIn('id', $ids)->orderBy('lot_id')->orderBy('id')->lockForUpdate()->get();
            if ($rows->count() !== 2) {
                return null;
            }
            $newId = self::insertMerged(self::merge($rows, ''));
            self::replace($rows->pluck('id')->all(), $newId);

            return $newId;
        });
    }

    /**
     * CombineTrack() เดิม — คืนข้อความที่เดิม echo ออกไป
     * เดิม order ว่าง = exit(0) (จบทั้ง request) → คืน ['text', true] ให้ผู้เรียกหยุดเอง
     *
     * @return array{0: string, 1: bool}  [ข้อความ, ต้องหยุด request]
     */
    public static function run(string $trackingNumber, string $orderNumber): array
    {
        $out = $orderNumber.' > '.$trackingNumber;

        DB::table('tracking')->insert(['order' => $orderNumber, 'tracking' => $trackingNumber]);
        if ($orderNumber === '') {
            return [$out, true];
        }

        DB::transaction(function () use ($trackingNumber, $orderNumber) {
            $rows = DB::table('tracking')
                ->where('tracking', $trackingNumber)->orWhere('order', $orderNumber)
                ->orderBy('tracking')->orderBy('id')
                ->lockForUpdate()->get();
            $n = $rows->count();
            if ($n !== 1 && $n !== 0) {
                $newId = self::insertMerged(self::merge($rows, '0'));
                self::replace($rows->pluck('id')->all(), $newId);
            }
            if ($n === 1) {
                DB::table('tracking')->where('id', $rows[0]->id)->update(['tracking' => $trackingNumber]);
            }
        });

        return [$out, false];
    }
}
