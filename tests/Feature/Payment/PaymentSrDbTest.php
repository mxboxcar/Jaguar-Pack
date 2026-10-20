<?php

namespace Tests\Feature\Payment;

use App\Domain\Payment\PaymentTotals;
use App\Http\Controllers\Payment\Admin\AdminAlipayController;
use App\Http\Controllers\Payment\Admin\AdminPaymentController;
use App\Http\Controllers\Payment\Admin\AdminReceiptController;
use App\Http\Controllers\Payment\AlipayController;
use App\Http\Controllers\Payment\PaymentController;
use App\Models\User;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * พฤติกรรมเฉพาะ SR ของกลุ่ม payment (รันกับ admin_sr_bench ใน transaction ที่ rollback — opt-in JL_BENCH_DB_TESTS=1)
 * ค่าที่คาดหวังได้จากการอ่านไฟล์เดิมของ SR (/home/admin/bench/sr-prehotfix) — ดู docs/modules/payment.md "SR (2026-10-20)"
 */
class PaymentSrDbTest extends BenchDbTestCase
{
    private function customer(string $pin = 'zz1234'): User
    {
        $u = $this->zzUser(8);
        DB::table('user')->where('id', $u->id)->update(['pin' => $pin, 'email' => 'zz@example.invalid']);

        return User::query()->findOrFail($u->id);
    }

    private function tracking(User $cust, array $extra = []): int
    {
        return $this->insertRow('tracking', $extra + ['tracking' => 'zz_sr_'.bin2hex(random_bytes(3)), 'user' => $cust->id, 'payment_id' => 0,
            'weight' => 1, 'width' => 10, 'height' => 10, 'long' => 10, 'amount' => 1, 'lot_id' => 1, 'no' => 1, 'product_type_id' => 1,
            'price_kg' => 100, 'price_cubic' => 0, 'price_amount' => 0, 'price_qc' => 0, 'price_repack' => 0, 'price_express' => 0,
            'price_else' => 0, 'price_craft' => 0, 'subuser' => 'zz_sub', 'is_takeapic' => 1, 'header' => 0, 'quote' => 'zz_q']);
    }

    private function shippingType(float $storage = 50, float $shipping = 0): int
    {
        return $this->insertRow('shipping_type', ['name' => 'zz_sr ship', 'is_enable' => 1, 'price_extened' => 0, 'price_thai_storage' => $storage,
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

    private function submit(string $ran, int $st, array $extra = []): string
    {
        return $this->act(PaymentController::class, 'saveSubmitPayment', $extra + ['id' => $ran, 'ref_id' => $ran, 'shipping_type' => $st,
            'shipping_type_radio' => '', 'sender_type' => 1, 'name' => 'zz_n', 'address' => 'zz_a', 'phone' => '0', 'sender_name' => 'zz_s',
            'sender_address' => 'zz_sa', 'sender_phone' => '1', 'submit_remark' => 'zz_r', 'paymentpin' => 'zz1234']);
    }

    private function points(int $billId): array
    {
        return DB::table('balance_point')->where('prefix', $this->prefixId('export'))->where('prefix_ref_id', (string) $billId)
            ->orderBy('id')->pluck('amount')->map(fn ($v) => (string) $v)->all();
    }

    public function test_customer_payment_needs_pin_and_writes_sr_columns(): void
    {
        $cust = $this->customer('');
        $this->actAs($cust);
        $this->credit($cust, 1000);
        $st = $this->shippingType();
        $t1 = $this->tracking($cust);
        $child = $this->tracking($cust, ['header' => $t1, 'tracking' => 'zz_sr_child', 'price_kg' => 0]);
        $ran = $this->temp($cust, [$t1]);

        $this->assertStringContainsString('method : "new"', $this->submit($ran, $st), 'ยังไม่มี PIN = กล่องตั้ง PIN');
        DB::table('user')->where('id', $cust->id)->update(['pin' => 'zz1234']);
        $cust = User::query()->findOrFail($cust->id);
        $this->actAs($cust);
        $this->assertStringContainsString('method : "verified"', $this->submit($ran, $st, ['paymentpin' => '']));
        $this->assertStringContainsString('PIN จ่ายเงินไม่ถูกต้อง', $this->submit($ran, $st, ['paymentpin' => 'nope']));
        $this->assertSame(0, DB::table('bill_payment')->where('user', $cust->id)->count());

        $out = $this->submit($ran, $st);
        $this->assertStringContainsString('SuccessBox(', $out);
        $bill = DB::table('bill_payment')->where('user', $cust->id)->first();
        // total = storage 50 + ค่าเปลี่ยนผู้ส่ง (change_sender / charge_sender_edit ของรหัสนี้) + ค่าพัสดุ 100
        $charge = (float) PaymentTotals::senderCharge($cust->code);
        $this->assertSame(round(150 + $charge, 2), (float) $bill->total);
        $this->assertSame(round(50 + $charge, 2), (float) $bill->price_thai_storage);
        $this->assertSame((string) $bill->id, (string) DB::table('tracking')->where('id', $child)->value('payment_id'), 'tracking ลูก (header) ผูกบิลด้วย');
        $d = DB::table('bill_payment_detail')->where('payment_id', $bill->id)->get();
        $this->assertCount(1, $d, 'แถวลูกไม่ลง bill_payment_detail');
        $this->assertSame(['zz_sub', '1'], [(string) $d[0]->subuser, (string) $d[0]->is_takeapic]);
        $this->assertSame(round(1000 - 150 - $charge, 2), $this->cash($cust));

        // SR: สร้างได้ 1 บิลต่อ 20 วินาที (ก่อนตรวจรายการ)
        $t2 = $this->tracking($cust);
        $out = $this->submit($this->temp($cust, [$t2]), $st);
        $this->assertMatchesRegularExpression('/ไม่สามารถสร้างรายการได้ กรุณารอ \d+ วินาที/', $out);
        $this->assertSame(1, DB::table('bill_payment')->where('user', $cust->id)->count());
    }

    public function test_sender_charge_follows_charge_sender_edit(): void
    {
        $default = Lookup::getSetting('change_sender');
        $this->assertSame($default, PaymentTotals::senderCharge('ZZ_NOT_LISTED'));
        foreach (explode(',', (string) Lookup::getSetting('charge_sender_edit')) as $item) {
            $p = explode('|', $item);
            if (count($p) === 2) {
                $this->assertSame($p[1], PaymentTotals::senderCharge($p[0]), $item);
            }
        }
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
        $this->assertSame([number_format(-300 / $divide, 2, '.', '')], $this->points($bill), 'SR calPoint(id, "-") หักแต้ม');
    }

    public function test_admin_approve_awards_points_once_and_clears_shelf(): void
    {
        $cust = $this->customer();
        $this->actAs($this->zzUser(1));
        $bill = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 1, 'total' => 520, 'price_shipping' => 450, 'cancel_remark' => '']);
        $t = $this->tracking($cust, ['payment_id' => $bill]);
        $this->insertRow('bill_payment_detail', ['id' => $t, 'payment_id' => $bill, 'user' => $cust->id, 'tracking' => 'zz_sr']);
        $shelf = $this->insertRow('warehouse_section_detail', ['tracking_id' => $t, 'user' => $cust->id, 'section_id' => 0]);

        $this->act(AdminPaymentController::class, 'saveApproved', ['id' => $bill]);
        $this->act(AdminPaymentController::class, 'saveApproved', ['id' => $bill]);

        $this->assertSame('2', (string) DB::table('bill_payment')->where('id', $bill)->value('status'));
        $divide = (float) Lookup::getSetting('divide_point');
        $this->assertSame([number_format(450 / $divide, 2, '.', '')], $this->points($bill));
        $this->assertSame('1', (string) DB::table('warehouse_section_detail')->where('id', $shelf)->value('is_delete'));
    }

    public function test_admin_cancel_of_unpaid_bill_releases_parcels_without_refund(): void
    {
        $cust = $this->customer();
        $this->actAs($this->zzUser(1));
        $bill = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 0, 'total' => 80, 'cancel_remark' => '']);
        $t = $this->tracking($cust, ['payment_id' => $bill]);

        $out = $this->act(AdminPaymentController::class, 'saveDelete', ['id' => $bill, 'cancel_remark' => 'zz_why']);

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertSame('-1', (string) DB::table('bill_payment')->where('id', $bill)->value('status'));
        $this->assertStringStartsWith('zz_why ,ยกเลิกโดย ', (string) DB::table('bill_payment')->where('id', $bill)->value('cancel_remark'));
        $this->assertSame('0', (string) DB::table('tracking')->where('id', $t)->value('payment_id'));
        $this->assertSame(0, DB::table('balance')->where('prefix_ref_id', (string) $bill)->where('prefix', $this->prefixId('payment'))->count());
    }

    public function test_alipay_spends_yuan_first_and_cancel_refunds_both(): void
    {
        $cust = $this->customer();
        $this->actAs($cust);
        $this->credit($cust, 1000);
        $this->credit($cust, 20, 'balance_cn');
        $rate = (float) Lookup::getSetting('rate_alipay');
        $post = ['id' => '', 'total' => '50', 'taobao_id' => 'zz_t', 'remark' => 'zz', 'upload_file' => '', 'paymentpin' => 'zz1234'];

        $this->assertStringContainsString('method : "verified"', $this->act(AlipayController::class, 'save', ['paymentpin' => ''] + $post));
        $out = $this->act(AlipayController::class, 'save', $post);
        $this->assertStringContainsString('SuccessBox(', $out);
        $bill = DB::table('bill_alipay')->where('user', $cust->id)->first();
        $this->assertSame(['50.00', number_format(30 * $rate, 2, '.', ''), '20.00'], [(string) $bill->total, (string) $bill->usecash, (string) $bill->usecash_cn]);
        $this->assertSame(0.0, $this->cash($cust, 'balance_cn'));
        $this->assertSame(round(1000 - 30 * $rate, 2), $this->cash($cust));

        $this->act(AlipayController::class, 'cancel', ['id' => $bill->id]);
        $this->act(AlipayController::class, 'cancel', ['id' => $bill->id]);
        $this->assertSame(20.0, $this->cash($cust, 'balance_cn'));
        $this->assertSame(1000.0, $this->cash($cust));

        // ยอดน้อยกว่าเงินหยวน: ใช้หยวนอย่างเดียว ไม่มีแถว balance
        $this->act(AlipayController::class, 'save', ['total' => '5'] + $post);
        $b2 = DB::table('bill_alipay')->where('user', $cust->id)->orderByDesc('id')->first();
        $this->assertSame(['0.00', '5.00'], [(string) $b2->usecash, (string) $b2->usecash_cn]);
        $this->assertSame(0, DB::table('balance')->where('prefix', $this->prefixId('alipay'))->where('prefix_ref_id', (string) $b2->id)->count());

        $this->assertStringStartsWith('ErrorBox(', $this->act(AlipayController::class, 'save', ['total' => 'abc'] + $post));
    }

    public function test_alipay_negative_yuan_balance_counts_as_zero(): void
    {
        $cust = $this->customer();
        $this->actAs($cust);
        $this->credit($cust, 1000);
        $this->credit($cust, -5, 'balance_cn');
        $rate = (float) Lookup::getSetting('rate_alipay');

        $this->act(AlipayController::class, 'save', ['id' => '', 'total' => '10', 'upload_file' => '', 'paymentpin' => 'zz1234']);

        $bill = DB::table('bill_alipay')->where('user', $cust->id)->first();
        $this->assertSame([number_format(10 * $rate, 2, '.', ''), '0.00'], [(string) $bill->usecash, (string) $bill->usecash_cn]);
        $this->assertSame(-5.0, $this->cash($cust, 'balance_cn'));
    }

    public function test_admin_alipay_approve_only_pending_and_cancel_refunds_both(): void
    {
        $cust = $this->customer();
        $this->actAs($this->zzUser(1));
        $bill = $this->insertRow('bill_alipay', ['user' => $cust->id, 'status' => 1, 'total' => 30, 'rate' => 5, 'usecash' => 50, 'usecash_cn' => 20, 'cancel_remark' => '']);

        $this->assertStringContainsString('SuccessBox(', $this->act(AdminAlipayController::class, 'save', ['id' => $bill, 'approved_remark' => 'zz', 'upload_file' => '']));
        $this->assertStringContainsString('รายการไม่พร้อมที่จะดำเนินการนี้', $this->act(AdminAlipayController::class, 'save', ['id' => $bill, 'upload_file' => '']));
        $this->act(AdminAlipayController::class, 'rollback', ['id' => $bill]);
        $this->act(AdminAlipayController::class, 'cancel', ['id' => $bill, 'cancel_remark' => 'zz']);
        $this->act(AdminAlipayController::class, 'cancel', ['id' => $bill, 'cancel_remark' => 'zz']);

        $this->assertSame(50.0, $this->cash($cust));
        $this->assertSame(20.0, $this->cash($cust, 'balance_cn'));
    }

    public function test_receipt_grid_lists_bills_with_receipt_and_needs_permission(): void
    {
        $cust = $this->customer();
        $bill = $this->insertRow('bill_payment', ['user' => $cust->id, 'status' => 3, 'total' => 99, 'receipt_id' => 'ZZRC-'.bin2hex(random_bytes(3)),
            'receipt_name' => 'zz <b>name</b>', 'receipt_date' => '2026-10-20 10:00:00', 'cancel_remark' => '']);
        $rc = (string) DB::table('bill_payment')->where('id', $bill)->value('receipt_id');

        $this->actAs($cust);
        $this->assertSame('', $this->act(AdminReceiptController::class, 'grid', ['page' => '1', 'txtsearch' => '|']), 'ไม่มีสิทธิ์ = ว่าง');

        $this->actAs(User::query()->findOrFail(11938));
        $html = $this->act(AdminReceiptController::class, 'grid', ['page' => '1', 'txtsearch' => $rc.'|']);
        $this->assertStringContainsString('admin/export/receipt/'.$bill, $html);
        $this->assertStringContainsString('zz &lt;b&gt;name&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('_blank', $html);
        $this->assertStringContainsString('admin/export/receipt/'.$bill,
            $this->act(AdminReceiptController::class, 'grid', ['page' => '1', 'txtsearch' => strtolower((string) $cust->code).'|']));
    }
}
