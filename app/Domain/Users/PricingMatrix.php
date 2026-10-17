<?php

namespace App\Domain\Users;

use Illuminate\Support\Facades\DB;

/**
 * ตารางเรตราคา (รถ/เรือ × ประเภทสินค้า) ในฟอร์มผู้ใช้ (_admin/_user/form.php) และฟอร์มกลุ่มราคา (_admin/_user_type/form.php)
 *
 * เดิม: SELECT product_type ทีละแถว แล้ว SELECT user_type_detail ต่อแถว (N+1) — ถ้ามีเรตของกลุ่มนั้นใช้ค่าในกลุ่ม ไม่งั้นใช้ค่าตั้งต้นของ product_type
 * ใหม่: 2 query (product_type ทั้งหมด + user_type_detail ของกลุ่ม) แล้วจับคู่ใน PHP — แถวแรกตามลำดับ id = mysqli_fetch_array แถวแรกเดิม
 */
final class PricingMatrix
{
    /**
     * @return array{head_car: list<object>, head_ship: list<object>, car: list<object>, ship: list<object>}
     */
    public static function forUserType(mixed $userTypeId): array
    {
        $types = DB::table('product_type')->where('is_delete', 0)->whereIn('type', ['0', '1'])
            ->orderBy('id')->get(['id', 'name', 'kg', 'cubic', 'amount', 'type']);

        // เดิมฝัง '$id' / '".$data['type']."' ในเครื่องหมายคำพูด → bind เป็น string ให้ MySQL แปลงแบบเดิม (null/'' = 0)
        $details = [];
        $rows = DB::table('user_type_detail')->where('is_delete', 0)->whereIn('type', ['0', '1'])
            ->where('user_type_id', (string) ($userTypeId ?? ''))
            ->orderBy('id')->get(['kg', 'cubic', 'amount', 'product_type_id as id', 'type']);
        foreach ($rows as $r) {
            $k = ((int) $r->type).'|'.$r->id;
            $details[$k] ??= $r;
        }

        $out = ['head_car' => [], 'head_ship' => [], 'car' => [], 'ship' => []];
        foreach ($types as $t) {
            $side = (int) $t->type === 0 ? 'car' : 'ship';
            $out['head_'.$side][] = $t;
            $out[$side][] = $details[((int) $t->type).'|'.$t->id] ?? $t;
        }

        return $out;
    }
}
