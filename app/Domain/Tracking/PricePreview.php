<?php

namespace App\Domain\Tracking;

use Illuminate\Support\Facades\DB;

/**
 * _tracking/load_pricing.php และ _admin/_tracking/load_pricing.php เดิม (โค้ดเหมือนกันทุกบรรทัด)
 * คำนวณราคาตามน้ำหนัก/ขนาดที่กรอกด้วยอัตรา user_type_detail ของเจ้าของ tracking
 * ตัวเลขคิดแบบ PHP 7.4 ("" → 0, int คงเป็น int) เพื่อให้ JSON ตรงกับเดิม
 */
final class PricePreview
{
    /** @param  array<string, mixed>  $in  ค่า POST: product_type_id, weight, width, height, amount, long */
    public static function json(mixed $userType, array $in): string
    {
        $rate = DB::table('user_type_detail')
            ->where('user_type_id', (string) ($userType ?? ''))
            ->where('product_type_id', (string) ($in['product_type_id'] ?? ''))
            ->where('is_delete', 0)
            ->first(['kg', 'cubic']);

        $n = fn (string $k) => Php74::num($in[$k] ?? '');
        $kg = ($n('weight') * Php74::num($rate->kg ?? null)) * $n('amount');
        $cubic = ((($n('width') * $n('height') * $n('long')) / 1000000) * Php74::num($rate->cubic ?? null)) * $n('amount');

        if ($kg > $cubic) {
            $cubic = 0;
        } else {
            $kg = 0;
        }

        return json_encode(['kg' => $kg, 'cubic' => $cubic]);
    }
}
