<?php

namespace App\Domain\Settings;

use App\Support\Legacy\Lang;
use Illuminate\Support\Facades\DB;

/**
 * getproducttype($id, $name, $value = 0, $type = 10) ของ system_config.php เดิม — <select> ประเภทสินค้า
 *
 * SQL เดิมใช้คอลัมน์ product_type.ship_type ซึ่งไม่มีใน DB → mysqli คืน false → ไม่มี <option> เลย ทุกครั้ง
 * (CONVENTIONS ข้อ 7) จึงตอบ <select> ว่างแบบเดิมโดยไม่ query; ถ้าวันหน้ามีคอลัมน์ ship_type จะทำงานตามโค้ดเดิม
 * ไม่มีไฟล์ใดในเว็บเดิมเรียกฟังก์ชันนี้แล้ว — พอร์ตไว้ให้กลุ่มอื่นใช้ได้
 */
final class ProductTypeSelect
{
    public static function getproducttype(string $id, string $name, mixed $value = 0, mixed $type = 10): string
    {
        $str = '<select ';
        if ($id !== '') {
            $str .= 'id="'.e($id).'" ';
        }
        $str .= 'class="form-control select2';
        $str .= '" '; // $class เดิมไม่เคยถูกตั้ง (undefined → '')
        if ($name !== '') {
            $str .= 'name="'.e($name).'" ';
        }
        $str .= ' >';
        foreach (self::rows($type) as $data) {
            $selected = Php74::eq($data->id, $value) ? 'selected="selected"' : '';
            $shipType = Php74::eq($data->ship_type, 0) ? Lang::get('car') : Lang::get('ship');
            $str .= '<option '.$selected.' value="'.e((string) $data->id).'">'.$shipType.' - '.e((string) $data->name).'</option>';
        }

        return $str.'</select>';
    }

    /** @return iterable<object> */
    private static function rows(mixed $type): iterable
    {
        static $hasColumn = null;
        $hasColumn ??= DB::table('information_schema.columns')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'product_type')->where('column_name', 'ship_type')->exists();
        if (! $hasColumn) {
            return [];
        }
        $q = DB::table('product_type')->where('is_delete', '0');
        if (! Php74::eq($type, 10)) {
            $q->where('ship_type', $type);
        }

        return $q->orderBy('id')->get();
    }
}
