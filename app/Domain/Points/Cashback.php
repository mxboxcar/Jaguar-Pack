<?php

namespace App\Domain\Points;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ของที่ใช้ร่วมกันระหว่าง _shop_cashback (ลูกค้า) กับ _admin/_shop_cashback (แอดมิน)
 */
final class Cashback
{
    /** str_replace(strtolower(getPrefix('shop_cashback')), '', strtolower($term)) */
    public static function searchTerm(string $term): string
    {
        $prefix = strtolower(Grid::prefixText('shop_cashback'));
        $term = strtolower($term);

        return $prefix === '' ? $term : str_replace($prefix, '', $term);
    }

    /**
     * รายการย่อย (price, status) ของหลายหัวรายการในคำสั่งเดียว — เดิม SELECT 1 ครั้งต่อแถวของ grid (N+1, ไม่มี index = full scan ทุกแถว)
     *
     * @param  iterable<object>  $items
     * @return Collection<int|string, Collection<int, object>> keyed by shop_cashback_id
     */
    public static function detailsFor(iterable $items): Collection
    {
        $ids = collect($items)->pluck('id')->all();
        if ($ids === []) {
            return collect();
        }

        return DB::table('shop_cashback_detail')->whereIn('shop_cashback_id', $ids)->orderBy('id')
            ->get(['shop_cashback_id', 'price', 'status'])->groupBy('shop_cashback_id');
    }

    /** HTML ยอดย่อยใน grid: <div>¥ราคา <ไอคอนสถานะ> </div> ต่อรายการ */
    public static function detailHtml(?Collection $rows): string
    {
        $x = '';
        foreach ($rows ?? [] as $d) {
            $x .= '<div>¥'.e((string) $d->price);
            if (Php74::eq($d->status, 2)) {
                $x .= ' <i class="fa fa-check text-success"></i>';
            } elseif (Php74::eq($d->status, 1)) {
                $x .= ' <i class="fa fa-circle-o-notch text-info"></i>';
            } elseif (Php74::eq($d->status, -1)) {
                $x .= ' <i class="fa fa-close text-danger"></i>';
            }
            $x .= ' </div>';
        }

        return $x;
    }

    /**
     * รายการบัญชีบาท + หยวนที่อ้างถึงหัวรายการนี้ เรียงตาม create_date (usort sortFunction เดิม)
     *
     * @return Collection<int, object> แต่ละแถวมี ->kind = 'balance' | 'balance_cn'
     */
    public static function ledger(int $id): Collection
    {
        $prefix = Grid::prefixId('shop_cashback');
        $rows = collect();
        foreach (['balance', 'balance_cn'] as $table) {
            $rows = $rows->concat(DB::table($table)->leftJoin('prefix', $table.'.prefix', '=', 'prefix.id')
                ->where($table.'.prefix', $prefix)->where($table.'.prefix_ref_id', $id)->where($table.'.is_delete', 0)
                ->orderBy($table.'.id')
                ->get([$table.'.*', 'prefix.link as link'])
                ->map(fn ($r) => tap($r, fn ($r) => $r->kind = $table)));
        }

        // usort(..., sortFunction) = strtotime(a) - strtotime(b); sort ของ PHP 8 เสถียร (แถวเวลาเท่ากันคงลำดับเดิม)
        $all = $rows->all();
        usort($all, fn ($a, $b) => (int) strtotime((string) $a->create_date) <=> (int) strtotime((string) $b->create_date));

        return collect($all);
    }

    /** $prices = $prices + $item['price'] (เริ่มจาก null แบบเดิม) */
    public static function sum(iterable $rows, ?callable $filter = null): int|float|null
    {
        $sum = null;
        foreach ($rows as $r) {
            if ($filter === null || $filter($r)) {
                $sum = Php74::num($sum) + Php74::num($r->price);
            }
        }

        return $sum;
    }
}
