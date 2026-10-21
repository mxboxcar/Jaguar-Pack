<?php

namespace Tests\Feature\Access;

use App\Http\Controllers\Messaging\Admin\AdminNoticeController;
use App\Http\Middleware\LegacyPermission;
use App\Support\Legacy\Alerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * ผลปฏิเสธสิทธิ์ต่อ role ต้องตรงกับเว็บเดิม (acceptance check C, docs/role-matrix.md)
 */
class RoleDenialTest extends TestCase
{
    use FakeLegacyUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLegacyWorld();
    }

    /** รัน legacy.perm ของ route ตามลำดับจริง → ชื่อโมดูลของ ErrorBox ตัวแรกที่ปฏิเสธ (null = ผ่าน) */
    private function deniedModule(string $method, string $uri, Request $req): ?string
    {
        $route = Route::getRoutes()->match(Request::create($uri, $method));
        foreach ($route->gatherMiddleware() as $mw) {
            if (! is_string($mw) || ! str_starts_with($mw, 'legacy.perm:')) {
                continue;
            }
            $modules = explode(',', substr($mw, strlen('legacy.perm:')));
            $res = $this->app->make(LegacyPermission::class)->handle($req, fn () => response('next'), ...$modules);
            if ($res->headers->get('X-Legacy-Denied') === '1') {
                return $modules[0];
            }
        }

        return null;
    }

    /** SR _admin/_transfer/form.php ตรวจ getPermission('admin_transfer') (jl ตรวจ 'transfer' = IDOR — SR แก้ในไฟล์เดิมแล้ว) */
    /** PACK _admin/_transfer/form.php (= jl) ตรวจ getPermission('transfer') ก่อน → ผู้ไม่มี transfer ได้ข้อความโมดูล transfer เหมือนเดิม */
    public function test_admin_transfer_form_checks_transfer_first(): void
    {
        foreach (['/admin/transfer/create', '/admin/transfer/123'] as $uri) {
            // PACK role 3/5/6: ไม่มี transfer → เดิม "โอนเงินไปจีน"
            $req = $this->actAs(910, ['admin_tracking', 'admin_lot_view', 'admin_order']);
            $this->assertSame('transfer', $this->deniedModule('GET', $uri, $req), $uri);
            $req = $this->actAs(912, ['admin_transfer']);
            $this->assertSame('transfer', $this->deniedModule('GET', $uri, $req), $uri);

            // ลูกค้า / role 7/9: มี transfer แต่ไม่มี admin_transfer → ปฏิเสธ (ต่างจากเดิมโดยตั้งใจ: IDOR)
            $req = $this->actAs(911, ['transfer', 'admin_order']);
            $this->assertSame('admin_transfer', $this->deniedModule('GET', $uri, $req), $uri);

            $req = $this->actAs(914, ['transfer', 'admin_transfer']);
            $this->assertNull($this->deniedModule('GET', $uri, $req), $uri);
        }
    }

    public function test_admin_transfer_denial_text_matches_legacy_module_label(): void
    {
        // หน้าเต็มใช้ view legacy.no-permission (ต้องต่อ DB) — ที่นี่ตรวจข้อความแบบ ajax ซึ่งสร้างจากโมดูลเดียวกัน
        $req = $this->actAs(913, ['admin_order']);
        $res = $this->app->make(LegacyPermission::class)->handle($req, fn () => response('next'), 'transfer');
        $this->assertSame(Alerts::permission('transfer'), (string) $res->getContent());
        $this->assertStringContainsString('โอนเงินไปจีน', (string) $res->getContent());
        $this->assertStringNotContainsString('ผู้ดูแล - โอนเงินไปจีน', (string) $res->getContent());
    }

    /** _admin/_notice/grid.php เดิม: ไม่มีสิทธิ์ admin_notice → exit() ตอบว่าง (ไม่ใช่ ErrorBox) */
    public function test_admin_notice_grid_without_permission_is_empty(): void
    {
        $route = Route::getRoutes()->match(Request::create('/_admin/_notice/grid', 'POST'));
        $mw = $route->gatherMiddleware();
        $this->assertContains('legacy.auth', $mw);
        $this->assertContains('legacy.ajax', $mw);
        $this->assertEmpty(array_filter($mw, fn ($m) => is_string($m) && str_starts_with($m, 'legacy.perm:')));

        $req = $this->actAs(914, ['admin_product_type', 'admin_order'], [], ['page' => '1']);
        $res = $this->app->make(AdminNoticeController::class)->grid($req);
        $this->assertSame('', (string) $res->getContent());
        $this->assertSame(200, $res->getStatusCode());
    }
}
