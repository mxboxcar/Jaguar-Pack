<?php

namespace Tests\Feature\Shop;

use App\Domain\Shop\CartRepo;
use App\Http\Controllers\Shop\Admin\MallStockController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\ApiController;
use App\Http\Controllers\Shop\TaobaoController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * กลุ่ม shop ของ PACK กับ admin_pack_bench จริง (opt-in JL_BENCH_DB_TESTS=1, ทุกอย่าง rollback)
 *   - save_checkout: รายการที่ไม่มีลิงก์ไม่ลง bill_product_detail (แต่ยังนับในยอดรวม — เหมือน SR), ไม่มีบริการถ่ายรูป
 *   - load_full_cart: ไม่มีคอลัมน์ "ถ่ายรูป", สี #rrggbb เป็นข้อความ (ไม่มีแถบสีแบบ SR)
 *   - _taobao/save: ต้องยืนยัน PIN (เหมือน SR) แต่ไม่มีการตรวจ "กรุณาใส่ราคา"
 *   - _admin/_mallstock/grid = grid บัญชีเงินบาท (สำเนา _admin/_balance รุ่นเก่า)
 *   - _api/get_order_number_v2 = "<order>[xx]<url>" 60 วัน
 */
class ShopPackDbTest extends BenchDbTestCase
{
    private function item(int $cartId, int $uid, string $shop, array $extra = []): int
    {
        return $this->insertRow('cart_detail', $extra + [
            'cart_id' => $cartId, 'url' => 'https://item.taobao.com/item.htm?id=zz_test', 'product_name' => 'zz_test', 'shop_name' => $shop,
            'main_image' => '', 'price' => '10.00', 'color' => '', 'amount' => '2', 'remark' => '', 'upload_file' => '', 'size' => '', 'user' => $uid,
            'is_craft' => 0, 'is_qc' => 0,
        ]);
    }

    public function test_checkout_skips_items_without_link_and_has_no_take_a_photo(): void
    {
        $cust = $this->zzUser(2);
        $cart = CartRepo::findOrCreate($cust->id);
        $a1 = $this->item($cart->id, $cust->id, 'zz_shop_a', ['color' => '#ff0000']);
        $a2 = $this->item($cart->id, $cust->id, 'zz_shop_a', ['is_craft' => 1]);
        $b1 = $this->item($cart->id, $cust->id, 'zz_shop_b', ['url' => '']); // ไม่มีลิงก์: นับยอดแต่ไม่ลงรายการ
        $mine = $this->item($cart->id, $cust->id, 'zz_shop_c');
        $this->actAs($cust);

        $b64 = base64_encode("$a1,$a2,$b1");
        $html = $this->act(CartController::class, 'loadFullCart', ['ids' => $b64]);
        $this->assertStringNotContainsString('ถ่ายรูป', $html);
        $this->assertStringNotContainsString('SaveCartTakeapic', $html);
        $this->assertStringContainsString('#ff0000', $html);
        $this->assertStringNotContainsString('background-color:#ff0000', $html);

        $out = $this->act(CartController::class, 'saveCheckout', ['id' => $cart->random, 'quote' => 'zz_q', 'ship' => '0', 'ids' => $b64]);
        $this->assertStringContainsString('SuccessBox', $out);
        $bill = DB::table('bill_product')->where('user', $cust->id)->first();
        $rate = (float) $bill->exchange_rate;
        $this->assertSame(number_format(3 * 10 * 2 * $rate, 2, '.', ''), (string) $bill->total, '3 รายการ (รวมรายการไม่มีลิงก์) × 10 × 2 × เรท');
        $this->assertCount(2, DB::table('bill_product_detail')->where('bill_product_id', $bill->id)->get(), 'รายการไม่มีลิงก์ไม่ลง bill_product_detail');
        $tracks = DB::table('tracking')->where('user', $cust->id)->get();
        $this->assertCount(1, $tracks, 'ร้าน b ไม่มีรายการใน bill → ไม่มี tracking');
        $this->assertSame('zz_q', (string) $tracks[0]->quote);
        $this->assertSame([$mine], DB::table('cart_detail')->where('cart_id', $cart->id)->pluck('id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_save_product_without_url_adds_nothing_but_succeeds(): void
    {
        $cust = $this->zzUser(2);
        $this->actAs($cust);
        $out = $this->act(CartController::class, 'saveProduct', ['search_url' => '', 'search_amount' => '1', 'search_price' => '1']);
        $this->assertStringContainsString('SuccessBox', $out);
        $cart = CartRepo::find($cust->id);
        $this->assertNotNull($cart, 'ตะกร้ายังถูกสร้างแบบเดิม');
        $this->assertSame(0, DB::table('cart_detail')->where('cart_id', $cart->id)->count());
    }

    public function test_taobao_save_requires_pin_but_allows_zero_price(): void
    {
        $cust = $this->zzUser(2);
        $this->actAs($cust);
        $post = ['id' => '0', 'order' => ['zz_o1'], 'price' => ['10'], 'remark' => 'zz_tb'];
        $this->assertStringContainsString('var htmlpin', $this->act(TaobaoController::class, 'save', $post), 'ยังไม่มี PIN = getNewPinBox');

        DB::table('user')->where('id', $cust->id)->update(['pin' => '246810']);
        $cust->refresh();
        $this->actAs($cust);
        $this->assertStringContainsString('pinbox', $this->act(TaobaoController::class, 'save', $post), 'ไม่ได้กรอก PIN = getPinBox');
        $this->assertStringContainsString("ErrorBox('PIN จ่ายเงินไม่ถูกต้อง'", $this->act(TaobaoController::class, 'save', $post + ['paymentpin' => '000000']));
        $this->assertSame(0, DB::table('taobao')->where('user', $cust->id)->count());

        $this->credit($cust, 100);
        // PACK ไม่มี "กรุณาใส่ราคา" ของ SR: ราคา 0 ผ่าน (ติดลบยังปฏิเสธ — แก้จากรีวิว)
        $this->assertStringContainsString('ราคาไม่ถูกต้อง (ติดลบ)', $this->act(TaobaoController::class, 'save', ['price' => ['-1'], 'order' => ['zz_n']] + $post + ['paymentpin' => '246810']));
        $out = $this->act(TaobaoController::class, 'save', ['price' => ['0'], 'order' => ['zz_z']] + $post + ['paymentpin' => '246810']);
        $this->assertStringContainsString('SuccessBox', $out);
        $out = $this->act(TaobaoController::class, 'save', $post + ['paymentpin' => '246810']);
        $this->assertStringContainsString('SuccessBox', $out);
        $tb = DB::table('taobao')->where('user', $cust->id)->first();
        $this->assertNotNull($tb);
        $this->assertSame(1, DB::table('taobao_detail')->where('taobao_id', $tb->id)->count());

        // แก้หมายเหตุก็ต้องยืนยัน PIN (PACK/SR)
        $this->assertStringContainsString('pinbox', $this->act(TaobaoController::class, 'save', ['id' => (string) $tb->id, 'remark' => 'zz_x']));
        $this->assertSame('zz_tb', DB::table('taobao')->where('id', $tb->id)->value('remark'));
    }

    public function test_mallstock_grid_is_the_old_admin_balance_grid(): void
    {
        $admin = $this->zzUser(1);
        $cust = $this->zzUser(2);
        $this->credit($cust, 12.5);
        $this->actAs($admin);
        $html = $this->act(MallStockController::class, 'grid', ['page' => '1', 'txtsearch' => $cust->code.'|-2']);
        $this->assertStringContainsString($cust->code, $html);
        $this->assertStringContainsString("loadAjaxGrid('_admin/_balance/grid'", $html, 'ajaxpage เดิม = _admin/_balance/grid');
        $this->assertStringNotContainsString('ผู้ทำรายการ', $html, 'รุ่นเก่า: ไม่มีคอลัมน์ผู้ทำรายการ');
        $this->assertStringNotContainsString('target="_blank"', $html);

        $this->actAs($cust);
        $this->assertSame('', $this->act(MallStockController::class, 'grid', ['page' => '1', 'txtsearch' => '']));
    }

    public function test_get_order_number_v2_lists_order_and_url_of_the_last_60_days(): void
    {
        config(['legacy.shop.order_api_token' => 'zz_test_token']);
        $cust = $this->zzUser(2);
        $bill = $this->insertRow('bill_product', ['user' => $cust->id, 'total' => 0, 'usecash' => 0, 'status' => 1, 'quote' => '', 'ship' => 0,
            'is_lock' => 0, 'cancel_remark' => '', 'admin_quote' => '', 'exchange_rate' => 5]);
        $d = $this->insertRow('bill_product_detail', ['user' => $cust->id, 'bill_product_id' => $bill, 'url' => 'https://item.taobao.com/zz', 'amount' => 1, 'price' => 1]);
        $t = $this->insertRow('tracking', ['user' => $cust->id, 'tracking' => '', 'order' => 'zz_ord_'.$cust->id, 'payment_id' => 0, 'lot_id' => 0]);
        $this->insertRow('bill_product_on_tracking', ['user' => $cust->id, 'bill_product_id' => $bill, 'bill_product_detail_id' => $d, 'tracking_id' => $t]);
        $old = $this->insertRow('tracking', ['user' => $cust->id, 'tracking' => '', 'order' => 'zz_old_'.$cust->id, 'payment_id' => 0, 'lot_id' => 0]);
        DB::table('tracking')->where('id', $old)->update(['create_date' => DB::raw('NOW() - INTERVAL 61 DAY')]);
        $this->insertRow('bill_product_on_tracking', ['user' => $cust->id, 'bill_product_id' => $bill, 'bill_product_detail_id' => $d, 'tracking_id' => $old]);

        $req = \Illuminate\Http\Request::create('/_api/get_order_number_v2', 'GET', ['token' => 'zz_test_token']);
        $out = (string) app(ApiController::class)->getOrderNumberV2($req)->getContent();
        $this->assertStringContainsString('zz_ord_'.$cust->id."[xx]https://item.taobao.com/zz\n", $out);
        $this->assertStringNotContainsString('zz_old_'.$cust->id, $out);
        $this->assertSame('', (string) app(ApiController::class)->getOrderNumberV2(\Illuminate\Http\Request::create('/x', 'GET', ['token' => 'bad']))->getContent());
    }
}
