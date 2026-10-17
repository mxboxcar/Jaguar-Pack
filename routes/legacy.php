<?php

use App\Http\Controllers\Auth\LegacyLoginController;
use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\Route;

/*
| routes ของฐานร่วม: login/logout/ภาษา (ใช้ session เดียวกับเว็บเดิม)
| โหลดจาก bootstrap/app.php ใต้ middleware group "legacy" — ห้ามย้ายไป web.php (CSRF token/StartSession ของ Laravel จะตีกลับ 419)
*/

// index.php เดิม: ไม่มี page = แสดงหน้า dashboard ที่ / เลย (ไม่ redirect) — กลุ่ม reports ตั้งชื่อ route หน้า dashboard ว่า "page.dashboard"
Route::get('/', function () {
    if (Legacy::userId() === null) {
        return redirect()->to(Legacy::href('login'));
    }

    return Route::has('page.dashboard')
        ? app()->call(Route::getRoutes()->getByName('page.dashboard')->getAction('uses'))
        : redirect()->to(Legacy::href('dashboard'));
});

Route::get('/login', [LegacyLoginController::class, 'show'])->name('login');
Route::post('/system_check_login', [LegacyLoginController::class, 'check'])->middleware('legacy.ajax');
// system_checkuser.php เดิมไม่ตรวจ ajax (index.php/login.php require ไฟล์นี้ทุกครั้ง)
Route::post('/system_checkuser', [LegacyLoginController::class, 'checkUser']);
Route::post('/system_change_language', [LegacyLoginController::class, 'changeLanguage'])->middleware('legacy.ajax');

if (! app()->isProduction()) {
    // ตรวจ login ร่วม: ใครล็อกอินอยู่ (ไม่คืนข้อมูลลับ)
    Route::get('/dev/whoami', function () {
        $u = Legacy::user();

        return response()->json([
            'id' => $u?->id, 'username' => $u?->username, 'role' => $u?->role,
            'permissions' => $u ? array_keys(array_filter(app(\App\Support\Legacy\Permissions::class)->all())) : [],
        ]);
    });
    // ตัวอย่าง layout (เทียบหน้าตากับ index.php เดิม)
    Route::view('/dev/layout', 'legacy.dev-layout')->middleware('legacy.auth')->defaults('legacy_page', 'dashboard');
    // ตัวอย่างหน้าที่ต้องมีสิทธิ์แอดมิน
    Route::view('/dev/admin-only', 'legacy.dev-layout')->middleware('legacy.perm:admin_setting');

    // ต้นแบบ tracking + ตัววัดผลเทียบ _tracking/grid.php เดิม — เดิมเปิดโดยไม่ต้องล็อกอิน (ใครก็ดูข้อมูลลูกค้าคนไหนก็ได้
    // ผ่าน 127.0.0.1 เช่น SSRF จากเว็บอื่นในเครื่อง) → ต้องล็อกอิน และดูได้เฉพาะ {userId} ของตัวเอง หรือมีสิทธิ์ admin_tracking
    Route::middleware('legacy.own:userId,admin_tracking')->group(function () {
        Route::get('/dev/tracking/{userId}', [\App\Http\Controllers\TrackingController::class, 'index'])
            ->whereNumber('userId')->name('dev.tracking');
        Route::get('/dev/tracking-legacy/{userId}', [\App\Http\Controllers\LegacyTrackingBenchController::class, 'index'])
            ->whereNumber('userId')->name('dev.tracking-legacy');
    });
}
