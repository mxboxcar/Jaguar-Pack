<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Order\Admin\AdminOrderController;
use App\Http\Controllers\Order\Admin\AdminTaobaoController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * order / taobao (รายงานข้อ 14) — รันกับ admin_pack_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class OrderDbTest extends BenchDbTestCase
{
    /**
     * ใบฝากจ่ายแบบที่ _taobao/save สร้าง: ราคาหยวนตัดจากกระเป๋าหยวนก่อน ที่เหลือ × rate + charge × จำนวนออเดอร์ ตัดจากบาท
     *
     * @param  list<float>  $prices
     * @return array{id: int, details: list<int>}
     */
    private function taobao(User $cust, array $prices, float $cnyWallet, float $rate = 5.0, float $charge = 10.0): array
    {
        $sum = array_sum($prices);
        $useCn = min($sum, $cnyWallet);
        $useThb = ($sum - $useCn) * $rate + count($prices) * $charge;
        $this->credit($cust, $cnyWallet, 'balance_cn');
        $this->credit($cust, $useThb + 1000);
        $id = $this->insertRow('taobao', ['rate' => $rate, 'charge' => $charge, 'price' => 0, 'taobao_name' => 'zz_test', 'taobao_password' => '',
            'status' => 1, 'user' => $cust->id, 'approved_remark' => '', 'approved_upload' => '', 'cancel_remark' => '',
            'usecash' => $useThb, 'usecash_cn' => $useCn, 'remark' => 'zz_test', 'remark_admin' => '']);
        $details = [];
        foreach ($prices as $i => $p) {
            $details[] = $this->insertRow('taobao_detail', ['taobao_id' => $id, 'order' => 'zz_test_'.$i, 'price' => $p, 'status' => 1]);
        }
        $pid = $this->prefixId('taobao');
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -$useThb, 'detail' => 'TAO-'.$id, 'type' => 7, 'prefix' => $pid, 'prefix_ref_id' => $id]);
        if ($useCn > 0) {
            $this->insertRow('balance_cn', ['user' => $cust->id, 'amount' => -$useCn, 'detail' => 'TAO-'.$id, 'type' => 7, 'prefix' => $pid, 'prefix_ref_id' => $id]);
        }

        return ['id' => $id, 'details' => $details];
    }

    private function net(string $table, int $taobaoId, ?int $detail = null): float
    {
        $q = DB::table($table)->where('prefix', $this->prefixId('taobao'))->where('prefix_ref_id', $taobaoId)->where('is_delete', 0);
        if ($detail !== null) {
            $q->where('ref_id_2', $detail);
        }

        return round((float) $q->sum('amount'), 2);
    }

    public function test_cancel_refunds_in_the_currency_paid_and_rollback_reverses_it(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        // ราคา 100 + 50 หยวน, มีหยวนในกระเป๋า 120 → หยวน 120, บาท (30 × 5) + 2 × 10 = 170
        $tb = $this->taobao($cust, [100, 50], 120);
        [$d1, $d2] = $tb['details'];
        $this->actAs($admin);
        $thb0 = $this->cash($cust);
        $cny0 = $this->cash($cust, 'balance_cn');

        $this->assertStringContainsString('SuccessBox', $this->act(AdminTaobaoController::class, 'saveCancel', ['id' => $d1, 'cancel_remark' => 'zz']));
        // รายการแรก 100 หยวน: คืนเป็นหยวนทั้งหมด + ค่าบริการ 10 บาท
        $this->assertSame(round($cny0 + 100, 2), $this->cash($cust, 'balance_cn'));
        $this->assertSame(round($thb0 + 10, 2), $this->cash($cust));
        // ยกเลิกซ้ำ = ปฏิเสธ ไม่คืนซ้ำ
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminTaobaoController::class, 'saveCancel', ['id' => $d1]));
        $this->assertSame(round($cny0 + 100, 2), $this->cash($cust, 'balance_cn'));

        // รายการที่สอง 50 หยวน: หยวนเหลือค้างในใบ 20 → คืนหยวน 20 + บาท 30 × 5 = 150 + ค่าบริการ 10
        $this->act(AdminTaobaoController::class, 'saveCancel', ['id' => $d2]);
        $this->assertSame(round($cny0 + 120, 2), $this->cash($cust, 'balance_cn'));
        $this->assertSame(round($thb0 + 170, 2), $this->cash($cust));
        $this->assertSame(0.0, $this->net('balance', $tb['id']), 'คืนครบเท่าที่จ่าย ไม่เกิน');
        $this->assertSame(0.0, $this->net('balance_cn', $tb['id']));

        // rollback รายการที่สอง: หักคืนตามที่คืนจริง (หยวน 20, บาท 160) ไม่ใช่ rate × price เป็นบาท
        $this->act(AdminTaobaoController::class, 'rollback', ['id' => $d2]);
        $this->assertSame('1', (string) DB::table('taobao_detail')->where('id', $d2)->value('status'));
        $this->assertSame(0.0, $this->net('balance', $tb['id'], $d2));
        $this->assertSame(0.0, $this->net('balance_cn', $tb['id'], $d2));
        $this->assertSame(round($cny0 + 100, 2), $this->cash($cust, 'balance_cn'));
        $this->assertSame(round($thb0 + 10, 2), $this->cash($cust));
        // rollback ซ้ำ (สถานะ 1 แล้ว) ไม่หักอีก
        $this->act(AdminTaobaoController::class, 'rollback', ['id' => $d2]);
        $this->assertSame(round($thb0 + 10, 2), $this->cash($cust));
    }

    public function test_cancel_and_approve_only_from_pending(): void
    {
        $cust = $this->zzUser();
        $tb = $this->taobao($cust, [10], 0);
        [$d] = $tb['details'];
        $this->actAs($this->zzUser(1));
        $this->assertStringContainsString('SuccessBox', $this->act(AdminTaobaoController::class, 'saveApproved', ['id' => $d]));
        $this->assertSame('2', (string) DB::table('taobao_detail')->where('id', $d)->value('status'));
        $c = $this->cash($cust);
        // จ่ายร้านแล้ว ยกเลิกไม่ได้ (เดิมคืนเงินได้)
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminTaobaoController::class, 'saveCancel', ['id' => $d]));
        $this->assertSame($c, $this->cash($cust));

        // เส้นทางคืนเงินซ้ำเดิม: ยกเลิก → อนุมัติ → rollback (2 → 1 ไม่หัก) → ยกเลิกอีก
        $tb2 = $this->taobao($cust, [10], 0);
        [$e] = $tb2['details'];
        $this->act(AdminTaobaoController::class, 'saveCancel', ['id' => $e]);
        $afterCancel = $this->cash($cust);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminTaobaoController::class, 'saveApproved', ['id' => $e]));
        $this->assertSame('-1', (string) DB::table('taobao_detail')->where('id', $e)->value('status'));
        $this->assertSame($afterCancel, $this->cash($cust));
    }

    public function test_rollback_of_a_legacy_cancel_takes_back_cny_and_fee_and_checks_balance(): void
    {
        $cust = $this->zzUser();
        $tb = $this->taobao($cust, [40], 0, 5, 10);
        [$d] = $tb['details'];
        $pid = $this->prefixId('taobao');
        // ยกเลิกโดยเว็บเดิม: คืนราคาเป็นหยวน + ค่าบริการเป็นบาท ไม่มี ref_id_2
        DB::table('taobao_detail')->where('id', $d)->update(['status' => -1]);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => 10, 'detail' => 'TAO-'.$tb['id'].', คืนเงินค่าบริการ', 'type' => 7, 'prefix' => $pid, 'prefix_ref_id' => $tb['id']]);
        $this->insertRow('balance_cn', ['user' => $cust->id, 'amount' => 40, 'detail' => 'TAO-'.$tb['id'].', รายการสั่งซื้อ : zz', 'type' => 7, 'prefix' => $pid, 'prefix_ref_id' => $tb['id']]);
        $thb = $this->cash($cust);
        $cny = $this->cash($cust, 'balance_cn');

        $this->actAs($this->zzUser(1));
        $this->act(AdminTaobaoController::class, 'rollback', ['id' => $d]);
        $this->assertSame(round($cny - 40, 2), $this->cash($cust, 'balance_cn'), 'หักหยวนที่คืนไป (เดิมหักบาท 40 × 5 = 200)');
        $this->assertSame(round($thb - 10, 2), $this->cash($cust));

        // ยกเลิกใหม่ (ใบนี้จ่ายด้วยบาททั้งหมด → คืนเป็นบาท 40 × 5 + 10) แล้วลูกค้าใช้เงินหมด → rollback ปฏิเสธ ไม่เปลี่ยนสถานะ
        $t0 = $this->cash($cust);
        $this->act(AdminTaobaoController::class, 'saveCancel', ['id' => $d]);
        $this->assertSame(round($t0 + 210, 2), $this->cash($cust));
        $this->credit($cust, -$this->cash($cust));
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminTaobaoController::class, 'rollback', ['id' => $d]));
        $this->assertSame('-1', (string) DB::table('taobao_detail')->where('id', $d)->value('status'));
    }

    public function test_grid_cancelled_filter_lists_fully_cancelled_bills(): void
    {
        $cust = $this->zzUser();
        $tb = $this->taobao($cust, [5], 0);
        $this->actAs($this->zzUser(1));
        $this->act(AdminTaobaoController::class, 'saveCancel', ['id' => $tb['details'][0]]);
        $html = $this->act(AdminTaobaoController::class, 'grid', ['txtsearch' => $cust->code.'|-1', 'page' => '1']);
        $this->assertStringContainsString('taobao/'.$tb['id'], $html);
        $html = $this->act(AdminTaobaoController::class, 'grid', ['txtsearch' => $cust->code.'|1', 'page' => '1']);
        $this->assertStringNotContainsString('taobao/'.$tb['id'].'"', $html);
    }

    private function order(User $cust, int $status, float $total = 300): int
    {
        return $this->insertRow('bill_product', ['user' => $cust->id, 'total' => $total, 'usecash' => 0, 'status' => $status, 'quote' => '', 'ship' => 0,
            'is_lock' => 0, 'cancel_remark' => '', 'admin_quote' => 'zz_test']);
    }

    public function test_order_approve_finish_and_cancel_refund(): void
    {
        $cust = $this->zzUser();
        $this->actAs($this->zzUser(1));
        $pid = $this->prefixId('order');

        // save_approved: เฉพาะสถานะ 0 (เดิมพลิกใบที่จ่ายแล้วกลับเป็น 1 = ลูกค้าจ่ายซ้ำได้)
        $paid = $this->order($cust, 2);
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminOrderController::class, 'saveApproved', ['id' => $paid]));
        $this->assertSame('2', (string) DB::table('bill_product')->where('id', $paid)->value('status'));
        $new = $this->order($cust, 0);
        $this->assertStringContainsString('SuccessBox', $this->act(AdminOrderController::class, 'saveApproved', ['id' => $new]));
        $this->assertSame('1', (string) DB::table('bill_product')->where('id', $new)->value('status'));

        // save_finish: ต้องมีแถวชำระเงินของ prefix "order"
        $this->assertStringContainsString('ErrorBox(', $this->act(AdminOrderController::class, 'saveFinish', ['id' => $paid]));
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -300, 'detail' => 'ชำระเงิน zz', 'type' => 2, 'prefix' => $pid, 'prefix_ref_id' => $paid]);
        $this->act(AdminOrderController::class, 'saveFinish', ['id' => $paid]);
        $this->assertSame('3', (string) DB::table('bill_product')->where('id', $paid)->value('status'));

        // ยกเลิกใบที่จ่ายแล้วหลังคืนเงินบางรายการ: คืนเฉพาะยอดที่ยังถูกตัดอยู่ (เดิมคืนเต็ม 300 = ได้ 340)
        $o = $this->order($cust, 2);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => -300, 'detail' => 'ชำระเงิน zz', 'type' => 2, 'prefix' => $pid, 'prefix_ref_id' => $o]);
        $this->insertRow('balance', ['user' => $cust->id, 'amount' => 40, 'detail' => 'ยกเลิกรายการ zz', 'type' => 2, 'prefix' => $pid, 'prefix_ref_id' => $o, 'ref_id_2' => 1]);
        $before = $this->cash($cust);
        $this->act(AdminOrderController::class, 'delete', ['id' => $o, 'cancel_remark' => 'zz']);
        $this->act(AdminOrderController::class, 'delete', ['id' => $o, 'cancel_remark' => 'zz']);
        $this->assertSame(round($before + 260, 2), $this->cash($cust));
        $this->assertSame(0.0, round((float) DB::table('balance')->where('prefix', $pid)->where('prefix_ref_id', $o)->where('is_delete', 0)->sum('amount'), 2));
    }
}
