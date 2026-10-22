<?php

namespace Tests\Feature\Warehouse;

use App\Http\Controllers\Warehouse\Admin\AdminGoodsconfirmController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * อนุมัติยืนยันสินค้า (โอน tracking ให้ลูกค้าคนใหม่) ต้องคิดค่าขนส่งใหม่ตามเรทของเจ้าของใหม่
 * เดิมราคาค้างตามเจ้าของเดิม (vr 2026-10-22: tracking 2089969 ได้เรทคูปอง 15/กก. ของเจ้าของเดิม แทน 45/กก. → 3 บาทแทน 9)
 * รันกับ bench DB ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class GoodsconfirmRepriceDbTest extends BenchDbTestCase
{
    private function customer(int $type): User
    {
        $u = $this->zzUser();
        DB::table('user')->where('id', $u->id)->update(['type' => $type]);

        return $u;
    }

    /** @return array{0:int,1:int} [tracking id, goodsconfirm id] */
    private function claimed(User $old, User $new, int $paymentId): array
    {
        // calPriceAutoDiff คิดเฉพาะรายการที่อยู่ในล็อตแล้ว และจัด product_type ให้ตรงประเภทล็อต → ใช้ล็อตจริง + ประเภทที่ตรงกัน
        $lot = DB::table('lot')->orderByDesc('id')->first(['id', 'type']);
        $lotType = (string) $lot->type === '0' ? 0 : 1;
        $pt = (int) DB::table('product_type')->where('type', $lotType)->orderBy('id')->value('id');
        $name = DB::table('product_type')->where('id', $pt)->value('name');
        $pt = (int) DB::table('product_type')->where('name', $name)->where('type', $lotType)->value('id');
        $this->insertRow('user_type_detail', ['user_type_id' => 990001, 'product_type_id' => $pt, 'kg' => 15, 'cubic' => 1000]);
        $this->insertRow('user_type_detail', ['user_type_id' => 990002, 'product_type_id' => $pt, 'kg' => 45, 'cubic' => 1000]);
        $t = $this->insertRow('tracking', ['tracking' => 'zz_test_'.bin2hex(random_bytes(3)), 'user' => $old->id, 'payment_id' => $paymentId,
            'weight' => 0.2, 'width' => 0, 'height' => 0, 'long' => 0, 'amount' => 1, 'lot_id' => $lot->id, 'no' => 0,
            'product_type_id' => $pt, 'price_kg' => 3, 'price_cubic' => 0]);
        $gc = $this->insertRow('goodsconfirm', ['tracking_id' => $t, 'attachment' => '', 'submit_remark' => 'zz_test',
            'user' => $new->id, 'status' => 1]);

        return [$t, $gc];
    }

    public function test_approve_reprices_with_the_new_owner_rate(): void
    {
        [$old, $new] = [$this->customer(990001), $this->customer(990002)];
        [$t, $gc] = $this->claimed($old, $new, 0);
        $this->actAs($this->zzUser(1));

        $this->act(AdminGoodsconfirmController::class, 'saveApproved', ['id' => $gc]);

        $row = DB::table('tracking')->where('id', $t)->first(['user', 'price_kg']);
        $this->assertSame($new->id, (int) $row->user);
        $this->assertSame('9.00', number_format((float) $row->price_kg, 2, '.', ''), '0.2 กก. × 45 ของเจ้าของใหม่');
    }

    public function test_approve_keeps_the_price_of_a_billed_tracking(): void
    {
        [$old, $new] = [$this->customer(990001), $this->customer(990002)];
        [$t, $gc] = $this->claimed($old, $new, 123456789);
        $this->actAs($this->zzUser(1));

        $this->act(AdminGoodsconfirmController::class, 'saveApproved', ['id' => $gc]);

        $row = DB::table('tracking')->where('id', $t)->first(['user', 'price_kg']);
        $this->assertSame($new->id, (int) $row->user);
        $this->assertSame('3.00', number_format((float) $row->price_kg, 2, '.', ''), 'ออกบิลแล้ว ห้ามเปลี่ยนราคาย้อนหลัง');
    }
}
