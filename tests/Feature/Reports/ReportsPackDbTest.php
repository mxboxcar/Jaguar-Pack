<?php

namespace Tests\Feature\Reports;

use App\Domain\Reports\ExportBill;
use App\Http\Controllers\Reports\Admin\AdminExportController;
use App\Http\Controllers\Reports\ExportController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * PACK: บิลส่งสินค้าที่มี VAT (bill_payment.is_vat/v/vat_before), ส่วนลด (discount) และคูปองท้ายบิล (ตาราง coupon)
 * + สิทธิ์ admin_viewprice_on_payment (role 1, 3 ถือใน admin_pack_bench; role 7 ไม่มี)
 * รันกับ admin_pack_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class ReportsPackDbTest extends BenchDbTestCase
{
    private function bill(int $user, array $extra = []): int
    {
        return $this->insertRow('bill_payment', $extra + ['user' => $user, 'status' => 2, 'total' => 535, 'price_shipping' => 500,
            'price_thai_storage' => 0, 'price_thai_shipping' => 0, 'discount' => 25.5, 'is_vat' => 1, 'v' => 7, 'vat_before' => 35,
            'cancel_remark' => '', 'submit_remark' => 'zz_test', 'send_name' => 'zz_pack_send', 'send_address' => 'zz_addr',
            'export_tracking' => '', 'export_remark' => '', 'export_price' => 0, 'export_attachment' => '', 'sender_type' => 0]);
    }

    private function table(int $id, bool $admin, bool $prices): string
    {
        $data = DB::table('bill_payment')->where('id', $id)->first();
        $details = ExportBill::details($id);

        return view('reports.partials.export_table', ['admin' => $admin, 'prices' => $prices, 'data' => $data,
            'totals' => ExportBill::totals($details), 'rows' => ExportBill::trackingRows($details, $admin, $prices),
            'coupon' => ExportBill::coupon($id)])->render();
    }

    public function test_coupon_vat_and_discount_rows_follow_pack_forms(): void
    {
        $cust = $this->zzUser(2);
        $id = $this->bill($cust->id);
        $this->insertRow('coupon', ['name' => 'zz_old', 'price' => 99, 'payment_id' => $id, 'is_delete' => 1]);
        $this->insertRow('coupon', ['name' => 'zz_cp', 'price' => 30, 'payment_id' => $id]);

        $this->assertSame('zz_cp', ExportBill::coupon($id)->name, 'คูปองที่ถูกลบไม่นับ');
        $this->assertNull(ExportBill::coupon(0), 'ไม่มีบิล = ไม่มีคูปอง (เดิม payment_id = 0 ได้คูปองที่ยังไม่ใช้)');
        $this->assertNull(ExportBill::coupon(''));

        $cust = $this->table($id, false, true);
        $this->assertStringContainsString('Discount', $cust);
        $this->assertStringContainsString('<span class="currency">25.50</span>', $cust);
        $this->assertStringContainsString('ส่วนลดท้ายบิล', $cust);
        $this->assertStringContainsString('ภาษีมูลค่าเพิ่ม 7.00%', $cust);
        $this->assertStringNotContainsString('total_net addcomma', $cust, 'บิล VAT: แถวยอดสุทธิไม่มี class total_net');
        $this->assertStringNotContainsString('รหัสย่อย', $cust);

        $admin = $this->table($id, true, true);
        $this->assertStringNotContainsString('Discount', $admin, 'แอดมินไม่มีแถว Discount');
        $this->assertStringContainsString('ส่วนลดท้ายบิล', $admin);
        $this->assertStringContainsString(res('price_kg'), $admin);

        $noPrice = $this->table($id, true, false);
        $this->assertStringNotContainsString('ส่วนลดท้ายบิล', $noPrice);
        $this->assertStringNotContainsString('ภาษีมูลค่าเพิ่ม', $noPrice);
        $this->assertStringNotContainsString(res('price_kg'), $noPrice);
        $this->assertStringNotContainsString(res('total_net'), $noPrice);
    }

    public function test_balance_box_without_price_permission_shows_export_rows_only(): void
    {
        $cust = $this->zzUser(2);
        $id = $this->bill($cust->id);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -535, 'detail' => 'zz_pay', 'type' => 3, 'prefix' => $this->prefixId('payment'), 'prefix_ref_id' => $id]);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => 40, 'detail' => 'zz_exp', 'type' => 4, 'prefix' => $this->prefixId('export'), 'prefix_ref_id' => $id]);

        $all = ExportBill::balanceBox($id, true);
        $this->assertStringContainsString('zz_pay', $all);
        $this->assertStringContainsString('zz_exp', $all);
        $only = ExportBill::balanceBox($id, true, true);
        $this->assertStringNotContainsString('zz_pay', $only);
        $this->assertStringContainsString('zz_exp', $only);
    }

    public function test_grids_show_total_minus_discount_and_vat_badge(): void
    {
        $cust = $this->zzUser(2);
        $id = $this->bill($cust->id, ['send_name' => 'zz_pack_grid_'.bin2hex(random_bytes(3))]);
        $name = DB::table('bill_payment')->where('id', $id)->value('send_name');

        $this->actAs($cust);
        $html = $this->act(ExportController::class, 'grid', ['page' => '1', 'txtsearch' => $id.'|']);
        $this->assertStringContainsString('<span class="currency">509.50</span>', $html, '535 - 25.50');
        $this->assertStringContainsString("label label-danger'>VAT", $html);

        // role 1 มี admin_viewprice_on_payment → เห็นยอด; role 7 ไม่มี → ช่องยอดว่าง (เห็นแค่ค่าส่งในไทย)
        $this->actAs($this->zzUser(1));
        $html = $this->act(AdminExportController::class, 'grid', ['page' => '1', 'txtsearch' => $name.'|']);
        $this->assertStringContainsString('<span class="currency">509.50</span>', $html);
        $this->assertStringNotContainsString('admin/export/receipt', $html, 'PACK ไม่มีใบเสร็จ');
        $this->actAs($this->zzUser(7));
        $html = $this->act(AdminExportController::class, 'grid', ['page' => '1', 'txtsearch' => $name.'|']);
        $this->assertStringContainsString($name, $html);
        $this->assertStringNotContainsString('509.50', $html);
    }
}
