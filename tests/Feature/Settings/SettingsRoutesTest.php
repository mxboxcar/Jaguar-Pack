<?php

namespace Tests\Feature\Settings;

use App\Domain\Settings\Input;
use App\Domain\Settings\Php74;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** กลุ่ม settings: ทุก URL เดิมมี route + สิทธิ์ตามไฟล์เดิม, และ helper ที่แทน SQL ต่อ string (ไม่แตะ DB) */
class SettingsRoutesTest extends TestCase
{
    /** method uri => middleware ที่ต้องมี */
    private const ROUTES = [
        'GET admin/setting' => ['legacy.perm:admin_setting'],
        'POST _admin/_setting/save' => ['legacy.ajax', 'legacy.perm:admin_setting'],
        'GET admin/exchange' => ['legacy.perm:admin_exchange'],
        'GET admin/exchange/status/{status}' => ['legacy.perm:admin_exchange'],
        'GET admin/exchange/{id}' => ['legacy.perm:admin_exchange'],
        'POST _admin/_exchange/grid' => ['legacy.ajax', 'legacy.auth'],
        'POST _admin/_exchange/save_approved' => ['legacy.ajax', 'legacy.perm:admin_exchange'],
        'POST _admin/_exchange/cancel' => ['legacy.ajax', 'legacy.perm:admin_withdrawpoint'],
        'GET admin/product_type' => ['legacy.perm:admin_product_type'],
        'GET admin/product_type/create' => ['legacy.perm:admin_product_type'],
        'GET admin/product_type/{id}' => ['legacy.perm:admin_product_type'],
        'POST _admin/_product_type/grid' => ['legacy.ajax', 'legacy.auth'],
        'POST _admin/_product_type/save' => ['legacy.ajax', 'legacy.perm:admin_product_type'],
        'POST _admin/_product_type/delete' => ['legacy.ajax', 'legacy.perm:admin_product_type'],
        'GET admin/shipping_type' => ['legacy.perm:admin_shipping_type'],
        'GET admin/shipping_type/create' => ['legacy.perm:admin_shipping_type'],
        'GET admin/shipping_type/{id}' => ['legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/grid' => ['legacy.ajax', 'legacy.auth'],
        'POST _admin/_shipping_type/save' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/save_listno' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/delete' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/delete_cal' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/load_cal_1' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/load_cal_2' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/save_new_price_by_kg' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST _admin/_shipping_type/save_new_price_by_cubic' => ['legacy.ajax', 'legacy.perm:admin_shipping_type'],
        'POST load_quick_search' => ['legacy.ajax', 'legacy.auth'],
        'POST get_price' => ['legacy.ajax', 'legacy.perm:view_bol'],
        'POST system_upload' => ['legacy.ajax', 'legacy.auth'],
        'POST system_upload_2' => ['legacy.ajax', 'legacy.auth'],
        'GET gateway' => ['legacy'],
        'GET index_close' => ['legacy'],
        'GET get_resource' => ['legacy'],
    ];

    public function test_every_legacy_url_has_a_route_with_the_legacy_permission(): void
    {
        $have = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $have[$m.' '.$r->uri()] = $r->gatherMiddleware();
            }
        }
        foreach (self::ROUTES as $key => $mw) {
            $this->assertArrayHasKey($key, $have, "missing route $key");
            foreach ($mw as $m) {
                $this->assertContains($m, $have[$key], "$key lacks $m");
            }
        }
    }

    public function test_delete_id_list_accepts_only_integer_lists(): void
    {
        $ids = fn (array $post) => Input::idList(Request::create('/x', 'POST', $post));
        $this->assertSame([5], $ids(['id' => '5']));
        $this->assertSame([1, 2, 3], $ids(['arr_id' => ',1,2,3']));
        $this->assertSame([], $ids(['id' => '']));
        $this->assertSame([], $ids(['id' => '910719) OR (1=1']));
        $this->assertSame([], $ids(['arr_id' => ',1,2) OR (1=1']));
    }

    public function test_php74_semantics_used_by_the_port(): void
    {
        $this->assertTrue(Php74::eq(null, 0));        // is_use_rate ว่าง → ราคาเหมา
        $this->assertFalse(Php74::eq(null, '0'));     // option "0" ไม่ถูกเลือกเมื่อไม่มีแถว
        $this->assertTrue(Php74::eq('abc', 0));       // 7.4: 'abc' == 0
        $this->assertSame('6', Php74::str(Php74::num('5.0000') + 1));
        $this->assertSame('0', Php74::str(-0.0001 + 0.0001));
        $this->assertNull(Php74::offset(''));         // LIMIT -10 → SQL เดิมพัง → ไม่มีแถว
        $this->assertSame(10, Php74::offset('2'));
    }
}
