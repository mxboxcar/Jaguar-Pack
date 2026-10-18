<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Reports\Admin\AdminExportController;
use App\Http\Controllers\Reports\ExportController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * reports / export (รายงานข้อ 15) — รันกับ admin_jl_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class ReportsDbTest extends BenchDbTestCase
{
    private function bill(User $cust, int $status, float $total = 80): int
    {
        $id = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => $status, 'total' => $total, 'cancel_remark' => '', 'submit_remark' => 'zz_test',
            'export_tracking' => '', 'export_remark' => '', 'export_price' => 0, 'export_attachment' => '']);
        if ($status >= 1) {
            $this->insertRow('balance', ['user' => $cust->id, 'amount' => -$total, 'detail' => 'ชำระเงิน zz', 'type' => 3, 'prefix' => $this->prefixId('payment'), 'prefix_ref_id' => $id]);
        }

        return $id;
    }

    private function billStatus(int $id): string
    {
        return (string) DB::table('bill_payment')->where('id', $id)->value('status');
    }

    public function test_customer_can_cancel_only_unpaid_bills(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 500);
        $this->actAs($cust);
        foreach ([1, 2, 3] as $st) {
            $id = $this->bill($cust, $st);
            $c = $this->cash($cust);
            $this->assertStringContainsString('ErrorBox(', $this->act(ExportController::class, 'saveDelete', ['id' => $id, 'cancel_remark' => 'zz']), "status $st");
            $this->assertSame((string) $st, $this->billStatus($id));
            $this->assertSame($c, $this->cash($cust), "status $st ต้องไม่คืนเงิน");
        }
        $id = $this->bill($cust, 0);
        $c = $this->cash($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(ExportController::class, 'saveDelete', ['id' => $id]));
        $this->assertSame('-1', $this->billStatus($id));
        $this->assertSame($c, $this->cash($cust), 'สถานะ 0 ยังไม่ถูกตัดเงิน = ไม่มีเงินคืน');
        // ยกเลิกซ้ำไม่ทำอะไร
        $this->act(ExportController::class, 'saveDelete', ['id' => $id]);
        $this->assertSame($c, $this->cash($cust));

        // บิลของคนอื่น: ไม่แตะ
        $other = $this->zzUser();
        $oid = $this->bill($other, 0);
        $this->act(ExportController::class, 'saveDelete', ['id' => $oid]);
        $this->assertSame('0', $this->billStatus($oid));
    }

    public function test_admin_export_approve_only_for_bills_waiting_to_ship(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 500);
        $this->actAs($this->zzUser(1));
        foreach ([-1, 0, 1] as $st) {
            $id = $this->bill($cust, $st);
            $c = $this->cash($cust);
            $out = $this->act(AdminExportController::class, 'saveApproved', ['id' => $id, 'export_tracking' => 'zz', 'export_price' => '-50', 'export_date' => '']);
            $this->assertStringContainsString('ErrorBox(', $out, "status $st");
            $this->assertSame((string) $st, $this->billStatus($id));
            $this->assertSame($c, $this->cash($cust));
        }
        $id = $this->bill($cust, 2);
        $c = $this->cash($cust);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminExportController::class, 'saveApproved', ['id' => $id, 'export_tracking' => 'zz', 'export_price' => '-50', 'export_date' => '']));
        $this->assertSame('3', $this->billStatus($id));
        $this->assertSame(round($c - 50, 2), $this->cash($cust));
        // แก้ข้อมูลการส่งของบิลที่ส่งแล้ว: ค่าส่งเดิมถูกแทนที่ ไม่ตัดซ้ำ
        $this->act(AdminExportController::class, 'saveApproved', ['id' => $id, 'export_tracking' => 'zz2', 'export_price' => '-60', 'export_date' => '']);
        $this->assertSame(round($c - 60, 2), $this->cash($cust));
    }
}
