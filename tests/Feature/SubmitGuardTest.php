<?php

namespace Tests\Feature;

use App\Domain\Balance\Withdraw;
use App\Domain\Money\Money;
use App\Support\SubmitGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กันกดซ้ำ/ส่งซ้อน (legacy.once / App\Support\SubmitGuard) — ไม่แตะ DB
 * ผลกับ DB จริง (1 รายการ / ตัดเงินครั้งเดียว): tests/Feature/Money/DoubleSubmitDbTest.php (JL_BENCH_DB_TESTS=1)
 */
class SubmitGuardTest extends TestCase
{
    /** endpoint ที่สร้างรายการใหม่ และ/หรือ ตัด/เติมเงินตอนสร้าง — ต้องผ่าน legacy.once ทุกตัว */
    private const GUARDED = [
        // ลูกค้า
        '_taobao/save' => 'legacy.once', '_transfer/save' => 'legacy.once', '_balance/save_withdraw' => 'legacy.once',
        '_balance_cn/save_withdraw' => 'legacy.once', '_refill/save' => 'legacy.once', '_payment/save' => 'legacy.once',
        '_payment/save_create' => 'legacy.once', '_payment/save_submit_payment' => 'legacy.once',
        '_payment/save_create_receiptaddress' => 'legacy.once', '_point/save_withdraw' => 'legacy.once',
        '_lost_package/save' => 'legacy.once', '_cart/save_checkout' => 'legacy.once', '_export/save_approved' => 'legacy.once',
        '_tracking/save' => 'legacy.once', '_alipay/save' => 'legacy.once', '_mall/save' => 'legacy.once',
        '_shop_cashback/save' => 'legacy.once', '_order/save_payment' => 'legacy.once',
        // แอดมิน
        '_admin/_refill/save' => 'legacy.once', '_admin/_balance/save_amount' => 'legacy.once',
        '_admin/_balance_cn/save_amount' => 'legacy.once', '_admin/_balance_company/save_amount' => 'legacy.once',
        '_admin/_payment/save_create' => 'legacy.once', '_admin/_payment/save_submit_payment' => 'legacy.once',
        '_admin/_tracking/save' => 'legacy.once', '_admin/_goodsconfirm/save' => 'legacy.once',
        '_admin/_lot/save_new_lot' => 'legacy.once:lock', '_admin/_warehouse/save_new_warehouse' => 'legacy.once:lock',
        '_admin/_order/save_add_new_track' => 'legacy.once:lock,html',
        // E2E admin writes (2026-10-18): สร้างรายการซ้ำเมื่อดับเบิลคลิก (ผู้ใช้ username ซ้ำ, role/กลุ่มราคา/สินค้า/สี-ขนาด/ชั้นวางซ้ำ)
        '_admin/_user/save' => 'legacy.once', '_admin/_role/save' => 'legacy.once', '_admin/_user_type/save' => 'legacy.once',
        '_admin/_mall/save' => 'legacy.once', '_admin/_mall/save_sizecolor' => 'legacy.once',
        '_admin/_mall_category/save' => 'legacy.once', '_admin/_warehouse_section/save' => 'legacy.once',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_every_create_endpoint_is_guarded(): void
    {
        foreach (self::GUARDED as $uri => $mw) {
            $route = Route::getRoutes()->match(Request::create('/'.$uri, 'POST'));
            $this->assertSame($uri, $route->uri());
            $this->assertContains($mw, $route->gatherMiddleware(), "$uri ต้องมี $mw");
            // lock ต้องอยู่หลังตรวจสิทธิ์/ajax (คำขอที่ไม่มีสิทธิ์ไม่แย่ง lock)
            $names = array_values(array_filter($route->gatherMiddleware(), 'is_string'));
            $this->assertSame($mw, end($names), "$uri: legacy.once ต้องเป็น middleware สุดท้าย");
        }
    }

    public function test_shelf_allocation_get_is_locked(): void
    {
        $route = Route::getRoutes()->match(Request::create('/_admin/_warehouse/save_lot_detail', 'GET'));
        $names = array_values(array_filter($route->gatherMiddleware(), 'is_string'));
        $this->assertSame('legacy.once:lock', end($names));
    }

    private function guarded(string $hash, string $body, bool $window = true, ?int &$calls = null): string
    {
        $calls ??= 0;

        return (string) SubmitGuard::run(42, '_zz/save', $hash, $window,
            fn (string $t) => response(SubmitGuard::refusal($t)),
            function () use ($body, &$calls) {
                $calls++;

                return response($body);
            })->getContent();
    }

    public function test_concurrent_request_is_refused_while_the_first_holds_the_lock(): void
    {
        $lock = Cache::lock('submit:42:_zz/save', 30);
        $this->assertTrue($lock->get());
        $calls = 0;
        $out = $this->guarded('h1', "SuccessBox('x','y');", true, $calls);
        $this->assertSame(0, $calls, 'คำขอซ้อนต้องไม่เข้า controller');
        $this->assertSame("ErrorBox('".SubmitGuard::BUSY."', 'CloseLoading()');", $out);
        $lock->release();
        // ผู้ใช้อื่น/route อื่นไม่โดนล็อกนี้
        $this->assertTrue(Cache::lock('submit:43:_zz/save', 1)->get());
        $this->assertTrue(Cache::lock('submit:42:_zz/other', 1)->get());
    }

    public function test_identical_payload_after_success_is_refused_within_the_window(): void
    {
        $calls = 0;
        $this->assertStringContainsString('SuccessBox', $this->guarded('h1', "SuccessBox('x','y');", true, $calls));
        $this->assertStringContainsString(SubmitGuard::DUPLICATE, $this->guarded('h1', "SuccessBox('x','y');", true, $calls));
        $this->assertSame(1, $calls);
        // payload ต่าง = รายการใหม่ ทำได้ทันที
        $this->assertStringContainsString('SuccessBox', $this->guarded('h2', "SuccessBox('x','y');", true, $calls));
        $this->assertSame(2, $calls);
        // lock ถูกปล่อยหลังจบทุกคำขอ
        $this->assertTrue(Cache::lock('submit:42:_zz/save', 1)->get());
    }

    public function test_window_expires(): void
    {
        $this->guarded('h1', "window.location.href = 'x'");
        $this->travel(SubmitGuard::WINDOW_SECONDS + 1)->seconds();
        $calls = 0;
        $this->guarded('h1', "SuccessBox('x','y');", true, $calls);
        $this->assertSame(1, $calls);
    }

    public function test_errors_and_pin_prompts_are_not_remembered(): void
    {
        $calls = 0;
        $this->guarded('h1', "ErrorBox('ยอดเงินไม่พอ', 'CloseLoading()');", true, $calls);
        $this->guarded('h1', '$(".pin").modal("show");', true, $calls); // กล่องถาม PIN
        $this->guarded('h1', '', true, $calls);
        $this->guarded('h1', "SuccessBox('x','y');", true, $calls);
        $this->assertSame(4, $calls);
    }

    public function test_lock_only_mode_allows_identical_payloads_in_a_row(): void
    {
        $calls = 0;
        $this->guarded('h1', "window.location.href = 'x'", false, $calls);
        $this->guarded('h1', "window.location.href = 'x'", false, $calls);
        $this->assertSame(2, $calls);
        $this->assertSame('<script>ErrorBox(\'a\', \'CloseLoading()\');</script>', SubmitGuard::refusal('a', true));
    }

    public function test_exception_in_controller_releases_the_lock(): void
    {
        try {
            SubmitGuard::run(42, '_zz/save', 'h1', true, fn ($t) => response($t), fn () => throw new \RuntimeException('boom'));
            $this->fail('ต้องโยน exception ต่อ');
        } catch (\RuntimeException) {
        }
        $this->assertTrue(Cache::lock('submit:42:_zz/save', 1)->get());
    }

    public function test_payload_hash_ignores_key_order_but_not_values(): void
    {
        $a = SubmitGuard::payloadHash(Request::create('/x', 'POST', ['a' => '1', 'b' => ['y' => '2', 'x' => '3']]));
        $b = SubmitGuard::payloadHash(Request::create('/x', 'POST', ['b' => ['x' => '3', 'y' => '2'], 'a' => '1']));
        $c = SubmitGuard::payloadHash(Request::create('/x', 'POST', ['a' => '1', 'b' => ['y' => '2', 'x' => '4']]));
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
    }

    public function test_comma_amounts(): void
    {
        foreach (['1,00', '1,0000', ',5', '1,000,00', '10,00.50', '1,2,3', '1.000,50'] as $bad) {
            $this->assertTrue(Money::ambiguousComma($bad), $bad);
            $this->assertNull(Withdraw::amount($bad), $bad);
            $this->assertFalse(Money::validRefillAmount($bad), $bad);
        }
        foreach (['1,000' => '1000', '12,345.50' => '12345.50', '1,000,000' => '1000000', '100' => '100', '1.5' => '1.5', '-1,000' => '1000'] as $ok => $norm) {
            $this->assertFalse(Money::ambiguousComma($ok), $ok);
            $this->assertSame($norm, Withdraw::amount($ok), $ok);
        }
        $this->assertTrue(Money::validRefillAmount('1,000.50'));
    }
}
