<?php

namespace Tests\Feature\Order;

use App\Domain\Order\Html;
use App\Domain\Order\OrderLookups;
use App\Domain\Order\OrderQueries;
use App\Domain\Order\Php74;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม order: ทุก URL เดิมมี route + สิทธิ์ตรงกับ getPermission() ของไฟล์เดิม และ helper ที่เลียนแบบ PHP 7.4 / SQL เดิม
 * (ไม่แตะ DB — toSql() ไม่เปิด connection)
 */
class OrderRoutesTest extends TestCase
{
    /** method uri => middleware สิทธิ์ที่ต้องมี */
    private const EXPECTED = [
        'GET order' => 'legacy.perm:order',
        'GET order/status/{status}' => 'legacy.perm:order',
        'GET order/{id}' => 'legacy.perm:order',
        'GET order/{id}/{div}' => 'legacy.perm:order',
        'POST _order/delete' => 'legacy.perm:order',
        'POST _order/grid' => 'legacy.auth',
        'POST _order/load_paging' => 'legacy.auth',
        'POST _order/load_total_order' => 'legacy.auth',
        'POST _order/save_payment' => 'legacy.auth',
        'POST _order/save_quote' => 'legacy.auth',
        'POST _order/save_quote_tracking' => 'legacy.auth',
        'POST _order/save_remark' => 'legacy.auth',
        'GET _order/print' => 'legacy.auth',
        'GET admin/dashboard' => 'legacy.perm:admin_order',
        'GET admin/order' => 'legacy.perm:admin_order',
        'GET admin/order/status/{status}' => 'legacy.perm:admin_order',
        'GET admin/order/{id}' => 'legacy.perm:admin_order',
        'GET admin/order/{id}/{div}' => 'legacy.perm:admin_order',
        'POST _admin/_order/grid' => 'legacy.auth',
        'POST _admin/_order/load_paging' => 'legacy.perm:admin_order',
        'POST _admin/_order/load_total_order' => 'legacy.perm:admin_order',
        'POST _admin/_order/delete' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_approved' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_finish' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_lock' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_remark' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_quote_admin' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_vat' => 'legacy.perm:admin_order',
        'POST _admin/_order/load_detail' => 'legacy.perm:admin_order',
        'POST _admin/_order/load_calculate_cashback' => 'legacy.perm:admin_order',
        'POST _admin/_order/load_color_form' => 'legacy.perm:admin_order',
        'POST _admin/_order/load_remark_product' => 'legacy.perm:admin_order',
        'POST _admin/_order/load_remark_shop' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_amount' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_price' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_shipping' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_cashback' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_cashback_cancel' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_color' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_craft' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_qc' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_remark_product' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_remark_shop' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_product_type' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_order_number' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_tracking' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_quote' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_valid_track' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_add_new_track' => 'legacy.perm:admin_order',
        'POST _admin/_order/save_remove_track' => 'legacy.perm:admin_order',
        'GET _admin/_order/email_approved' => 'legacy.perm:admin_order',
        'GET _admin/_order/email_cancel' => 'legacy.perm:admin_order',
        'GET _admin/_order/email_cashback' => 'legacy.perm:admin_order',
        'GET admin/taobao' => 'legacy.perm:admin_taobao',
        'GET admin/taobao/create' => 'legacy.perm:admin_taobao',
        'GET admin/taobao/status/{status}' => 'legacy.perm:admin_taobao',
        'GET admin/taobao/{id}' => 'legacy.perm:admin_taobao',
        'POST _admin/_taobao/grid' => 'legacy.auth',
        'POST _admin/_taobao/save' => 'legacy.perm:admin_taobao',
        'POST _admin/_taobao/save_remark' => 'legacy.perm:admin_taobao',
        'POST _admin/_taobao/save_cancel' => 'legacy.perm:admin_taobao',
        'POST _admin/_taobao/save_approved' => 'legacy.auth',
        'POST _admin/_taobao/rollback' => 'legacy.perm:admin_alipay',
        'POST _admin/_taobao/load_calculator' => 'legacy.perm:taobao',
        'POST _admin/_taobao/load_new_line' => 'legacy.perm:taobao',
        'GET _admin/_taobao/email_approved' => 'legacy.perm:admin_taobao',
        'GET _admin/_taobao/email_cancel' => 'legacy.perm:admin_taobao',
    ];

    public function test_every_legacy_url_has_a_route_with_the_legacy_permission(): void
    {
        $found = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $found[$m.' '.$r->uri()] = $r;
            }
        }
        foreach (self::EXPECTED as $key => $mw) {
            $this->assertArrayHasKey($key, $found, "ไม่มี route $key");
            $this->assertContains($mw, $found[$key]->gatherMiddleware(), "$key ต้องมี $mw");
            if (str_starts_with($key, 'POST ')) {
                $this->assertContains('legacy.ajax', $found[$key]->gatherMiddleware(), "$key เดิมผ่าน system_check_ajax");
            }
        }
    }

    public function test_status_routes_only_accept_numbers(): void
    {
        $this->assertSame(404, $this->get('/order/status/abc')->getStatusCode());
        $this->assertSame(404, $this->get('/admin/order/abc')->getStatusCode());
    }

    public function test_mysqli_style_rows_are_strings(): void
    {
        $row = Php74::row((object) ['no' => 0, 'id' => 12, 'x' => null, 'p' => '1.50']);
        $this->assertSame(['no' => '0', 'id' => '12', 'x' => null, 'p' => '1.50'], $row);
        // เดิม "0" == '' เป็นเท็จ (แต่ int 0 == '' เป็นจริงใน 7.4)
        $this->assertFalse(Php74::eq($row['no'], ''));
        $this->assertTrue(Php74::eq(0, ''));
    }

    public function test_grid_filters_match_the_legacy_where_and_having(): void
    {
        $q = OrderQueries::base();
        $this->assertSame('3', OrderQueries::applyShipStatus($q, '1', '3'));
        $this->assertStringContainsString('bill_product.ship = ? and bill_product.status = ?', str_replace(['`', '"'], '', $q->toSql()));
        $this->assertSame(['1', '3'], $q->getBindings());

        $q = OrderQueries::base();
        $this->assertNull(OrderQueries::applyShipStatus($q, '-2', ''));
        $this->assertStringNotContainsString('where', $q->toSql());

        // load_paging ของลูกค้าเดิม: "= 0" กับ derived table → ไม่มีแถวผ่าน — แก้แล้ว (เจ้าของสั่ง): ใช้ HAVING เดียวกับ grid
        $this->assertSame('5', OrderQueries::applyShipStatus(OrderQueries::base(), null, '5'));
        $this->assertSame('6', OrderQueries::applyShipStatus(OrderQueries::base(), null, '6'));
    }

    public function test_load_total_order_ref_uses_php74_comparison(): void
    {
        $q = OrderQueries::base();
        $this->assertNull(OrderQueries::applyRef($q, '4'));
        $this->assertSame(['-1'], $q->getBindings());

        $q = OrderQueries::base();
        OrderQueries::applyRef($q, 'abc'); // "abc" <= 2 เป็นจริงใน 7.4
        $this->assertSame(['abc'], $q->getBindings());

        $q = OrderQueries::base();
        $this->assertSame('6', OrderQueries::applyRef($q, '6'));
        $this->assertSame(['-1'], $q->getBindings());
    }

    public function test_paging_offset_like_make_paging(): void
    {
        $this->assertSame(0, OrderLookups::offset('1'));
        $this->assertSame(10, OrderLookups::offset('2'));
        $this->assertNull(OrderLookups::offset(''));     // LIMIT -10 = SQL error เดิม
        $this->assertNull(OrderLookups::offset('abc'));
    }

    public function test_escaping_keeps_existing_entities(): void
    {
        $this->assertSame('a &amp; &lt;b&gt; &quot;c&quot; &#039; &amp;', Html::h('a & <b> "c" \' &amp;'));
        $this->assertSame("x\\'y\\x22<\\/script>", Html::js("x'y\"</script>"));
    }

    public function test_like_escapes_wildcards(): void
    {
        $this->assertSame('%50\%\_x%', OrderQueries::like('50%_x'));
    }

    public function test_js_inside_html_attribute_re_encodes_entities(): void
    {
        // h() ไม่ encode entity ซ้ำ: "&#39;" ใน attribute จะถูก browser ถอดเป็น ' ก่อนรัน JS → jsAttr ต้อง encode ซ้ำ
        $this->assertSame('1&amp;#39;);alert(1);//', Html::jsAttr('1&#39;);alert(1);//'));
        $this->assertSame("a\\&#039;b\\x22", Html::jsAttr("a'b\""));
        $this->assertSame('12', Html::jsAttr('12'));
    }

    public function test_js_nested_in_double_quoted_eval_string(): void
    {
        // fn_public = "loadAjaxGrid('x', '<page>')" แล้ว eval() → ต้อง escape 2 ชั้น
        $this->assertSame("1\\\\\\');alert(1);//", Html::jsNested("1');alert(1);//"));
        $this->assertSame('1\\\\x22+alert(1)+\\\\x22', Html::jsNested('1"+alert(1)+"'));
        $this->assertSame('3', Html::jsNested('3'));
    }

    public function test_url_drops_script_schemes(): void
    {
        $this->assertSame('http://x/?a=1&amp;b', Html::url('http://x/?a=1&amp;b'));
        $this->assertSame('//item.taobao.com/item.htm?id=1', Html::url('//item.taobao.com/item.htm?id=1'));
        $this->assertSame('#', Html::url('javascript:alert(1)'));
        $this->assertSame('#', Html::url(' JaVa&#x09;script&#58;alert(1)'));
        $this->assertSame('#', Html::url('data:text/html,<script>x</script>'));
        $this->assertSame('', Html::url(null));
    }
}
