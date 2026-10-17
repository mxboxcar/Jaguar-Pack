<?php

namespace App\Domain\Points;

use App\Support\Legacy\Format;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Lookup;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * ของที่ใช้ร่วมกันของ grid / ajax กลุ่ม points (_point, _admin/_point, _admin/_withdrawpoint, _shop_cashback, _admin/_shop_cashback)
 */
final class Grid
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

    /** ค่า POST ที่เป็น array (order[] ฯลฯ) แบบที่ลง DB — ไม่ใช่ array = [] */
    public static function inArray(Request $request, string $key): array
    {
        $v = LegacyInput::post($request, $key, []);

        return is_array($v) ? array_values($v) : [];
    }

    /**
     * txtsearch = "<คำค้น>|<สถานะ/ประเภท>" (explode เดิม)
     *
     * @return array{term: string, filter: ?string, termPhp: string, filterPhp: ?string, raw: string}
     */
    public static function search(Request $request): array
    {
        $sql = explode('|', self::in($request, 'txtsearch'));
        $php = explode('|', self::php($request, 'txtsearch'));

        return [
            'term' => $sql[0],
            'filter' => $sql[1] ?? null,
            'termPhp' => $php[0],
            'filterPhp' => $php[1] ?? null,
            'raw' => self::php($request, 'txtsearch'),
        ];
    }

    /** เดิม: if($txtSearch[1] != "-2" && $txtSearch[1] != "") */
    public static function filterApplies(?string $v): bool
    {
        return $v !== null && ! Php74::eq($v, '-2') && ! Php74::eq($v, '');
    }

    /** '%term%' — เดิมต่อ LIKE ตรงๆ (%/_ ของผู้ใช้เป็น wildcard) ที่นี่ escape ตาม CONVENTIONS ข้อ 4 */
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    /** getPrefix/getPrefixID ที่ต่อเข้า SQL/HTML เดิม: null → '' */
    public static function prefixText(string $module): string
    {
        return (string) (Lookup::getPrefix($module) ?? '');
    }

    public static function prefixId(string $module): string
    {
        return (string) (Lookup::getPrefixID($module) ?? '');
    }

    /** str_replace(getPrefix($module), "", $term) */
    public static function stripPrefix(string $module, string $term): string
    {
        $prefix = self::prefixText($module);

        return $prefix === '' ? $term : str_replace($prefix, '', $term);
    }

    /**
     * นับแถว (เดิม mysqli_num_rows ของ query เต็ม) + ดึงหน้าปัจจุบัน ORDER BY id DESC LIMIT (MakePaging)
     *
     * @return array{0: int, 1: Collection}
     */
    public static function page(Builder $q, string $idColumn, mixed $page, array $columns): array
    {
        $total = (clone $q)->count();
        $offset = Php74::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc($idColumn)->offset($offset)->limit(10)->get($columns);

        return [$total, $items];
    }

    /** ยอดคงเหลือ: numbercal($item['amount'] + SUM(ก่อนหน้า)) */
    public static function remain(mixed $amount, mixed $prevSum): float
    {
        return Format::numbercal(Php74::num($amount) + Php74::num($prevSum));
    }

    /** $x >= 0 แบบ 7.4 */
    public static function nonNegative(mixed $v): bool
    {
        return Php74::cmp($v, 0) >= 0;
    }

    /** datetime เดิม: "0000-00-00 00:00:00" หรือ "" → "-<br />-" ไม่งั้น getthaidate(short)<br />gettime */
    public static function dateOrDash(?string $raw): string
    {
        $raw = (string) $raw;
        if ($raw === '0000-00-00 00:00:00' || $raw === '') {
            return '-<br />-';
        }
        $exp = explode(' ', $raw);

        return Format::getthaidate($exp[0], true).'<br />'.Format::gettime($exp[1] ?? '');
    }
}
