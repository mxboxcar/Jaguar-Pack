<?php

namespace Tests\Feature\Lot;

use App\Domain\Lot\LotPrint;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * _admin/_lot/print: header_count เดิมเป็น subquery ต่อแถว (t2.header = tracking.id, TEXT กับ int → ใช้ index ไม่ได้)
 * = สแกน tracking ทั้งตารางต่อแถว → ล็อต ~100 tracking ใช้ ~75 วินาที (504 ที่ nginx) — ตอนนี้นับครั้งเดียวด้วย header IN (...)
 * รันกับ admin_pack_bench (opt-in JL_BENCH_DB_TESTS=1) — แถว zz_ ใน transaction ที่ rollback ทั้งหมด
 */
class LotPrintHeaderCountDbTest extends BenchDbTestCase
{
    /** @return array{0: int, 1: array<string, int>} lot id + tracking ids ตามชื่อ */
    private function seedLot(int $parents): array
    {
        $u = $this->zzUser();
        $lot = $this->insertRow('lot', ['name' => 'zz_print_'.bin2hex(random_bytes(3)), 'type' => 0, 'status' => 0]);
        $ids = [];
        for ($i = 1; $i <= $parents; $i++) {
            $ids["p$i"] = $this->insertRow('tracking', ['tracking' => "ZZPRINT{$lot}P{$i}", 'lot_id' => $lot, 'no' => $i,
                'user' => $u->id, 'amount' => 1, 'weight' => 1.5, 'width' => 10, 'height' => 10, 'long' => 10, 'header' => '0']);
        }
        // p1: ลูก 2 แถว + ลูกที่ลบแล้ว 1 แถว (เดิมนับรวมแถวที่ลบ), p2: ไม่มีลูก
        foreach (['c1' => 0, 'c2' => 0, 'cdel' => 1] as $name => $del) {
            $ids[$name] = $this->insertRow('tracking', ['tracking' => "ZZPRINT{$lot}".strtoupper($name), 'lot_id' => $lot, 'no' => 0,
                'user' => $u->id, 'amount' => 1, 'header' => (string) $ids['p1']]);
            if ($del) {
                DB::table('tracking')->where('id', $ids[$name])->update(['is_delete' => 1]);
            }
        }

        return [$lot, $ids];
    }

    /** @return list<string> */
    private function sqlDuring(callable $fn): array
    {
        $sql = [];
        DB::listen(function ($q) use (&$sql) {
            $sql[] = $q->sql;
        });
        $fn();

        return $sql;
    }

    public function test_header_count_matches_legacy_per_row_subquery(): void
    {
        [$lot, $ids] = $this->seedLot(2);
        foreach (['p1', 'p2'] as $p) {
            $legacy = DB::selectOne('SELECT count(id) AS c FROM tracking as t2 WHERE t2.header = ?', [$ids[$p]])->c;
            $this->assertSame($p === 'p1' ? 3 : 0, (int) $legacy, "subquery เดิมของ $p");
        }

        $html = LotPrint::print([$lot]);
        // แถวแม่ p1: rowspan = header_count (3 รวมแถวที่ลบ) + 1 เหมือนเดิม, แถวลูกพิมพ์ครบ
        $this->assertMatchesRegularExpression('/rowspan="4" class="text-center">1<\/td>/', $html);
        $this->assertMatchesRegularExpression('/rowspan="1" class="text-center">2<\/td>/', $html);
        foreach (['C1', 'C2', 'CDEL'] as $c) {
            $this->assertStringContainsString("ZZPRINT{$lot}{$c}", $html);
        }
        $this->assertSame('1', (string) DB::table('lot')->where('id', $lot)->value('is_print'));
        // PACK print.php = jl: มีคอลัมน์ราคา kg/คิว (colspan 7) แต่ไม่มีเครื่องหมายตรวจแล้ว (ไม่มี tracking.is_checked)
        $this->assertStringContainsString('<th class="text-center" >'.\App\Support\Legacy\Lang::get('price_kg').'</th>', $html);
        $this->assertStringNotContainsString('&#10004;', $html);
        $this->assertStringContainsString('colspan="7">', $html);
    }

    public function test_print_counts_children_once_instead_of_per_row(): void
    {
        [$small] = $this->seedLot(1);
        [$big] = $this->seedLot(6);
        $q1 = $this->sqlDuring(fn () => LotPrint::print([$small]));
        $q6 = $this->sqlDuring(fn () => LotPrint::print([$big]));

        foreach (array_merge($q1, $q6) as $sql) {
            $this->assertStringNotContainsString('t2.header = tracking.id', $sql, 'ไม่มี subquery header ต่อแถว');
        }
        $this->assertSame(count($q1), count($q6), 'จำนวน query ไม่โตตามจำนวน tracking');
    }
}
