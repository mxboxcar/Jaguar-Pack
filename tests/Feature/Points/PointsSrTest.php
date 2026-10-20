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
 * กลุ่ม points ของ SR (2026-10-20) — ไม่แตะ DB จริง (DB ปลอม: ถ้ามีคำสั่งอื่นนอกจาก ips = test ล้ม)
 * - _point/save_withdraw.php ของ SR: ตรวจ PIN แล้วตอบ 'ยังไม่เปิดบริการให้แลก' เสมอ (ไม่เขียน point / balance_point)
 * - _shop_cashback/save.php ของ SR: ถาม PIN จ่ายเงินก่อนบันทึก
 * - _admin/_shop_cashback/email_*.php: SR hotfix 2026-10-18 = include เท่านั้น เปิดตรง 403
 */
class PointsSrTest extends TestCase
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

    public function test_withdraw_with_correct_pin_is_not_open_and_writes_nothing(): void
    {
        DB::shouldReceive('transaction')->never();
        $this->assertSame("ErrorBox('ยังไม่เปิดบริการให้แลก', 'CloseLoading()');", $this->withdraw(['paymentpin' => '123456']));
    }

    public function test_withdraw_asks_for_pin_with_sr_callback(): void
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

    public function test_cashback_mail_button_is_sr_style(): void
    {
        $m = new \ReflectionMethod(CashbackMailer::class, 'button');
        $html = $m->invoke(null, 'https://x/shop_cashback/1');
        $this->assertStringContainsString('width:100%;text-align:center;font-size:15px;background-color:#a3b2f1;padding:10px 0px;', $html);
        $this->assertStringNotContainsString('_blank', $html);
        $this->assertStringNotContainsString('991b1e', $html);
    }

    public function test_point_page_js_is_sr(): void
    {
        $blade = file_get_contents(resource_path('views/points/point/index.blade.php'));
        $this->assertStringNotContainsString('function withdrawpointing', $blade, 'SR ไม่มี withdrawpointing() ในหน้า');
        $this->assertStringNotContainsString('&paymentpin=', $blade);
        $this->assertStringContainsString("data : 'wantcash=' + \$(\"#wantcash\").val(),\n\t        url: rooturl + '_point/save_withdraw'", $blade);
    }
}
