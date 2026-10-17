<?php

namespace Tests\Feature\Payment;

use App\Domain\Payment\PaymentLookups;
use App\Domain\Payment\PaymentTotals;
use App\Domain\Payment\PinBox;
use App\Domain\Payment\TempUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม payment: ทุก URL เดิมมี route + สิทธิ์ตรงกับ getPermission() ของไฟล์เดิม และ helper ที่เลียนแบบ PHP 7.4 (ไม่แตะ DB)
 */
class PaymentRoutesTest extends TestCase
{
    /** method uri => middleware สิทธิ์ที่ต้องมี */
    private const EXPECTED = [
        // ลูกค้า: payment
        'GET payment' => 'legacy.perm:payment',
        'GET payment/create' => 'legacy.perm:payment',
        'GET payment/create/{random}' => 'legacy.perm:payment',
        'GET payment/status/{status}' => 'legacy.perm:payment',
        'GET payment/{id}' => 'legacy.perm:payment',
        'POST _payment/grid' => 'legacy.auth',
        'POST _payment/load_create' => 'legacy.perm:payment',
        'POST _payment/load_create_sum' => 'legacy.perm:payment',
        'POST _payment/load_select_lot' => 'legacy.perm:payment',
        'POST _payment/load_shipping_price' => 'legacy.perm:payment',
        'POST _payment/load_vat' => 'legacy.perm:payment',
        'POST _payment/load_receipt_address' => 'legacy.perm:payment',
        'POST _payment/save_create' => 'legacy.perm:payment',
        'POST _payment/save_submit_payment' => 'legacy.perm:payment',
        'POST _payment/save_delete' => 'legacy.perm:payment',
        'POST _payment/save_create_receiptaddress' => 'legacy.perm:payment',
        'POST _payment/save_deleteaddress' => 'legacy.perm:payment',
        'POST _payment/save_deletereceiptaddress' => 'legacy.perm:payment',
        'POST _payment/save' => 'legacy.perm:refill',
        'GET _payment/view_example' => 'legacy.auth',
        'POST _payment/view_example' => 'legacy.auth',
        // แอดมิน: payment
        'GET admin/payment' => 'legacy.perm:admin_payment',
        'GET admin/payment/status/{status}' => 'legacy.perm:admin_payment',
        'GET admin/payment/{id}' => 'legacy.perm:admin_payment',
        'POST _admin/_payment/grid' => 'legacy.auth',
        'POST _admin/_payment/save_approved' => 'legacy.perm:admin_payment',
        'POST _admin/_payment/save_delete' => 'legacy.perm:admin_payment',
        'POST _admin/_payment/save_deletenopush' => 'legacy.perm:admin_payment',
        'POST _admin/_payment/save_vat' => 'legacy.perm:admin_payment',
        'POST _admin/_payment/load_shipping_price' => 'legacy.perm:admin_payment',
        'POST _admin/_payment/save_new_sender' => 'legacy.perm:admin_change_sender',
        'GET _admin/_payment/email_approved' => 'legacy.perm:admin_payment',
        'GET _admin/_payment/email_cancel' => 'legacy.perm:admin_payment',
        'GET admin/payment/create' => 'legacy.perm:admin_payment_create',
        'GET admin/payment/create/{user_id}' => 'legacy.perm:admin_payment_create',
        'GET admin/payment/create/{user_id}/{random}' => 'legacy.perm:admin_payment_create',
        'POST _admin/_payment/load_autocomplete_user' => 'legacy.perm:admin_payment_create',
        'POST _admin/_payment/load_user_by_id' => 'legacy.perm:admin_payment_create',
        'POST _admin/_payment/load_create' => 'legacy.perm:admin_payment_create',
        'POST _admin/_payment/load_select_lot' => 'legacy.auth',
        'POST _admin/_payment/save_create' => 'legacy.perm:admin_payment_create',
        'POST _admin/_payment/save_submit_payment' => 'legacy.perm:admin_payment_create',
        // ลูกค้า: alipay
        'GET alipay' => 'legacy.perm:alipay',
        'GET alipay/create' => 'legacy.perm:alipay',
        'GET alipay/status/{status}' => 'legacy.perm:alipay',
        'GET alipay/{id}' => 'legacy.perm:alipay',
        'POST _alipay/grid' => 'legacy.auth',
        'POST _alipay/save' => 'legacy.perm:alipay',
        'POST _alipay/cancel' => 'legacy.perm:alipay',
        // แอดมิน: alipay
        'GET admin/alipay' => 'legacy.perm:admin_alipay',
        'GET admin/alipay/create' => 'legacy.perm:admin_alipay',
        'GET admin/alipay/status/{status}' => 'legacy.perm:admin_alipay',
        'GET admin/alipay/{id}' => 'legacy.perm:admin_alipay',
        'POST _admin/_alipay/grid' => 'legacy.auth',
        'POST _admin/_alipay/save' => 'legacy.perm:admin_alipay',
        'POST _admin/_alipay/cancel' => 'legacy.perm:admin_alipay',
        'POST _admin/_alipay/rollback' => 'legacy.perm:admin_alipay',
        'GET _admin/_alipay/email_cancel' => 'legacy.perm:admin_alipay',
        'GET _admin/_alipay/email_success' => 'legacy.perm:admin_alipay',
    ];

    /** @return array<string, list<string>> */
    private function routes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $m) {
                if ($m !== 'HEAD') {
                    $out[$m.' '.$route->uri()] = $route->gatherMiddleware();
                }
            }
        }

        return $out;
    }

    public function test_every_legacy_url_has_a_route_with_the_legacy_permission(): void
    {
        $routes = $this->routes();
        foreach (self::EXPECTED as $key => $perm) {
            $this->assertArrayHasKey($key, $routes, "ไม่มี route $key");
            $this->assertContains($perm, $routes[$key], "$key ต้องมี $perm");
            $this->assertContains('legacy', $routes[$key], "$key ต้องอยู่ใต้ group legacy");
        }
    }

    public function test_ajax_endpoints_require_xhr_like_system_check_ajax(): void
    {
        $routes = $this->routes();
        foreach (array_keys(self::EXPECTED) as $key) {
            if (str_starts_with($key, 'POST _') && ! str_contains($key, 'view_example')) {
                $this->assertContains('legacy.ajax', $routes[$key], "$key ต้องมี legacy.ajax");
            }
        }
    }

    public function test_legacy_route_patterns(): void
    {
        $this->assertSame('payment.create', $this->match('GET', '/payment/create'));
        $this->assertSame('payment.createForm', $this->match('GET', '/payment/create/ABCDEFGHIJKLMNO'));
        $this->assertSame('payment.form', $this->match('GET', '/payment/12'));
        $this->assertSame('admin.createForm', $this->match('GET', '/admin/payment/create/137/ABCDEFGHIJKLMNO'));
        $this->assertSame('admin.create', $this->match('GET', '/admin/payment/create/137'));
        $this->assertSame('alipay.form', $this->match('GET', '/alipay/create'));
    }

    private function match(string $method, string $uri): string
    {
        $route = Route::getRoutes()->match(Request::create($uri, $method));
        $action = class_basename($route->getControllerClass()).'.'.$route->getActionMethod();

        return [
            'PaymentController.create' => 'payment.create',
            'PaymentController.createForm' => 'payment.createForm',
            'PaymentController.form' => 'payment.form',
            'AdminPaymentCreateController.createForm' => 'admin.createForm',
            'AdminPaymentCreateController.create' => 'admin.create',
            'AlipayController.form' => 'alipay.form',
        ][$action] ?? $action;
    }

    public function test_pin_box_matches_legacy_structure(): void
    {
        $js = PinBox::getPinBox(0, '.cashback_form', 'withdrawpointing()');
        $this->assertStringStartsWith("var htmlpin = '<div id=\"divpin\">", $js);
        $this->assertStringContainsString('method : "verified"', $js);
        $this->assertStringContainsString('callback : "withdrawpointing()"', $js);
        $this->assertStringContainsString('$(".cashback_form").eq(0).append(htmlpin);', $js);
        $this->assertStringContainsString('method : "new"', PinBox::getNewPinBox());
        // ค่าที่ฝังใน JS ถูก escape (เดิมต่อดิบ)
        $this->assertStringContainsString('callback : "a\\"); alert(1); //"', PinBox::getPinBox(0, 'form', 'a"); alert(1); //'));
        $this->assertStringContainsString('$("form").eq(0)', PinBox::getNewPinBox(''));
    }

    public function test_vat_formula_matches_legacy(): void
    {
        // ค่าจาก php7.4 + โค้ดเดิม (_payment/save_submit_payment.php, _admin/_payment/save_vat.php) บน admin_jl_bench
        $t = PaymentTotals::vat(30, 155.72, '0.00', '7', false);
        $this->assertSame(['13.00', '198.72', '5.57', '193.15'], [$t['vat_before'], $t['vat'], $t['shipvat_before'], $t['shipvat']]);
        $t = PaymentTotals::vat('30.00', 155.72, '0.00', '7', true);
        $this->assertSame(['13.00', '198.72', '5.96', '192.76'], [$t['vat_before'], $t['vat'], $t['shipvat_before'], $t['shipvat']]);
        $this->assertEquals(265.72, PaymentTotals::plain('10.00', 155.72, '0.00', '100.00'));
    }

    public function test_row_sums_follow_legacy_order_and_types(): void
    {
        $row = (object) ['price_kg' => '87.10', 'price_cubic' => '0.00', 'price_amount' => '0.00', 'price_qc' => '10.50',
            'price_repack' => '0.00', 'price_express' => '5.25', 'price_else' => '0.00', 'price_craft' => '1.10',
            'width' => '12.5', 'height' => '12', 'long' => '36', 'amount' => '2'];
        $this->assertSame('103.95', PaymentLookups::s(PaymentLookups::add($row, ['price_kg', 'price_cubic', 'price_amount', 'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft'])));
        $this->assertSame('0.0108', PaymentLookups::s(PaymentLookups::cubic($row)));
        $this->assertSame('-<br />-', PaymentLookups::dateCell('0000-00-00 00:00:00', false));
        $this->assertNull(PaymentLookups::offset('-1'));
        $this->assertSame(10, PaymentLookups::offset('2'));
        $str = PaymentLookups::str((object) ['a' => 1, 'b' => null, 'c' => 1.5]);
        $this->assertSame(['a' => '1', 'b' => null, 'c' => '1.5'], (array) $str);
    }

    public function test_temp_upload_names_are_whitelisted(): void
    {
        $this->assertFalse(TempUpload::exists('../../.env'));
        $this->assertFalse(TempUpload::safeName('a/b.jpg'));
        $this->assertTrue(TempUpload::safeName('abc_1.jpg'));
        $this->assertStringContainsString("data : 'path=../upload/temp/x\\'y.jpg'", TempUpload::blobJs("x'y.jpg"));
    }
}
