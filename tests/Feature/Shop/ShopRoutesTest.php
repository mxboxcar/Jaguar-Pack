<?php

namespace Tests\Feature\Shop;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม shop: ทุก URL เดิม (RewriteRule + ajax ใน docs/url-map.md) มี route, method และสิทธิ์ตรงกับ getPermission() ของไฟล์เดิม
 */
class ShopRoutesTest extends TestCase
{
    /** [method, uri, middleware ที่ต้องมี, legacy_page|null] */
    private const ROUTES = [
        // หน้า (RewriteRule)
        ['GET', 'taobao', 'legacy.auth', 'taobao'],
        ['GET', 'taobao/create', 'legacy.perm:taobao', 'taobao_detail'],
        ['GET', 'taobao/{id}', 'legacy.perm:taobao', 'taobao_detail'],
        ['GET', 'cart/checkout', 'legacy.perm:order', 'checkout'],
        ['GET', 'cart/checkout/{ids}', 'legacy.perm:order', 'checkout'],
        ['GET', 'cart/product/{url?}', 'legacy.perm:order', 'checkout_product'],
        // ajax
        ['POST', '_admin/_mallstock/grid', 'legacy.auth', null],                 // PACK/SR: ตรวจ admin_balance ใน controller (เดิม exit เงียบ)
        ['POST', '_admin/_mallstock/load_amount', 'legacy.perm:admin_balance', null], // PACK/SR = สำเนา _admin/_balance
        ['POST', '_admin/_mallstock/save_amount', 'legacy.perm:admin_balance', null],
        ['POST', '_api/blockblob', 'legacy.auth', null],
        ['POST', '_api/blockblob_ajax', 'legacy.auth', null],
        ['POST', '_api/crawl_guard', 'legacy.auth', null],
        ['GET', '_api/get_order_number_v2', null, null],
        ['POST', '_api/image_shrink', 'legacy.auth', null],
        ['POST', '_api/load_product', 'legacy.perm:order', null],
        ['POST', '_api/load_product_v5', 'legacy.auth', null],
        ['GET', '_api/submit_order_number_v2', null, null],
        ['GET', '_api/unban', null, null],
        ['POST', '_api/unban', null, null],                                       // PACK = jl: token จาก POST ก็ได้
        ['POST', '_api/useragent', 'legacy.auth', null],
        ['POST', '_cart/delete_cart', 'legacy.perm:order', null],
        ['POST', '_cart/delete_carts', 'legacy.perm:order', null],
        ['POST', '_cart/load_cart', 'legacy.perm:order', null],
        ['POST', '_cart/load_choose_product', 'legacy.auth', null],
        ['POST', '_cart/load_full_cart', 'legacy.perm:order', null],
        ['POST', '_cart/save_amount', 'legacy.perm:order', null],
        ['POST', '_cart/save_checkout', 'legacy.perm:order', null],
        ['POST', '_cart/save_craft', 'legacy.perm:order', null],
        ['POST', '_cart/save_product', 'legacy.perm:order', null],
        ['POST', '_cart/save_qc', 'legacy.perm:order', null],
        ['POST', '_taobao/grid', 'legacy.auth', null],
        ['POST', '_taobao/load_calculator', 'legacy.perm:taobao', null],
        ['POST', '_taobao/load_new_line', 'legacy.perm:taobao', null],
        ['POST', '_taobao/save', 'legacy.perm:taobao', null],
        ['POST', '_taobao/save_remark', 'legacy.perm:taobao', null],
    ];

    public function test_every_legacy_shop_url_is_routed_with_the_legacy_permission(): void
    {
        $problems = [];
        foreach (self::ROUTES as [$method, $uri, $mw, $page]) {
            $route = null;
            foreach (Route::getRoutes() as $r) {
                if ($r->uri() === $uri && in_array($method, $r->methods(), true)) {
                    $route = $r;
                }
            }
            if ($route === null) {
                $problems[] = "missing $method $uri";

                continue;
            }
            $have = $route->gatherMiddleware();
            if ($mw !== null && ! in_array($mw, $have, true)) {
                $problems[] = "$method $uri: missing $mw (has ".implode(',', array_filter($have, 'is_string')).')';
            }
            if ($page !== null && ($route->defaults['legacy_page'] ?? null) !== $page) {
                $problems[] = "$method $uri: legacy_page != $page";
            }
            if (str_starts_with($uri, '_') && $mw !== null && ! str_starts_with($uri, '_api/')
                && ! in_array('legacy.ajax', $have, true)) {
                $problems[] = "$method $uri: ajax ต้องมี legacy.ajax (system_check_ajax เดิม)";
            }
        }
        $this->assertSame([], $problems);
    }

    /** ไฟล์ที่ PACK ไม่มี (jl-only, SR-only ตาม docs/pack-diff.md) และหน้าที่ไม่มีใน content.php (dead) → ไม่มี route (404 → หน้าแรก) */
    public function test_files_missing_in_pack_and_dead_shop_urls_are_not_routed(): void
    {
        $gone = ['_cart/cart', '_cart/product_2', '_cart/product_bk', '_taobao/delete', '_api/updateImagePrefix', 'admin/mall_stock',
            'admin/mall_stock/{id}', 'admin/findproduct', 'admin/findproduct/create', 'admin/findproduct/{id}',
            '_admin/_findproduct/grid', 'mall', 'mall/cart', '_mall/grid', 'admin/mall', '_admin/_mall/grid',
            // SR-only
            '_cart/save_takeapic', 'findproduct', 'findproduct/create', 'findproduct/{id}', '_findproduct/grid', '_findproduct/save',
            '_findproduct/delete', '_api/load_product_v2', '_api/load_product_v7', '_api/send_sms', '_api/get_order_number_v3'];
        $uris = array_map(fn ($r) => $r->uri(), Route::getRoutes()->getRoutes());
        $this->assertSame([], array_values(array_intersect($gone, $uris)));
    }

    public function test_mallstock_save_amount_has_double_submit_guard_like_admin_balance(): void
    {
        $r = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === '_admin/_mallstock/save_amount');
        $this->assertContains('legacy.once', $r->gatherMiddleware());
        $this->assertNotContains('legacy.perm:admin_mall_stock', $r->gatherMiddleware());
    }

    public function test_page_checkout_product_is_named_for_index_redirect(): void
    {
        $this->assertTrue(Route::has('page.checkout_product'));
        $this->assertSame('/cart/product', route('page.checkout_product', [], false));
    }
}
