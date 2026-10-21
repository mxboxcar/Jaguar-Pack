<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Balance\Admin\AdminBalanceController;
use App\Http\Controllers\Balance\BalanceController;
use App\Http\Controllers\MoneyMove\Admin\AdminRefillController;
use App\Http\Controllers\MoneyMove\RefillController;
use App\Models\User;
use App\Support\GridQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * grid ตารางใหญ่ (balance / refill): GridQuery ต้องได้ค่าเท่ากับ query เดิมทุกค่า + จำนวน query ต่อ request คงที่
 * รันกับ admin_pack_bench (opt-in JL_BENCH_DB_TESTS=1) — อ่านข้อมูลจริงอย่างเดียว; แถวทดสอบเฉพาะ balance_company ใน transaction ที่ rollback
 */
class GridPerfDbTest extends BenchDbTestCase
{
    /** ยอดก่อนหน้าแบบเดิม (dependent subquery ต่อแถว) */
    private function legacyPrev(string $table, int $id, ?int $user): ?string
    {
        $q = DB::table($table)->where('id', '<', $id)->where('is_delete', 0);
        if ($user !== null) {
            $q->where('user', $user);
        }
        $v = $q->selectRaw('SUM(amount) AS s')->value('s');

        return $v === null ? null : (string) $v;
    }

    private function busiestUser(string $table): int
    {
        return (int) DB::table($table)->where('is_delete', 0)->groupBy('user')->orderByRaw('COUNT(*) DESC')->value('user');
    }

    private function assertSameMoney(?string $want, mixed $got, string $msg): void
    {
        $this->assertSame($want === null, $got === null, $msg.' (NULL)');
        if ($want !== null) {
            $this->assertSame(bcadd($want, '0', 2), bcadd((string) $got, '0', 2), $msg);
        }
    }

    public function test_prev_sums_match_per_row_subquery_for_customer_pages(): void
    {
        $uid = $this->busiestUser('balance');
        $base = fn () => DB::table('balance')->where('is_delete', 0)->where('user', $uid)->orderByDesc('id');
        // SR bench เล็กกว่า jl (ลูกค้าที่มีรายการมากสุด ~350 แถว) → หน้าลึก = กลางประวัติ (ไม่เกิน offset 500 แบบเดิม)
        $deep = min(500, intdiv($base()->count(), 2));
        // ประเภทที่มีน้อยที่สุด (≥ 2 แถว) = แถวในหน้าห่างกัน (เดิม type 12 ซึ่ง SR bench ไม่มี)
        $type = DB::table('balance')->where('is_delete', 0)->where('user', $uid)->groupBy('type')->havingRaw('COUNT(*) >= 2')
            ->orderByRaw('COUNT(*)')->value('type');
        $pages = [
            'หน้า 1' => $base()->limit(10)->get(['id', 'amount']),
            'หน้าลึก' => $base()->offset($deep)->limit(10)->get(['id', 'amount']),
            // แถวในหน้าห่างกัน (กรองประเภท) = มีแถวอื่นคั่นระหว่างแถวในหน้า
            'กรองประเภท' => $base()->where('type', $type)->limit(10)->get(['id', 'amount']),
            'แถวเดียว' => $base()->limit(1)->get(['id', 'amount']),
        ];
        foreach ($pages as $name => $items) {
            $this->assertNotEmpty($items, $name);
            GridQuery::attachPrevSums('balance', $items, fn () => $uid);
            foreach ($items as $it) {
                $this->assertSameMoney($this->legacyPrev('balance', (int) $it->id, $uid), $it->prev_sum, "$name id {$it->id}");
            }
        }
    }

    public function test_prev_sums_match_per_row_subquery_for_admin_page_with_many_owners(): void
    {
        $items = DB::table('balance')->leftJoin('user', 'user.id', '=', 'balance.user')
            ->where('balance.is_delete', 0)->orderByDesc('balance.id')->offset(20)->limit(10)
            ->get(['balance.id', 'balance.amount', 'user.id as user_id']);
        $this->assertGreaterThan(1, $items->pluck('user_id')->unique()->count(), 'ควรมีหลายเจ้าของในหน้า');
        GridQuery::attachPrevSums('balance', $items, fn ($i) => (int) ($i->user_id ?? 0));
        foreach ($items as $it) {
            $this->assertSameMoney($this->legacyPrev('balance', (int) $it->id, (int) ($it->user_id ?? 0)), $it->prev_sum, "id {$it->id}");
        }
    }

    public function test_prev_sums_without_owner_handle_deleted_null_and_empty_history(): void
    {
        $rows = [[0, '100.10'], [1, '999.99'], [null, '5.00'], [0, '-30.05'], [0, '0.01'], [2, '7.00'], [0, '12.34']];
        $ids = [];
        foreach ($rows as [$del, $amt]) {
            $ids[] = (int) DB::table('balance_company')->insertGetId(['create_date' => DB::raw('NOW()'), 'create_by' => 0, 'mod_date' => DB::raw('NOW()'),
                'mod_by' => 0, 'is_delete' => $del, 'amount' => $amt, 'detail' => 'zz_gridperf']);
        }
        $min = min($ids);
        // ทุกแถว (รวมแถวแรกที่ไม่มีประวัติก่อนหน้าใน zz) + แถวห่างๆ
        foreach ([$ids, [$ids[0]], [$ids[6], $ids[2], $ids[0]]] as $pick) {
            $items = DB::table('balance_company')->whereIn('id', $pick)->orderByDesc('id')->get(['id', 'amount']);
            GridQuery::attachPrevSums('balance_company', $items, null);
            foreach ($items as $it) {
                $this->assertSameMoney($this->legacyPrev('balance_company', (int) $it->id, null), $it->prev_sum, "id {$it->id}");
            }
        }
        // ไม่มีแถวก่อนหน้าเลย = NULL (เหมือน SUM เดิม) — ตารางนี้ว่างก่อน test ก็จะเป็น NULL ที่แถวแรก
        if ((int) DB::table('balance_company')->where('id', '<', $min)->count() === 0) {
            $items = DB::table('balance_company')->where('id', $min)->get(['id', 'amount']);
            GridQuery::attachPrevSums('balance_company', $items, null);
            $this->assertNull($items[0]->prev_sum);
        }
    }

    public function test_live_count_equals_plain_count(): void
    {
        $uid = $this->busiestUser('balance');
        $cases = [
            ['balance', fn (Builder $q) => $q->where('balance.user', $uid)],
            ['balance', fn (Builder $q) => $q->where('balance.user', $uid)->where('balance.type', '1')],
            ['balance', fn (Builder $q) => $q->where('balance.user', $uid)->where(fn ($w) => $w->where('balance.id', 'like', '%12%')->orWhere('balance.detail', 'like', '%12%'))],
            ['balance', fn (Builder $q) => $q],
            ['balance', fn (Builder $q) => $q->where('balance.type', 12)],
            ['refill', fn (Builder $q) => $q->where('refill.user', $this->busiestUser('refill'))],
            ['refill', fn (Builder $q) => $q->where('refill.user', $this->busiestUser('refill'))->where('refill.id', 'like', '%17%')],
            ['refill', fn (Builder $q) => $q->where(fn ($w) => $w->where('refill.id', 'like', '%JL0%')
                ->orWhereIn('refill.user', DB::table('user')->select('id')->where('code', 'like', '%JL0%')))],
        ];
        foreach ($cases as $i => [$table, $where]) {
            $plain = DB::table($table)->where("$table.is_delete", '0');
            $where($plain);
            $this->assertSame($plain->count(), GridQuery::liveCount($table, $where), "case $i");
        }
    }

    /** @return list<string> */
    private function queriesOf(callable $fn): array
    {
        $log = [];
        DB::listen(function ($e) use (&$log) {
            $log[] = $e->sql;
        });
        $fn();

        return $log;
    }

    public function test_grids_run_a_constant_number_of_queries_without_per_row_sums(): void
    {
        $cust = User::query()->findOrFail($this->busiestUser('balance'));
        $this->actAs($cust);
        foreach ([
            [BalanceController::class, ['page' => '1', 'txtsearch' => '']],
            [BalanceController::class, ['page' => '3', 'txtsearch' => '|12']],
            [RefillController::class, ['page' => '1', 'txtsearch' => '']],
        ] as [$ctl, $post]) {
            $html = '';
            $sql = $this->queriesOf(function () use ($ctl, $post, &$html) {
                $html = $this->act($ctl, 'grid', $post);
            });
            $this->assertStringContainsString('<tbody>', $html, $ctl);
            $this->assertLessThanOrEqual(8, count($sql), $ctl.': '.implode("\n", $sql));
            foreach ($sql as $q) {
                $this->assertStringNotContainsString('b2.', $q, 'ห้ามมี dependent subquery ต่อแถว');
            }
        }

        // แอดมินที่ใช้ bench (role 1)
        $admin = User::query()->find(11938);
        if ($admin === null) {
            $this->markTestSkipped('bench ไม่มีแอดมิน 11938');
        }
        $this->actAs($admin);
        $this->assertTrue(perm('admin_balance') && perm('admin_refill'));
        foreach ([AdminBalanceController::class, AdminRefillController::class] as $ctl) {
            $sql = $this->queriesOf(fn () => $this->act($ctl, 'grid', ['page' => '1', 'txtsearch' => '']));
            $this->assertLessThanOrEqual(8, count($sql), $ctl.': '.implode("\n", $sql));
            foreach ($sql as $q) {
                $this->assertStringNotContainsString('b2.', $q);
                $this->assertDoesNotMatchRegularExpression('/count\(\*\).*left join/i', $q, 'COUNT ห้าม join ตารางแสดงผล');
            }
        }
    }
}
