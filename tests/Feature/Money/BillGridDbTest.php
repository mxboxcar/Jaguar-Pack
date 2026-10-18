<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Payment\Admin\AdminPaymentController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Reports\Admin\AdminExportController;
use App\Http\Controllers\Reports\ExportController;
use App\Models\User;
use App\Support\GridQuery;
use Illuminate\Support\Facades\DB;

/**
 * grid บิล (bill_payment ~31k แถวบน bench, ไม่มี index `user`): GridQuery::idPage + rowsInOrder ต้องได้จำนวนและแถวของหน้า
 * เท่ากับ query เดิม (LEFT JOIN + COUNT(*) แล้ว ORDER BY id DESC LIMIT) ทุกกรณี และอ่าน bill_payment รอบเดียวต่อ request
 * รันกับ admin_jl_bench (opt-in JL_BENCH_DB_TESTS=1) — อ่านอย่างเดียว
 */
class BillGridDbTest extends BenchDbTestCase
{
    private function busiest(): int
    {
        return (int) DB::table('bill_payment')->where('is_delete', 0)->groupBy('user')->orderByRaw('COUNT(*) DESC')->value('user');
    }

    /** query เดิมของ _admin/_export/grid (join + OR user.code LIKE) */
    private function legacyAdminExport(string $s, ?string $status)
    {
        $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';
        $q = DB::table('bill_payment')
            ->leftJoin('shipping_type', 'bill_payment.shipping_type', '=', 'shipping_type.id')
            ->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.is_delete', 0)
            ->where(fn ($w) => $w->where('bill_payment.id', 'like', $like(str_replace('pay-', '', $s)))
                ->orWhere('user.code', 'like', $like($s))->orWhere('bill_payment.send_name', 'like', $like($s)))
            ->where(fn ($w) => $w->where('bill_payment.status', '>=', 2)->orWhere('bill_payment.status', -2));
        if ($status !== null) {
            $q->where('bill_payment.status', $status);
        }

        return $q;
    }

    /** query เดิมของ _admin/_payment/grid */
    private function legacyAdminPayment(string $s, ?string $status)
    {
        $q = DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.is_delete', 0)
            ->where(fn ($w) => $w->where('bill_payment.id', 'like', '%'.addcslashes((string) preg_replace('/^[A-Za-z]+-/', '', $s), '%_\\').'%')
                ->orWhere('user.code', 'like', addcslashes(strtolower($s), '%_\\')));
        if ($status !== null) {
            $q->where('bill_payment.status', $status);
        }

        return $q;
    }

    /** id ที่ grid แสดง (ลิงก์ไปหน้ารายละเอียด) ตามลำดับ */
    private function idsInHtml(string $html, string $path): array
    {
        preg_match_all('#'.preg_quote($path, '#').'/([0-9]+)#', $html, $m);

        return array_values(array_unique(array_map('intval', $m[1])));
    }

    private function expectPage($legacy, int $page): array
    {
        $total = (clone $legacy)->count();
        $ids = (clone $legacy)->orderByDesc('bill_payment.id')->offset(($page - 1) * 10)->limit(10)->pluck('bill_payment.id')
            ->map(fn ($v) => (int) $v)->all();

        return [$total, $ids];
    }

    public function test_id_page_equals_count_plus_limit_for_admin_filters(): void
    {
        $code = (string) DB::table('user')->where('id', $this->busiest())->value('code');
        $name = (string) DB::table('bill_payment')->where('status', 3)->whereNotNull('send_name')->where('send_name', '!=', '')
            ->orderByDesc('id')->value('send_name');
        $cases = [['', null, 1], ['', null, 3], ['', '3', 2], ['', '2', 1], [strtolower($code), null, 1], [strtolower($code), '3', 4],
            ['pay-12', null, 1], ['1235', null, 2], ['zzqq_none', null, 1], [mb_substr($name, 0, 3), null, 1], ['%', null, 1], ['_', null, 1]];
        foreach ($cases as [$s, $status, $page]) {
            $legacy = $this->legacyAdminExport($s, $status);
            [$wantTotal, $wantIds] = $this->expectPage($legacy, $page);
            $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';
            $q = DB::table('bill_payment')->where('bill_payment.is_delete', 0)
                ->where(fn ($w) => $w->where('bill_payment.id', 'like', $like(str_replace('pay-', '', $s)))
                    ->orWhereIn('bill_payment.user', DB::table('user')->select('user.id')->where('user.code', 'like', $like($s)))
                    ->orWhere('bill_payment.send_name', 'like', $like($s)))
                ->where(fn ($w) => $w->where('bill_payment.status', '>=', 2)->orWhere('bill_payment.status', -2));
            if ($status !== null) {
                $q->where('bill_payment.status', $status);
            }
            [$total, $ids] = GridQuery::idPage($q, 'bill_payment.id', ($page - 1) * 10);
            $this->assertSame($wantTotal, $total, "total s=$s st=$status p=$page");
            $this->assertSame($wantIds, $ids, "ids s=$s st=$status p=$page");
            $rows = GridQuery::rowsInOrder(DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id'),
                'bill_payment.id', $ids, ['bill_payment.*', 'user.code']);
            $this->assertSame($wantIds, $rows->pluck('id')->map(fn ($v) => (int) $v)->all());
            $this->assertSame((clone $legacy)->orderByDesc('bill_payment.id')->offset(($page - 1) * 10)->limit(10)->pluck('user.code')->all(),
                $rows->pluck('code')->all(), "code s=$s");
        }
        [, $none] = GridQuery::idPage(DB::table('bill_payment'), 'bill_payment.id', null);
        $this->assertSame([], $none, 'หน้าไม่ถูกต้อง (offset null) = ไม่มีแถว');
    }

    public function test_bill_detail_rows_follow_legacy_order_not_tracking_id(): void
    {
        // เดิม SELECT … WHERE payment_id = ? ไม่มี ORDER BY → อ่านผ่าน index payment_id = เรียงตาม PK tracking_id
        $bill = DB::selectOne('SELECT payment_id FROM (SELECT payment_id, ROW_NUMBER() OVER (PARTITION BY payment_id ORDER BY tracking_id) a,'
            .' ROW_NUMBER() OVER (PARTITION BY payment_id ORDER BY id) b FROM bill_payment_detail) x WHERE a <> b ORDER BY payment_id DESC LIMIT 1');
        if ($bill === null) {
            $this->markTestSkipped('bench ไม่มีบิลที่ลำดับ tracking_id ต่างจาก id');
        }
        $id = (int) $bill->payment_id;
        $want = array_map(fn ($r) => (string) $r->tracking, DB::select('SELECT tracking FROM bill_payment_detail WHERE payment_id = ?', [$id]));
        $this->assertNotSame(DB::table('bill_payment_detail')->where('payment_id', $id)->orderBy('id')->pluck('tracking')->all(), $want);
        $data = DB::table('bill_payment')->where('id', $id)->first();
        $got = PaymentController::detailData($data, $id, true)['items']->pluck('tracking')->map(fn ($v) => (string) $v)->all();
        $this->assertSame($want, $got, "bill $id");
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

    private function assertGrid(string $ctl, array $post, string $path, $legacy, int $page): void
    {
        [$wantTotal, $wantIds] = $this->expectPage($legacy, $page);
        $html = '';
        $sql = $this->queriesOf(function () use ($ctl, $post, &$html) {
            $html = $this->act($ctl, 'grid', $post);
        });
        $label = $ctl.' '.json_encode($post, JSON_UNESCAPED_UNICODE);
        $this->assertSame($wantIds, $this->idsInHtml($html, $path), $label);
        $bill = array_values(array_filter($sql, fn ($q) => str_contains($q, 'from `bill_payment`')));
        $this->assertCount($wantIds === [] ? 1 : 2, $bill, $label.': ids รอบเดียว + 10 แถว (ไม่มีแถว = ไม่โหลด)'."\n".implode("\n", $bill));
        foreach ($sql as $q) {
            $this->assertDoesNotMatchRegularExpression('/count\(\*\)/i', $q, $label);
        }
        if ($wantTotal > 10) {
            // MakePaging แสดงจำนวนหน้า = ceil(total / 10)
            $this->assertStringContainsString((string) (int) ceil($wantTotal / 10), $html, $label);
        }
    }

    public function test_bill_grids_show_the_same_rows_as_legacy_queries(): void
    {
        $uid = $this->busiest();
        $this->actAs(User::query()->findOrFail($uid));
        $cust = fn (string $s, ?array $st) => DB::table('bill_payment')->where('bill_payment.is_delete', 0)
            ->where('bill_payment.id', 'like', '%'.$s.'%')->where('bill_payment.user', $uid)
            ->when($st !== null, fn ($q) => $q->whereIn('bill_payment.status', $st));
        foreach ([['', 1, ['2', '3'], ''], ['', 3, ['2', '3'], '|-2'], ['1', 2, ['3'], '1|3'], ['', 1, ['-1'], '|-1']] as [$s, $p, $st, $txt]) {
            $this->assertGrid(PaymentController::class, ['page' => (string) $p, 'txtsearch' => $txt === '' ? '|' : $txt], 'payment', $cust($s, $st), $p);
        }
        foreach ([['', 1, null, ''], ['', 3, null, '|-2'], ['1', 2, ['3'], '1|3']] as [$s, $p, $st, $txt]) {
            $this->assertGrid(ExportController::class, ['page' => (string) $p, 'txtsearch' => $txt], 'export', $cust($s, $st), $p);
        }

        $admin = User::query()->find(11938);
        if ($admin === null) {
            $this->markTestSkipped('bench ไม่มีแอดมิน 11938');
        }
        $this->actAs($admin);
        $code = strtolower((string) DB::table('user')->where('id', $uid)->value('code'));
        foreach ([['', null, 1], ['', '-1', 2], [$code, null, 2], ['PAY-1235', null, 1], ['zzqq', null, 1]] as [$s, $st, $p]) {
            $this->assertGrid(AdminPaymentController::class, ['page' => (string) $p, 'txtsearch' => $s.'|'.($st ?? '-2')],
                'admin/payment', $this->legacyAdminPayment($s, $st), $p);
        }
        foreach ([['', null, 1], ['', '3', 3], [$code, null, 2], ['pay-1235', null, 1], ['zzqq', null, 1]] as [$s, $st, $p]) {
            $this->assertGrid(AdminExportController::class, ['page' => (string) $p, 'txtsearch' => $s.'|'.($st ?? '-2')],
                'admin/export', $this->legacyAdminExport($s, $st), $p);
        }
    }
}
