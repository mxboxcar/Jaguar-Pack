<?php

namespace Tests\Feature\Lot;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** ทุก URL/ajax ของกลุ่ม lot ใน docs/url-map.md มี route พร้อมสิทธิ์เท่ากับ getPermission() ของไฟล์เดิม */
class LotRoutesTest extends TestCase
{
    private const VIEW = 'legacy.perm:admin_lot,admin_lot_view';

    private const LOT = 'legacy.perm:admin_lot';

    /** [method, uri, middleware ที่ต้องมี] */
    private const ROUTES = [
        ['GET', 'lot', 'legacy.perm:lot'],
        ['GET', 'lot/{id}', 'legacy.perm:lot'],
        ['POST', '_lot/grid', 'legacy.auth'],
        ['POST', '_lot/load_lot_detail', 'legacy.perm:lot'],
        ['GET', '_lot/_message/index', 'legacy.auth'],
        ['POST', '_lot/_message/load', 'legacy.auth'],
        ['POST', '_lot/_message/load_dept', 'legacy.auth'],
        ['POST', '_lot/_message/send', 'legacy.auth'],
        ['GET', 'admin/lot', self::VIEW],
        ['GET', 'admin/lot/create', self::VIEW],
        ['GET', 'admin/lot/{id}', self::VIEW],
        ['GET', '_admin/_lot/print_all', self::VIEW],
        ['POST', '_admin/_lot/grid', self::VIEW],
        ['POST', '_admin/_lot/load_autocomplete_tracking', self::VIEW],
        ['POST', '_admin/_lot/load_autocomplete_user', self::VIEW],
        ['POST', '_admin/_lot/load_check_tracking', self::VIEW],
        ['POST', '_admin/_lot/load_lastest_lot', self::VIEW],
        ['POST', '_admin/_lot/load_lot_detail', self::VIEW],
        ['POST', '_admin/_lot/load_lot_memo', self::VIEW],
        ['POST', '_admin/_lot/load_ref_print', self::VIEW],
        ['POST', '_admin/_lot/load_tracking', self::VIEW],
        ['POST', '_admin/_lot/load_user_rate', self::VIEW],
        ['POST', '_admin/_lot/save', self::LOT],
        ['POST', '_admin/_lot/save_delete_lot_memo', self::LOT],
        ['POST', '_admin/_lot/save_file_lot', self::LOT],
        ['POST', '_admin/_lot/save_lot_memo', self::LOT],
        ['POST', '_admin/_lot/save_lot_name', self::LOT],
        ['POST', '_admin/_lot/save_lot_name_admin', self::LOT],
        ['POST', '_admin/_lot/save_lot_ship_amount', self::LOT],
        ['POST', '_admin/_lot/save_new_lot', self::LOT],
        ['POST', '_admin/_lot/save_print', self::LOT],
        ['POST', '_admin/_lot/save_remove_tracking', self::LOT],
        ['POST', '_admin/_lot/save_status_lot', self::LOT],
        ['POST', '_admin/_lot/save_status_lot_date', self::LOT],
        ['POST', '_admin/_lot/save_status_reset_tracking', self::LOT],
        ['POST', '_admin/_lot/save_status_tracking', self::LOT],
        ['GET', '_admin/_lot/print', self::LOT],
        ['GET', '_admin/_lot/print_receive', self::LOT],
        ['GET', '_admin/_lot/email_lot', self::LOT],
        ['GET', '_admin/_lot/cron_lot_mail', self::LOT],
        ['GET', '_admin/_lot/email', self::LOT],
        ['POST', '_admin/_lot/delete', self::LOT],
        ['POST', '_admin/_lot/save_verified', self::LOT],
        ['POST', '_admin/_lot/cancel', 'legacy.perm:admin_refill'],
    ];

    public function test_every_lot_file_has_a_route_with_the_legacy_permission(): void
    {
        $routes = Route::getRoutes();
        foreach (self::ROUTES as [$method, $uri, $mw]) {
            $found = null;
            foreach ($routes as $r) {
                if ($r->uri() === $uri && in_array($method, $r->methods(), true)) {
                    $found = $r;
                    break;
                }
            }
            $this->assertNotNull($found, "$method $uri");
            $this->assertContains($mw, $found->gatherMiddleware(), "$method $uri");
            if ($method === 'POST' && ! str_starts_with($uri, '_lot/_message')) {
                $this->assertContains('legacy.ajax', $found->gatherMiddleware(), "$method $uri");
            }
        }
    }

    public function test_route_count_matches_url_map(): void
    {
        $map = file_get_contents(base_path('docs/url-map.md'));
        $section = substr($map, strpos($map, '### lot ('));
        $section = substr($section, 0, strpos($section, "\n### ", 5) ?: strlen($section));
        preg_match_all('/^\| `(_[^`]+)\.php` \| `\/([^`]+)` \|/m', $section, $m);
        $this->assertCount(43, $m[2]);
        $uris = array_column(self::ROUTES, 1);
        foreach ($m[2] as $uri) {
            // form.php / index.php เดิมเปิดผ่าน RewriteRule (lot, lot/{id}, admin/lot...) ไม่ใช่ path ไฟล์
            if (preg_match('#/(form|index)$#', $uri) && ! str_starts_with($uri, '_lot/_message')) {
                continue;
            }
            $this->assertContains($uri, $uris, $uri);
        }
    }
}
