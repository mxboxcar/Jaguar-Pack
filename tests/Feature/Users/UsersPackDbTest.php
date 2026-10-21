<?php

namespace Tests\Feature\Users;

use App\Http\Controllers\Users\Admin\AdminUserController;
use App\Http\Controllers\Users\ProfileController;
use App\Http\Controllers\Users\RegisterController;
use App\Models\User;
use App\Support\Legacy\LegacyCrypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * พฤติกรรมเฉพาะของ SR ในกลุ่ม users กับ admin_pack_bench จริง (JL_BENCH_DB_TESTS=1, rollback ทุก test)
 *   - สมัคร: รหัส SU####, lineid/wechatid, อีเมลซ้ำไม่ได้, ไม่มี commission
 *   - โปรไฟล์: ต้องยืนยัน PIN ก่อนบันทึก และบันทึกอีเมล/โทร/WeChat
 *   - แอดมิน: code จากฟอร์ม, รหัสผ่านเปลี่ยนได้เฉพาะ role 1, save_approved ตอบ $('form').submit(), type 4 = 500
 *   - grid: ไม่แสดงผู้ใช้ที่ยกเลิก, ตัวกรอง 99 (ตั้งเอง), คอลัมน์ Line ID + ยอดเงิน
 */
class UsersSrDbTest extends BenchDbTestCase
{
    private function asRole(int $role): User
    {
        $u = $this->zzUser($role);
        $this->actAs($u);

        return $u;
    }

    public function test_register_writes_sr_code_and_social_ids(): void
    {
        $tag = 'zz_sr_'.bin2hex(random_bytes(3));
        $out = $this->act(RegisterController::class, 'save', [
            'username' => $tag, 'email' => $tag.'@example.invalid', 'name' => 'zz', 'address' => 'zz addr', 'phone' => '0812345678',
            'phone2' => '0812345679', 'user_type' => '2', 'lineid' => 'zz_line', 'wechatid' => 'zz_wc', 'upload_file' => '',
        ]);
        $this->assertStringStartsWith('SuccessBox(', $out);
        $row = DB::table('user')->where('username', $tag)->first();
        $this->assertNotNull($row);
        $this->assertMatchesRegularExpression('/^SU\d{4,}$/', $row->code);
        $this->assertSame(['zz_line', 'zz_wc', '0', '0'], [$row->lineid, $row->wechatid, (string) $row->role, (string) $row->is_approved]);
        $this->assertSame(1, DB::table('crawl_guard_signup')->where('user', $row->id)->count());

        // อีเมลซ้ำ (username ใหม่) → ข้อความของ SR
        $dup = $this->act(RegisterController::class, 'save', [
            'username' => $tag.'b', 'email' => $tag.'@example.invalid', 'user_type' => '2',
        ]);
        $this->assertSame("ErrorBox('อีเมล์นี้มีในระบบแล้ว', 'CloseLoading()'); ", $dup);
        $this->assertSame("ErrorBox('ผู้ใช้งานนี้มีในระบบแล้ว', 'CloseLoading()'); ", $this->act(RegisterController::class, 'save', ['username' => $tag]));
    }

    public function test_profile_save_requires_payment_pin(): void
    {
        $u = $this->asRole(8);
        $post = ['name' => 'zz_n', 'email' => 'zz_e@example.invalid', 'phone' => '01', 'phone2' => '02', 'lineid' => 'zz_l', 'wechatid' => 'zz_w', 'address' => 'zz_a', 'password' => ''];

        $this->assertStringContainsString('method : "new"', $this->act(ProfileController::class, 'save', $post));
        DB::table('user')->where('id', $u->id)->update(['pin' => '135790']);
        $u->refresh();
        $this->actAs($u);
        $this->assertStringContainsString('method : "verified"', $this->act(ProfileController::class, 'save', $post));
        $this->assertSame('zz_mm', substr((string) DB::table('user')->where('id', $u->id)->value('name'), 0, 5));

        $ok = $this->act(ProfileController::class, 'save', $post + ['paymentpin' => '135790']);
        $this->assertStringStartsWith('SuccessBox(', $ok);
        $row = DB::table('user')->where('id', $u->id)->first();
        $this->assertSame(['zz_n', 'zz_e@example.invalid', '01', '02', 'zz_l', 'zz_w', 'zz_a'], [$row->name, $row->email, $row->phone, $row->phone2, $row->lineid, $row->wechatid, $row->address]);
    }

    public function test_admin_save_uses_form_code_and_only_role_1_changes_password(): void
    {
        $this->asRole(1);
        $base = ['code' => 'zz_code_a', 'name' => 'zz_admin_made', 'email' => 'zz_am@example.invalid', 'phone' => '1', 'phone2' => '2',
            'lineid' => 'zz_l', 'wechatid' => 'zz_w', 'address' => 'zz', 'username' => 'zz_am_'.bin2hex(random_bytes(3)),
            'password' => 'zzPw1', 'role' => '8', 'user_type' => '2', 'option_price' => '0', 'diff' => '0'];
        $out = $this->act(AdminUserController::class, 'save', ['id' => ''] + $base);
        $this->assertStringStartsWith('SuccessBox(', $out);
        $row = DB::table('user')->where('username', $base['username'])->first();
        $this->assertSame(['zz_code_a', 'zz_l', 'zz_w'], [$row->code, $row->lineid, $row->wechatid]);

        $this->act(AdminUserController::class, 'save', ['id' => (string) $row->id, 'password' => 'zzPw2', 'code' => 'zz_code_b'] + $base);
        $this->assertSame('zzPw2', LegacyCrypt::decrypt(DB::table('user')->where('id', $row->id)->value('password')));

        // role อื่นที่มี admin_user (bench: role 12) บันทึกได้ แต่รหัสผ่านไม่เปลี่ยน
        $holder = DB::table('role_on_detail as o')->join('role_detail as d', 'd.id', '=', 'o.role_detail_id')
            ->where('d.role_detail_name', 'admin_user')->where('o.permission', 1)->where('o.role_id', '<>', 1)->value('o.role_id');
        if ($holder === null) {
            $this->markTestIncomplete('bench ไม่มี role อื่นที่ถือ admin_user');
        }
        $this->asRole((int) $holder);
        $this->act(AdminUserController::class, 'save', ['id' => (string) $row->id, 'password' => 'zzPw3', 'name' => 'zz_by_other'] + $base);
        $this->assertSame('zzPw2', LegacyCrypt::decrypt(DB::table('user')->where('id', $row->id)->value('password')));
        $this->assertSame('zz_by_other', DB::table('user')->where('id', $row->id)->value('name'));
    }

    public function test_save_approved_submits_form_and_type_4_fails_like_legacy(): void
    {
        $this->asRole(1);
        $c = $this->zzUser(8);
        DB::table('user')->where('id', $c->id)->update(['is_approved' => 0, 'type' => 2]);
        $this->assertSame("$('form').submit();", $this->act(AdminUserController::class, 'saveApproved', ['id' => (string) $c->id]));
        $this->assertSame('1', (string) DB::table('user')->where('id', $c->id)->value('is_approved'));

        DB::table('user')->where('id', $c->id)->update(['is_approved' => 0, 'type' => 4]);
        $req = \Illuminate\Http\Request::create('/test', 'POST', ['id' => (string) $c->id]);
        app()->instance('request', $req);
        $res = app(AdminUserController::class)->saveApproved($req);
        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame('1', (string) DB::table('user')->where('id', $c->id)->value('is_approved'));
    }

    public function test_grid_hides_cancelled_and_shows_balance(): void
    {
        $this->asRole(1);
        $c = $this->zzUser(8);
        DB::table('user')->where('id', $c->id)->update(['lineid' => 'zz_line_grid', 'type' => 99999]);
        $this->credit($c, 123.45);
        $html = $this->act(AdminUserController::class, 'grid', ['txtsearch' => 'zz_line_grid|-1|-1|-2', 'page' => '1']);
        $this->assertStringContainsString('zz_line_grid</td>', $html);
        $this->assertStringContainsString('<span class="currency">123.45</span>', $html);
        $this->assertStringNotContainsString('target=', $html);

        $this->assertStringContainsString('zz_line_grid</td>', $this->act(AdminUserController::class, 'grid', ['txtsearch' => 'zz_line_grid|-1|99|-2', 'page' => '1']));
        $this->assertStringNotContainsString('zz_line_grid</td>', $this->act(AdminUserController::class, 'grid', ['txtsearch' => 'zz_line_grid|-1|2|-2', 'page' => '1']));

        DB::table('user')->where('id', $c->id)->update(['is_approved' => -1]);
        $this->assertStringNotContainsString('zz_line_grid</td>', $this->act(AdminUserController::class, 'grid', ['txtsearch' => 'zz_line_grid|-1|-1|-1', 'page' => '1']));
    }
}
