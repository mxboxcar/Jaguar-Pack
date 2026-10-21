<?php

namespace Tests\Feature\Money;

use App\Domain\Tracking\CombineTrack;
use App\Http\Controllers\Payment\Admin\AdminPaymentController;
use App\Http\Controllers\Points\Admin\AdminShopCashbackController;
use App\Http\Controllers\Points\ShopCashbackController;
use App\Http\Controllers\Tracking\Admin\AdminTrackingController;
use App\Http\Controllers\Tracking\Admin\CombineTrackController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ความเสี่ยงที่ติดมาจากเว็บเดิม (fix/legacy-risks, เจ้าของอนุมัติให้แก้เฉพาะ Laravel 2026-10-18)
 * ใช้เฉพาะผู้ใช้ zz_fdn_cust (11937) / zz_fdn_admin (11938) + แถว zz_ ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class LegacyRisksDbTest extends BenchDbTestCase
{
    private const CUST = 11937;

    private const ADMIN = 11938;

    /** SR: _shop_cashback/save ถาม PIN จ่ายเงินก่อน — ตั้ง PIN ให้ลูกค้าทดสอบภายใน transaction ที่ rollback */
    private const PIN = '135790';

    /** shipping_type ใน admin_pack_bench: 43 = รับสินค้าเองที่โกดัง (ไม่มีค่าส่ง), 14 = นิวมงเซ้ง (storage 50 + shipping 100) */
    private const SHIP_FREE = 43;

    private const SHIP_150 = 14;

    private function cust(): User
    {
        return User::query()->findOrFail(self::CUST);
    }

    private function admin(): User
    {
        $a = User::query()->findOrFail(self::ADMIN);
        $this->actAs($a);

        return $a;
    }

    // ---- 1. save_new_sender: เปลี่ยนเป็นประเภทส่งที่แพงกว่า ต้องไม่ทำให้ยอดเงินติดลบ ----

    /** บิลที่ชำระแล้ว (status 1): รายการ 500 + ประเภทส่ง $ship (ยอด $total) + แถวตัดเงิน -$total */
    private function paidBill(User $cust, int $ship, float $total): int
    {
        $id = $this->insertRow('bill_payment', [
            'user' => $cust->id, 'status' => 1, 'total' => $total, 'price_shipping' => 500, 'price_thai_storage' => 0,
            'price_thai_shipping' => 0, 'shipping_type' => $ship, 'sender_type' => 0, 'send_name' => 'zz_old',
        ]);
        $this->insertRow('bill_payment_detail', ['id' => 0, 'payment_id' => $id, 'user' => $cust->id, 'price_kg' => 500, 'tracking' => 'zz_trk']);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -$total, 'detail' => 'zz pay', 'type' => 3,
            'prefix' => $this->prefixId('payment'), 'prefix_ref_id' => $id]);

        return $id;
    }

    private function senderPost(int $billId, int $ship): array
    {
        return ['id' => (string) $billId, 'a_name' => 'zz_new', 'a_address' => 'a', 'a_phone' => '1', 'a_type' => (string) $ship,
            'b_name' => 'undefined', 'b_address' => 'undefined', 'b_phone' => 'undefined'];
    }

    private function deduction(int $billId): float
    {
        return (float) DB::table('balance')->where('type', 3)->where('prefix', $this->prefixId('payment'))
            ->where('prefix_ref_id', $billId)->orderBy('id')->value('amount');
    }

    public function test_sender_change_to_more_expensive_may_go_negative(): void
    {
        // เจ้าของยืนยัน 2026-10-18: เปลี่ยนประเภทการส่งแล้วยอดติดลบได้ (เหมือนเว็บเดิม)
        $cust = $this->cust();
        $this->admin();
        $billId = $this->paidBill($cust, self::SHIP_FREE, 500);
        $this->credit($cust, 50 - $this->cash($cust));
        $this->assertSame(50.0, $this->cash($cust));

        $out = $this->act(AdminPaymentController::class, 'saveNewSender', $this->senderPost($billId, self::SHIP_150));

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertSame(-650.0, $this->deduction($billId));
        $this->assertSame(-100.0, $this->cash($cust));
    }

    public function test_sender_change_to_more_expensive_allowed_when_balance_covers(): void
    {
        $cust = $this->cust();
        $this->admin();
        $billId = $this->paidBill($cust, self::SHIP_FREE, 500);
        $this->credit($cust, 150 - $this->cash($cust)); // พอดี

        $out = $this->act(AdminPaymentController::class, 'saveNewSender', $this->senderPost($billId, self::SHIP_150));

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertSame(-650.0, $this->deduction($billId));
        $this->assertSame(0.0, $this->cash($cust));
        $this->assertSame((string) self::SHIP_150, (string) DB::table('bill_payment')->where('id', $billId)->value('shipping_type'));
    }

    public function test_sender_change_to_cheaper_refunds_difference_even_with_zero_balance(): void
    {
        $cust = $this->cust();
        $this->admin();
        $billId = $this->paidBill($cust, self::SHIP_150, 650);
        $this->credit($cust, 0 - $this->cash($cust));

        $out = $this->act(AdminPaymentController::class, 'saveNewSender', $this->senderPost($billId, self::SHIP_FREE));

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertSame(-500.0, $this->deduction($billId));
        $this->assertSame(150.0, $this->cash($cust), 'ถูกลง = คืนส่วนต่าง');
    }

    // ---- 2. shop_cashback: ยอด <= 0 / รูปแบบผิด ----

    private function cashbackPost(array $prices): array
    {
        return ['order' => array_map(fn ($i) => 'zz_order_'.$i, array_keys($prices)), 'date' => array_fill(0, count($prices), ''),
            'price' => $prices, 'remark' => 'zz_test', 'paymentpin' => self::PIN];
    }

    public function test_customer_cashback_rejects_non_positive_or_bad_amounts(): void
    {
        DB::table('user')->where('id', self::CUST)->update(['pin' => self::PIN]);
        $cust = $this->cust();
        $this->actAs($cust);
        $count = fn () => DB::table('shop_cashback')->where('user', $cust->id)->count();
        $n = $count();
        foreach ([['-100'], ['0'], ['0.00'], ['abc'], [''], ['1.234'], ['1,00'], ['10', '-5']] as $prices) {
            $out = $this->act(ShopCashbackController::class, 'save', $this->cashbackPost($prices));
            $this->assertStringContainsString('ErrorBox(', $out, json_encode($prices));
            $this->assertStringContainsString(ShopCashbackController::BAD_PRICE, $out);
        }
        $this->assertSame($n, $count(), 'ต้องไม่สร้างรายการ');

        $out = $this->act(ShopCashbackController::class, 'save', $this->cashbackPost(['12.50', '1,000']));
        $this->assertStringContainsString('SuccessBox(', $out);
        $head = DB::table('shop_cashback')->where('user', $cust->id)->orderByDesc('id')->first();
        $this->assertSame('1012.50', (string) $head->total);
        $this->assertSame(['12.50', '1000.00'], DB::table('shop_cashback_detail')->where('shop_cashback_id', $head->id)
            ->orderBy('id')->pluck('price')->map(fn ($v) => (string) $v)->all());
    }

    public function test_admin_cannot_approve_non_positive_cashback(): void
    {
        $cust = $this->cust();
        $this->admin();
        $head = $this->insertRow('shop_cashback', ['user' => $cust->id, 'total' => -100, 'remark' => 'zz_test']);
        $mk = fn (float $p) => $this->insertRow('shop_cashback_detail', ['shop_cashback_id' => $head, 'order' => 'zz_o', 'price' => $p,
            'status' => 1, 'cancel_remark' => '', 'attachment' => '', 'submit_date' => '2026-10-18 00:00:00']);
        $cn = $this->cash($cust, 'balance_cn');

        foreach ([-100, 0] as $p) {
            $d = $mk($p);
            $out = $this->act(AdminShopCashbackController::class, 'saveApproved', ['id' => (string) $d]);
            $this->assertStringContainsString('ErrorBox(', $out);
            $this->assertStringContainsString(AdminShopCashbackController::BAD_PRICE, $out);
            $this->assertSame('1', (string) DB::table('shop_cashback_detail')->where('id', $d)->value('status'), 'สถานะต้องไม่เปลี่ยน');
        }
        $this->assertSame($cn, $this->cash($cust, 'balance_cn'), 'เงินหยวนต้องไม่ถูกหัก');

        $ok = $mk(25.5);
        $this->assertStringContainsString('SuccessBox(', $this->act(AdminShopCashbackController::class, 'saveApproved', ['id' => (string) $ok]));
        $this->assertSame(round($cn + 25.5, 2), $this->cash($cust, 'balance_cn'));
    }

    // ---- 3. combine_track ----

    private function track(int $user, array $extra = []): int
    {
        return $this->insertRow('tracking', $extra + ['tracking' => 'ZZ-RISK-'.bin2hex(random_bytes(4)), 'user' => $user, 'lot_id' => 0,
            'weight' => 1, 'width' => 1, 'height' => 1, 'long' => 1, 'amount' => 1, 'header' => 0, 'payment_id' => 0]);
    }

    private function combine(int $a, int $b): string
    {
        return $this->act(CombineTrackController::class, 'saveCombine', ['tracking' => ','.$a.','.$b]);
    }

    private function assertUntouched(array $ids, array $before, string $msg): void
    {
        $after = DB::table('tracking')->whereIn('id', $ids)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $this->assertSame($before, $after, $msg);
    }

    public function test_combine_refuses_mixed_users_and_billed_deleted_locked_rows(): void
    {
        $this->admin();
        $cases = [
            'ต่างลูกค้า' => [[], ['user' => self::ADMIN], CombineTrack::ERR_USER],
            'อยู่ในบิล' => [[], ['payment_id' => 2147483000], CombineTrack::ERR_STATE],
            'ลบแล้ว' => [[], ['is_delete' => 1], CombineTrack::ERR_STATE],
            'ล็อก -2' => [[], ['payment_id' => -2], CombineTrack::ERR_STATE],
            'claim -3' => [['payment_id' => -3], [], CombineTrack::ERR_STATE],
        ];
        foreach ($cases as $name => [$ea, $eb, $err]) {
            $a = $this->track(self::CUST, $ea);
            $b = $this->insertRow('tracking', $eb + ['tracking' => 'ZZ-RISK-'.bin2hex(random_bytes(4)), 'user' => self::CUST, 'lot_id' => 0,
                'weight' => 1, 'width' => 1, 'height' => 1, 'long' => 1, 'amount' => 1, 'header' => 0, 'payment_id' => 0]);
            $before = DB::table('tracking')->whereIn('id', [$a, $b])->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $max = (int) DB::table('tracking')->max('id');

            $out = $this->combine($a, $b);

            $this->assertStringContainsString('ErrorBox(', $out, $name);
            $this->assertStringContainsString($err, $out, $name);
            $this->assertUntouched([$a, $b], $before, $name.': แถวเดิมต้องไม่ถูกแก้/ลบ');
            $this->assertSame($max, (int) DB::table('tracking')->max('id'), $name.': ต้องไม่สร้างแถวใหม่');
        }
    }

    public function test_combine_same_user_unbilled_still_merges(): void
    {
        $this->admin();
        $a = $this->track(self::CUST);
        $b = $this->track(self::CUST);

        $out = $this->combine($a, $b);

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertSame(0, DB::table('tracking')->whereIn('id', [$a, $b])->count());
        $new = DB::table('tracking')->where('id', '>', $b)->where('user', self::CUST)->orderByDesc('id')->first();
        $this->assertNotNull($new);
    }

    // ---- 4. save_lock ----

    public function test_save_lock_keeps_minus3_but_skips_billed_and_deleted(): void
    {
        $this->admin();
        foreach (['อยู่ในบิล' => ['payment_id' => 2147483000], 'ลบแล้ว' => ['is_delete' => 1]] as $name => $extra) {
            $t = $this->track(self::CUST, $extra + ['date_thai' => '2026-10-01 10:00:00']);
            $before = (array) DB::table('tracking')->where('id', $t)->first();
            $out = $this->act(AdminTrackingController::class, 'saveLock', ['id' => (string) $t, 'value' => '-2']);
            $this->assertStringContainsString('ErrorBox(', $out, $name);
            $this->assertStringContainsString(AdminTrackingController::LOCK_REFUSED, $out);
            $this->assertSame($before, (array) DB::table('tracking')->where('id', $t)->first(), $name.': ต้องไม่ถูกแก้');
        }
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminTrackingController::class, 'saveLock', ['id' => '2147483000']));

        foreach ([0, -2, -3] as $pid) {
            $t = $this->track(self::CUST, ['payment_id' => $pid, 'date_thai' => '2026-10-01 10:00:00']);
            $this->assertSame('', $this->act(AdminTrackingController::class, 'saveLock', ['id' => (string) $t, 'value' => '0']));
            $row = DB::table('tracking')->where('id', $t)->first();
            $this->assertSame('-3', (string) $row->payment_id, 'คง payment_id = -3 แบบเดิม');
            $this->assertSame('0000-00-00 00:00:00', (string) $row->date_thai);
        }
    }
}
