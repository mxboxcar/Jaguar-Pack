<?php

namespace Tests\Feature\Order;

use App\Domain\Order\OrderLookups;
use App\Http\Controllers\Order\Admin\AdminOrderController;
use App\Http\Controllers\Order\Admin\AdminOrderDetailController;
use App\Http\Controllers\Order\Admin\AdminPrintorderController;
use App\Http\Controllers\Order\OrderController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * order ของ SR: PIN ตอนจ่ายเงิน, endpoint ที่มีเฉพาะ SR (delete_withcashback, save_changeuser, save_newrate,
 * save_change_send_type, save_takeapic) และหน้าพิมพ์รายการสั่งซื้อ (_admin/_printorder)
 * รันกับ admin_pack_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class OrderSrDbTest extends BenchDbTestCase
{
    private function order(User $cust, int $status, float $total = 300, array $extra = []): int
    {
        return $this->insertRow('bill_product', $extra + ['user' => $cust->id, 'total' => $total, 'usecash' => 0, 'status' => $status,
            'quote' => '', 'ship' => 0, 'is_lock' => 0, 'cancel_remark' => '', 'admin_quote' => 'zz_test', 'exchange_rate' => 5,
            'is_print' => 0, 'run_id' => '']);
    }

    /** รายการ 1 ชิ้น + tracking 1 ตัว (คืน [detail id, tracking id]) */
    private function line(User $cust, int $bill, float $price = 10, int $amount = 2, float $ship = 3): array
    {
        $d = $this->insertRow('bill_product_detail', ['user' => $cust->id, 'bill_product_id' => $bill, 'shop_name' => 'zz_shop',
            'product_name' => 'zz_p', 'url' => 'https://example.invalid/zz', 'amount' => $amount, 'amount_before' => $amount,
            'price' => $price, 'exchange_rate' => 5, 'is_craft' => 0, 'is_qc' => 0, 'is_takeapic' => 0]);
        $t = $this->insertRow('tracking', ['user' => $cust->id, 'tracking' => 'zz_t'.$d, 'price_shipping' => $ship, 'ship' => 0,
            'payment_id' => 0, 'lot_id' => 0, 'is_takeapic' => 0]);
        $this->insertRow('bill_product_on_tracking', ['user' => $cust->id, 'bill_product_id' => $bill, 'bill_product_detail_id' => $d, 'tracking_id' => $t]);

        return [$d, $t];
    }

    public function test_save_payment_asks_for_the_pin_first(): void
    {
        $cust = $this->zzUser(8);
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

    public function test_delete_withcashback_refunds_net_paid_and_fee_only_to_the_owner_once(): void
    {
        $cust = $this->zzUser(8);
        $admin = $this->zzUser(1);
        $this->actAs($admin);
        $pid = $this->prefixId('order');
        $prefix = OrderLookups::prefix('order');

        // ไม่มีแถวหักค่าธรรมเนียม (SR ปัจจุบัน comment การหัก -50 ไว้) = ไม่คืน 50 — เดิมเครดิต 50 ให้แอดมินที่กดทุกครั้ง
        $o1 = $this->order($cust, 2);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -300, 'detail' => 'zz ชำระเงิน', 'type' => 2, 'prefix' => $pid, 'prefix_ref_id' => $o1]);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => 40, 'detail' => 'zz ยกเลิกรายการ', 'type' => 2, 'prefix' => $pid, 'prefix_ref_id' => $o1, 'ref_id_2' => 1]);
        $this->act(AdminOrderController::class, 'deleteWithCashback', ['id' => $o1, 'cancel_remark' => 'zz']);
        $this->assertSame('-1', (string) DB::table('bill_product')->where('id', $o1)->value('status'));
        $this->assertSame(0.0, round((float) DB::table('balance')->where('prefix', $pid)->where('prefix_ref_id', $o1)->where('is_delete', 0)->sum('amount'), 2));
        $this->assertSame(0.0, $this->cash($admin));
        $this->assertSame(0, DB::table('balance')->where('detail', 'คืนธรรมเนียมการเปิดใบสั่งซื้อสินค้า '.$prefix.$o1)->count());

        // มีแถวหักค่าธรรมเนียมของใบนี้ = คืน 50 ให้เจ้าของใบครั้งเดียว (กดซ้ำ = ไม่ทำอะไร)
        $o2 = $this->order($cust, 0);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -50, 'detail' => 'ธรรมเนียมการเปิดใบสั่งซื้อสินค้า '.$prefix.$o2, 'type' => 2, 'prefix' => '', 'prefix_ref_id' => 0]);
        $before = $this->cash($cust);
        $this->act(AdminOrderController::class, 'deleteWithCashback', ['id' => $o2, 'cancel_remark' => 'zz']);
        $this->act(AdminOrderController::class, 'deleteWithCashback', ['id' => $o2, 'cancel_remark' => 'zz']);
        $this->assertSame(round($before + 50, 2), $this->cash($cust));
        $this->assertSame(0.0, $this->cash($admin));
        $this->assertStringContainsString(' ,ยกเลิกโดย '.$admin->code, (string) DB::table('bill_product')->where('id', $o2)->value('cancel_remark'));

        // ไม่พบใบ = ไม่ทำอะไร (เดิมเครดิต 50 ให้แอดมิน)
        $this->act(AdminOrderController::class, 'deleteWithCashback', ['id' => 0, 'cancel_remark' => 'zz']);
        $this->assertSame(0.0, $this->cash($admin));
    }

    public function test_change_customer_only_for_unpaid_orders(): void
    {
        $cust = $this->zzUser(8);
        $other = $this->zzUser(8);
        $this->actAs($this->zzUser(1));

        $paid = $this->order($cust, 2);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminOrderController::class, 'saveChangeuser', ['id' => $paid, 'code' => $other->code]));
        $this->assertSame($cust->id, (int) DB::table('bill_product')->where('id', $paid)->value('user'));

        $this->assertStringContainsString("ErrorBox('", $this->act(AdminOrderController::class, 'saveChangeuser', ['id' => $paid, 'code' => 'zz_no_such_code']));

        $bill = $this->order($cust, 0);
        [$d, $t] = $this->line($cust, $bill);
        $this->assertSame('window.location.reload();', $this->act(AdminOrderController::class, 'saveChangeuser', ['id' => $bill, 'code' => ' '.$other->code.' ']));
        $this->assertSame($other->id, (int) DB::table('bill_product')->where('id', $bill)->value('user'));
        $this->assertSame($other->id, (int) DB::table('bill_product_detail')->where('id', $d)->value('user'));
        $this->assertSame($other->id, (int) DB::table('bill_product_on_tracking')->where('bill_product_detail_id', $d)->value('user'));
        $this->assertSame($other->id, (int) DB::table('tracking')->where('id', $t)->value('user'));
    }

    public function test_new_rate_recalculates_total_only_before_payment(): void
    {
        $cust = $this->zzUser(8);
        $this->actAs($this->zzUser(1));
        $bill = $this->order($cust, 1, 0);
        [$d] = $this->line($cust, $bill, 10, 2, 3);

        $this->act(AdminOrderController::class, 'saveNewrate', ['id' => $bill, 'rate' => '5.5']);
        $this->assertSame('5.500', (string) DB::table('bill_product')->where('id', $bill)->value('exchange_rate'));
        $this->assertSame('5.500', (string) DB::table('bill_product_detail')->where('id', $d)->value('exchange_rate'));
        // (10 × 2 × 5.5) + (3 × 5.5)
        $this->assertSame('126.50', (string) DB::table('bill_product')->where('id', $bill)->value('total'));

        DB::table('bill_product')->where('id', $bill)->update(['status' => 2]);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminOrderController::class, 'saveNewrate', ['id' => $bill, 'rate' => '9']));
        $this->assertSame('5.500', (string) DB::table('bill_product')->where('id', $bill)->value('exchange_rate'));
        $this->assertSame('126.50', (string) DB::table('bill_product')->where('id', $bill)->value('total'));
    }

    public function test_change_send_type_updates_order_and_trackings(): void
    {
        $cust = $this->zzUser(8);
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

    public function test_take_a_photo_toggles_item_and_its_trackings(): void
    {
        $cust = $this->zzUser(8);
        $this->actAs($this->zzUser(1));
        $bill = $this->order($cust, 0);
        [$d, $t] = $this->line($cust, $bill);

        $this->act(AdminOrderDetailController::class, 'saveTakeapic', ['id' => $d, 'value' => '1']);
        $this->assertSame('1', (string) DB::table('bill_product_detail')->where('id', $d)->value('is_takeapic'));
        $this->assertSame('1', (string) DB::table('tracking')->where('id', $t)->value('is_takeapic'));
        $this->assertStringContainsString('ถ่ายรูป : <input type="checkbox" checked class="js-switch" onchange="SaveCartTakeapic', $this->act(AdminOrderDetailController::class, 'loadDetail', ['id' => $bill]));
        $this->act(AdminOrderDetailController::class, 'saveTakeapic', ['id' => $d, 'value' => '0']);
        $this->assertSame('0', (string) DB::table('bill_product_detail')->where('id', $d)->value('is_takeapic'));
        $this->assertSame('0', (string) DB::table('tracking')->where('id', $t)->value('is_takeapic'));
    }

    public function test_printorder_grid_and_save_print(): void
    {
        $cust = $this->zzUser(8);
        $this->actAs($this->zzUser(1));
        $done = $this->order($cust, 3, 300, ['run_id' => '0']);
        $open = $this->order($cust, 1);

        $html = $this->act(AdminPrintorderController::class, 'grid', ['txtsearch' => $cust->code.'|-2', 'page' => '1']);
        $this->assertStringContainsString('admin/printorder/'.$done.'">ORD-'.$done.'</a>', $html);
        $this->assertStringNotContainsString('admin/printorder/'.$open.'"', $html);

        $this->act(AdminPrintorderController::class, 'savePrint', ['id' => $done, 'status' => '1']);
        $this->assertSame('1', (string) DB::table('bill_product')->where('id', $done)->value('is_print'));
        $this->act(AdminPrintorderController::class, 'savePrint', ['id' => $done, 'status' => '5']);
        $this->assertSame('1', (string) DB::table('bill_product')->where('id', $done)->value('is_print'));
        $this->assertStringContainsString('admin/printorder/'.$done, $this->act(AdminPrintorderController::class, 'grid', ['txtsearch' => $cust->code.'|1', 'page' => '1']));
        $this->assertStringNotContainsString('admin/printorder/'.$done, $this->act(AdminPrintorderController::class, 'grid', ['txtsearch' => $cust->code.'|0', 'page' => '1']));
    }
}
