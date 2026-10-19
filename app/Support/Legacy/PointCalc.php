<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * calPoint() ของ SR (system_status_function.php) — แต้มจากบิลค่าขนส่ง (balance_point type 1, prefix = export)
 * ของ jl เป็นฟังก์ชันว่าง แต่ของ SR ทำงานจริง; ผู้เรียกในเว็บเดิม:
 *   _admin/_payment/save_approved.php   calPoint($id, '',  'คำนวณจากรายการชำระเงินค่าสินค้า')        (ได้แต้ม)
 *   _payment/save_delete.php            calPoint($id, '-', 'ยกเลิกรายการชำระเงินค่าขนส่งสินค้า')      (หักแต้ม)
 *   _export/save_delete.php, _admin/_export/save_delete.php (กลุ่ม reports — '-')
 *
 * แต้ม = bill_payment.price_shipping / setting divide_point แล้วต่อ string "<method><point>" ลง DECIMAL(20,2) (MySQL ปัดเอง)
 * ผู้เรียกต้องเรียกใน DB::transaction เดียวกับการเปลี่ยนสถานะบิล หลัง lockForUpdate แถวบิลแล้ว
 *
 * ต่างจากเดิม (ตั้งใจ): ด่านกันแจกซ้ำ (calpoint-dedupe-guard-v1) เดิมรวม type 1 ทุก prefix ที่ prefix_ref_id = id บิล
 * (แถวแลกแต้ม withdrawpoint ก็เป็น type 1 และยอดติดลบ → ทำให้ยอดรวม ≤ 0 แล้วแจกซ้ำได้) — ที่นี่นับเฉพาะ prefix export ที่ calPoint เขียน
 */
final class PointCalc
{
    public static function calPoint(mixed $paymentId, string $method = '', string $text = ''): void
    {
        $prefixExport = (string) Lookup::getPrefixID('export');
        if ($method === '') {
            $sum = DB::table('balance_point')->where('type', 1)->where('prefix', $prefixExport)
                ->where('prefix_ref_id', (string) $paymentId)->where('is_delete', 0)
                ->selectRaw('COALESCE(SUM(amount),0) AS s')->value('s');
            if ((float) $sum > 0) {
                return;
            }
        }
        $data = DB::table('bill_payment')->where('id', (string) $paymentId)->first(['id', 'user', 'price_shipping']);
        $divide = self::num(Lookup::getSetting('divide_point'));
        // 7.4: หารด้วย 0 = warning + false → '' (ลง DECIMAL = 0) — 8.4 โยน DivisionByZeroError
        $point = $divide == 0 ? '' : self::str(self::num($data->price_shipping ?? null) / $divide);
        $uid = Legacy::userId();

        DB::table('balance_point')->insert([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
            'user' => (string) ($data->user ?? ''), 'amount' => self::decimal($method.$point),
            'detail' => $text.' '.Lookup::getPrefix('payment').($data->id ?? ''),
            'type' => 1, 'prefix' => $prefixExport, 'prefix_ref_id' => (string) ($data->id ?? ''),
        ]);
    }

    /** ตัวเลขแบบ PHP 7.4 (string นำหน้าด้วยตัวเลข → ค่านั้น, อื่นๆ → 0) */
    private static function num(mixed $v): int|float
    {
        if (is_int($v) || is_float($v)) {
            return $v;
        }
        if (preg_match('/^\s*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?/', (string) $v, $m)) {
            return $m[0] + 0;
        }

        return 0;
    }

    /** echo ของตัวเลขแบบ 7.4 (precision = 14) */
    private static function str(int|float $v): string
    {
        if (is_int($v)) {
            return (string) $v;
        }
        $old = ini_get('precision');
        ini_set('precision', '14');
        $s = (string) $v;
        ini_set('precision', (string) $old);

        return $s;
    }

    /** ค่าที่ MySQL (sql_mode ว่าง) จะเก็บจาก '' / '-' = 0 — ส่งเป็น 0 ชัดๆ (ไม่พึ่ง warning) */
    private static function decimal(string $v): string
    {
        return $v === '' || $v === '-' ? '0' : $v;
    }
}
