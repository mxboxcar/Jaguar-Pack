<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * query ช่วยของ grid ที่ตารางใหญ่ (balance ~600k แถว, refill ~180k แถวบน production)
 *
 * ผลต้องเท่ากับ query เดิมทุกค่า — ที่นี่เปลี่ยนแค่ "วิธีนับ/วิธีรวม" ให้ MySQL อ่านน้อยลง (ดู docs/db-index-proposal.md)
 */
final class GridQuery
{
    /**
     * COUNT(*) ของแถว is_delete = 0 ที่ตรง $where
     *
     *   = COUNT(ตรง $where) − COUNT(ตรง $where และ (is_delete IS NULL OR is_delete <> 0))
     *
     * ทำไม: `WHERE user = ? AND is_delete = 0` ต้องเปิดแถวจริงทุกแถว (is_delete ไม่อยู่ใน idx_user) — ลูกค้า 37k แถว ≈ 150 ms
     * ส่วนแรกนับจาก index อย่างเดียว (idx_user / intersect กับ idx_balance_type / idx_refill_user ที่มี id อยู่แล้ว)
     * ส่วนหลังใช้ index (is_delete, …) ที่มีแถวถูกลบน้อยมาก (production: balance 4,385 / refill 0)
     * นับทั้งสองส่วนใน statement เดียว (snapshot เดียวกัน)
     *
     * $where ต้องอ้างคอลัมน์ด้วยชื่อตาราง ($table.col) และห้ามมีเงื่อนไข is_delete เอง
     */
    public static function liveCount(string $table, Closure $where): int
    {
        $all = DB::table($table)->selectRaw('COUNT(*)');
        $where($all);
        $dead = DB::table($table)->selectRaw('COUNT(*)')
            ->where(fn (Builder $w) => $w->whereNull($table.'.is_delete')->orWhere($table.'.is_delete', '<>', 0));
        $where($dead);
        $row = DB::query()->selectSub($all, 'a')->selectSub($dead, 'd')->first();

        return (int) $row->a - (int) $row->d;
    }

    /**
     * ยอดสะสมก่อนหน้าของแถวในหน้า ($item->prev_sum) — แทน dependent subquery ต่อแถวเดิม
     *   (SELECT SUM(b2.amount) FROM $table b2 WHERE b2.id < row.id AND b2.user = <เจ้าของ> AND b2.is_delete = 0)
     * ซึ่ง MySQL รันใหม่ทุกแถว = อ่านประวัติทั้งหมดของลูกค้า 10 รอบต่อหน้า
     *
     * ที่นี่ statement เดียว อ่านประวัติของเจ้าของแต่ละคนรอบเดียว (ทุกค่าเป็น DECIMAL ใน MySQL — ไม่บวกเลขใน PHP):
     *   ต่อเจ้าของ u ที่มี id ในหน้า x0 < x1 < … < xm
     *     bucket 0 = SUM(amount) WHERE user = u AND is_delete = 0 AND id < x0          (ยอดยกมา — SUM ธรรมดา ไม่มี CASE ต่อแถว)
     *     bucket k = SUM(amount) ของ x(k-1) <= id < xk                                  (เฉพาะช่วงระหว่างแถวในหน้า)
     *   prev_sum(xk) = SUM(bucket 0..k) OVER (PARTITION BY u ORDER BY k) — ไม่มีแถวเลย = NULL เหมือน SUM เดิม
     *
     * @param  Closure|null  $owner  fn(object $item): int = ค่า b2.user ของแถวนั้น · null = ไม่กรอง user (balance_company)
     */
    public static function attachPrevSums(string $table, Collection $items, ?Closure $owner): void
    {
        if ($items->isEmpty()) {
            return;
        }
        $t = '`'.str_replace('`', '', $table).'`';
        $groups = [];
        foreach ($items as $item) {
            $groups[$owner === null ? 0 : (int) $owner($item)][] = (int) $item->id;
        }
        $parts = [];
        $bind = [];
        foreach ($groups as $u => $ids) {
            $ids = array_values(array_unique($ids));
            sort($ids);
            $userSql = $owner === null ? '' : ' AND `user` = ?';
            $userBind = $owner === null ? [] : [$u];
            $parts[] = "SELECT ? AS u, 0 AS k, SUM(`amount`) AS s FROM $t WHERE `is_delete` = 0$userSql AND `id` < ?";
            array_push($bind, $u, ...$userBind);
            $bind[] = $ids[0];
            if (count($ids) > 1) {
                $case = [];
                $caseBind = [];
                for ($k = 1; $k < count($ids); $k++) {
                    $case[] = 'WHEN `id` < ? THEN '.$k;
                    $caseBind[] = $ids[$k];
                }
                $parts[] = 'SELECT ? AS u, CASE '.implode(' ', $case).' END AS k, SUM(`amount`) AS s'
                    ." FROM $t WHERE `is_delete` = 0$userSql AND `id` >= ? AND `id` < ? GROUP BY k";
                array_push($bind, $u, ...$caseBind, ...$userBind);
                array_push($bind, $ids[0], $ids[count($ids) - 1]);
            }
        }
        $rows = DB::select('SELECT u, k, SUM(s) OVER (PARTITION BY u ORDER BY k) AS cum FROM ('
            .implode(' UNION ALL ', $parts).') x', $bind);

        $cum = [];
        foreach ($rows as $r) {
            $cum[(int) $r->u][(int) $r->k] = $r->cum;
        }
        $prev = [];
        foreach ($groups as $u => $ids) {
            $ids = array_values(array_unique($ids));
            sort($ids);
            $last = null;
            foreach ($ids as $k => $id) {
                // bucket ว่าง (ไม่มีแถวระหว่าง x(k-1) กับ xk) = ยอดเท่ากับ bucket ก่อนหน้า
                $last = array_key_exists($k, $cum[$u] ?? []) ? $cum[$u][$k] : $last;
                $prev[$u][$id] = $last;
            }
        }
        foreach ($items as $item) {
            $item->prev_sum = $prev[$owner === null ? 0 : (int) $owner($item)][(int) $item->id];
        }
    }
}
