<?php

namespace Tests\Feature\Shop;

use App\Domain\Shop\CartRepo;
use App\Http\Controllers\Shop\Admin\MallStockController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\FindProductController;
use App\Http\Controllers\Shop\TaobaoController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * กลุ่ม shop ของ SR กับ admin_sr_bench จริง (opt-in JL_BENCH_DB_TESTS=1, ทุกอย่าง rollback)
 *   - บริการถ่ายรูป (cart_detail.is_takeapic → bill_product_detail / tracking) และ save_takeapic
 *   - save_checkout: รายการที่ไม่มีลิงก์ไม่ลง bill_product_detail (แต่ยังนับในยอดรวม) — SR
 *   - _taobao/save: ต้องยืนยัน PIN, ราคา 0 = "กรุณาใส่ราคา"
 *   - _admin/_mallstock/grid = grid บัญชีเงินบาท (สำเนา _admin/_balance รุ่นเก่า), _findproduct/grid
 */
class ShopSrDbTest extends BenchDbTestCase
{
    private function item(int $cartId, int $uid, string $shop, array $extra = []): int
    {
        return $this->insertRow('cart_detail', $extra + [
            'cart_id' => $cartId, 'url' => 'https://item.taobao.com/item.htm?id=zz_test', 'product_name' => 'zz_test', 'shop_name' => $shop,
            'main_image' => '', 'price' => '10.00', 'color' => '', 'amount' => '2', 'remark' => '', 'upload_file' => '', 'size' => '', 'user' => $uid,
            'is_craft' => 0, 'is_qc' => 0, 'is_takeapic' => 0,
        ]);
    }

    public function test_takeapic_flows_from_cart_to_bill_and_tracking(): void
    {
        $cust = $this->zzUser(8);
        $other = $this->zzUser(8);
        $cart = CartRepo::findOrCreate($cust->id);
        $a1 = $this->item($cart->id, $cust->id, 'zz_shop_a');
        $a2 = $this->item($cart->id, $cust->id, 'zz_shop_a');
        $b1 = $this->item($cart->id, $cust->id, 'zz_shop_b', ['url' => '']); // ไม่มีลิงก์: นับยอดแต่ไม่ลงรายการ
        $mine = $this->item($cart->id, $cust->id, 'zz_shop_c');
        $this->actAs($cust);

        $this->assertSame('', $this->act(CartController::class, 'saveTakeapic', ['id' => (string) $a1, 'value' => '1']));
        $this->assertSame(1, (int) DB::table('cart_detail')->where('id', $a1)->value('is_takeapic'));

        // รายการของคนอื่นแก้ไม่ได้ (WHERE user = me)
        $oCart = CartRepo::findOrCreate($other->id);
        $foreign = $this->item($oCart->id, $other->id, 'zz_shop_x');
        $this->act(CartController::class, 'saveTakeapic', ['id' => (string) $foreign, 'value' => '1']);
        $this->assertSame(0, (int) DB::table('cart_detail')->where('id', $foreign)->value('is_takeapic'));

        // ตาราง checkout มีคอลัมน์ "ถ่ายรูป" และสวิตช์ SaveCartTakeapic (ต่อร้าน)
        $b64 = base64_encode("$a1,$a2,$b1");
        $html = $this->act(CartController::class, 'loadFullCart', ['ids' => $b64]);
        $this->assertStringContainsString('<th class="text-center">ถ่ายรูป</th>', $html);
        $this->assertStringContainsString('checked class="js-switch" onchange="SaveCartTakeapic($(this), '.$a1.')" value="0"', $html);

        $out = $this->act(CartController::class, 'saveCheckout', ['id' => $cart->random, 'quote' => 'zz_q', 'ship' => '0', 'ids' => $b64]);
        $this->assertStringContainsString('SuccessBox', $out);
        $bill = DB::table('bill_product')->where('user', $cust->id)->first();
        $this->assertSame('312.00', (string) $bill->total, '3 รายการ (รวมรายการไม่มีลิงก์) × 10 × 2 × 5.2');
        $details = DB::table('bill_product_detail')->where('bill_product_id', $bill->id)->orderBy('id')->get();
        $this->assertCount(2, $details, 'รายการไม่มีลิงก์ไม่ลง bill_product_detail');
        $this->assertSame([1, 0], $details->pluck('is_takeapic')->map(fn ($v) => (int) $v)->all());
        $tracks = DB::table('tracking')->where('user', $cust->id)->get();
        $this->assertCount(1, $tracks, 'ร้าน b ไม่มีรายการใน bill → ไม่มี tracking');
        $this->assertSame(1, (int) $tracks[0]->is_takeapic);
        $this->assertSame([$mine], DB::table('cart_detail')->where('cart_id', $cart->id)->pluck('id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_save_product_without_url_adds_nothing_but_succeeds(): void
    {
        $cust = $this->zzUser(8);
        $this->actAs($cust);
        $out = $this->act(CartController::class, 'saveProduct', ['search_url' => '', 'search_amount' => '1', 'search_price' => '1']);
        $this->assertStringContainsString('SuccessBox', $out);
        $cart = CartRepo::find($cust->id);
        $this->assertNotNull($cart, 'ตะกร้ายังถูกสร้างแบบเดิม');
        $this->assertSame(0, DB::table('cart_detail')->where('cart_id', $cart->id)->count());
    }

    public function test_taobao_save_requires_pin_and_nonzero_prices(): void
    {
        $cust = $this->zzUser(8);
        $this->actAs($cust);
        $post = ['id' => '0', 'order' => ['zz_o1'], 'price' => ['10'], 'remark' => 'zz_tb'];
        $this->assertStringContainsString('var htmlpin', $this->act(TaobaoController::class, 'save', $post), 'ยังไม่มี PIN = getNewPinBox');

        DB::table('user')->where('id', $cust->id)->update(['pin' => '246810']);
        $cust->refresh();
        $this->actAs($cust);
        $this->assertStringContainsString('pinbox', $this->act(TaobaoController::class, 'save', $post), 'ไม่ได้กรอก PIN = getPinBox');
        $this->assertStringContainsString("ErrorBox('PIN จ่ายเงินไม่ถูกต้อง'", $this->act(TaobaoController::class, 'save', $post + ['paymentpin' => '000000']));
        foreach (['0', '0.00', '', 'abc'] as $zero) {
            $out = $this->act(TaobaoController::class, 'save', ['price' => ['5', $zero], 'order' => ['a', 'b']] + $post + ['paymentpin' => '246810']);
            $this->assertStringContainsString("ErrorBox('กรุณาใส่ราคา'", $out, var_export($zero, true));
        }
        $this->assertSame(0, DB::table('taobao')->where('user', $cust->id)->count());

        $this->credit($cust, 100);
        $out = $this->act(TaobaoController::class, 'save', $post + ['paymentpin' => '246810']);
        $this->assertStringContainsString('SuccessBox', $out);
        $tb = DB::table('taobao')->where('user', $cust->id)->first();
        $this->assertNotNull($tb);
        $this->assertSame(1, DB::table('taobao_detail')->where('taobao_id', $tb->id)->count());

        // แก้หมายเหตุก็ต้องยืนยัน PIN (SR)
        $this->assertStringContainsString('pinbox', $this->act(TaobaoController::class, 'save', ['id' => (string) $tb->id, 'remark' => 'zz_x']));
        $this->assertSame('zz_tb', DB::table('taobao')->where('id', $tb->id)->value('remark'));
    }

    public function test_mallstock_grid_is_the_old_admin_balance_grid(): void
    {
        $admin = $this->zzUser(1);
        $cust = $this->zzUser(8);
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

    public function test_findproduct_grid_denied_without_permission(): void
    {
        // สิทธิ์ findproduct ไม่มีใน role_detail ของ SR → แม้แอดมิน role 1 ก็ได้ผลว่าง (exit เงียบแบบเดิม)
        $this->actAs($this->zzUser(1));
        $this->assertSame('', $this->act(FindProductController::class, 'grid', ['page' => '1', 'txtsearch' => '']));
    }

    public function test_findproduct_form_renders_find_product_row(): void
    {
        $this->actAs($this->zzUser(1));
        $id = $this->insertRow('find_product', ['name' => 'zz_find <x>', 'type' => 1, 'status' => 0]);
        $req = \Illuminate\Http\Request::create('/findproduct/'.$id, 'GET');
        $req->setRouteResolver(fn () => (new \Illuminate\Routing\Route(['GET'], 'findproduct/{id}', []))->bind($req));
        app()->instance('request', $req);
        $html = app(FindProductController::class)->form($req)->render();
        $this->assertStringContainsString('value="zz_find &lt;x&gt;"', $html);
        $this->assertStringContainsString("selected='selected' value=\"1\"", $html);
        $this->assertStringContainsString('_findproduct/save', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }
}
