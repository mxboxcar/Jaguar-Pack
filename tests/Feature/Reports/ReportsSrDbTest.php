<?php

namespace Tests\Feature\Reports;

use App\Http\Controllers\Reports\Admin\AdminExportController;
use App\Http\Controllers\Reports\ExportController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * SR/PACK: การหักแต้ม calPoint('-') ตอนยกเลิกบิลส่งสินค้า (PACK ไม่มีใบเสร็จ/เลขรัน running ของ SR)
 * รันกับ admin_pack_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class ReportsSrDbTest extends BenchDbTestCase
{
    private function bill(int $user, int $status, array $extra = []): int
    {
        return $this->insertRow('bill_payment', $extra + ['user' => $user, 'status' => $status, 'total' => 450, 'price_shipping' => 300,
            'cancel_remark' => '', 'submit_remark' => 'zz_test', 'send_name' => 'zz_send', 'send_address' => 'zz_addr',
            'export_tracking' => '', 'export_remark' => '', 'export_price' => 0, 'export_attachment' => '']);
    }

    private function points(int $billId): array
    {
        return DB::table('balance_point')->where('prefix_ref_id', (string) $billId)->where('type', 1)
            ->pluck('amount')->map(fn ($v) => (string) $v)->all();
    }

    public function test_cancels_deduct_points_once(): void
    {
        $cust = $this->zzUser();
        $divide = (float) DB::table('setting')->where('key', 'divide_point')->value('value');
        $expected = number_format(-300 / $divide, 2, '.', '');

        // ลูกค้ายกเลิกบิลสถานะ 0
        $this->actAs($cust);
        $id = $this->bill($cust->id, 0);
        $this->act(ExportController::class, 'saveDelete', ['id' => (string) $id, 'cancel_remark' => 'zz']);
        $this->act(ExportController::class, 'saveDelete', ['id' => (string) $id, 'cancel_remark' => 'zz']);
        $this->assertSame('-1', (string) DB::table('bill_payment')->where('id', $id)->value('status'));
        $this->assertSame([$expected], $this->points($id));

        // แอดมินยกเลิกบิลสถานะ 2 (คืนเงิน + หักแต้ม)
        $this->actAs($this->zzUser(1));
        $id = $this->bill($cust->id, 2);
        $this->act(AdminExportController::class, 'saveDelete', ['id' => (string) $id, 'cancel_remark' => 'zz']);
        $this->act(AdminExportController::class, 'saveDelete', ['id' => (string) $id, 'cancel_remark' => 'zz']);
        $this->assertSame([$expected], $this->points($id));
        $this->assertSame(1, DB::table('balance')->where('prefix_ref_id', (string) $id)->where('type', 3)->count());
    }
}
