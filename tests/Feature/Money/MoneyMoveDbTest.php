<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Lot\Admin\AdminLotSaveController;
use App\Http\Controllers\MoneyMove\Admin\AdminRefillController;
use App\Http\Controllers\MoneyMove\Admin\AdminTransferController;
use App\Http\Controllers\MoneyMove\Admin\AdminWithdrawCashController;
use App\Http\Controllers\MoneyMove\RefillController;
use App\Http\Controllers\MoneyMove\TransferController;
use App\Http\Controllers\Payment\PaymentController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * money-move (ข้อ 9–12 ของรายงาน): สถานะที่ยอมให้เปลี่ยน + ไม่คืน/หักเงินซ้ำ — รันกับ admin_pack_bench ใน transaction ที่ rollback (opt-in)
 */
class MoneyMoveDbTest extends BenchDbTestCase
{
    private const PIN = 'zz42';

    /** SR: _transfer/save ถาม PIN จ่ายเงิน — ตั้ง PIN ให้ผู้ใช้ทดสอบ (rollback ตอนจบ) */
    private function withPin(User $u): User
    {
        DB::table('user')->where('id', $u->id)->update(['pin' => self::PIN]);

        return $u->refresh();
    }

    /** ลูกค้าสร้างรายการโอน (ตัดเงิน usecash) ผ่าน _transfer/save จริง */
    private function transfer(User $cust, float $cash = 1000, string $total = '100'): object
    {
        $this->credit($cust, $cash);
        $this->actAs($this->withPin($cust));
        $out = $this->act(TransferController::class, 'save', ['total' => $total, 'acc_name' => 'zz_acc', 'acc_no' => '1', 'acc_bank' => 'zz', 'acc_branch' => 'zz', 'remark' => 'zz_test', 'upload_file' => '', 'paymentpin' => self::PIN]);
        $this->assertStringContainsString('SuccessBox', $out);

        return DB::table('bill_transfer')->where('user', $cust->id)->orderByDesc('id')->first();
    }

    private function transferNet(object $t, string $table = 'balance'): float
    {
        return round((float) DB::table($table)->where('prefix', $this->prefixId('transfer'))->where('prefix_ref_id', $t->id)->where('is_delete', 0)->sum('amount'), 2);
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

        // รายการรอโอน → 2; PACK (= jl port): บันทึกซ้ำ (status 2) = บันทึกหลักฐานใหม่ได้ (ไม่แตะเงิน)
        $t2 = $this->transfer($cust);
        $this->actAs($admin);
        $before = $this->cash($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminTransferController::class, 'save', ['id' => $t2->id, 'approved_remark' => 'zz']));
        $this->assertStringContainsString('SuccessBox', $this->act(AdminTransferController::class, 'save', ['id' => $t2->id, 'approved_remark' => 'zz2']));
        $this->assertSame('zz2', (string) DB::table('bill_transfer')->where('id', $t2->id)->value('approved_remark'));
        // ไม่พบรายการ = ปฏิเสธ
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminTransferController::class, 'save', ['id' => '999999999', 'approved_remark' => 'zz']));
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

    /** PACK: _transfer/cancel.php ถูก comment ทั้งไฟล์ → ตอบว่าง ไม่ยกเลิก/ไม่คืนเงิน */
    public function test_customer_cancel_is_a_no_op(): void
    {
        $cust = $this->zzUser();
        $t = $this->transfer($cust);
        $after = $this->cash($cust);
        $this->assertSame('', $this->act(TransferController::class, 'cancel', ['id' => $t->id]));
        $this->assertSame('1', (string) DB::table('bill_transfer')->where('id', $t->id)->value('status'));
        $this->assertSame($after, $this->cash($cust));
    }

    /**
     * PACK (= SR): กระเป๋าหยวน — _transfer/save ใช้ balance_cn ก่อน ส่วนที่ขาด × เรท ตัดบาท; แอดมินยกเลิกคืนทั้งสองกระเป๋าครั้งเดียว;
     * RollBack ตัดคืนทั้งสองกระเป๋าเท่าที่ขาด และปฏิเสธเมื่อยอดหยวนไม่พอ
     */
    public function test_transfer_uses_yuan_wallet_first_and_refunds_both_once(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $rate = (float) \App\Support\Legacy\Lookup::getSetting('rate_transfer');
        $this->credit($cust, 20, 'balance_cn');
        $t = $this->transfer($cust, 1000, '50');
        $this->assertSame('20.00', (string) $t->usecash_cn);
        $this->assertSame(round(30 * $rate, 2), (float) $t->usecash);
        $this->assertSame(-20.0, $this->transferNet($t, 'balance_cn'));
        $this->assertSame(-round(30 * $rate, 2), $this->transferNet($t));
        $this->assertSame(0.0, $this->cash($cust, 'balance_cn'));

        // ยกเลิก (แอดมิน) ซ้ำ 2 ครั้ง = คืนครั้งเดียวทั้งสองกระเป๋า (ครั้งที่ 2 = "รายการถูกยืนยันไปแล้ว")
        $this->actAs($admin);
        $this->act(AdminTransferController::class, 'cancel', ['id' => $t->id, 'cancel_remark' => 'zz']);
        $this->assertStringContainsString('รายการถูกยืนยันไปแล้ว', $this->act(AdminTransferController::class, 'cancel', ['id' => $t->id]));
        $this->assertSame(0.0, $this->transferNet($t));
        $this->assertSame(0.0, $this->transferNet($t, 'balance_cn'));
        $this->assertSame(20.0, $this->cash($cust, 'balance_cn'));

        // RollBack ×2 = ตัดคืนครั้งเดียวทั้งสองกระเป๋า
        $this->act(AdminTransferController::class, 'rollback', ['id' => $t->id]);
        $this->act(AdminTransferController::class, 'rollback', ['id' => $t->id]);
        $this->assertSame(-20.0, $this->transferNet($t, 'balance_cn'));
        $this->assertSame(-round(30 * $rate, 2), $this->transferNet($t));

        // แอดมินยกเลิก → คืนทั้งสอง; ลูกค้าใช้หยวนไปบางส่วน → RollBack ปฏิเสธ ไม่เขียนอะไร
        $this->act(AdminTransferController::class, 'cancel', ['id' => $t->id, 'cancel_remark' => 'zz']);
        $this->assertSame(20.0, $this->cash($cust, 'balance_cn'));
        $this->credit($cust, -15, 'balance_cn');
        $n = DB::table('balance')->where('user', $cust->id)->count();
        $out = $this->act(AdminTransferController::class, 'rollback', ['id' => $t->id]);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame('-1', (string) DB::table('bill_transfer')->where('id', $t->id)->value('status'));
        $this->assertSame($n, DB::table('balance')->where('user', $cust->id)->count(), 'ไม่ตัดบาทเมื่อหยวนไม่พอ');

        // ยอดน้อยกว่าหยวนที่มี: ใช้หยวนล้วน ไม่มีแถว balance
        $t2 = $this->transfer($cust, 0, '3');
        $this->assertSame('3', (string) (float) $t2->usecash_cn);
        $this->assertSame(0.0, (float) $t2->usecash);
        $this->assertSame(0, DB::table('balance')->where('prefix', $this->prefixId('transfer'))->where('prefix_ref_id', $t2->id)->count());
        $this->assertSame(-3.0, $this->transferNet($t2, 'balance_cn'));
    }

    /** SR: _transfer/save ถาม PIN ก่อนทุกอย่าง (ไม่มี PIN = กล่องตั้ง PIN, ว่าง = กล่องกรอก, ผิด = ErrorBox) — ไม่สร้างรายการ */
    public function test_transfer_save_requires_payment_pin(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 1000);
        $this->actAs($cust);
        $post = ['total' => '10', 'acc_name' => 'zz_acc', 'remark' => 'zz_test', 'upload_file' => ''];
        $this->assertStringContainsString('htmlpin', $this->act(TransferController::class, 'save', $post));
        $this->actAs($this->withPin($cust));
        $this->assertStringContainsString('htmlpin', $this->act(TransferController::class, 'save', $post));
        $this->assertStringContainsString('PIN จ่ายเงินไม่ถูกต้อง', $this->act(TransferController::class, 'save', $post + ['paymentpin' => 'nope']));
        $this->assertSame(0, DB::table('bill_transfer')->where('user', $cust->id)->count());
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

        // จ่ายแล้ว (2) ยกเลิกไม่ได้ = ไม่คืนเงิน (PACK hotfix 2026-10-21: getError('', 'รายการนี้ยกเลิกไม่ได้'))
        $paid = $this->withdraw($cust, 100, 2);
        $c0 = $this->cash($cust);
        $this->assertSame("ErrorBox('รายการนี้ยกเลิกไม่ได้');", $this->act(AdminWithdrawCashController::class, 'cancel', ['id' => $paid]));
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
        $this->assertStringContainsString('SuccessBox', $this->act(AdminRefillController::class, 'saveApproved', ['id' => $r->id]));
        // PACK (= SR ไม่มี approved_user): อนุมัติซ้ำ = "รายการไม่พร้อม"
        $this->assertSame("ErrorBox('รายการไม่พร้อมที่จะดำเนินการนี้', 'window.location.reload();'); ", $this->act(AdminRefillController::class, 'saveApproved', ['id' => $r->id]));
        $this->assertSame(1000.5, $this->cash($cust), 'อนุมัติซ้ำไม่เติมซ้ำ');
        $this->assertObjectNotHasProperty('approved_user', DB::table('refill')->where('id', $r->id)->first(), 'PACK ไม่มีคอลัมน์ approved_user');

        $out = $this->act(AdminRefillController::class, 'cancel', ['id' => $r->id, 'cancel_remark' => 'zz']);
        $this->assertSame("ErrorBox('รายการถูกดำเนินการสำเร็จไปแล้ว', 'window.location.reload();'); ", $out);
        $this->assertSame('2', (string) DB::table('refill')->where('id', $r->id)->value('status'));
        $this->assertSame(1000.5, $this->cash($cust));

        // รออนุมัติ → ยกเลิกได้ (ไม่มีเงินเคลื่อน), ยกเลิกแล้วอนุมัติไม่ได้
        $this->actAs($cust);
        $this->act(RefillController::class, 'save', ['amount' => '10', 'date' => '']);
        $r2 = DB::table('refill')->where('user', $cust->id)->orderByDesc('id')->first();
        $this->actAs($admin);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminRefillController::class, 'cancel', ['id' => $r2->id]));
        $this->assertSame('-1', (string) DB::table('refill')->where('id', $r2->id)->value('status'));
        $this->assertStringContainsString('รายการไม่พร้อมที่จะดำเนินการนี้', $this->act(AdminRefillController::class, 'saveApproved', ['id' => $r2->id]));
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

    /** _admin/_lot/cancel (สำเนาเดิมของ refill cancel): กติกาเดียวกับ _admin/_refill/cancel — เฉพาะ status 1, ซ้ำไม่เขียน */
    public function test_lot_cancel_only_cancels_pending_refill_and_is_idempotent(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $row = fn (int $status) => $this->insertRow('refill', ['status' => $status, 'user' => $cust->id, 'amount' => 100, 'date' => DB::raw('NOW()'), 'attachment' => '', 'remark' => 'zz_test', 'approved_remark' => '']);
        $done = $row(2);
        $pending = $row(1);
        $this->actAs($admin);

        $out = $this->act(AdminLotSaveController::class, 'cancel', ['id' => $done]);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertStringContainsString('อนุมัติ', $out);
        $this->assertSame('2', (string) DB::table('refill')->where('id', $done)->value('status'));

        $out = $this->act(AdminLotSaveController::class, 'cancel', ['id' => $pending]);
        $this->assertStringContainsString('SuccessBox', $out);
        $this->assertStringContainsString('admin/refill', $out);
        $r = DB::table('refill')->where('id', $pending)->first();
        $this->assertSame('-1', (string) $r->status);
        $this->assertSame((string) $admin->id, (string) $r->mod_by);

        DB::table('refill')->where('id', $pending)->update(['mod_by' => 0]);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminLotSaveController::class, 'cancel', ['id' => $pending]), 'ยกเลิกซ้ำ');
        $this->assertSame('0', (string) DB::table('refill')->where('id', $pending)->value('mod_by'), 'ยกเลิกซ้ำไม่เขียนแถว');
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminLotSaveController::class, 'cancel', ['id' => 0]), 'ไม่พบ');

        // _admin/_refill/cancel ยังทำงานผ่าน helper เดียวกัน (ซ้ำ = ErrorBox)
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminRefillController::class, 'cancel', ['id' => $pending]));
        $this->assertSame(0.0, $this->cash($cust), 'ไม่มีเงินเคลื่อน');
    }

    /** _admin/_refill/save (แอดมินเติมเงินให้ลูกค้าโดยตรง): ยอดต้องเป็นตัวเลขบวก ทศนิยมไม่เกิน 2 ตำแหน่ง */
    public function test_admin_refill_save_rejects_non_positive_and_bad_amounts(): void
    {
        $cust = $this->zzUser();
        $this->actAs($this->zzUser(1));
        foreach (['-100', '0', '0.00', 'abc', '', '1e3', '10.123', '-0.5', '5abc'] as $bad) {
            $out = $this->act(AdminRefillController::class, 'save', ['id' => '', 'code' => $cust->code, 'amount' => $bad, 'date' => '', 'remark' => 'zz_test', 'upload_file' => '']);
            $this->assertStringContainsString("ErrorBox('กรุณาระบุจำนวนเงินให้ถูกต้อง", $out, $bad);
        }
        $this->assertSame(0, DB::table('refill')->where('user', $cust->id)->count());
        $this->assertSame(0.0, $this->cash($cust));

        $out = $this->act(AdminRefillController::class, 'save', ['id' => '', 'code' => $cust->code, 'amount' => '1,250.75', 'date' => '', 'remark' => 'zz_test', 'upload_file' => '']);
        $this->assertStringContainsString('SuccessBox', $out);
        $r = DB::table('refill')->where('user', $cust->id)->first();
        $this->assertSame('2', (string) $r->status);
        $this->assertSame('1250.75', (string) $r->amount);
        $this->assertSame(1250.75, $this->cash($cust));
    }
}
