<?php

namespace Tests\Feature\Balance;

use App\Domain\Balance\Withdraw;
use App\Http\Controllers\Balance\BalanceController;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * กลุ่ม balance ของ SR บน admin_sr_bench (transaction ที่ rollback, opt-in JL_BENCH_DB_TESTS=1)
 */
class BalanceSrDbTest extends BenchDbTestCase
{
    protected function tearDown(): void
    {
        Lookup::flush();
        parent::tearDown();
    }

    private function withdrawRow(int $user, string $createDate, array $extra = []): int
    {
        return $this->insertRow('balance_withdraw', $extra + ['create_date' => $createDate, 'status' => 1, 'user' => $user, 'amount' => 10,
            'attachment' => '', 'remark' => 'zz_sr', 'approved_remark' => '', 'cancel_remark' => '', 'approved_upload' => '',
            'branch_no' => '', 'branch_bank' => '', 'branch_name' => '']);
    }

    public function test_one_withdrawal_per_week_with_bypass_setting(): void
    {
        $u = $this->zzUser(8);
        $now = strtotime('2026-10-21 10:00:00'); // สัปดาห์ 2026-10-19 – 2026-10-25
        $this->withdrawRow($u->id, '2026-10-18 23:59:59'); // สัปดาห์ก่อน
        $this->withdrawRow($u->id, '2026-10-26 00:00:00'); // สัปดาห์หน้า
        $this->withdrawRow($u->id, '2026-10-20 09:00:00', ['is_delete' => 1]); // ถูกลบ
        $this->assertTrue(Withdraw::weeklyAllowed($u->id, $u->code, $now));

        $this->withdrawRow($u->id, '2026-10-25 23:59:59', ['status' => -1]); // ยกเลิกแล้วก็นับ (เดิมไม่กรอง status)
        $this->assertFalse(Withdraw::weeklyAllowed($u->id, $u->code, $now));

        // setting bypass_withdraw = รหัสลูกค้าคั่นด้วย , (เทียบตรงทั้งค่า ไม่ trim)
        $sid = $this->insertRow('setting', ['key' => 'bypass_withdraw', 'value' => 'ZZ_OTHER, '.$u->code]);
        Lookup::flush();
        $this->assertFalse(Withdraw::weeklyAllowed($u->id, $u->code, $now), 'มีช่องว่างหน้ารหัส = ไม่ตรง');
        DB::table('setting')->where('id', $sid)->update(['value' => 'ZZ_OTHER,'.$u->code]);
        Lookup::flush();
        $this->assertTrue(Withdraw::weeklyAllowed($u->id, $u->code, $now));
    }

    public function test_grid_shows_cancel_reason_of_cancelled_withdrawal(): void
    {
        $u = $this->zzUser(8);
        $this->actAs($u);
        if (! perm('balance')) {
            $this->markTestSkipped('role 8 ไม่มีสิทธิ์ balance');
        }
        $prefix = (int) DB::table('prefix')->where('name', 'withdrawcash')->value('id');
        $w1 = $this->withdrawRow($u->id, '2026-09-01 10:00:00', ['status' => -1, 'cancel_remark' => 'zz_wrong <acct> ,ยกเลิกโดย ZZ']);
        $w2 = $this->withdrawRow($u->id, '2026-09-01 10:00:00', ['status' => 1, 'cancel_remark' => 'zz_not_shown']);
        foreach ([$w1, $w2] as $w) {
            $this->insertRow('balance', ['user' => $u->id, 'amount' => -10, 'detail' => 'zz_w'.$w, 'type' => 12, 'prefix' => $prefix, 'prefix_ref_id' => $w]);
        }
        $html = $this->act(BalanceController::class, 'grid', ['page' => '1', 'txtsearch' => '']);
        $this->assertStringContainsString(' (zz_wrong &lt;acct&gt; )', $html);
        $this->assertStringNotContainsString('ยกเลิกโดย', $html);
        $this->assertStringNotContainsString('zz_not_shown', $html);
    }
}
