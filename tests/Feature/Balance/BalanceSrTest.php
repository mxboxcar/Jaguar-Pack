<?php

namespace Tests\Feature\Balance;

use App\Domain\Balance\Withdraw;
use App\Http\Controllers\Balance\BalanceController;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * กลุ่ม balance ของ SR (2026-10-20) — ไม่แตะ DB จริง
 * - _balance/save_withdraw.php ของ SR: ถาม PIN จ่ายเงินก่อน (กล่องต่อท้าย .cashback_form, callback withdrawcashing())
 * - ถอนได้ 1 รายการต่อสัปดาห์ (จันทร์–อาทิตย์ ตาม strtotime("last monday") ของเดิม)
 */
class BalanceSrTest extends TestCase
{
    use FakeLegacyUser;

    /** ค่าจาก php7.4 ด้วยนิพจน์เดิมของ SR (Asia/Bangkok) */
    public function test_week_range_matches_php74(): void
    {
        $cases = [
            '2026-10-19 00:00:01' => ['2026-10-19', '2026-10-25'],
            '2026-10-19 23:59:59' => ['2026-10-19', '2026-10-25'],
            '2026-10-20 10:00:00' => ['2026-10-19', '2026-10-25'],
            '2026-10-24 12:00:00' => ['2026-10-19', '2026-10-25'],
            '2026-10-25 23:59:59' => ['2026-10-19', '2026-10-25'],
            '2026-10-18 12:00:00' => ['2026-10-12', '2026-10-18'],
            '2026-12-31 08:00:00' => ['2026-12-28', '2027-01-03'],
            '2027-01-04 00:30:00' => ['2027-01-04', '2027-01-10'],
        ];
        foreach ($cases as $now => $range) {
            $this->assertSame($range, Withdraw::weekRange(strtotime($now)), $now);
        }
    }

    private function withdraw(array $post, string $pin = '123456'): string
    {
        $req = $this->actAs(261, ['balance'], [], $post + ['wantcash' => '1,00', 'bank_no' => '1', 'branch_bank' => 'b', 'branch_name' => 'n'],
            '/_balance/save_withdraw', '10.1.2.5', ['pin' => $pin]);

        return (string) app(BalanceController::class)->saveWithdraw($req)->getContent();
    }

    public function test_withdraw_asks_for_pin_before_anything(): void
    {
        $this->setUpLegacyWorld();
        $ips = Mockery::mock();
        $ips->shouldReceive('insert')->andReturn(true);
        DB::shouldReceive('table')->with('ips')->andReturn($ips);
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new \Illuminate\Database\Query\Expression($v));

        $this->assertStringContainsString('textconfirmpaymentpin', $this->withdraw([], ''), 'ยังไม่มี PIN = กล่องตั้ง PIN');
        foreach (['', 'undefined'] as $p) {
            $box = $this->withdraw(['paymentpin' => $p]);
            $this->assertStringContainsString('callback : "withdrawcashing()"', $box);
            $this->assertStringContainsString('$(".cashback_form").eq(0).append(htmlpin)', $box);
        }
        // ยอด "1,00" ผิดรูปแบบ แต่ PIN ผิดตอบก่อน (ลำดับเดิม)
        $this->assertSame("ErrorBox('PIN จ่ายเงินไม่ถูกต้อง', 'CloseLoading()');", $this->withdraw(['paymentpin' => '000000']));
    }
}
