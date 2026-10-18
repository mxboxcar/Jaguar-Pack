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
        ['GET', 'mall', 'legacy.auth', 'mall'],
        ['GET', 'mall/{search}', 'legacy.auth', 'mall'],
        ['GET', 'mall/create', 'legacy.auth', 'mall_detail'],
        ['GET', 'mall/cart', 'legacy.auth', 'mall_cart'],
        ['GET', 'mall/{id}', 'legacy.auth', 'mall_detail'],
        ['GET', 'admin/mall', 'legacy.perm:admin_mall', 'admin_mall'],
        ['GET', 'admin/mall/{id}', 'legacy.perm:admin_mall', 'admin_mall_detail'],
        ['GET', 'admin/mall_stock', 'legacy.perm:admin_mall_stock', 'admin_mall_stock'],
        ['GET', 'admin/mall/create', 'legacy.perm:admin_mall', 'admin_mall_detail'],
        ['GET', 'admin/mall_category', 'legacy.perm:admin_mall_category', 'admin_mall_category'],
        ['GET', 'admin/mall_category/{id}', 'legacy.perm:admin_user_type', 'admin_mall_category_detail'],
        ['GET', 'admin/mall_category/create', 'legacy.perm:admin_user_type', 'admin_mall_category_detail'],
        // ajax
        ['POST', '_admin/_mall/delete', 'legacy.perm:admin_product_type', null],
        ['POST', '_admin/_mall/grid', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall/load_detail', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall/load_sizecolor', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall/load_stock', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall/save', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall/save_delsizecolor', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall/save_isshow', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall/save_sizecolor', 'legacy.perm:admin_mall', null],
        ['POST', '_admin/_mall_category/delete', 'legacy.perm:admin_user_type', null],
        ['POST', '_admin/_mall_category/grid', 'legacy.perm:admin_mall_category', null],
        ['POST', '_admin/_mall_category/save', 'legacy.perm:admin_mall_category', null],
        ['POST', '_admin/_mallstock/grid', 'legacy.perm:admin_mall_stock', null],
        ['POST', '_admin/_mallstock/load_amount', 'legacy.perm:admin_mall_stock', null], // แก้จากเดิม (admin_balance)
        ['POST', '_admin/_mallstock/save_amount', 'legacy.perm:admin_mall_stock', null], // แก้จากเดิม (admin_balance)
        ['POST', '_api/blockblob', 'legacy.auth', null],
        ['POST', '_api/blockblob_ajax', 'legacy.auth', null],
        ['POST', '_api/crawl_guard', 'legacy.auth', null],
        ['GET', '_api/get_order_number_v2', null, null],
        ['GET', '_api/get_order_number_v3', null, null],
        ['POST', '_api/image_shrink', 'legacy.auth', null],
        ['POST', '_api/load_product', 'legacy.perm:order', null],
        ['POST', '_api/load_product_v2', 'legacy.perm:order', null],
        ['POST', '_api/load_product_v5', 'legacy.auth', null],
        ['POST', '_api/load_product_v7', 'legacy.auth', null],
        ['POST', '_api/send_sms', 'legacy.auth', null],
        ['GET', '_api/submit_order_number_v2', null, null],
        ['GET', '_api/unban', null, null],
        ['GET', '_api/updateImagePrefix', null, null],
        ['POST', '_api/useragent', 'legacy.auth', null],
        ['POST', '_cart/cart', 'legacy.auth', null],
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
        ['POST', '_mall/addtocart', 'legacy.perm:mall', null],
        ['POST', '_mall/delete_cart', 'legacy.perm:mall', null],
        ['POST', '_mall/grid', 'legacy.auth', null],
        ['POST', '_mall/load_detail', 'legacy.perm:mall', null],
        ['POST', '_mall/save', 'legacy.perm:mall', null],
        ['POST', '_taobao/delete', 'legacy.auth', null],
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
            if (str_starts_with($uri, '_') && $mw !== null && ! str_starts_with($uri, '_api/') && $uri !== '_cart/cart'
                && $uri !== '_taobao/delete' && ! in_array('legacy.ajax', $have, true)) {
                $problems[] = "$method $uri: ajax ต้องมี legacy.ajax (system_check_ajax เดิม)";
            }
        }
        $this->assertSame([], $problems);
    }

    public function test_specific_mall_paths_win_over_search(): void
    {
        $this->assertSame('mall_cart', Route::getRoutes()->match(request()->create('/mall/cart'))->defaults['legacy_page']);
        $this->assertSame('mall_detail', Route::getRoutes()->match(request()->create('/mall/12'))->defaults['legacy_page']);
        $this->assertSame('mall', Route::getRoutes()->match(request()->create('/mall/abc/def'))->defaults['legacy_page']);
    }

    public function test_page_checkout_product_is_named_for_index_redirect(): void
    {
        $this->assertTrue(Route::has('page.checkout_product'));
        $this->assertSame('/cart/product', route('page.checkout_product', [], false));
    }
}
