<?php

namespace Tests\Feature\Payment;

use App\Http\Controllers\Payment\Admin\AdminAlipayController;
use App\Http\Controllers\Payment\Admin\AdminCouponController;
use App\Http\Controllers\Payment\Admin\AdminPaymentController;
use App\Http\Controllers\Payment\AlipayController;
use App\Http\Controllers\Payment\PaymentController;
use App\Models\User;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * พฤติกรรมของ PACK ในกลุ่ม payment (รันกับ admin_pack_bench ใน transaction ที่ rollback — opt-in JL_BENCH_DB_TESTS=1)
 * ค่าที่คาดหวังได้จากการอ่านไฟล์เดิมของ PACK (/home/admin/bench/pack-old) — ดู docs/modules/payment.md "PACK (2026-10-22)"
 */
class PaymentPackDbTest extends BenchDbTestCase
{
    private function customer(string $pin = 'zz1234'): User
    {
        $u = $this->zzUser(2);
        DB::table('user')->where('id', $u->id)->update(['pin' => $pin, 'email' => 'zz@example.invalid']);

        return User::query()->findOrFail($u->id);
    }

    private function tracking(User $cust, array $extra = []): int
    {
        return $this->insertRow('tracking', $extra + ['tracking' => 'zz_pk_'.bin2hex(random_bytes(3)), 'user' => $cust->id, 'payment_id' => 0,
            'weight' => 1, 'width' => 10, 'height' => 10, 'long' => 10, 'amount' => 1, 'lot_id' => 1, 'no' => 1, 'product_type_id' => 1,
            'price_kg' => 100, 'price_cubic' => 0, 'price_amount' => 0, 'price_qc' => 0, 'price_repack' => 0, 'price_express' => 0,
            'price_else' => 0, 'price_craft' => 0, 'header' => 0, 'quote' => 'zz_q']);
    }

    private function shippingType(float $storage = 50, float $shipping = 0): int
    {
        return $this->insertRow('shipping_type', ['name' => 'zz_pk ship', 'is_enable' => 1, 'price_extened' => 0, 'price_thai_storage' => $storage,
            'price_thai_shipping' => $shipping, 'is_use_rate' => 0, 'is_quick_show' => 0, 'no' => 0]);
    }

    private function temp(User $cust, array $trackings): string
    {
        $ran = 'ZZ'.strtoupper(bin2hex(random_bytes(6)));
        foreach ($trackings as $t) {
            $this->insertRow('bill_payment_detail_temp', ['shipping_id' => $t, 'random' => $ran, 'create_by' => $cust->id]);
        }

        return $ran;
    }

    private function coupon(string $name, float $price, int $paymentId = 0): int
    {
        return $this->insertRow('coupon', ['name' => $name, 'price' => $price, 'payment_id' => $paymentId]);
    }

    private function submit(string $ran, int $st, array $extra = []): string
    {
        return $this->act(PaymentController::class, 'saveSubmitPayment', $extra + ['id' => $ran, 'ref_id' => $ran, 'shipping_type' => $st,
            'shipping_type_radio' => '', 'sender_type' => 1, 'name' => 'zz_n', 'address' => 'zz_a', 'phone' => '0', 'sender_name' => 'zz_s',
            'sender_address' => 'zz_sa', 'sender_phone' => '1', 'submit_remark' => 'zz_r', 'paymentpin' => 'zz1234',
            'is_vat' => '0', 'discount_code' => '']);
    }

    private function points(int $billId): array
    {
        return DB::table('balance_point')->where('prefix', $this->prefixId('export'))->where('prefix_ref_id', (string) $billId)
            ->orderBy('id')->pluck('amount')->map(fn ($v) => (string) $v)->all();
    }

    public function test_customer_payment_needs_pin_and_takes_the_coupon_before_vat(): void
    {
        $cust = $this->customer('');
        $this->actAs($cust);
        $this->credit($cust, 1000);
        $st = $this->shippingType();
        $t1 = $this->tracking($cust);
        $child = $this->tracking($cust, ['header' => $t1, 'tracking' => 'zz_pk_child', 'price_kg' => 0]);
        $ran = $this->temp($cust, [$t1]);
        $code = 'ZZC'.strtoupper(bin2hex(random_bytes(3)));
        $coupon = $this->coupon($code, 20);

        $this->assertStringContainsString('method : "new"', $this->submit($ran, $st), 'ยังไม่มี PIN = กล่องตั้ง PIN');
        DB::table('user')->where('id', $cust->id)->update(['pin' => 'zz1234']);
        $cust = User::query()->findOrFail($cust->id);
        $this->actAs($cust);
        $this->assertStringContainsString('method : "verified"', $this->submit($ran, $st, ['paymentpin' => '']));
        $this->assertStringContainsString('PIN จ่ายเงินไม่ถูกต้อง', $this->submit($ran, $st, ['paymentpin' => 'nope']));
        $this->assertStringContainsString("ErrorBox('ไม่พบคูปองส่วนลดนี้'", $this->submit($ran, $st, ['discount_code' => 'ZZ_NO_SUCH']));
        $this->assertSame(0, DB::table('bill_payment')->where('user', $cust->id)->count());

        $out = $this->submit($ran, $st, ['is_vat' => '1', 'discount_code' => strtolower($code)]);
        $this->assertStringContainsString('SuccessBox(', $out);
        $bill = DB::table('bill_payment')->where('user', $cust->id)->first();
        // ก่อน VAT = ค่าฝาก 50 + ค่าเปลี่ยนผู้ส่ง (change_sender) + ค่าพัสดุ 100 - คูปอง 20; VAT = x * v / 100 ตัดเหลือ 2 ตำแหน่ง
        $charge = (float) Lookup::getSetting('change_sender');
        $v = (float) Lookup::getSetting('vat');
        $before = 150 + $charge - 20;
        $vat = floor($before * $v) / 100;
        $this->assertSame([round($before + $vat, 2), round(50 + $charge, 2), $vat, round($before + $vat, 2), 1.0, $v, 0.0, 0.0, 0.0, 0.0],
            array_map('floatval', [$bill->total, $bill->price_thai_storage, $bill->vat_before, $bill->shipvat, $bill->is_vat, $bill->v,
                $bill->vat, $bill->shipvat_before, $bill->shipv, $bill->discount]));
        $this->assertSame((string) $bill->id, (string) DB::table('coupon')->where('id', $coupon)->value('payment_id'), 'คูปองผูกกับบิล (ใช้ซ้ำไม่ได้)');
        $this->assertSame((string) $bill->id, (string) DB::table('tracking')->where('id', $child)->value('payment_id'), 'tracking ลูก (header) ผูกบิลด้วย');
        $this->assertCount(1, DB::table('bill_payment_detail')->where('payment_id', $bill->id)->get(), 'แถวลูกไม่ลง bill_payment_detail');
        $this->assertSame(round(1000 - $before - $vat, 2), $this->cash($cust));

        // บิลละ 20 วินาที / คูปองที่ใช้แล้ว = ไม่พบ (ตรวจคูปองก่อน 20 วินาทีแบบเดิม)
        $t2 = $this->tracking($cust);
        $ran2 = $this->temp($cust, [$t2]);
        $this->assertStringContainsString('ไม่พบคูปองส่วนลดนี้', $this->submit($ran2, $st, ['discount_code' => $code]));
        $this->assertMatchesRegularExpression('/ไม่สามารถสร้างรายการได้ กรุณารอ \d+ วินาที/', $this->submit($ran2, $st));
        $this->assertSame(1, DB::table('bill_payment')->where('user', $cust->id)->count());
    }

    public function test_customer_payment_without_vat_keeps_vat_columns_zero(): void
    {
        $cust = $this->customer();
        $this->actAs($cust);
        $this->credit($cust, 1000);
        $st = $this->shippingType(40, 10);
        $ran = $this->temp($cust, [$this->tracking($cust)]);

        $this->assertStringContainsString('SuccessBox(', $this->submit($ran, $st, ['sender_type' => 0]));
        $bill = DB::table('bill_payment')->where('user', $cust->id)->first();
        $this->assertSame([150.0, 0.0, 0.0, 0.0], array_map('floatval', [$bill->total, $bill->is_vat, $bill->vat_before, $bill->shipvat]));
        $this->assertSame(850.0, $this->cash($cust));
    }

    public function test_discount_code_lookup_and_shipping_price_preview(): void
    {
        $cust = $this->customer();
        $this->actAs($cust);
        $code = 'ZZD'.strtoupper(bin2hex(random_bytes(3)));
        $this->coupon($code, 35.5);
        $used = 'ZZU'.strtoupper(bin2hex(random_bytes(3)));
        $this->coupon($used, 10, 999999);

        $this->assertSame("35.50\n", $this->act(PaymentController::class, 'loadDiscoutcode', ['code' => $code]));
        $this->assertSame("ErrorBox('ไม่มีรหัสส่วนลดนี้', 'CloseLoading()');", $this->act(PaymentController::class, 'loadDiscoutcode', ['code' => $used]));
        $this->assertSame('', trim($this->act(PaymentController::class, 'loadDiscoutcode', ['code' => ''])), 'ค่าว่าง = ไม่ตอบอะไร (เดิมแค่บรรทัดว่างท้ายไฟล์)');

        $st = $this->shippingType(50, 0);
        $ran = $this->temp($cust, [$this->tracking($cust)]);
        $j = json_decode($this->act(PaymentController::class, 'loadShippingPrice', ['id' => $st, 'payment_ref' => $ran, 'sender' => 0, 'is_vat' => 0, 'discount' => $code]), true);
        $this->assertEquals(114.5, $j['total'], '50 + 100 - 35.50');
        $this->assertStringContainsString('ส่วนลดท้ายบิล', $j['html']);
        $j = json_decode($this->act(PaymentController::class, 'loadShippingPrice', ['id' => $st, 'payment_ref' => $ran, 'sender' => 0, 'is_vat' => 1, 'discount' => $code]), true);
        // numberformatforsubmit() เดิม "ตัด" ทศนิยม (ไม่ปัด): v = 7 → 114.5 * 7 / 100 = 8.015 → 8.01
        $this->assertSame('7', (string) Lookup::getSetting('vat'));
        $this->assertSame('122.51', $j['total']);
    }

    public function test_customer_cancel_refunds_and_deducts_points(): void
    {
        $cust = $this->customer();
        $this->actAs($cust);
        $bill = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 1, 'total' => 300, 'price_shipping' => 300, 'cancel_remark' => '']);
        $t = $this->tracking($cust, ['payment_id' => $bill]);
        $this->credit($cust, -300);

        $this->act(PaymentController::class, 'saveDelete', ['id' => $bill]);
        $this->act(PaymentController::class, 'saveDelete', ['id' => $bill]);

        $this->assertSame('-1', (string) DB::table('bill_payment')->where('id', $bill)->value('status'));
        $this->assertSame('0', (string) DB::table('tracking')->where('id', $t)->value('payment_id'));
        $this->assertSame(0.0, $this->cash($cust), 'คืนเงินครั้งเดียว');
        $divide = (float) Lookup::getSetting('divide_point');
        $this->assertSame([number_format(-300 / $divide, 2, '.', '')], $this->points($bill), 'calPoint(id, "-") หักแต้ม');
    }

    public function test_admin_approve_awards_points_once(): void
    {
        $cust = $this->customer();
        $this->actAs($this->zzUser(1));
        $bill = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 1, 'total' => 520, 'price_shipping' => 450, 'cancel_remark' => '']);

        $this->act(AdminPaymentController::class, 'saveApproved', ['id' => $bill]);
        $this->act(AdminPaymentController::class, 'saveApproved', ['id' => $bill]);

        $this->assertSame('2', (string) DB::table('bill_payment')->where('id', $bill)->value('status'));
        $divide = (float) Lookup::getSetting('divide_point');
        $this->assertSame([number_format(450 / $divide, 2, '.', '')], $this->points($bill));
    }

    public function test_admin_cancel_only_handles_waiting_bills(): void
    {
        $cust = $this->customer();
        $this->actAs($this->zzUser(1));
        $unpaid = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 0, 'total' => 80, 'cancel_remark' => '']);
        $t = $this->tracking($cust, ['payment_id' => $unpaid]);

        // PACK (= jl): สถานะ 0 ไม่ยกเลิก (SR ยกเลิก + คืนพัสดุ)
        $this->assertStringContainsString('รายการถูกยืนยันไปแล้ว', $this->act(AdminPaymentController::class, 'saveDelete', ['id' => $unpaid, 'cancel_remark' => 'zz']));
        $this->assertSame('0', (string) DB::table('bill_payment')->where('id', $unpaid)->value('status'));
        $this->assertSame((string) $unpaid, (string) DB::table('tracking')->where('id', $t)->value('payment_id'));

        $bill = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 1, 'total' => 120, 'cancel_remark' => '']);
        $this->credit($cust, -120);
        $this->act(AdminPaymentController::class, 'saveDelete', ['id' => $bill, 'cancel_remark' => 'zz_why']);
        $this->act(AdminPaymentController::class, 'saveDelete', ['id' => $bill, 'cancel_remark' => 'zz_why']);
        $this->assertStringStartsWith('zz_why ,ยกเลิกโดย ', (string) DB::table('bill_payment')->where('id', $bill)->value('cancel_remark'));
        $this->assertSame(0.0, $this->cash($cust), 'คืนเงินครั้งเดียว');
    }

    public function test_admin_save_vat_follows_pack_formula_and_skips_cancelled_bills(): void
    {
        $cust = $this->customer();
        $this->actAs($this->zzUser(1));
        $st = $this->shippingType(50, 20);
        $bill = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 2, 'total' => 170, 'price_shipping' => 100,
            'shipping_type' => $st, 'sender_type' => 0, 'cancel_remark' => '']);
        $this->insertRow('bill_payment_detail', ['id' => 1, 'payment_id' => $bill, 'user' => $cust->id, 'tracking' => 'zz_pk', 'price_kg' => 100]);
        $row = $this->insertRow('balance', ['user' => $cust->id, 'amount' => -170, 'detail' => 'zz', 'type' => 3, 'prefix' => 18, 'prefix_ref_id' => $bill]);

        $this->assertSame('', $this->act(AdminPaymentController::class, 'saveVat', ['id' => $bill, 'value' => '1']));
        $b = DB::table('bill_payment')->where('id', $bill)->first();
        $v = (float) Lookup::getSetting('vat');
        // เปิด VAT: ยอด = ค่าฝาก 50 + ค่าพัสดุ 100 (+ ค่าส่งเพิ่ม 0) — ไม่รวมค่าส่งในไทยแบบเดิม
        $vat = floor(150 * $v) / 100;
        $this->assertSame([1.0, $vat, round(150 + $vat, 2), round(150 + $vat, 2), 3.0, 0.0, 0.0],
            array_map('floatval', [$b->is_vat, $b->vat_before, $b->shipvat, $b->total, $b->shipv, $b->vat, $b->shipvat_before]));
        $this->assertSame(-round(150 + $vat, 2), (float) DB::table('balance')->where('id', $row)->value('amount'));

        $this->act(AdminPaymentController::class, 'saveVat', ['id' => $bill, 'value' => '0']);
        $b = DB::table('bill_payment')->where('id', $bill)->first();
        $this->assertSame([0.0, 170.0, 0.0], array_map('floatval', [$b->is_vat, $b->total, $b->v]));
        $this->assertSame(-170.0, (float) DB::table('balance')->where('id', $row)->value('amount'));

        DB::table('bill_payment')->where('id', $bill)->update(['status' => -1]);
        $this->act(AdminPaymentController::class, 'saveVat', ['id' => $bill, 'value' => '1']);
        $this->assertSame('0.00', (string) DB::table('bill_payment')->where('id', $bill)->value('is_vat'), 'บิลที่ยกเลิกแล้วไม่แก้');
    }

    public function test_alipay_spends_yuan_first_and_customer_cancel_does_nothing(): void
    {
        $cust = $this->customer();
        $this->actAs($cust);
        $this->credit($cust, 1000);
        $this->credit($cust, 20, 'balance_cn');
        $rate = (float) Lookup::getSetting('rate_alipay');
        $post = ['id' => '', 'total' => '50', 'taobao_id' => 'zz_t', 'remark' => 'zz', 'upload_file' => '', 'paymentpin' => 'zz1234'];

        $this->assertStringContainsString('method : "verified"', $this->act(AlipayController::class, 'save', ['paymentpin' => ''] + $post));
        $this->assertStringContainsString('SuccessBox(', $this->act(AlipayController::class, 'save', $post));
        $bill = DB::table('bill_alipay')->where('user', $cust->id)->first();
        $this->assertSame(['50.00', number_format(30 * $rate, 2, '.', ''), '20.00'], [(string) $bill->total, (string) $bill->usecash, (string) $bill->usecash_cn]);

        // PACK: _alipay/cancel.php ถูก comment ทั้งไฟล์ → ไม่ยกเลิก ไม่คืนเงิน
        $this->assertSame('', $this->act(AlipayController::class, 'cancel', ['id' => $bill->id]));
        $this->assertSame('1', (string) DB::table('bill_alipay')->where('id', $bill->id)->value('status'));
        $this->assertSame(0.0, $this->cash($cust, 'balance_cn'));
        $this->assertSame(round(1000 - 30 * $rate, 2), $this->cash($cust));
        $this->assertStringStartsWith('ErrorBox(', $this->act(AlipayController::class, 'save', ['total' => 'abc'] + $post));
    }

    public function test_admin_alipay_save_rejects_cancelled_and_cancel_refunds_non_zero_parts(): void
    {
        $cust = $this->customer();
        $this->actAs($this->zzUser(1));
        $bill = $this->insertRow('bill_alipay', ['user' => $cust->id, 'status' => 1, 'total' => 30, 'rate' => 5, 'usecash' => 0, 'usecash_cn' => 20, 'cancel_remark' => '']);

        $this->assertStringContainsString('SuccessBox(', $this->act(AdminAlipayController::class, 'save', ['id' => $bill, 'approved_remark' => 'zz', 'upload_file' => '']));
        // PACK (= jl): บันทึกซ้ำบิลสถานะ 2 ได้ (แก้หลักฐาน)
        $this->assertStringContainsString('SuccessBox(', $this->act(AdminAlipayController::class, 'save', ['id' => $bill, 'approved_remark' => 'zz2', 'upload_file' => '']));
        $this->assertStringContainsString('รายการถูกยืนยันไปแล้ว', $this->act(AdminAlipayController::class, 'cancel', ['id' => $bill, 'cancel_remark' => 'zz']));
        $this->act(AdminAlipayController::class, 'rollback', ['id' => $bill]);
        $this->act(AdminAlipayController::class, 'cancel', ['id' => $bill, 'cancel_remark' => 'zz']);
        $this->act(AdminAlipayController::class, 'cancel', ['id' => $bill, 'cancel_remark' => 'zz']);

        $this->assertSame(20.0, $this->cash($cust, 'balance_cn'));
        $this->assertSame(0, DB::table('balance')->where('user', $cust->id)->count(), 'ยอดบาท 0 ไม่เขียนแถว balance');
        $this->assertStringContainsString("ErrorBox('รายการนี้ถูกยกเลิกไปแล้ว'", $this->act(AdminAlipayController::class, 'save', ['id' => $bill, 'upload_file' => '']));
    }

    public function test_admin_coupon_save_rules(): void
    {
        $this->actAs($this->zzUser(1));
        $name = 'ZZN'.strtoupper(bin2hex(random_bytes(3)));

        $this->assertStringStartsWith('ErrorBox(', $this->act(AdminCouponController::class, 'save', ['id' => '', 'name' => $name, 'price' => '1,00']));
        $this->assertStringStartsWith('ErrorBox(', $this->act(AdminCouponController::class, 'save', ['id' => '', 'name' => $name, 'price' => '-5']));
        $out = $this->act(AdminCouponController::class, 'save', ['id' => '', 'name' => $name, 'price' => '1,000']);
        $this->assertStringContainsString('SuccessBox(', $out);
        $c = DB::table('coupon')->where('name', $name)->first();
        $this->assertSame(['1000.00', '0', '0'], [(string) $c->price, (string) $c->payment_id, (string) $c->is_delete]);
        $this->assertStringContainsString('COUP-'.$c->id, $out);

        $other = $this->coupon('ZZO'.strtoupper(bin2hex(random_bytes(3))), 5);
        $otherName = (string) DB::table('coupon')->where('id', $other)->value('name');
        $this->assertStringContainsString('คูปองส่วนลดซ้ำ', $this->act(AdminCouponController::class, 'save', ['id' => $c->id, 'name' => $otherName, 'price' => '1']));
        $this->act(AdminCouponController::class, 'save', ['id' => $c->id, 'name' => $name.'X', 'price' => '12.5']);
        $this->assertSame([$name.'X', '12.50'], [(string) DB::table('coupon')->where('id', $c->id)->value('name'), (string) DB::table('coupon')->where('id', $c->id)->value('price')]);

        // คูปองที่ใช้แล้วแก้ไม่ได้ (ตอบสำเร็จแบบเดิม)
        $used = $this->coupon('ZZU'.strtoupper(bin2hex(random_bytes(3))), 7, 999999);
        $this->assertStringContainsString('SuccessBox(', $this->act(AdminCouponController::class, 'save', ['id' => $used, 'name' => 'zz_changed', 'price' => '1']));
        $this->assertSame('7.00', (string) DB::table('coupon')->where('id', $used)->value('price'));
    }

    public function test_admin_coupon_delete_touches_nothing(): void
    {
        $this->actAs($this->zzUser(1));
        $pt = $this->insertRow('product_type', ['name' => 'zz_pt']);
        $c = $this->coupon('ZZX'.strtoupper(bin2hex(random_bytes(3))), 5);

        // เดิม delete.php = สำเนาของ _admin/_product_type/delete.php → ลบ product_type ที่ id ตรงกัน (ไม่ใช่คูปอง)
        $this->assertSame("SuccessBox('', '".\App\Support\Legacy\Lang::get('success_process')."');", $this->act(AdminCouponController::class, 'delete', ['id' => $pt]));
        $this->act(AdminCouponController::class, 'delete', ['id' => $c]);
        $this->assertSame('0', (string) DB::table('product_type')->where('id', $pt)->value('is_delete'));
        $this->assertSame('0', (string) DB::table('coupon')->where('id', $c)->value('is_delete'));
    }
}
