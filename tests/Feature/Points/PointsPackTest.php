<?php

namespace Tests\Feature\Points;

use App\Domain\Points\CashbackMailer;
use App\Http\Controllers\Points\Admin\AdminShopCashbackController;
use App\Http\Controllers\Points\PointController;
use App\Http\Controllers\Points\ShopCashbackController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * กลุ่ม points ของ PACK (2026-10-22) — ไม่แตะ DB จริง (DB ปลอม: ถ้ามีคำสั่งอื่นนอกจาก ips = test ล้ม)
 * - _point/save_withdraw.php ของ PACK (= jl): ตรวจ PIN แล้วแลกแต้มจริง (SR ตอบ 'ยังไม่เปิดบริการให้แลก' เสมอ)
 * - _shop_cashback/save.php (= SR): ถาม PIN จ่ายเงินก่อนบันทึก
 * - _admin/_shop_cashback/email_*.php: hotfix 2026-10-18 = include เท่านั้น เปิดตรง 403; ปุ่มสี #eb4823 ของ PACK
 */
class PointsPackTest extends TestCase
{
    use FakeLegacyUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLegacyWorld();
        $ips = Mockery::mock();
        $ips->shouldReceive('insert')->andReturn(true);
        DB::shouldReceive('table')->with('ips')->andReturn($ips);
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new \Illuminate\Database\Query\Expression($v));
    }

    private function withdraw(array $post, array $attrs = ['pin' => '123456'], array $granted = ['point']): string
    {
        $req = $this->actAs(251, $granted, [], $post + ['wantcash' => '10'], '/_point/save_withdraw', '10.1.2.3', $attrs);

        return (string) app(PointController::class)->saveWithdraw($req)->getContent();
    }

    public function test_withdraw_with_correct_pin_goes_on_to_the_exchange(): void
    {
        // PACK: PIN ถูก → ตรวจยอด/INSERT ใน transaction (ปลอมให้คืน null = แต้มไม่พอ)
        DB::shouldReceive('transaction')->once()->andReturn(null);
        $this->assertSame("ErrorBox('".\App\Support\Legacy\Lang::get('error_point')."', 'CloseLoading()'); ", $this->withdraw(['paymentpin' => '123456']));
        $this->assertStringNotContainsString('ยังไม่เปิดบริการให้แลก', (string) file_get_contents(app_path('Http/Controllers/Points/PointController.php')));
    }

    public function test_withdraw_rejects_ambiguous_comma_before_the_transaction(): void
    {
        DB::shouldReceive('transaction')->never();
        $this->assertSame("ErrorBox('".\App\Domain\Money\Money::BAD_AMOUNT."', 'CloseLoading()');", $this->withdraw(['paymentpin' => '123456', 'wantcash' => '1,00']));
    }

    public function test_withdraw_asks_for_pin_with_withdrawpointing_callback(): void
    {
        foreach (['', 'undefined'] as $pin) {
            $out = $this->withdraw(['paymentpin' => $pin]);
            $this->assertStringContainsString('htmlpin', $out);
            $this->assertStringContainsString('callback : "withdrawpointing()"', $out);
            $this->assertStringContainsString('$(".cashback_form").eq(0).append(htmlpin)', $out);
        }
        $this->assertStringContainsString('textconfirmpaymentpin', $this->withdraw([], ['pin' => '']), 'ยังไม่มี PIN = กล่องตั้ง PIN');
        $this->assertSame("ErrorBox('PIN จ่ายเงินไม่ถูกต้อง', 'CloseLoading()');", $this->withdraw(['paymentpin' => '654321']));
        $this->assertSame('', $this->withdraw(['paymentpin' => '123456'], ['pin' => '123456'], []), 'ไม่มีสิทธิ์ point = ว่าง (เดิม exit)');
    }

    public function test_cashback_save_asks_for_pin_before_anything(): void
    {
        $call = function (array $post, string $pin) {
            $req = $this->actAs(252, ['shop_cashback'], [], $post + ['order' => ['zz'], 'price' => ['-5'], 'date' => ['']],
                '/_shop_cashback/save', '10.1.2.4', ['pin' => $pin]);

            return (string) app(ShopCashbackController::class)->save($req)->getContent();
        };
        $this->assertStringContainsString('textconfirmpaymentpin', $call([], ''));
        $box = $call([], '111222');
        $this->assertStringContainsString('htmlpin', $box);
        $this->assertStringContainsString('$("form").eq(0).append(htmlpin)', $box);
        $this->assertStringContainsString('callback : ""', $box);
        $this->assertSame("ErrorBox('PIN จ่ายเงินไม่ถูกต้อง', 'CloseLoading()');", $call(['paymentpin' => '999999'], '111222'));
        // PIN ถูกแล้วจึงตรวจยอด (ยอดติดลบ = ปฏิเสธ ไม่สร้างรายการ)
        $this->assertStringContainsString(ShopCashbackController::BAD_PRICE, $call(['paymentpin' => '111222'], '111222'));
    }

    public function test_email_files_are_include_only(): void
    {
        $this->assertSame(403, app(AdminShopCashbackController::class)->emailDirect()->getStatusCode());
        $this->assertSame('', app(AdminShopCashbackController::class)->emailDirect()->getContent());
        foreach (['email_approved', 'email_cancel'] as $f) {
            foreach (['GET', 'POST'] as $m) {
                $r = Route::getRoutes()->match(\Illuminate\Http\Request::create('/_admin/_shop_cashback/'.$f, $m));
                $this->assertSame(AdminShopCashbackController::class.'@emailDirect', $r->getActionName());
                $this->assertContains('legacy.perm:admin_shop_cashback', $r->gatherMiddleware());
            }
        }
    }

    public function test_cashback_mail_button_is_pack_style(): void
    {
        $m = new \ReflectionMethod(CashbackMailer::class, 'button');
        $html = $m->invoke(null, 'https://x/shop_cashback/1');
        $this->assertStringContainsString('width:100%;text-align:center;font-size:15px;background-color:#eb4823;padding:10px 0px;', $html);
        $this->assertStringNotContainsString('_blank', $html);
        $this->assertStringNotContainsString('a3b2f1', $html);
        $this->assertStringNotContainsString('991b1e', $html);
        // PACK ไม่มี sendLine / push_notification และไม่เลือก line_uid
        $src = (string) file_get_contents(app_path('Domain/Points/CashbackMailer.php'));
        $this->assertStringNotContainsString('line_uid', $src);
    }

    public function test_point_page_js_is_pack(): void
    {
        $blade = file_get_contents(resource_path('views/points/point/index.blade.php'));
        $this->assertStringContainsString('function withdrawpointing()', $blade, 'PACK (= jl) มี withdrawpointing() ให้กล่อง PIN');
        $this->assertStringContainsString("data : 'wantcash=' + \$(\"#wantcash\").val() + '&paymentpin=' + \$(\"input[name='paymentpin']\").val() ,", $blade);
        $this->assertStringContainsString("ErrorBox('กรุณากรอกจำนวน');", $blade);
    }
}
