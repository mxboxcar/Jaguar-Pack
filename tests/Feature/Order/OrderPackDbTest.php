<?php

namespace Tests\Feature\Order;

use App\Domain\Order\OrderDetailRenderer;
use App\Http\Controllers\Order\Admin\AdminOrderController;
use App\Http\Controllers\Order\Admin\AdminOrderDetailController;
use App\Http\Controllers\Order\OrderController;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * order ของ PACK: PIN ตอนจ่ายเงิน (เหมือน SR), save_change_send_type, load_detail/ฟอร์มลูกค้าแบบ PACK
 * (ยอดบาทรวม = Σ round(…, 2), ไม่มีถ่ายรูป/รูป tracking_image/VAT), load_paging + load_total_order แบบ jl (cache 10 นาที),
 * save_add_new_track ผูก tracking ใหม่กับรายการ
 * รันกับ admin_pack_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class OrderPackDbTest extends BenchDbTestCase
{
    private function order(User $cust, int $status, float $total = 300, array $extra = []): int
    {
        return $this->insertRow('bill_product', $extra + ['user' => $cust->id, 'total' => $total, 'usecash' => 0, 'status' => $status,
            'quote' => '', 'ship' => 0, 'is_lock' => 0, 'cancel_remark' => '', 'admin_quote' => 'zz_test', 'exchange_rate' => 5]);
    }

    /** รายการ 1 ชิ้น + tracking 1 ตัว (คืน [detail id, tracking id]) */
    private function line(User $cust, int $bill, float $price = 10, int $amount = 2, float $ship = 3, float $rate = 5): array
    {
        $d = $this->insertRow('bill_product_detail', ['user' => $cust->id, 'bill_product_id' => $bill, 'shop_name' => 'zz_shop'.$price,
            'product_name' => 'zz_p', 'url' => 'https://example.invalid/zz', 'amount' => $amount, 'amount_before' => $amount,
            'price' => $price, 'exchange_rate' => $rate, 'is_craft' => 0, 'is_qc' => 0]);
        $t = $this->insertRow('tracking', ['user' => $cust->id, 'tracking' => 'zz_t'.$d, 'price_shipping' => $ship, 'ship' => 0,
            'payment_id' => 0, 'lot_id' => 0]);
        $this->insertRow('bill_product_on_tracking', ['user' => $cust->id, 'bill_product_id' => $bill, 'bill_product_detail_id' => $d, 'tracking_id' => $t]);

        return [$d, $t];
    }

    public function test_save_payment_asks_for_the_pin_first(): void
    {
        $cust = $this->zzUser(2);
        DB::table('user')->where('id', $cust->id)->update(['pin' => '']);
        $this->actAs($cust->refresh());
        $this->credit($cust, 1000);
        $bill = $this->order($cust, 1, 300);

        // ไม่มี PIN = กล่องตั้ง PIN ใหม่, มี PIN แต่ไม่ส่ง = กล่องใส่ PIN, PIN ผิด = ErrorBox — ไม่หักเงิน
        $this->assertStringContainsString('textconfirmpaymentpin', $this->act(OrderController::class, 'savePayment', ['id' => $bill]));
        DB::table('user')->where('id', $cust->id)->update(['pin' => '4321']);
        $this->actAs($cust->refresh());
        $this->assertStringContainsString('htmlpin', $this->act(OrderController::class, 'savePayment', ['id' => $bill]));
        $this->assertStringContainsString("ErrorBox('PIN จ่ายเงินไม่ถูกต้อง'", $this->act(OrderController::class, 'savePayment', ['id' => $bill, 'paymentpin' => '0000']));
        $this->assertSame('1', (string) DB::table('bill_product')->where('id', $bill)->value('status'));
        $this->assertSame(1000.0, $this->cash($cust));

        $this->assertStringContainsString('SuccessBox', $this->act(OrderController::class, 'savePayment', ['id' => $bill, 'paymentpin' => '4321', 'submit_remark' => 'zz']));
        $this->assertSame('2', (string) DB::table('bill_product')->where('id', $bill)->value('status'));
        $this->assertSame(700.0, $this->cash($cust));
        // จ่ายซ้ำ: ไม่หักซ้ำ
        $this->act(OrderController::class, 'savePayment', ['id' => $bill, 'paymentpin' => '4321']);
        $this->assertSame(700.0, $this->cash($cust));
    }

    public function test_change_send_type_updates_order_and_trackings(): void
    {
        $cust = $this->zzUser(2);
        $this->actAs($this->zzUser(1));
        $bill = $this->order($cust, 0);
        [, $t] = $this->line($cust, $bill);

        $this->act(AdminOrderController::class, 'saveChangeSendType', ['id' => $bill, 'type' => '7']);
        $this->assertSame('0', (string) DB::table('bill_product')->where('id', $bill)->value('ship'));
        $this->assertSame('', $this->act(AdminOrderController::class, 'saveChangeSendType', ['id' => $bill, 'type' => '1']));
        $this->assertSame('1', (string) DB::table('bill_product')->where('id', $bill)->value('ship'));
        $this->assertSame('1', (string) DB::table('tracking')->where('id', $t)->value('ship'));

        DB::table('bill_product')->where('id', $bill)->update(['status' => -1]);
        $this->act(AdminOrderController::class, 'saveChangeSendType', ['id' => $bill, 'type' => '0']);
        $this->assertSame('1', (string) DB::table('bill_product')->where('id', $bill)->value('ship'));
    }

    /**
     * ยอดบาทรวมท้ายตาราง: PACK บวก round(ราคา × จำนวน × เรท, 2) ทีละรายการ (และค่าส่งจีน × เรท)
     * เรท decimal(20,3): 5.0025 → 5.003; 2.00 × 1 × 5.003 = 10.006 → round = 10.01 ×2 = 20.02 (ไม่ round: 20.012 → numberformat ตัด = 20.01)
     * ยอดต่อร้านยังไม่ round (เหมือนเดิม) = 20.01
     */
    public function test_load_detail_and_customer_form_sum_rounded_lines(): void
    {
        $cust = $this->zzUser(2);
        $this->actAs($this->zzUser(1));
        $bill = $this->order($cust, 1);
        $this->line($cust, $bill, 2.00, 1, 0, 5.0025);
        $this->line($cust, $bill, 2.00, 1, 0, 5.0025);

        $html = $this->act(AdminOrderDetailController::class, 'loadDetail', ['id' => $bill]);
        $this->assertStringContainsString('<td class="text-right"><span class="currency">20.02</span>&nbsp;฿</td>', $html);
        $this->assertStringContainsString('<td class="text-right"><span class="currency">20.01</span>&nbsp;฿</td>', $html);
        $this->assertStringNotContainsString('ถ่ายรูป', $html);
        $this->assertStringNotContainsString('image_list_items', $html);
        $this->assertStringContainsString('<div class="col-xs-6" style="padding:0px">', $html);
        $this->assertStringContainsString('width: 70%;', $html);

        // ส่วนตารางของ _order/form.php (หน้าเต็มทดสอบด้วยการเทียบกับเว็บเดิม)
        $form = (new OrderDetailRenderer)->customer($bill, []);
        $this->assertStringContainsString('<td class="text-right"><span class="currency">20.02</span>&nbsp;฿</td>', $form);
        $this->assertStringNotContainsString('ภาษีมูลค่าเพิ่ม', $form);
        // แถว tracking แรกของลูกค้าแก้ไขได้ + ปุ่ม + (แบบ jl — SR เป็นข้อความ)
        $this->assertStringContainsString('onclick="addnewline(', $form);
    }

    public function test_admin_paging_and_status_counts_are_cached_like_redis(): void
    {
        $cust = $this->zzUser(2);
        $this->actAs($this->zzUser(1));
        Cache::flush();
        $o1 = $this->order($cust, 0, 300, ['admin_quote' => 'zz_quote_'.$cust->id]);
        for ($i = 0; $i < 10; $i++) {
            $last = $this->order($cust, 0, 300, ['admin_quote' => 'zz_quote_'.$cust->id]);
        }

        $grid = $this->act(AdminOrderController::class, 'grid', ['txtsearch' => $cust->code.'||', 'page' => '1']);
        $this->assertStringContainsString('<div class="pull-right" id="paging">', $grid);
        $this->assertStringContainsString("url: rooturl + '_admin/_order/grid/../load_paging'", $grid);
        $this->assertStringContainsString('ORD-'.$last, $grid);
        $this->assertStringNotContainsString('ORD-'.$o1.'<', $grid);

        // load_paging ค้น admin_quote ด้วย (grid ไม่ค้น) — 11 ใบ = 2 หน้า
        $paging = $this->act(AdminOrderController::class, 'loadPaging', ['txtsearch' => 'zz_quote_'.$cust->id.'||', 'page' => '1']);
        $this->assertStringContainsString("loadAjaxGrid('_admin/_order/grid', '2',", $paging);
        $this->assertStringNotContainsString("loadAjaxGrid('_admin/_order/grid', '3',", $paging);
        $this->assertStringNotContainsString('ORD-'.$last, $this->act(AdminOrderController::class, 'grid', ['txtsearch' => 'zz_quote_'.$cust->id.'||', 'page' => '1']));

        $before = (int) $this->act(AdminOrderController::class, 'loadTotalOrder', ['ref' => '0']);
        $this->order($cust, 0);
        // ภายใน 10 นาทีได้ค่าเดิม (readRedis) — เหมือนเว็บเดิม
        $this->assertSame($before, (int) $this->act(AdminOrderController::class, 'loadTotalOrder', ['ref' => '0']));
        Cache::flush();
        $this->assertSame($before + 1, (int) $this->act(AdminOrderController::class, 'loadTotalOrder', ['ref' => '0']));
    }

    /** เดิม INSERT bill_product_on_tracking ด้วย $ins ที่ไม่มี (tracking ลอย) — ใหม่ผูกกับรายการ (เจตนาเดิม / แบบ SR และ jl Laravel) */
    public function test_add_new_track_links_the_tracking_to_the_item(): void
    {
        $cust = $this->zzUser(2);
        $admin = $this->zzUser(1);
        $this->actAs($admin);
        $bill = $this->order($cust, 2, 300, ['quote' => "zz'q"]);
        [$d] = $this->line($cust, $bill);

        $html = $this->act(AdminOrderDetailController::class, 'saveAddNewTrack', ['ref' => $d, 'value' => '', 'order' => 'zz_o']);
        $t = (int) DB::table('tracking')->where('user', $cust->id)->max('id');
        $on = DB::table('bill_product_on_tracking')->where('tracking_id', $t)->first();
        $this->assertNotNull($on);
        $this->assertSame($d, (int) $on->bill_product_detail_id);
        $this->assertSame($admin->id, (int) $on->user);
        $this->assertSame('zzq', (string) DB::table('tracking')->where('id', $t)->value('quote'));
        $this->assertStringContainsString('deleteline($(this), '.$t.', '.$on->id.')', $html);
    }
}
