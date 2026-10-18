<?php

namespace App\Domain\Tracking;

use Illuminate\Support\Facades\DB;

/**
 * _tracking/load_product_type.php / _admin/_tracking/load_product_type.php เดิม: <select name="shipping_type">
 * ของประเภทสินค้าตาม ship (รถ/เรือ) เลือกค่าเดิมของ tracking ไว้
 */
final class ProductTypeSelect
{
    public static function html(mixed $selectedId, mixed $ship, bool $admin): string
    {
        $rows = DB::table('product_type')
            ->where('is_delete', '0')
            ->where('type', (string) $ship)
            ->orderBy('id')
            ->get(['id', 'name']);

        $html = $admin
            ? '<select class=" form-control" name="shipping_type" onchange="getPricing()">'
            : '<select class=" form-control" name="shipping_type">';
        foreach ($rows as $row) {
            // ชื่อประเภทสินค้าเดิม echo ดิบ — escape ไว้ (แอดมินตั้งชื่อ)
            $name = e($row->name);
            if (Php74::eq($row->id, $selectedId)) {
                $html .= '<option selected="selected" value="'.$row->id.'">'.$name.'</option>';
            } else {
                $html .= '<option value="'.$row->id.'">'.$name.'</option>';
            }
        }

        return $html.'</select>';
    }
}
