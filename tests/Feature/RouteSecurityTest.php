<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กันลืมใส่ middleware ตอนพอร์ตโมดูล:
 *  - ทุก route (ยกเว้นรายการ PUBLIC) ต้องมี legacy.auth / legacy.perm / legacy.own
 *  - ทุก route ที่เปลี่ยนข้อมูล (ไม่ใช่ GET/HEAD) ต้องอยู่ใต้ group "legacy" (LegacyCsrf) หรือ "web" (CSRF token)
 * route สาธารณะใหม่ (เช่น register / lostpassword) ต้องเพิ่มใน PUBLIC พร้อมเหตุผลใน PR
 */
class RouteSecurityTest extends TestCase
{
    /** uri => เหตุผล */
    private const PUBLIC = [
        '/' => 'redirect ไป login/dashboard',
        'login' => 'หน้า login',
        'system_check_login' => 'รับ username/password',
        'system_checkuser' => 'logout / ตรวจ session (ตอบ null เมื่อไม่ได้ล็อกอิน)',
        'system_change_language' => 'ตั้ง cookie ภาษา',
        'up' => 'health check ของ Laravel',
        'dev/whoami' => 'dev เท่านั้น: บอกว่า session นี้เป็นใคร',
        // shop: API ของ crawler/cron (เดิมไม่ต้องล็อกอิน ตรวจ token) — token อยู่ใน .env (config legacy.shop.*), ไม่ตั้ง = ปิด
        '_api/get_order_number_v2' => 'token API: รายการเลขออเดอร์ที่ยังไม่มี tracking',
        '_api/get_order_number_v3' => 'token API: รายการเลขออเดอร์ + URL',
        '_api/submit_order_number_v2' => 'token API: crawler ส่งเลข tracking กลับมา',
        '_api/unban' => 'token API: cron ปลดแบน IP',
        // กลุ่ม users: หน้าสาธารณะของเว็บเดิม (ยังไม่มีบัญชี / ลืมรหัสผ่าน)
        'register' => 'หน้าสมัครสมาชิก (register.php)',
        '_register/save' => 'ส่งฟอร์มสมัครสมาชิก (ajax, CSRF ผ่าน group legacy)',
        'lostpassword' => 'หน้าลืมรหัสผ่าน (lost_password.php)',
        'lost_password' => 'หน้าลืมรหัสผ่าน (path ไฟล์เดิม)',
        '_register/lost_password' => 'ขอรหัสผ่านใหม่ทางอีเมล (ajax)',
        'system_check_username' => 'validationEngine ตรวจ username ซ้ำตอนสมัคร',
        'password' => 'password.php เดิม (สคริปต์ย้ายรหัส ตาราง user_copy ไม่มีแล้ว) ตอบว่างเสมอ',
        'index_close' => 'settings: หน้าปิดปรับปรุง (ข้อความคงที่ เดิมสาธารณะ)',
        'get_resource' => 'settings: ข้อความภาษาของระบบ (lang/*, เดิมสาธารณะ)',
        'resource' => 'settings (SR resource.php): ข้อความภาษาไทยของระบบ (lang/th, เดิมสาธารณะ)',
    ];

    private const AUTH = ['legacy.auth', 'legacy.perm', 'legacy.own'];

    public function test_every_non_public_route_requires_login(): void
    {
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            if (array_key_exists($route->uri(), self::PUBLIC)) {
                continue;
            }
            $mw = $route->gatherMiddleware();
            $ok = false;
            foreach ($mw as $m) {
                if (is_string($m) && in_array(explode(':', $m, 2)[0], self::AUTH, true)) {
                    $ok = true;
                }
            }
            if (! $ok) {
                $missing[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }
        $this->assertSame([], $missing, 'route ที่ไม่มี legacy.auth/legacy.perm/legacy.own');
    }

    public function test_every_state_changing_route_has_csrf_protection(): void
    {
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            if (array_diff($route->methods(), ['GET', 'HEAD']) === []) {
                continue;
            }
            $mw = $route->gatherMiddleware();
            if (! in_array('legacy', $mw, true) && ! in_array('web', $mw, true)) {
                $missing[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }
        $this->assertSame([], $missing, 'route เปลี่ยนข้อมูลที่ไม่มี CSRF');
    }
}
