<?php

namespace App\Domain\Users;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ประวัติการแก้ไขราคาของลูกค้า: _admin/_user/rate_history_lib.php เดิม (เพิ่ม 17 ต.ค. 2026)
 * save จด snapshot ก่อนบันทึก แล้วหลังบันทึกเทียบ ถ้าเปลี่ยนจริงจึง INSERT ลง user_rate_history
 *
 * ต่างจากเดิม: เดิม CREATE TABLE IF NOT EXISTS เองทุกครั้งที่เขียน — ที่นี่ไม่แก้ schema (CONVENTIONS ข้อ 7) ถ้าไม่มีตารางก็ข้าม
 */
final class RateHistory
{
    /** __rh_snapshot() */
    public static function snapshot(int $uid): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $u = DB::table('user')->where('id', $uid)->first(['type', 'option_price']);
        if (! $u) {
            return null;
        }
        $tid = (int) $u->type;
        $rates = [];
        $rows = DB::table('user_type_detail as d')->join('product_type as p', 'p.id', '=', 'd.product_type_id')
            ->where('d.user_type_id', $tid)->where('d.is_delete', 0)
            ->get(['p.name as pn', 'd.type as lt', 'd.kg', 'd.cubic']);
        foreach ($rows as $x) {
            $rates[$x->pn.'|'.(int) $x->lt] = [(float) $x->kg, (float) $x->cubic];
        }
        $gname = '';
        $g = DB::table('user_type as t')->where('t.id', $tid)
            ->first(['t.name', 't.child', DB::raw('(SELECT p2.`name` FROM `user_type` p2 WHERE p2.`id` = t.`child`) AS pname')]);
        if ($g) {
            $gname = ((int) $g->child > 0) ? (string) $g->pname : (string) $g->name;
        }

        return ['type' => $tid, 'option' => (int) $u->option_price, 'group' => $gname, 'rates' => $rates];
    }

    private static function fmt(mixed $v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
    }

    /** __rh_diff() */
    public static function diff(?array $b, array $a): array
    {
        $lines = [];
        $opt = fn ($o) => $o == 1 ? 'ตั้งเอง' : 'ใช้จากค่าเริ่มต้น';
        if ($b === null) {
            return ['สร้างลูกค้าใหม่ — กลุ่ม: '.$a['group'].' · ประเภท: '.$opt($a['option'])];
        }
        if ($b['group'] !== $a['group']) {
            $lines[] = 'กลุ่ม: '.($b['group'] === '' ? '-' : $b['group']).' → '.($a['group'] === '' ? '-' : $a['group']);
        }
        if ($b['option'] != $a['option']) {
            $lines[] = 'ประเภท: '.$opt($b['option']).' → '.$opt($a['option']);
        }
        $keys = array_values(array_unique(array_merge(array_map('strval', array_keys($b['rates'])), array_map('strval', array_keys($a['rates'])))));
        sort($keys, SORT_STRING);
        foreach ($keys as $k) {
            $o = $b['rates'][$k] ?? null;
            $n = $a['rates'][$k] ?? null;
            if ($o !== null && $n !== null && abs($o[0] - $n[0]) < 0.0001 && abs($o[1] - $n[1]) < 0.0001) {
                continue;
            }
            $p = explode('|', $k);
            $lbl = $p[0].' ('.((int) ($p[1] ?? 0) === 1 ? 'เรือ' : 'รถ').')';
            $lines[] = $lbl.': '.($o === null ? '-' : self::fmt($o[0]).' / '.self::fmt($o[1])).' → '.($n === null ? '-' : self::fmt($n[0]).' / '.self::fmt($n[1])).'  (กก. / คิว)';
        }

        return $lines;
    }

    /** register_shutdown_function ของ rate_history_begin(): เทียบแล้วเขียน (ห้ามทำให้การบันทึกลูกค้าพัง) */
    public static function record(int $target, int $actor, ?array $before): void
    {
        try {
            if ($target <= 0) {
                return;
            }
            $after = self::snapshot($target);
            if ($after === null) {
                return;
            }
            $lines = self::diff($before, $after);
            if (count($lines) === 0 || ! Schema::hasTable('user_rate_history')) {
                return;
            }
            DB::table('user_rate_history')->insert([
                'create_date' => DB::raw('NOW()'),
                'user_id' => $target,
                'actor_id' => $actor,
                'old_type' => (int) ($before ? $before['type'] : 0),
                'new_type' => (int) $after['type'],
                'old_option' => (int) ($before ? $before['option'] : 0),
                'new_option' => (int) $after['option'],
                'summary' => mb_substr(implode("\n", array_slice($lines, 0, 60)), 0, 6000, 'UTF-8'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('users: rate history failed', ['user' => $target, 'error' => $e->getMessage()]);
        }
    }
}
