<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\MoneyMove\Admin\AdminRefillController;
use App\Http\Controllers\MoneyMove\Admin\AdminTransferController;
use App\Http\Controllers\MoneyMove\Admin\AdminWithdrawCashController;
use App\Http\Controllers\MoneyMove\RefillController;
use App\Http\Controllers\MoneyMove\TransferController;
use App\Http\Controllers\Payment\PaymentController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * money-move (ข้อ 9–12 ของรายงาน): สถานะที่ยอมให้เปลี่ยน + ไม่คืน/หักเงินซ้ำ — รันกับ admin_jl_bench ใน transaction ที่ rollback (opt-in)
 */
class MoneyMoveDbTest extends BenchDbTestCase
{
    /** ลูกค้าสร้างรายการโอน (ตัดเงิน usecash) ผ่าน _transfer/save จริง */
    private function transfer(User $cust, float $cash = 1000, string $total = '100'): object
    {
        $this->credit($cust, $cash);
        $this->actAs($cust);
        $out = $this->act(TransferController::class, 'save', ['total' => $total, 'acc_name' => 'zz_acc', 'acc_no' => '1', 'acc_bank' => 'zz', 'acc_branch' => 'zz', 'remark' => 'zz_test', 'upload_file' => '']);
        $this->assertStringContainsString('SuccessBox', $out);

        return DB::table('bill_transfer')->where('user', $cust->id)->orderByDesc('id')->first();
    }

    private function transferNet(object $t): float
    {
        return round((float) DB::table('balance')->where('prefix', $this->prefixId('transfer'))->where('prefix_ref_id', $t->id)->where('is_delete', 0)->sum('amount'), 2);
    }

    public function test_admin_save_refuses_a_cancelled_and_refunded_transfer(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $t = $this->transfer($cust);
        $after = $this->cash($cust);
        $this->actAs($admin);
        $this->act(AdminTransferController::class, 'cancel', ['id' => $t->id, 'cancel_remark' => 'zz']);
        $this->assertSame(round($after + (float) $t->usecash, 2), $this->cash($cust), 'ยกเลิก = คืน usecash');

        $out = $this->act(AdminTransferController::class, 'save', ['id' => $t->id, 'approved_remark' => 'zz', 'upload_approved_file' => '']);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame('-1', (string) DB::table('bill_transfer')->where('id', $t->id)->value('status'), 'ต้องยังเป็นยกเลิก');

        // รายการรอโอน → 2 และบันทึกหลักฐานซ้ำได้ (ไม่แตะเงิน)
        $t2 = $this->transfer($cust);
        $this->actAs($admin);
        $before = $this->cash($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminTransferController::class, 'save', ['id' => $t2->id, 'approved_remark' => 'zz']));
        $this->assertStringContainsString('SuccessBox', $this->act(AdminTransferController::class, 'save', ['id' => $t2->id, 'approved_remark' => 'zz2']));
        $this->assertSame('2', (string) DB::table('bill_transfer')->where('id', $t2->id)->value('status'));
        $this->assertSame($before, $this->cash($cust));
        // โอนสำเร็จแล้วยกเลิกไม่ได้ (เดิมก็ไม่ได้)
        $this->act(AdminTransferController::class, 'cancel', ['id' => $t2->id]);
        $this->assertSame($before, $this->cash($cust));
    }

    public function test_rollback_of_cancelled_transfer_deducts_once_and_checks_balance(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $t = $this->transfer($cust, 1000, '100');
        $use = (float) $t->usecash;
        $this->actAs($admin);
        $this->act(AdminTransferController::class, 'cancel', ['id' => $t->id]);
        $this->assertSame(0.0, $this->transferNet($t));

        $this->act(AdminTransferController::class, 'rollback', ['id' => $t->id]);
        $this->assertSame('1', (string) DB::table('bill_transfer')->where('id', $t->id)->value('status'));
        $this->assertSame(-$use, $this->transferNet($t), 'rollback = ตัดคืน usecash ครั้งเดียว');
        // กดซ้ำ (สถานะ 1 แล้ว) ไม่หักอีก
        $this->act(AdminTransferController::class, 'rollback', ['id' => $t->id]);
        $this->assertSame(-$use, $this->transferNet($t));
        $this->assertSame(0, DB::table('balance_cn')->where('prefix', $this->prefixId('transfer'))->where('prefix_ref_id', $t->id)->count());

        // ยกเลิกอีกรอบ → ลูกค้าใช้เงินหมด → rollback ต้องถูกปฏิเสธ (เดิมหักจนติดลบ)
        $this->act(AdminTransferController::class, 'cancel', ['id' => $t->id]);
        $this->credit($cust, -$this->cash($cust));
        $out = $this->act(AdminTransferController::class, 'rollback', ['id' => $t->id]);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame('-1', (string) DB::table('bill_transfer')->where('id', $t->id)->value('status'));
        $this->assertSame(0.0, $this->transferNet($t));

        // status 2 → 1: ไม่แตะเงิน
        $t2 = $this->transfer($cust, 1000);
        $this->actAs($admin);
        $this->act(AdminTransferController::class, 'save', ['id' => $t2->id]);
        $this->act(AdminTransferController::class, 'rollback', ['id' => $t2->id]);
        $this->assertSame('1', (string) DB::table('bill_transfer')->where('id', $t2->id)->value('status'));
        $this->assertSame(-(float) $t2->usecash, $this->transferNet($t2));
    }

    public function test_customer_cancel_refunds_once(): void
    {
        $cust = $this->zzUser();
        $t = $this->transfer($cust);
        $after = $this->cash($cust);
        $this->act(TransferController::class, 'cancel', ['id' => $t->id]);
        $this->act(TransferController::class, 'cancel', ['id' => $t->id]);
        $this->assertSame(round($after + (float) $t->usecash, 2), $this->cash($cust));
    }

    private function withdraw(User $cust, float $amount, int $status): int
    {
        $id = $this->insertRow('balance_withdraw', ['status' => $status, 'user' => $cust->id, 'amount' => $amount, 'date' => DB::raw('NOW()'),
            'attachment' => '', 'remark' => 'zz_test', 'approved_remark' => '', 'cancel_remark' => '', 'branch_no' => '1', 'branch_bank' => 'zz', 'branch_name' => 'zz']);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -$amount, 'detail' => 'zz_test withdraw', 'type' => 12, 'prefix' => $this->prefixId('withdrawcash'), 'prefix_ref_id' => $id]);

        return $id;
    }

    public function test_withdrawal_state_transitions(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $this->credit($cust, 500);
        $this->actAs($admin);

        // จ่ายแล้ว (2) ยกเลิกไม่ได้ = ไม่คืนเงิน
        $paid = $this->withdraw($cust, 100, 2);
        $c0 = $this->cash($cust);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminWithdrawCashController::class, 'cancel', ['id' => $paid]));
        $this->assertSame($c0, $this->cash($cust));
        $this->assertSame('2', (string) DB::table('balance_withdraw')->where('id', $paid)->value('status'));

        // รอจ่าย (1) ยกเลิก = คืนครั้งเดียว กดซ้ำไม่คืนซ้ำ
        $pending = $this->withdraw($cust, 120, 1);
        $c1 = $this->cash($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminWithdrawCashController::class, 'cancel', ['id' => $pending]));
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminWithdrawCashController::class, 'cancel', ['id' => $pending]));
        $this->assertSame(round($c1 + 120, 2), $this->cash($cust));

        // ยกเลิก/คืนเงินแล้ว อนุมัติไม่ได้
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminWithdrawCashController::class, 'saveApproved', ['id' => $pending, 'approved_remark' => 'zz']));
        $this->assertSame('-1', (string) DB::table('balance_withdraw')->where('id', $pending)->value('status'));

        // id ที่ไม่มี: ไม่สร้างแถวขยะ
        $n = DB::table('balance')->where('user', $cust->id)->count();
        $this->act(AdminWithdrawCashController::class, 'cancel', ['id' => '999999999']);
        $this->assertSame($n, DB::table('balance')->where('user', $cust->id)->count());

        // รอจ่าย → อนุมัติได้, บันทึกหลักฐานซ้ำได้ (ไม่แตะเงิน)
        $p2 = $this->withdraw($cust, 50, 1);
        $c2 = $this->cash($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminWithdrawCashController::class, 'saveApproved', ['id' => $p2]));
        $this->assertStringContainsString('SuccessBox', $this->act(AdminWithdrawCashController::class, 'saveApproved', ['id' => $p2, 'approved_remark' => 'zz2']));
        $this->assertSame('2', (string) DB::table('balance_withdraw')->where('id', $p2)->value('status'));
        $this->assertSame($c2, $this->cash($cust));
    }

    public function test_admin_refill_cancel_refuses_approved_refill(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $this->actAs($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(RefillController::class, 'save', ['amount' => '1,000.50', 'date' => '', 'remark' => 'zz_test']));
        $r = DB::table('refill')->where('user', $cust->id)->orderByDesc('id')->first();
        $this->assertSame('1000.50', (string) $r->amount);

        $this->actAs($admin);
        $this->act(AdminRefillController::class, 'saveApproved', ['id' => $r->id]);
        $this->act(AdminRefillController::class, 'saveApproved', ['id' => $r->id]);
        $this->assertSame(1000.5, $this->cash($cust), 'อนุมัติซ้ำไม่เติมซ้ำ');

        $out = $this->act(AdminRefillController::class, 'cancel', ['id' => $r->id, 'cancel_remark' => 'zz']);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame('2', (string) DB::table('refill')->where('id', $r->id)->value('status'));
        $this->assertSame(1000.5, $this->cash($cust));

        // รออนุมัติ → ยกเลิกได้ (ไม่มีเงินเคลื่อน), ยกเลิกแล้วอนุมัติไม่ได้
        $this->actAs($cust);
        $this->act(RefillController::class, 'save', ['amount' => '10', 'date' => '']);
        $r2 = DB::table('refill')->where('user', $cust->id)->orderByDesc('id')->first();
        $this->actAs($admin);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminRefillController::class, 'cancel', ['id' => $r2->id]));
        $this->assertSame('-1', (string) DB::table('refill')->where('id', $r2->id)->value('status'));
        $this->act(AdminRefillController::class, 'saveApproved', ['id' => $r2->id]);
        $this->assertSame(1000.5, $this->cash($cust));
    }

    public function test_customer_refill_rejects_negative_zero_and_non_numeric(): void
    {
        $cust = $this->zzUser();
        $this->actAs($cust);
        foreach (['-100', '0', '0.00', 'abc', '', '1e3', '10.123', '-0.5', '5abc'] as $bad) {
            foreach ([RefillController::class, PaymentController::class] as $ctl) {
                $out = $this->act($ctl, 'save', ['amount' => $bad, 'date' => '']);
                $this->assertStringContainsString('ErrorBox(', $out, $ctl.' '.$bad);
            }
        }
        $this->assertSame(0, DB::table('refill')->where('user', $cust->id)->count());

        // ค้างจากเว็บเดิม: รายการรออนุมัติยอดติดลบ อนุมัติไม่ได้ (เดิม = หักเงินลูกค้า)
        $id = $this->insertRow('refill', ['status' => 1, 'user' => $cust->id, 'amount' => -500, 'date' => DB::raw('NOW()'), 'attachment' => '', 'remark' => 'zz_test', 'approved_remark' => '']);
        $this->actAs($this->zzUser(1));
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminRefillController::class, 'saveApproved', ['id' => $id]));
        $this->assertSame('1', (string) DB::table('refill')->where('id', $id)->value('status'));
        $this->assertSame(0.0, $this->cash($cust));
    }
}
