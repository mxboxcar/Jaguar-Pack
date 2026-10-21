<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Tracking\Admin\AdminLostPackageController;
use App\Http\Controllers\Tracking\LostPackageController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * แจ้งไม่ได้รับสินค้า (lost_package): อนุมัติ = คืนเงินเข้า balance — ยกเลิก/อนุมัติได้เฉพาะรายการที่รอ (1)
 * e2e 2026-10-18: ลูกค้า/แอดมินยกเลิกรายการที่อนุมัติแล้ว (ไม่หักคืน) → RollBack (-1 → 1 ไม่หัก) → อนุมัติอีกครั้ง = คืนเงินซ้ำ
 * PACK: ยอดที่คืน = cashback_th (บาท) ที่แอดมินกรอก; lost_package.cashback = round(cashback_th / อัตรา, 2)
 * รันกับ admin_pack_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class LostPackageDbTest extends BenchDbTestCase
{
    private function claim(User $cust, string $cashback = '10'): object
    {
        $billId = $this->insertRow('bill_product', [
            'user' => $cust->id, 'total' => '0', 'usecash' => '0', 'exchange_rate' => '5.000', 'attachment' => '', 'status' => '0', 'quote' => 'zz_test', 'ship' => '1',
        ]);
        $this->actAs($cust);
        $out = $this->act(LostPackageController::class, 'save', ['id' => '', 'order' => (string) $billId, 'submit_remark' => 'zz_test', 'cashback' => $cashback]);
        $this->assertStringContainsString('SuccessBox', $out);

        return DB::table('lost_package')->where('user', $cust->id)->orderByDesc('id')->first();
    }

    private function lpStatus(object $lp): string
    {
        return (string) DB::table('lost_package')->where('id', $lp->id)->value('status');
    }

    public function test_approve_credits_once_and_refuses_cancelled_claims(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $lp = $this->claim($cust);
        $this->actAs($admin);

        $this->assertStringContainsString('SuccessBox', $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback' => '10', 'cashback_th' => '50', 'approved_remark' => 'zz']));
        $this->assertSame(50.0, $this->cash($cust), '¥10 × 5.000');
        // กดซ้ำ (สถานะ 2) ไม่คืนซ้ำ
        $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback' => '10', 'cashback_th' => '50']);
        $this->assertSame(50.0, $this->cash($cust));

        // ยกเลิกรายการที่อนุมัติแล้ว: ลูกค้า (ยิงตรง) และแอดมิน (หน้าเก่า) ถูกปฏิเสธ — สถานะ/เงินไม่เปลี่ยน
        $this->actAs($cust);
        $this->assertStringContainsString('ErrorBox(', $this->act(LostPackageController::class, 'cancel', ['id' => $lp->id]));
        $this->actAs($admin);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminLostPackageController::class, 'adminCancel', ['id' => $lp->id, 'cancel_remark' => 'zz']));
        $this->assertSame('2', $this->lpStatus($lp));

        // ทางที่ถูก: RollBack (หักคืน) → อนุมัติใหม่ = ยอดสุทธิคืนครั้งเดียว
        $this->act(AdminLostPackageController::class, 'rollback', ['id' => $lp->id]);
        $this->assertSame('1', $this->lpStatus($lp));
        $this->assertSame(0.0, $this->cash($cust));
        $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback' => '10', 'cashback_th' => '50']);
        $this->assertSame(50.0, $this->cash($cust));
    }

    public function test_cancelled_claim_cannot_be_approved_until_rolled_back(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $lp = $this->claim($cust);
        $this->actAs($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(LostPackageController::class, 'cancel', ['id' => $lp->id]));
        $this->assertSame('-1', $this->lpStatus($lp));
        $this->assertStringContainsString('ErrorBox(', $this->act(LostPackageController::class, 'cancel', ['id' => $lp->id]), 'ยกเลิกซ้ำ');

        $this->actAs($admin);
        $out = $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback' => '10', 'cashback_th' => '50']);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame('-1', $this->lpStatus($lp));
        $this->assertSame(0.0, $this->cash($cust));
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminLostPackageController::class, 'adminCancel', ['id' => $lp->id]), 'ยกเลิกซ้ำ');

        $this->act(AdminLostPackageController::class, 'rollback', ['id' => $lp->id]);
        $this->assertSame('1', $this->lpStatus($lp));
        $this->assertSame(0.0, $this->cash($cust), 'rollback ของรายการที่ยกเลิก (ไม่เคยคืนเงิน) ไม่แตะเงิน');
        $this->assertStringContainsString('SuccessBox', $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback' => '10', 'cashback_th' => '50']));
        $this->assertSame(50.0, $this->cash($cust));
    }

    public function test_admin_cancel_of_pending_claim_and_negative_cashback(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $lp = $this->claim($cust);
        $this->actAs($admin);
        $out = $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback' => '10', 'cashback_th' => '-50']);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame('1', $this->lpStatus($lp));
        $this->assertSame(0.0, $this->cash($cust));

        $this->assertStringContainsString('SuccessBox', $this->act(AdminLostPackageController::class, 'adminCancel', ['id' => $lp->id, 'cancel_remark' => 'zz']));
        $this->assertSame('-1', $this->lpStatus($lp));
        $this->assertSame(0.0, $this->cash($cust));
    }

    public function test_customer_cannot_cancel_someone_elses_claim(): void
    {
        $owner = $this->zzUser();
        $other = $this->zzUser();
        $lp = $this->claim($owner);
        $this->actAs($other);
        $this->act(LostPackageController::class, 'cancel', ['id' => $lp->id]);
        $this->assertSame('1', $this->lpStatus($lp));
    }

    /** PACK: คืนตามยอดบาทที่กรอก, เก็บ ¥ ย้อนกลับ (round แบบ 7.4), detail ใช้ ¥ แบบ float และ RollBack หาแถวนั้นเจอ */
    public function test_pack_baht_refund_and_rollback_of_float_detail(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $lp = $this->claim($cust);
        DB::table('bill_product')->where('id', $lp->order_id)->update(['exchange_rate' => '5.200']);
        $this->actAs($admin);

        // 1.69 / 5.2 = 0.32499999999999996 → PHP 7.4 round = 0.33 (8.4 = 0.32)
        $out = $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback' => '99', 'cashback_th' => '1.69', 'approved_remark' => 'zz']);
        $this->assertStringContainsString('SuccessBox', $out);
        $this->assertSame('0.33', (string) DB::table('lost_package')->where('id', $lp->id)->value('cashback'));
        $bal = DB::table('balance')->where('user', $cust->id)->where('type', 8)->orderByDesc('id')->first();
        $this->assertSame('1.69', (string) $bal->amount);
        $this->assertSame('คืนเงิน LOS-'.$lp->id.' ยอดจำนวน ¥0.33 * 5.200฿', $bal->detail);
        $this->act(AdminLostPackageController::class, 'rollback', ['id' => $lp->id]);
        $this->assertSame(0.0, $this->cash($cust));

        // ยอดเต็มหยวน: detail = ¥77 (DB เก็บ 77.00) — เดิม RollBack หาไม่เจอ (เทียบกับ ¥77.00) → ไม่หักคืน
        $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback_th' => '400.40']);
        $this->assertSame('77.00', (string) DB::table('lost_package')->where('id', $lp->id)->value('cashback'));
        $this->assertSame('คืนเงิน LOS-'.$lp->id.' ยอดจำนวน ¥77 * 5.200฿', DB::table('balance')->where('user', $cust->id)->where('type', 8)->orderByDesc('id')->value('detail'));
        $this->assertSame(400.4, $this->cash($cust));
        $this->act(AdminLostPackageController::class, 'rollback', ['id' => $lp->id]);
        $this->assertSame('1', $this->lpStatus($lp));
        $this->assertSame(0.0, $this->cash($cust), 'RollBack หักคืนได้');
    }

    public function test_pack_refuses_order_without_exchange_rate(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $lp = $this->claim($cust);
        DB::table('bill_product')->where('id', $lp->order_id)->update(['exchange_rate' => '0']);
        $this->actAs($admin);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminLostPackageController::class, 'saveApproved', ['id' => $lp->id, 'cashback_th' => '50']));
        $this->assertSame('1', $this->lpStatus($lp));
        $this->assertSame(0.0, $this->cash($cust));
    }
}
