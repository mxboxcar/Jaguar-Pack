<?php

namespace Tests\Feature\Money;

use App\Domain\Money\Money;
use App\Http\Controllers\Balance\Admin\AdminBalanceCnController;
use App\Http\Controllers\Balance\Admin\AdminBalanceCompanyController;
use App\Http\Controllers\Balance\Admin\AdminBalanceController;
use App\Http\Controllers\Payment\Admin\AdminPaymentController;
use App\Http\Controllers\Points\Admin\AdminShopCashbackController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * E2E admin write flows (2026-10-18, fix/admin-writes) — ข้อที่พบแล้วแก้ ทดสอบกับ DB bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class AdminWritesDbTest extends BenchDbTestCase
{
    private function admin(): User
    {
        $a = $this->zzUser(1);
        $this->actAs($a);

        return $a;
    }

    private function shippingTypeId(): int
    {
        return (int) DB::table('shipping_type')->where('is_delete', 0)->orderBy('id')->value('id');
    }

    /** บิล zz + รายการ 1 แถว (ราคา 500) + แถวตัดเงิน (-500) ตามสถานะ; ยกเลิกแล้ว = มีแถวคืนเงิน (+500) ด้วย */
    private function bill(User $cust, int $status): int
    {
        $payPrefix = (string) $this->prefixId('payment');
        $id = $this->insertRow('bill_payment', [
            'user' => $cust->id, 'status' => $status, 'total' => 500, 'price_shipping' => 500, 'price_thai_storage' => 0,
            'price_thai_shipping' => 0, 'shipping_type' => $this->shippingTypeId(), 'sender_type' => 0, 'send_name' => 'zz_old',
        ]);
        $this->insertRow('bill_payment_detail', ['id' => 0, 'payment_id' => $id, 'user' => $cust->id, 'price_kg' => 500, 'tracking' => 'zz_trk']);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -500, 'detail' => 'zz pay', 'type' => 3, 'prefix' => $payPrefix, 'prefix_ref_id' => $id]);
        if ($status === -1) {
            $this->insertRow('balance', ['user' => $cust->id, 'amount' => 500, 'detail' => 'zz refund', 'type' => 3, 'prefix' => $payPrefix, 'prefix_ref_id' => $id]);
        }

        return $id;
    }

    private function senderPost(int $billId): array
    {
        return ['id' => (string) $billId, 'a_name' => 'zz_new', 'a_address' => 'a', 'a_phone' => '1', 'a_type' => (string) $this->shippingTypeId(),
            'b_name' => 'undefined', 'b_address' => 'undefined', 'b_phone' => 'undefined'];
    }

    public function test_change_sender_on_cancelled_bill_leaves_money_alone(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 1000);
        $this->admin();
        $billId = $this->bill($cust, -1);
        $cash = $this->cash($cust);
        $before = (array) DB::table('bill_payment')->where('id', $billId)->first();

        $out = $this->act(AdminPaymentController::class, 'saveNewSender', $this->senderPost($billId));

        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertStringContainsString(AdminPaymentController::SENDER_CANCELLED, $out);
        $this->assertSame($cash, $this->cash($cust), 'ยอดเงินลูกค้าต้องไม่เปลี่ยน');
        $this->assertSame($before, (array) DB::table('bill_payment')->where('id', $billId)->first(), 'บิลที่ยกเลิกแล้วต้องไม่ถูกแก้');
    }

    public function test_change_sender_on_open_bill_still_recalculates(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 1000);
        $this->admin();
        $billId = $this->bill($cust, 1);

        $out = $this->act(AdminPaymentController::class, 'saveNewSender', $this->senderPost($billId));

        $this->assertStringContainsString('SuccessBox(', $out);
        $bill = DB::table('bill_payment')->where('id', $billId)->first();
        $this->assertSame('zz_new', $bill->send_name);
        $deduct = DB::table('balance')->where('user', $cust->id)->where('type', 3)->where('prefix_ref_id', $billId)->value('amount');
        $this->assertSame(-1 * (float) $bill->total, (float) $deduct, 'แถวตัดเงิน = ยอดบิลใหม่');
        $this->assertStringContainsString('ไม่พบข้อมูล', $this->act(AdminPaymentController::class, 'saveNewSender', $this->senderPost(2147483000)));
    }

    public function test_admin_balance_amount_rejects_odd_input(): void
    {
        $cust = $this->zzUser();
        $this->admin();
        $code = (string) $cust->getRawOriginal('code');
        $post = fn (string $amount, string $id = '') => ['id' => $id, 'code' => $code, 'amount' => $amount, 'type' => '1', 'detail' => 'zz_adj'];

        foreach (['abc', '', '1,00', '99999999999999999999'] as $bad) {
            $out = $this->act(AdminBalanceController::class, 'saveAmount', $post($bad));
            $this->assertStringContainsString(Money::BAD_ADMIN_AMOUNT, $out, $bad);
            $this->assertStringContainsString(Money::BAD_ADMIN_AMOUNT, $this->act(AdminBalanceCnController::class, 'saveAmount', $post($bad)), $bad);
        }
        $this->assertSame(0.0, $this->cash($cust));
        $this->assertSame(0.0, $this->cash($cust, 'balance_cn'));

        $this->assertStringContainsString('SuccessBox(', $this->act(AdminBalanceController::class, 'saveAmount', $post('1,000')));
        $this->assertSame(1000.0, $this->cash($cust), '"1,000" = 1000 (เดิม 1.00)');
        $rowId = (string) DB::table('balance')->where('user', $cust->id)->value('id');
        // แก้แถวเดิมด้วยค่าผิด = เดิมยอดกลายเป็น 0
        $this->act(AdminBalanceController::class, 'saveAmount', $post('x', $rowId));
        $this->assertSame(1000.0, $this->cash($cust));

        $companyBefore = DB::table('balance_company')->count();
        $out = $this->act(AdminBalanceCompanyController::class, 'saveAmount', ['id' => '', 'amount' => 'abc', 'detail' => 'zz_co']);
        $this->assertStringContainsString(Money::BAD_ADMIN_AMOUNT, $out);
        $this->assertSame($companyBefore, DB::table('balance_company')->count());
    }

    public function test_cashback_cancel_twice_keeps_first_reason(): void
    {
        $cust = $this->zzUser();
        $this->admin();
        $head = $this->insertRow('shop_cashback', ['user' => $cust->id, 'total' => 10, 'remark' => 'zz']);
        $detail = $this->insertRow('shop_cashback_detail', ['shop_cashback_id' => $head, 'order' => 'zz_o', 'price' => 10, 'status' => 1]);

        $this->act(AdminShopCashbackController::class, 'saveCancel', ['id' => (string) $detail, 'cancel_remark' => 'first']);
        $this->act(AdminShopCashbackController::class, 'saveCancel', ['id' => (string) $detail, 'cancel_remark' => 'second']);

        $row = DB::table('shop_cashback_detail')->where('id', $detail)->first();
        $this->assertSame('-1', (string) $row->status);
        $this->assertStringStartsWith('first ,', (string) $row->cancel_remark);
    }
}
