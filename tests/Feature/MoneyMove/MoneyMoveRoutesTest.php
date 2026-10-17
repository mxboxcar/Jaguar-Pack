<?php

namespace Tests\Feature\MoneyMove;

use App\Domain\MoneyMove\Lookups;
use App\Domain\MoneyMove\Php74;
use App\Domain\MoneyMove\TempUpload;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม money-move: ทุก URL เดิมมี route + สิทธิ์ตรงกับ getPermission() ของไฟล์เดิม และ helper ที่เลียนแบบเว็บเดิม (ไม่แตะ DB)
 */
class MoneyMoveRoutesTest extends TestCase
{
    /** method uri => middleware สิทธิ์ที่ต้องมี (หลายตัว = ต้องมีครบ) */
    private const EXPECTED = [
        'GET refill' => ['legacy.perm:refill'],
        'GET refill/create' => ['legacy.perm:refill'],
        'GET refill/status/{status}' => ['legacy.perm:refill'],
        'GET refill/{id}' => ['legacy.perm:refill'],
        'POST _refill/grid' => ['legacy.auth'],
        'POST _refill/save' => ['legacy.perm:refill'],
        'GET transfer' => ['legacy.perm:transfer'],
        'GET transfer/create' => ['legacy.perm:transfer'],
        'GET transfer/status/{status}' => ['legacy.perm:transfer'],
        'GET transfer/{id}' => ['legacy.perm:transfer'],
        'POST _transfer/grid' => ['legacy.auth'],
        'POST _transfer/save' => ['legacy.perm:transfer'],
        'POST _transfer/cancel' => ['legacy.perm:transfer'],
        'GET admin/refill' => ['legacy.perm:admin_refill'],
        'GET admin/refill/create' => ['legacy.perm:admin_refill'],
        'GET admin/refill/status/{status}' => ['legacy.perm:admin_refill'],
        'GET admin/refill/{id}' => ['legacy.perm:admin_refill'],
        'POST _admin/_refill/grid' => ['legacy.auth'],
        'POST _admin/_refill/save' => ['legacy.perm:admin_refill'],
        'POST _admin/_refill/save_approved' => ['legacy.perm:admin_refill'],
        'POST _admin/_refill/cancel' => ['legacy.perm:admin_refill'],
        'GET _admin/_refill/email_approved' => ['legacy.perm:admin_refill'],
        'GET _admin/_refill/email_cancel' => ['legacy.perm:admin_refill'],
        'GET admin/transfer' => ['legacy.perm:admin_transfer'],
        'GET admin/transfer/status/{status}' => ['legacy.perm:admin_transfer'],
        'GET admin/transfer/create' => ['legacy.perm:admin_transfer', 'legacy.perm:transfer'],
        'GET admin/transfer/{id}' => ['legacy.perm:admin_transfer', 'legacy.perm:transfer'],
        'POST _admin/_transfer/grid' => ['legacy.auth'],
        'POST _admin/_transfer/save' => ['legacy.perm:admin_transfer'],
        'POST _admin/_transfer/cancel' => ['legacy.perm:admin_transfer'],
        'POST _admin/_transfer/rollback' => ['legacy.perm:admin_transfer'],
        'GET _admin/_transfer/email_success' => ['legacy.perm:admin_transfer'],
        'GET _admin/_transfer/email_cancel' => ['legacy.perm:admin_transfer'],
        'GET admin/withdrawcash' => ['legacy.perm:admin_withdrawcash'],
        'GET admin/withdrawcash/status/{status}' => ['legacy.perm:admin_withdrawcash'],
        'GET admin/withdrawcash/{id}' => ['legacy.perm:admin_withdrawcash'],
        'POST _admin/_withdrawcash/grid' => ['legacy.auth'],
        'POST _admin/_withdrawcash/save_approved' => ['legacy.perm:admin_withdrawcash'],
        'POST _admin/_withdrawcash/cancel' => ['legacy.perm:admin_withdrawcash'],
    ];

    public function test_every_legacy_url_has_a_route_with_the_legacy_permission(): void
    {
        $found = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $found[$m.' '.$r->uri()] = $r;
            }
        }
        foreach (self::EXPECTED as $key => $mws) {
            $this->assertArrayHasKey($key, $found, "ไม่มี route $key");
            foreach ($mws as $mw) {
                $this->assertContains($mw, $found[$key]->gatherMiddleware(), "$key ต้องมี $mw");
            }
            if (str_starts_with($key, 'POST ') && ! str_contains($key, 'email_')) {
                $this->assertContains('legacy.ajax', $found[$key]->gatherMiddleware(), "$key เดิมผ่าน system_check_ajax");
            }
        }
    }

    public function test_page_routes_set_legacy_page(): void
    {
        $pages = [
            'refill' => 'refill', 'refill/create' => 'refill_detail', 'refill/status/{status}' => 'refill', 'refill/{id}' => 'refill_detail',
            'transfer' => 'transfer', 'transfer/create' => 'transfer_detail', 'transfer/{id}' => 'transfer_detail',
            'admin/refill' => 'admin_refill', 'admin/refill/{id}' => 'admin_refill_detail',
            'admin/transfer/status/{status}' => 'admin_transfer', 'admin/transfer/{id}' => 'admin_transfer_detail',
            'admin/withdrawcash' => 'admin_withdrawcash', 'admin/withdrawcash/{id}' => 'admin_withdrawcash_detail',
        ];
        $seen = 0;
        foreach (Route::getRoutes() as $r) {
            if (isset($pages[$r->uri()]) && in_array('GET', $r->methods(), true)) {
                $this->assertSame($pages[$r->uri()], $r->defaults['legacy_page'] ?? null, $r->uri());
                $seen++;
            }
        }
        $this->assertSame(count($pages), $seen);
    }

    public function test_legacy_paging_offset(): void
    {
        $this->assertSame(0, Lookups::offset('1'));
        $this->assertSame(20, Lookups::offset('3'));
        $this->assertSame(0, Lookups::offset('1abc'));
        // LIMIT -10,10 / LIMIT 5,10 ของ "1.5" = SQL error ในเว็บเดิม → ไม่มีแถว
        $this->assertNull(Lookups::offset(''));
        $this->assertNull(Lookups::offset('0'));
        $this->assertNull(Lookups::offset('1.55'));
    }

    public function test_php74_comparisons_used_by_forms(): void
    {
        // _refill/form.php: if($id != "") กับ (int) id — 0 = ฟอร์มสร้างใหม่
        $this->assertFalse(Php74::neq(0, ''));
        $this->assertTrue(Php74::neq(5, ''));
        // save.php: if($id == "") — ไม่มี key / '' = สร้าง, '0' = ไม่ทำอะไร
        $this->assertTrue(Php74::eq(null, ''));
        $this->assertFalse(Php74::eq('0', ''));
        // grid: status filter
        $this->assertTrue(Php74::eq('-2', '-2'));
        $this->assertFalse(Php74::eq(null, '-2'));
        // transfer save: "" * rate = 0, "1000" * "5.20"
        $this->assertSame('0', Php74::str(Php74::num('') * Php74::num('5.20')));
        $this->assertSame('5200', Php74::str(Php74::num('1000') * Php74::num('5.20')));
        $this->assertSame('51.5', Php74::str(Php74::num('10.3') * Php74::num('5')));
    }

    public function test_temp_upload_names_are_whitelisted(): void
    {
        $this->assertTrue(TempUpload::safeName('1700000000_abcdEF12.jpg'));
        $this->assertFalse(TempUpload::safeName('../../etc/passwd'));
        $this->assertFalse(TempUpload::safeName('a"><script>.png'));
        $this->assertFalse(TempUpload::safeName(''));
    }
}
