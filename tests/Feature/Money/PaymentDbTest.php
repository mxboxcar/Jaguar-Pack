<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Payment\Admin\AdminPaymentCreateController;
use App\Models\User;
use App\Support\Legacy\Pricing;
use Illuminate\Support\Facades\DB;

/**
 * payment (รายงานข้อ 13): ราคาติดลบตอนแอดมินสร้างบิล + getShippingTypePrice_EditSender join คอลัมน์ผิด
 * รันกับ admin_sr_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class PaymentDbTest extends BenchDbTestCase
{
    private function tracking(User $cust, array $extra = []): int
    {
        return $this->insertRow('tracking', $extra + ['tracking' => 'zz_test_'.bin2hex(random_bytes(3)), 'user' => $cust->id, 'payment_id' => 0,
            'weight' => 1, 'width' => 10, 'height' => 10, 'long' => 10, 'amount' => 1, 'lot_id' => 0, 'no' => 0, 'product_type_id' => 0]);
    }

    private function shippingType(int $useRate = 0): int
    {
        return $this->insertRow('shipping_type', ['name' => 'zz_test ship', 'is_enable' => 1, 'price_extened' => 0, 'price_thai_storage' => 0,
            'price_thai_shipping' => 0, 'is_use_rate' => $useRate, 'is_quick_show' => 0, 'no' => 0]);
    }

    public function test_save_create_blocks_negative_prices(): void
    {
        $cust = $this->zzUser();
        $this->actAs($this->zzUser(1));
        $t1 = $this->tracking($cust);
        $t2 = $this->tracking($cust);

        $out = $this->act(AdminPaymentCreateController::class, 'saveCreate', [
            'tracking_id' => [$t1, $t2], 'user_id' => $cust->id, 'prices' => '|10*0*0*0*0*0*0|5*0*0*0*-50*0*0',
        ]);
        $this->assertStringStartsWith('ErrorBox(', $out);
        $this->assertSame(0, DB::table('bill_payment_detail_temp_admin')->whereIn('shipping_id', [$t1, $t2])->count(), 'บล็อกการเซฟทั้งชุด');

        $out = $this->act(AdminPaymentCreateController::class, 'saveCreate', [
            'tracking_id' => [$t1, $t2], 'user_id' => $cust->id, 'prices' => '|10*0*0*0*0*0*0|5*1,000*0*0*0*0*0',
        ]);
        $this->assertStringContainsString('window.location.href', $out);
        $this->assertSame(2, DB::table('bill_payment_detail_temp_admin')->whereIn('shipping_id', [$t1, $t2])->count());
    }

    public function test_submit_payment_refuses_negative_items_and_never_credits(): void
    {
        $cust = $this->zzUser();
        $this->actAs($this->zzUser(1));
        $this->credit($cust, 100);
        $st = $this->shippingType();
        $t1 = $this->tracking($cust);
        $ran = 'zz'.bin2hex(random_bytes(6));
        // temp ที่ถูกบันทึกไว้ก่อนแก้ (หรือยิงตรง) ราคาติดลบ
        $this->insertRow('bill_payment_detail_temp_admin', ['shipping_id' => $t1, 'random' => $ran, 'kg' => -500, 'cubic' => 0, 'qc' => 0, 'repack' => 0, 'express' => 0, 'else' => 0, 'craft' => 0]);

        $out = $this->act(AdminPaymentCreateController::class, 'saveSubmitPayment', ['user_id' => $cust->id, 'ref_id' => $ran, 'shipping_type' => $st, 'sender_type' => 0, 'pay_now' => 1]);
        $this->assertStringStartsWith('ErrorBox(', $out);
        $this->assertSame(0, DB::table('bill_payment')->where('user', $cust->id)->count());
        $this->assertSame(100.0, $this->cash($cust), 'เดิม: บิลยอด -500 + pay_now = เพิ่มเงินลูกค้า 500');
        $this->assertSame('0', (string) DB::table('tracking')->where('id', $t1)->value('payment_id'));

        // ราคาปกติ → สร้างบิล + ตัดเงิน
        DB::table('bill_payment_detail_temp_admin')->where('random', $ran)->update(['kg' => 40]);
        $out = $this->act(AdminPaymentCreateController::class, 'saveSubmitPayment', ['user_id' => $cust->id, 'ref_id' => $ran, 'shipping_type' => $st, 'sender_type' => 0, 'pay_now' => 1]);
        $this->assertStringContainsString('SuccessBox', $out);
        $this->assertSame(1, DB::table('bill_payment')->where('user', $cust->id)->count());
        $this->assertSame(60.0, $this->cash($cust));
    }

    public function test_edit_sender_price_uses_weight_of_the_bill_trackings(): void
    {
        $cust = $this->zzUser();
        $st = $this->shippingType(1);
        $this->insertRow('shipping_type_rate', ['shipping_type_id' => $st, 'type' => 1, 'min' => 0, 'max' => 4, 'value' => 100]);
        $this->insertRow('shipping_type_rate', ['shipping_type_id' => $st, 'type' => 1, 'min' => 5, 'max' => 999, 'value' => 500]);
        $heavy = $this->tracking($cust, ['weight' => 12]);
        $pay = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 1, 'total' => 0, 'shipping_type' => $st, 'cancel_remark' => '']);
        // bill_payment_detail: id = tracking.id, tracking_id = AUTO_INCREMENT ของตารางนี้เอง (ไม่ใช่ tracking.id)
        DB::table('bill_payment_detail')->insert(['create_date' => DB::raw('NOW()'), 'is_delete' => 0, 'id' => $heavy, 'tracking_id' => null,
            'payment_id' => $pay, 'tracking' => 'zz_test', 'weight' => 12, 'amount' => 1, 'user' => $cust->id]);

        $r = Pricing::getShippingTypePriceEditSender($st, $pay, 0);
        $this->assertSame('500.00', (string) $r['price_thai_shipping'], 'น้ำหนัก 12 กก. ของ tracking ในบิลนี้ → ช่วง 5–999');
    }
}
