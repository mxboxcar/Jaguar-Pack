<?php

namespace Tests\Feature\Money;

use App\Domain\Shop\CartRepo;
use App\Http\Controllers\Shop\CartController;
use Illuminate\Support\Facades\DB;

/**
 * _cart/save_checkout: กดสั่งซื้อซ้ำ (รายการถูกตัดออกจากตะกร้าไปแล้ว) ต้องไม่สร้างใบสั่งซื้อเปล่ายอด 0 (e2e 2026-10-18)
 * รันกับ admin_sr_bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class CartCheckoutDbTest extends BenchDbTestCase
{
    public function test_repeated_checkout_does_not_create_an_empty_order(): void
    {
        $cust = $this->zzUser();
        $cart = CartRepo::findOrCreate($cust->id);
        $ids = [];
        foreach (['zz_shop_a', 'zz_shop_a', 'zz_shop_b'] as $shop) {
            $ids[] = $this->insertRow('cart_detail', [
                'cart_id' => $cart->id, 'url' => 'https://item.taobao.com/item.htm?id=zz_test', 'product_name' => 'zz_test', 'shop_name' => $shop,
                'main_image' => '', 'price' => '10.00', 'color' => '', 'amount' => '2', 'remark' => '', 'upload_file' => '', 'size' => '', 'user' => $cust->id,
            ]);
        }
        $post = ['id' => $cart->random, 'quote' => 'zz_test', 'ship' => '1', 'ids' => base64_encode(implode(',', $ids))];
        $this->actAs($cust);

        $this->assertStringContainsString('SuccessBox', $this->act(CartController::class, 'saveCheckout', $post));
        $bills = DB::table('bill_product')->where('user', $cust->id)->get();
        $this->assertCount(1, $bills);
        $this->assertSame(3, DB::table('bill_product_detail')->where('bill_product_id', $bills[0]->id)->count());
        $this->assertSame(2, DB::table('tracking')->where('user', $cust->id)->count(), '1 tracking ต่อร้าน');
        $this->assertSame(0, DB::table('cart_detail')->where('cart_id', $cart->id)->count());

        // กดซ้ำ: ไม่มีรายการเหลือ → ErrorBox ไม่สร้างใบ/tracking
        $out = $this->act(CartController::class, 'saveCheckout', $post);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame(1, DB::table('bill_product')->where('user', $cust->id)->count());
        $this->assertSame(2, DB::table('tracking')->where('user', $cust->id)->count());

        // ids ที่ไม่ใช่ของตะกร้านี้ / ตะกร้าของคนอื่น = ไม่สร้างใบ
        $other = $this->zzUser();
        $this->actAs($other);
        $this->assertStringContainsString('ErrorBox(', $this->act(CartController::class, 'saveCheckout', $post));
        $this->assertSame(0, DB::table('bill_product')->where('user', $other->id)->count());
    }
}
