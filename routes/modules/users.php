<?php

use App\Http\Controllers\Users\Admin\AdminRoleController;
use App\Http\Controllers\Users\Admin\AdminUserController;
use App\Http\Controllers\Users\Admin\AdminUserTypeController;
use App\Http\Controllers\Users\ProfileController;
use App\Http\Controllers\Users\RegisterController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: users — _profile, _register, _admin/_user, _admin/_user_type, _admin/_role,
|               register.php, lost_password.php, password.php, system_check_username.php
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/users.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม
| - หน้า index (รายการ) เดิมพิมพ์หัวหน้าก่อนแล้วค่อยตรวจสิทธิ์ (ไม่มีสิทธิ์ = หัวหน้า + ErrorBox) → ตรวจใน view
| - grid / load_* เดิม exit() เงียบเมื่อไม่มีสิทธิ์ (ตอบว่าง) → ตรวจใน controller
| - save / delete / approve / cancel เดิมตอบ getPermissionAlert() → middleware legacy.perm (ข้อความเดียวกัน)
*/

// ---------- สาธารณะ (ยังไม่ล็อกอิน): สมัครสมาชิก / ลืมรหัสผ่าน ----------
Route::get('/register', [RegisterController::class, 'register']);
Route::get('/lostpassword', [RegisterController::class, 'lostPassword']);
Route::get('/lost_password', [RegisterController::class, 'lostPassword']);
// password.php = สคริปต์ย้ายรหัสผ่านครั้งเดียว อ่านตาราง user_copy ที่ไม่มีใน DB → ตอบว่างเสมอ
Route::get('/password', [RegisterController::class, 'password']);
Route::middleware('legacy.ajax')->group(function () {
    Route::post('/_register/save', [RegisterController::class, 'save']);
    Route::post('/_register/lost_password', [RegisterController::class, 'resetPassword']);
    // validationEngine ajax[checkusername] เรียกแบบ GET
    Route::get('/system_check_username', [RegisterController::class, 'checkUsername']);
});

// ---------- ลูกค้า: โปรไฟล์ ----------
Route::middleware('legacy.auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->defaults('legacy_page', 'profile');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_profile/load_user_type_detail', [ProfileController::class, 'loadUserTypeDetail']);
        Route::post('/_profile/load_role_detail', [ProfileController::class, 'loadRoleDetail']);
        Route::post('/_profile/pin', [ProfileController::class, 'pin']);
        // เดิมไม่ตรวจสิทธิ์ profile (แก้ได้เฉพาะแถวของตัวเองอยู่แล้ว)
        Route::post('/_profile/save', [ProfileController::class, 'save']);
    });
});

// ---------- แอดมิน: ผู้ใช้ ----------
Route::middleware('legacy.auth')->group(function () {
    // index เดิม: หัวหน้า + ตรวจ admin_user_view || admin_user ใน view
    Route::get('/admin/user', [AdminUserController::class, 'index'])->defaults('legacy_page', 'admin_user');
    Route::get('/admin/user/status/{status}', [AdminUserController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_user');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_user/grid', [AdminUserController::class, 'grid']);
        Route::post('/_admin/_user/load_user_type_detail', [AdminUserController::class, 'loadUserTypeDetail']);
        Route::post('/_admin/_user/load_role_detail', [AdminUserController::class, 'loadRoleDetail']);
        Route::post('/_admin/_user/rate_history', [AdminUserController::class, 'rateHistory']);
        Route::post('/_admin/_user/btn_viewpassword', [AdminUserController::class, 'btnViewPassword']);
        Route::post('/_admin/_user/btn_resetpassword', [AdminUserController::class, 'btnResetPassword']);
        Route::post('/_admin/_user/btn_pinreset', [AdminUserController::class, 'btnPinReset']);
        Route::post('/_admin/_user/btn_unbanned', [AdminUserController::class, 'btnUnbanned']);
        // save เดิมตรวจรหัสผ่านมี & ก่อนตรวจสิทธิ์ → ตรวจสิทธิ์ใน controller ตามลำดับเดิม
        Route::post('/_admin/_user/save', [AdminUserController::class, 'save'])->middleware('legacy.once');
    });
});
Route::middleware('legacy.perm:admin_user,admin_user_view')->group(function () {
    Route::get('/admin/user/create', [AdminUserController::class, 'form'])->defaults('legacy_page', 'admin_user_detail');
    Route::get('/admin/user/{id}', [AdminUserController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_user_detail');
});
Route::middleware(['legacy.perm:admin_user', 'legacy.ajax'])->group(function () {
    Route::post('/_admin/_user/save_approved', [AdminUserController::class, 'saveApproved']);
    Route::post('/_admin/_user/cancel', [AdminUserController::class, 'cancel']);
    Route::post('/_admin/_user/delete', [AdminUserController::class, 'delete']);
});

// ---------- แอดมิน: ประเภทผู้ใช้ (กลุ่มราคา) ----------
Route::middleware('legacy.auth')->group(function () {
    Route::get('/admin/user_type', [AdminUserTypeController::class, 'index'])->defaults('legacy_page', 'admin_user_type');
    Route::post('/_admin/_user_type/grid', [AdminUserTypeController::class, 'grid'])->middleware('legacy.ajax');
});
Route::middleware('legacy.perm:admin_user_type')->group(function () {
    Route::get('/admin/user_type/create', [AdminUserTypeController::class, 'form'])->defaults('legacy_page', 'admin_user_type_detail');
    Route::get('/admin/user_type/{id}', [AdminUserTypeController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_user_type_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_user_type/save', [AdminUserTypeController::class, 'save'])->middleware('legacy.once');
        Route::post('/_admin/_user_type/delete', [AdminUserTypeController::class, 'delete']);
    });
});

// ---------- แอดมิน: บทบาท / สิทธิ์ ----------
Route::middleware('legacy.auth')->group(function () {
    Route::get('/admin/role', [AdminRoleController::class, 'index'])->defaults('legacy_page', 'admin_role');
    Route::post('/_admin/_role/grid', [AdminRoleController::class, 'grid'])->middleware('legacy.ajax');
});
Route::middleware('legacy.perm:admin_role')->group(function () {
    Route::get('/admin/role/create', [AdminRoleController::class, 'form'])->defaults('legacy_page', 'admin_role_detail');
    Route::get('/admin/role/{id}', [AdminRoleController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_role_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_role/save', [AdminRoleController::class, 'save'])->middleware('legacy.once');
        Route::post('/_admin/_role/delete', [AdminRoleController::class, 'delete']);
    });
});
