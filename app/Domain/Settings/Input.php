<?php

namespace App\Domain\Settings;

use App\Support\Legacy\Alerts;
use App\Support\Legacy\LegacyInput;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * ค่าที่รับจาก request ของไฟล์เดิมกลุ่ม settings + ของที่ grid ทุกตัวใช้ร่วมกัน
 */
final class Input
{
    /** ค่า POST แบบที่ลง DB (เดิม ' → '' แล้ว MySQL อ่านกลับเป็น ') — array = '' */
    public static function in(Request $request, string $key): string
    {
        $v = LegacyInput::post($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }

    /** ค่า POST ในตัวแปร PHP เดิม (' เป็น '') — ใช้แสดงผล/เทียบแบบเดิม */
    public static function php(Request $request, string $key): string
    {
        $v = LegacyInput::postPhp($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }

    /** ค่าที่ฝังใน string JS (ทั้ง '...' และ "...") — เดิม echo ดิบ (XSS) */
    public static function js(mixed $v): string
    {
        return str_replace('"', '\\"', Alerts::js($v));
    }

    /** '%term%' — เดิมต่อ LIKE ตรงๆ (% _ ของผู้ใช้เป็น wildcard) ที่นี่ escape ตาม CONVENTIONS ข้อ 4 */
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    /**
     * mysqli_num_rows(query เต็ม) + "ORDER BY id DESC LIMIT MakePaging()" เดิม
     * page ที่ทำให้ LIMIT ติดลบ/ทศนิยม (เช่น '' หรือ 0) = SQL เดิมพัง → ไม่มีแถว แต่จำนวนรวมยังแสดง
     *
     * @return array{0: int, 1: Collection}
     */
    public static function page(Builder $q, string $idColumn, mixed $page, array $columns = ['*']): array
    {
        $total = (clone $q)->count();
        $offset = Php74::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc($idColumn)->offset($offset)->limit(10)->get($columns);

        return [$total, $items];
    }

    /**
     * delete.php เดิม: arr_id = ",1,2,3" (ตัดตัวแรก) หรือ id = "5" → `id` IN (...) ต่อ string ตรง (SQL injection)
     * ที่นี่รับเฉพาะรายการเลขจำนวนเต็ม; รูปแบบอื่น SQL เดิม syntax error = ไม่ลบอะไร → คืน [] (ไม่ลบเหมือนเดิม)
     *
     * @return list<int>
     */
    public static function idList(Request $request): array
    {
        $arr = self::in($request, 'arr_id');
        $list = $arr !== '' ? mb_substr($arr, 1) : self::in($request, 'id');
        $ids = [];
        foreach (explode(',', $list) as $tok) {
            if (preg_match('/^\s*[+-]?\d+\s*$/', $tok) !== 1) {
                return [];
            }
            $ids[] = (int) trim($tok);
        }

        return $ids;
    }
}
