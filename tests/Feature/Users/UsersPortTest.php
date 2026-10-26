<?php

namespace Tests\Feature\Users;

use App\Domain\Users\Php74;
use App\Domain\Users\RateHistory;
use App\Domain\Users\SignupGuard;
use App\Http\Controllers\Users\Admin\AdminUserController;
use App\Http\Controllers\Users\Concerns\ReadsLegacyInput;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม users: route ครบตาม docs/url-map.md + ตรรกะที่ไม่แตะ DB (ตัวแยก id ของ delete, paging, ประวัติราคา, วงเครือข่ายตอนสมัคร)
 */
class UsersPortTest extends TestCase
{
    /** method uri => middleware ที่ต้องมี (อย่างน้อย) */
    private const ROUTES = [
        'GET register' => [], 'GET lostpassword' => [], 'GET lost_password' => [],
        'POST _register/save' => ['legacy.ajax'], 'POST _register/lost_password' => ['legacy.ajax'],
        'GET system_check_username' => ['legacy.ajax'],
        'GET profile' => ['legacy.auth'],
        'POST _profile/load_user_type_detail' => ['legacy.auth', 'legacy.ajax'], 'POST _profile/load_role_detail' => ['legacy.auth', 'legacy.ajax'],
        'POST _profile/pin' => ['legacy.auth', 'legacy.ajax'], 'POST _profile/save' => ['legacy.auth', 'legacy.ajax'],
        'GET admin/user' => ['legacy.auth'], 'GET admin/user/status/{status}' => ['legacy.auth'],
        'GET admin/user/create' => ['legacy.perm:admin_user,admin_user_view'], 'GET admin/user/{id}' => ['legacy.perm:admin_user,admin_user_view'],
        'POST _admin/_user/grid' => ['legacy.auth', 'legacy.ajax'], 'POST _admin/_user/save' => ['legacy.auth', 'legacy.ajax'],
        'POST _admin/_user/load_user_type_detail' => ['legacy.ajax'], 'POST _admin/_user/load_role_detail' => ['legacy.ajax'],
        'POST _admin/_user/rate_history' => ['legacy.ajax'],
        'POST _admin/_user/btn_viewpassword' => ['legacy.ajax'], 'POST _admin/_user/btn_resetpassword' => ['legacy.ajax'],
        'POST _admin/_user/btn_pinreset' => ['legacy.ajax'], 'POST _admin/_user/btn_unbanned' => ['legacy.ajax'],
        'POST _admin/_user/save_approved' => ['legacy.perm:admin_user', 'legacy.ajax'], 'POST _admin/_user/cancel' => ['legacy.perm:admin_user', 'legacy.ajax'],
        'POST _admin/_user/delete' => ['legacy.perm:admin_user', 'legacy.ajax'],
        'GET admin/user_type' => ['legacy.auth'], 'GET admin/user_type/create' => ['legacy.perm:admin_user_type'],
        'GET admin/user_type/{id}' => ['legacy.perm:admin_user_type'], 'POST _admin/_user_type/grid' => ['legacy.ajax'],
        'POST _admin/_user_type/save' => ['legacy.perm:admin_user_type', 'legacy.ajax'], 'POST _admin/_user_type/delete' => ['legacy.perm:admin_user_type', 'legacy.ajax'],
        'GET admin/role' => ['legacy.auth'], 'GET admin/role/create' => ['legacy.perm:admin_role'],
        'GET admin/role/{id}' => ['legacy.perm:admin_role'], 'POST _admin/_role/grid' => ['legacy.ajax'],
        'POST _admin/_role/save' => ['legacy.perm:admin_role', 'legacy.ajax'], 'POST _admin/_role/delete' => ['legacy.perm:admin_role', 'legacy.ajax'],
    ];

    public function test_every_legacy_url_of_the_group_has_a_route_with_its_middleware(): void
    {
        $have = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $have[$m.' '.$r->uri()] = $r->gatherMiddleware();
            }
        }
        foreach (self::ROUTES as $key => $mw) {
            $this->assertArrayHasKey($key, $have, $key);
            foreach ($mw as $m) {
                $this->assertContains($m, $have[$key], $key.' ต้องมี '.$m);
            }
            $this->assertContains('legacy', $have[$key], $key.' ต้องอยู่ใต้ group legacy (CSRF)');
        }
    }

    public function test_delete_ids_reject_sql_fragments(): void
    {
        $this->assertSame([5], AdminUserController::deleteIds('5', ''));
        $this->assertSame([1, 2, 3], AdminUserController::deleteIds('', ',1,2,3'));
        $this->assertSame([7], AdminUserController::deleteIds("'7'", ''));
        $this->assertNull(AdminUserController::deleteIds('910719) OR (1=1', ''));
        $this->assertNull(AdminUserController::deleteIds('1', ',1) OR (1=1'));
        $this->assertNull(AdminUserController::deleteIds('', ''));
        $this->assertNull(AdminUserController::deleteIds('1 OR 1', ''));
    }

    public function test_page_offset_matches_make_paging(): void
    {
        $o = new class { use ReadsLegacyInput; public function off(string $p): ?int { return $this->pageOffset($p); } };
        $this->assertSame(0, $o->off('1'));
        $this->assertSame(10, $o->off('2'));
        $this->assertSame(10, $o->off('2abc'));
        $this->assertNull($o->off(''));
        $this->assertNull($o->off('abc'));
        $this->assertNull($o->off('0'));
        $this->assertSame(5, $o->off('1.5'));
        $this->assertNull($o->off('1.25'));
        $this->assertSame('12script', AdminUserController::pageJs("1'2</script>"));
        $this->assertSame('1.5', AdminUserController::pageJs('1.5'));
        $this->assertSame('2abc', AdminUserController::pageJs('2abc'));
        $this->assertSame('1alert1', AdminUserController::pageJs("1');alert(1);//"));
    }

    public function test_rate_history_diff(): void
    {
        $a = ['type' => 5, 'option' => 1, 'group' => 'G2', 'rates' => ['A|0' => [10.0, 20.0], 'B|1' => [1.5, 2.0]]];
        $this->assertSame(['สร้างลูกค้าใหม่ — กลุ่ม: G2 · ประเภท: ตั้งเอง'], RateHistory::diff(null, $a));
        $b = ['type' => 4, 'option' => 0, 'group' => '', 'rates' => ['A|0' => [10.0, 20.00001], 'C|0' => [1000.0, 3.0]]];
        $this->assertSame([
            'กลุ่ม: - → G2',
            'ประเภท: ใช้จากค่าเริ่มต้น → ตั้งเอง',
            'B (เรือ): - → 1.5 / 2  (กก. / คิว)',
            'C (รถ): 1,000 / 3 → -  (กก. / คิว)',
        ], RateHistory::diff($b, $a));
        $this->assertSame([], RateHistory::diff($a, $a));
    }

    public function test_signup_network(): void
    {
        $this->assertSame('203.0.113.0/24', SignupGuard::net('203.0.113.77'));
        $this->assertSame('2001:db8:1:2::/64', SignupGuard::net('2001:db8:1:2:3:4:5:6'));
    }

    /** SR: แม่แบบอีเมล + ผู้ส่ง = email_header/center/footer.php และ sendmail() ของ SR (ไม่มีข้อความ jl เหลือ) */
    public function test_mail_template_and_sender_are_sr(): void
    {
        $this->assertSame('no-reply@pack-cargo.net', config('legacy.mail.from_address'));
        $this->assertSame('PACK-Cargo', config('legacy.mail.from_name'));
        $html = \App\Domain\Users\UsersMailer::wrap('S', '<p>B</p>');
        $this->assertSame('<div style="font-family: arial,sans-serif;font-size:13px;max-width: 560px"><strong>S</strong><div style="text-indent: 30px;"><p>B</p>'
            ."</div>\r\nขอแสดงความนับถือ<br />\r\nPACK-Cargo\r\n</div>", $html);
        $src = (string) file_get_contents(app_path('Domain/Users/UsersMailer.php'));
        foreach (['JLcargo', 'jlcargoth', '991b1e', 'target='] as $jl) {
            $this->assertStringNotContainsString($jl, $src, $jl);
        }
    }

    /** SR ไม่มีคอลัมน์ user.commission / ช่องรหัสผู้แนะนำ — ห้ามโค้ดกลุ่มนี้อ่าน/เขียน */
    public function test_no_commission_or_jl_branding_left(): void
    {
        $files = array_merge(
            glob(app_path('Http/Controllers/Users/{,*/}*.php'), GLOB_BRACE) ?: [],
            glob(resource_path('views/users/{,*/,*/*/}*.blade.php'), GLOB_BRACE) ?: [],
        );
        $this->assertNotEmpty($files);
        foreach ($files as $f) {
            $src = (string) file_get_contents($f);
            foreach (["'commission'", 'comcode', 'JLCARGO', 'JLCargo'] /* target=_blank กลับมาตามเว็บเดิม (2026-10-26) */ as $bad) {
                $this->assertStringNotContainsString($bad, $src, basename($f).': '.$bad);
            }
        }
    }

    public function test_php74_comparisons_used_by_forms(): void
    {
        $this->assertTrue(Php74::eq(0, ''));        // _admin/_user/form.php: $id == "" เมื่อ id = 0 (สร้างใหม่)
        $this->assertFalse(Php74::eq(5, ''));
        $this->assertTrue(Php74::eq(null, 0));      // option_price NULL == 0
        $this->assertTrue(Php74::eq('0123', '123')); // PIN ตัวเลข
        $this->assertTrue(Php74::eq('abc', 0));     // grid: approved == 0
        $this->assertSame('a&quot;b\'c&lt;', Php74::h('a"b\'c<'));
    }
}
