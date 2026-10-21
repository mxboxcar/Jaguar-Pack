<?php

namespace Tests\Feature\Reports;

use App\Domain\Reports\Running;
use App\Http\Controllers\Reports\Admin\AdminExportController;
use App\Http\Controllers\Reports\Admin\AdminReceiptController;
use App\Http\Controllers\Reports\ExportController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * SR: ใบเสร็จ (save_receipt + เลขรัน running) และการหักแต้ม calPoint('-') ตอนยกเลิกบิลส่งสินค้า
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

    public function test_save_receipt_issues_one_running_number_and_then_updates(): void
    {
        $this->actAs($this->zzUser(1));
        $year = substr((string) ((int) date('Y') + 543), -2);
        $start = (int) (DB::table('running')->where('is_delete', 0)->where('name', $year)->orderBy('id')->value('no') ?? 1);
        $a = $this->bill($this->zzUser()->id, 3);
        $b = $this->bill($this->zzUser()->id, 3);

        $post = ['id' => $a, 'receipt_name' => "zz_O'Neil", 'receipt_address' => 'zz_addr', 'receipt_tax' => 'zz1',
            'date' => '20 ตุลาคม 2026', 'is_vat' => '1', 'receipt_total' => '1234.56'];
        $this->assertSame('window.location.reload();', $this->act(AdminReceiptController::class, 'save', $post));
        $row = DB::table('bill_payment')->where('id', $a)->first();
        $no = $year.str_pad((string) $start, 5, '0', STR_PAD_LEFT);
        $this->assertSame($no, $row->receipt_id);
        $this->assertSame('zz_ONeil', $row->receipt_name, "' ถูกตัดทิ้งแบบเดิม");
        $this->assertSame('1', (string) $row->receipt_is_vat);
        $this->assertSame('1234.56', (string) $row->receipt_total);
        $this->assertSame('2026-10-20 00:00:00', (string) $row->receipt_date);

        // บันทึกซ้ำ = แก้ข้อมูล เลขเดิม ไม่เดินเลขรัน
        $this->act(AdminReceiptController::class, 'save', ['receipt_total' => '99', 'is_vat' => ''] + $post);
        $row = DB::table('bill_payment')->where('id', $a)->first();
        $this->assertSame($no, $row->receipt_id);
        $this->assertSame('0', (string) $row->receipt_is_vat);
        $this->assertSame('99.00', (string) $row->receipt_total);

        // บิลถัดไปได้เลขถัดไป
        $this->act(AdminReceiptController::class, 'save', ['id' => $b] + $post);
        $this->assertSame($year.str_pad((string) ($start + 1), 5, '0', STR_PAD_LEFT), DB::table('bill_payment')->where('id', $b)->value('receipt_id'));

        // id ที่ไม่ใช่ตัวเลขล้วนไม่แตะบิล
        $this->act(AdminReceiptController::class, 'save', ['id' => $a.'abc', 'receipt_name' => 'zz_x'] + $post);
        $this->assertSame('zz_ONeil', DB::table('bill_payment')->where('id', $a)->value('receipt_name'));
    }

    public function test_running_numbers_pad_and_create_rows_like_get_running(): void
    {
        $this->actAs($this->zzUser(1));
        $name = 'zz_run_'.bin2hex(random_bytes(3));
        $this->assertSame($name.'00001', Running::next($name, false, 5));
        $this->assertSame($name.'00002', Running::next($name, false, 5));
        $this->assertSame(3, (int) DB::table('running')->where('name', $name)->value('no'));
        $this->assertSame(1, DB::table('running')->where('name', $name)->count());
        $this->assertSame($name.date('ymd').'001', Running::next($name, true, 3));
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
