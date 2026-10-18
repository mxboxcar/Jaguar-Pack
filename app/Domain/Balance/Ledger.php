<?php

namespace App\Domain\Balance;

use App\Support\Legacy\Format;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Lookup;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ของที่ใช้ร่วมกันของ grid บัญชีเงิน 5 ไฟล์เดิม (_balance, _balance_cn, _admin/_balance, _admin/_balance_cn, _admin/_balance_company)
 *
 * N+1 เดิม (ทุกแถว): SUM(amount) ยอดคงเหลือก่อนหน้า, SELECT balance_withdraw (รูปสลิป), SELECT user (ผู้ทำรายการ)
 * → LEFT JOIN ในคำสั่งเดียว + ยอดสะสมก่อนหน้าทั้งหน้าใน query เดียว (GridQuery::attachPrevSums)
 *   นับแถวด้วย GridQuery::liveCount / query ที่ไม่มี join แสดงผล (จำนวน query คงที่ไม่ขึ้นกับจำนวนแถว)
 */
final class Ledger
{
    /** ค่า POST แบบที่ลง DB (เดิม: ' → '' แล้ว SQL อ่านกลับเป็น ') — array = '' */
    public static function in(Request $request, string $key): string
    {
        $v = LegacyInput::post($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }

    /** ค่า POST ในตัวแปร PHP เดิม (' เป็น '') — ใช้เทียบ/แสดงผลแบบเดิม */
    public static function php(Request $request, string $key): string
    {
        $v = LegacyInput::postPhp($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }

    /**
     * txtsearch = "<คำค้น>|<ประเภท>" (explode เดิม) — คืน [sql, php] ของแต่ละส่วน
     *
     * @return array{term: string, type: ?string, termPhp: string, typePhp: ?string, raw: string}
     */
    public static function search(Request $request): array
    {
        $sql = explode('|', self::in($request, 'txtsearch'));
        $php = explode('|', self::php($request, 'txtsearch'));

        return [
            'term' => $sql[0],
            'type' => $sql[1] ?? null,
            'termPhp' => $php[0],
            'typePhp' => $php[1] ?? null,
            'raw' => self::php($request, 'txtsearch'),
        ];
    }

    /** เดิม: if($txtSearch[1] != "-2" && $txtSearch[1] != "") → กรอง type */
    public static function typeFilterApplies(?string $type): bool
    {
        return $type !== null && ! Php74::eq($type, '-2') && ! Php74::eq($type, '');
    }

    /** '%term%' — เดิมต่อ LIKE ตรงๆ (%/_ ของผู้ใช้เป็น wildcard) ที่นี่ escape ตาม CONVENTIONS ข้อ 4 */
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    /** str_replace(getPrefix($module), "", $term) */
    public static function stripPrefix(string $module, string $term): string
    {
        $prefix = (string) (Lookup::getPrefix($module) ?? '');

        return $prefix === '' ? $term : str_replace($prefix, '', $term);
    }

    /** SELECT * FROM balance_type WHERE is_delete = 0 (dropdown กรอง) */
    public static function activeTypes(): Collection
    {
        return DB::table('balance_type')->where('is_delete', 0)->orderBy('id')->get(['id', 'name']);
    }

    /** SELECT * FROM balance_type (dropdown ใน modal แอดมิน — เดิมไม่กรอง is_delete) */
    public static function allTypes(): Collection
    {
        return DB::table('balance_type')->orderBy('id')->get(['id', 'name']);
    }

    /**
     * นับแถว (เดิม mysqli_num_rows ของ query เต็ม) + ดึงหน้าปัจจุบัน ORDER BY id DESC LIMIT
     * $total ส่งมาเอง = ไม่นับจาก $q (count ของ $q ต้อง LEFT JOIN ทุกตารางแสดงผล ซึ่งไม่เปลี่ยนจำนวนแถวเพราะ join ด้วย PK)
     *
     * @return array{0: int, 1: Collection}
     */
    public static function page(Builder $q, string $table, mixed $page, array $columns, ?int $total = null): array
    {
        $total ??= (clone $q)->count();
        $offset = Php74::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc($table.'.id')->offset($offset)->limit(10)->get($columns);

        return [$total, $items];
    }

    /** ยอดคงเหลือ: numbercal($item['amount'] + SUM(ก่อนหน้า)) */
    public static function remain(mixed $amount, mixed $prevSum): float
    {
        return Format::numbercal(Php74::num($amount) + Php74::num($prevSum));
    }

    /** $item['amount'] >= 0 แบบ 7.4 */
    public static function nonNegative(mixed $v): bool
    {
        return Php74::cmp($v, 0) >= 0;
    }

    /**
     * json_encode(mysqli_fetch_array($result)) เดิม: key ตัวเลข + ชื่อคอลัมน์สลับกัน, ค่าทุกตัวเป็น string (null คงเป็น null)
     * ไม่มีแถว → "null"
     */
    public static function mysqliJson(?object $row): string
    {
        if ($row === null) {
            return 'null';
        }
        $out = [];
        $i = 0;
        foreach ((array) $row as $k => $v) {
            $s = $v === null ? null : (is_float($v) ? Php74::str($v) : (string) $v);
            $out[$i++] = $s;
            $out[$k] = $s;
        }

        return (string) json_encode($out);
    }

    /** getPrefix/getPrefixID ที่ต่อเข้า SQL string เดิม: null → '' */
    public static function prefixText(string $module): string
    {
        return (string) (Lookup::getPrefix($module) ?? '');
    }

    public static function prefixId(string $module): string
    {
        return (string) (Lookup::getPrefixID($module) ?? '');
    }
}
