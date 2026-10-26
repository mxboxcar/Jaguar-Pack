<?php

namespace App\Domain\MoneyMove;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ค่าประกอบที่ไฟล์เดิมของกลุ่ม money-move เรียกซ้ำ (getPrefix ทุกแถวของ grid ฯลฯ) — cache ต่อ request
 * และตาราง "รายการเงินที่เกี่ยวข้อง" (ref_balance_list) ที่ form.php เดิมสร้างเป็น string HTML
 */
final class Lookups
{
    /** @var array<string, array{prefix: mixed, id: mixed}> */
    private static array $prefix = [];

    public static function prefix(string $module): string
    {
        return (string) self::row($module)['prefix'];
    }

    public static function prefixId(string $module): mixed
    {
        return self::row($module)['id'];
    }

    /** @return array{prefix: mixed, id: mixed} */
    private static function row(string $module): array
    {
        if (! array_key_exists($module, self::$prefix)) {
            $r = DB::table('prefix')->where('name', $module)->first(['prefix', 'id']);
            self::$prefix[$module] = ['prefix' => $r->prefix ?? null, 'id' => $r->id ?? null];
        }

        return self::$prefix[$module];
    }

    public static function flush(): void
    {
        self::$prefix = [];
        Lookup::flush();
    }

    /**
     * MakePaging() เดิม: LIMIT (10*page-10), 10 — ค่าติดลบ/ไม่ใช่จำนวนเต็ม = SQL error ของ mysqli → ไม่มีแถว (null)
     */
    public static function offset(mixed $page, int $perPage = 10): ?int
    {
        $start = ($perPage * Php74::num($page)) - $perPage;
        if ($start < 0 || (is_float($start) && floor($start) != $start)) {
            return null;
        }

        return (int) $start;
    }

    /**
     * แถว balance (+ prefix.link หรือ link_admin) ของรายการ — เดิมไม่มี ORDER BY (ได้ลำดับ PK) จึงเรียง id
     *
     * @param  list<mixed>  $prefixIds
     */
    public static function balances(array $prefixIds, mixed $refId, bool $adminLink, bool $onlyActive = true): Collection
    {
        $q = DB::table('balance')->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->whereIn('balance.prefix', array_map(fn ($v) => (string) ($v ?? ''), $prefixIds))
            ->where('balance.prefix_ref_id', $refId)
            ->orderBy('balance.id');
        if ($onlyActive) {
            $q->where('balance.is_delete', 0);
        }

        return $q->get(['balance.*', ($adminLink ? 'prefix.link_admin' : 'prefix.link').' as link']);
    }

    /**
     * SR (_transfer/form.php, _admin/_transfer/form.php): แถว balance (บาท) ตามด้วยแถว balance_cn (หยวน) ของรายการ — `types` บอกตารางที่มา
     * (balanceTable แสดงยอดหยวนด้วย numberformat(x, true, true, true) แบบเดิม) · เดิมทั้งสองไฟล์ใช้ prefix.link (แอดมินด้วย)
     */
    public static function balancesWithCn(mixed $prefixId, mixed $refId): Collection
    {
        $rows = fn (string $t) => DB::table($t)->leftJoin('prefix', $t.'.prefix', '=', 'prefix.id')
            ->where($t.'.prefix', (string) ($prefixId ?? ''))->where($t.'.prefix_ref_id', $refId)->where($t.'.is_delete', 0)
            ->orderBy($t.'.id')->get([$t.'.*', 'prefix.link as link'])->each(fn ($r) => $r->types = $t);

        return $rows('balance')->concat($rows('balance_cn'))->values();
    }

    /**
     * $html_payment ของ form.php เดิม (ตารางรายการเงินที่เกี่ยวข้อง)
     *
     * @param  array{col: string, w1: string, w2: string, br: string, tr: bool}  $o
     *   col = class ของกล่อง, w1/w2 = ความกว้างคอลัมน์วันที่/จำนวน, br = "<br />" หรือ "<br/>", tr = เปิด <tr> ต่อแถว (refill ลูกค้าเดิมไม่เปิด)
     */
    public static function balanceTable(Collection $rows, array $o): string
    {
        $href = Legacy::href();
        $h = '<div class="'.$o['col'].'">
        <div class="card-box">
            <h4 class="m-t-0 header-title"><b>'.Lang::get('ref_balance_list').'</b></h4>
            <p class="text-muted font-13 m-b-30">
            </p>
            <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: '.$o['w1'].';">'.Lang::get('working_date').'</th>
                            <th class="text-center">'.Lang::get('detail').'</th>
                            <th class="text-center" style="width: '.$o['w2'].';">'.Lang::get('amount').'</th>
                        </tr>
                    </thead>
                    <tbody>';
        if ($rows->isNotEmpty()) {
            foreach ($rows as $item) {
                $exp = explode(' ', (string) $item->create_date);
                $h .= ($o['tr'] ? '<tr>' : '').'<td>'.Format::getthaidate($exp[0]).$o['br'].e($exp[1] ?? '').'</td>';
                $h .= '<td class="text-left"><a target="_blank" href="'.e($href.$item->link.$item->prefix_ref_id).'">'.e($item->detail).'</a></td>';
                $h .= '<td class="text-right">';
                $cn = ($item->types ?? 'balance') === 'balance_cn';
                $amt = $cn ? Format::numberformat($item->amount, true, true, true) : Format::numberformat($item->amount);
                if ((float) $item->amount >= 0) {
                    $h .= '<span class="text-success">+'.$amt.'</span>';
                } else {
                    $h .= '<span class="text-danger">'.$amt.'</span>';
                }
                $h .= '</td>';
            }
        } else {
            $h .= '<tr><td colspan="3" class="text-center">'.Lang::get('no_data').'</td></tr>';
        }
        $h .= '
                </tbody>
            </table>
        </div>
    </div>';

        return $h;
    }

    /** "-<br />-" หรือ วันที่ไทยสั้น + เวลา (grid เดิม) */
    public static function gridDate(mixed $v): string
    {
        if (Php74::eq($v, '0000-00-00 00:00:00') || Php74::eq($v, '')) {
            return '-<br />-';
        }
        $exp = explode(' ', (string) $v);

        return Format::getthaidate($exp[0], true).'<br />'.Format::gettime(e($exp[1] ?? ''));
    }

    /** วันที่ + " / " + เวลา (form แอดมินเดิม) */
    public static function formDate(mixed $v): string
    {
        if (Php74::eq($v, '0000-00-00 00:00:00') || Php74::eq($v, '')) {
            return '-<br />-';
        }
        $exp = explode(' ', (string) $v);

        return Format::getthaidate($exp[0], true, true).' / '.e($exp[1] ?? '');
    }
}
