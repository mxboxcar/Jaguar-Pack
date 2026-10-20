<?php

namespace Tests\Feature\Lot;

use App\Http\Middleware\LegacyPermission;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Permissions;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): ผู้ถือ admin_lot_view อย่างเดียว = ดูได้อย่างเดียว
 * endpoint ที่เขียนข้อมูลทุกตัวในกลุ่ม lot ต้องมี admin_lot — ไม่มีสิทธิ์ได้ ErrorBox แบบ getPermissionAlert('admin_lot') เดิม
 */
class LotViewOnlyTest extends TestCase
{
    use FakeLegacyUser;

    /** endpoint แอดมินของ lot ที่เขียน DB / เข้าคิวเมล */
    private const WRITES = [
        ['POST', '_admin/_lot/save'], ['POST', '_admin/_lot/save_delete_lot_memo'], ['POST', '_admin/_lot/save_file_lot'],
        ['POST', '_admin/_lot/save_lot_memo'], ['POST', '_admin/_lot/save_lot_name'], ['POST', '_admin/_lot/save_lot_name_admin'],
        ['POST', '_admin/_lot/save_lot_ship_amount'], ['POST', '_admin/_lot/save_new_lot'], ['POST', '_admin/_lot/save_print'],
        ['POST', '_admin/_lot/save_remove_tracking'], ['POST', '_admin/_lot/save_status_lot'], ['POST', '_admin/_lot/save_status_lot_date'],
        ['POST', '_admin/_lot/save_status_reset_tracking'], ['POST', '_admin/_lot/save_status_tracking'], ['POST', '_admin/_lot/delete'],
        ['POST', '_admin/_lot/save_verified'], ['GET', '_admin/_lot/print'], ['GET', '_admin/_lot/print_receive'],
        ['GET', '_admin/_lot/email_lot'], ['POST', '_admin/_lot/save_quickfile'],
    ];

    private const READS = [
        ['GET', 'admin/lot'], ['GET', 'admin/lot/{id}'], ['POST', '_admin/_lot/grid'],
        ['POST', '_admin/_lot/load_lot_detail'], ['POST', '_admin/_lot/load_lot_memo'], ['POST', '_admin/_lot/load_tracking'],
        ['POST', '_admin/_lot/load_image'], ['POST', '_admin/_lot/load_quickfile'], ['POST', '_admin/_lot/load_toptable'],
        ['POST', '_admin/_lot/load_unpaid'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLegacyWorld();
    }

    private function route(string $method, string $uri): RoutingRoute
    {
        foreach (Route::getRoutes() as $r) {
            if ($r->uri() === $uri && in_array($method, $r->methods(), true)) {
                return $r;
            }
        }
        $this->fail("$method $uri ไม่มี route");
    }

    /** รัน legacy.perm ทุกตัวของ route ด้วยผู้ใช้ปัจจุบัน → null = ผ่าน, Response = ถูกปฏิเสธ */
    private function gate(RoutingRoute $route): ?Response
    {
        foreach ($route->gatherMiddleware() as $mw) {
            if (! is_string($mw) || ! str_starts_with($mw, 'legacy.perm:')) {
                continue;
            }
            $mods = explode(',', substr($mw, strlen('legacy.perm:')));
            $res = (new LegacyPermission($this->app->make(Permissions::class)))
                ->handle(request(), fn () => response('passed'), ...$mods);
            if ($res->getContent() !== 'passed') {
                return $res;
            }
        }

        return null;
    }

    public function test_write_routes_require_admin_lot_and_not_view(): void
    {
        foreach (self::WRITES as [$method, $uri]) {
            $mw = $this->route($method, $uri)->gatherMiddleware();
            $this->assertContains('legacy.perm:admin_lot', $mw, "$method $uri");
            foreach ($mw as $m) {
                $this->assertStringNotContainsString('admin_lot_view', (string) $m, "$method $uri");
            }
        }
    }

    public function test_view_only_user_is_refused_on_every_write_with_legacy_alert(): void
    {
        foreach (self::WRITES as [$method, $uri]) {
            $this->actAs(74, ['admin_lot_view', 'profile']);
            $res = $this->gate($this->route($method, $uri));
            $this->assertNotNull($res, "$method $uri ต้องถูกปฏิเสธ");
            $this->assertSame('1', $res->headers->get('X-Legacy-Denied'));
            $this->assertSame(Alerts::permission('admin_lot'), $res->getContent(), "$method $uri");
        }
    }

    public function test_view_only_user_can_still_read(): void
    {
        foreach (self::READS as [$method, $uri]) {
            $this->actAs(74, ['admin_lot_view']);
            $this->assertNull($this->gate($this->route($method, $uri)), "$method $uri");
        }
    }

    public function test_admin_lot_holder_can_write(): void
    {
        foreach (self::WRITES as [$method, $uri]) {
            $this->actAs(11938, ['admin_lot']);
            $this->assertNull($this->gate($this->route($method, $uri)), "$method $uri");
        }
    }
}
