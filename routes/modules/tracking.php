<?php

use App\Http\Controllers\Tracking\Admin\AdminLostPackageController;
use App\Http\Controllers\Tracking\Admin\AdminTrackingController;
use App\Http\Controllers\Tracking\Admin\CombineTrackController;
use App\Http\Controllers\Tracking\LostPackageController;
use App\Http\Controllers\Tracking\TrackingController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: tracking — _tracking, _admin/_tracking, _admin/_combine_track, _lost_package, _admin/_lost_package
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/tracking.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม; ไฟล์เดิมที่ไม่ตรวจสิทธิ์ใช้สิทธิ์ของหน้าแม่ (บันทึกในรายงาน)
| grid ajax ตรวจสิทธิ์ใน controller เพราะเดิมตอบว่างเมื่อไม่มีสิทธิ์ (ไม่ใช่ ErrorBox)
*/

// ---------- ลูกค้า: tracking ----------
Route::middleware('legacy.perm:tracking')->group(function () {
    Route::get('/tracking', [TrackingController::class, 'index'])->defaults('legacy_page', 'tracking');
    Route::get('/tracking/create', [TrackingController::class, 'form'])->defaults('legacy_page', 'tracking_detail');
    Route::get('/tracking/{id}', [TrackingController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'tracking_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_tracking/delete', [TrackingController::class, 'delete']);
        Route::post('/_tracking/save', [TrackingController::class, 'save'])->middleware('legacy.once');
    });
});
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_tracking/grid', [TrackingController::class, 'grid']);
    // เดิมไม่ตรวจสิทธิ์/ล็อกอิน: ต้องล็อกอิน และอ่าน/แก้ได้เฉพาะ tracking ของตัวเอง
    Route::post('/_tracking/load_pricing', [TrackingController::class, 'loadPricing']);
    Route::post('/_tracking/load_product_type', [TrackingController::class, 'loadProductType']);
    Route::post('/_tracking/save_quick_quote', [TrackingController::class, 'saveQuickQuote']);
});

// ---------- แอดมิน: tracking ----------
Route::middleware('legacy.perm:admin_tracking')->group(function () {
    Route::get('/admin/tracking', [AdminTrackingController::class, 'index'])->defaults('legacy_page', 'admin_tracking');
    Route::get('/admin/tracking/create', [AdminTrackingController::class, 'form'])->defaults('legacy_page', 'admin_tracking_detail');
    Route::get('/admin/tracking/status/{status}', [AdminTrackingController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_tracking');
    Route::get('/admin/tracking/{id}', [AdminTrackingController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_tracking_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_tracking/load_paging', [AdminTrackingController::class, 'loadPaging']);
        Route::post('/_admin/_tracking/delete', [AdminTrackingController::class, 'delete']);
        Route::post('/_admin/_tracking/save', [AdminTrackingController::class, 'save'])->middleware('legacy.once');
        // ไฟล์เดิม 3 ตัวนี้ไม่ตรวจสิทธิ์เลย → ใช้สิทธิ์ของหน้าแม่ (admin_tracking)
        Route::post('/_admin/_tracking/save_lock', [AdminTrackingController::class, 'saveLock']);
        Route::post('/_admin/_tracking/load_pricing', [AdminTrackingController::class, 'loadPricing']);
        Route::post('/_admin/_tracking/load_product_type', [AdminTrackingController::class, 'loadProductType']);
    });
});
Route::post('/_admin/_tracking/grid', [AdminTrackingController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: รวมรหัสพัสดุ ----------
// หน้า index เดิมไม่ตรวจสิทธิ์ (เมนูซ่อนไว้เท่านั้น) → ใช้ admin_combine_track เดียวกับ ajax
Route::middleware('legacy.perm:admin_combine_track')->group(function () {
    Route::get('/admin/combine_track', [CombineTrackController::class, 'index'])->defaults('legacy_page', 'admin_combine_track');
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_combine_track/load_tracking', [CombineTrackController::class, 'loadTracking']);
        Route::post('/_admin/_combine_track/save_combine', [CombineTrackController::class, 'saveCombine']);
    });
});

// ---------- ลูกค้า: แจ้งไม่ได้รับสินค้า ----------
Route::middleware('legacy.perm:lost_package')->group(function () {
    Route::get('/lost_package', [LostPackageController::class, 'index'])->defaults('legacy_page', 'lost_package');
    Route::get('/lost_package/create', [LostPackageController::class, 'create'])->defaults('legacy_page', 'lost_package_detail');
    // RewriteRule เดิม: lost_package/status/N → page=lost_package_detail&status=N (หน้า form ไม่มี id)
    Route::get('/lost_package/status/{status}', [LostPackageController::class, 'statusForm'])->whereNumber('status')->defaults('legacy_page', 'lost_package_detail');
    Route::get('/lost_package/{id}', [LostPackageController::class, 'show'])->whereNumber('id')->defaults('legacy_page', 'lost_package_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_lost_package/cancel', [LostPackageController::class, 'cancel']);
        Route::post('/_lost_package/load_order_number', [LostPackageController::class, 'loadOrderNumber']);
        Route::post('/_lost_package/load_upload', [LostPackageController::class, 'loadUpload']);
        Route::post('/_lost_package/save', [LostPackageController::class, 'save'])->middleware('legacy.once');
        // save_image เดิมไม่ตรวจสิทธิ์ → lost_package
        Route::post('/_lost_package/save_image', [LostPackageController::class, 'saveImage']);
    });
});
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_lost_package/grid', [LostPackageController::class, 'grid']);
    // upload.php เดิมตรวจแค่ล็อกอิน
    Route::post('/_lost_package/upload', [LostPackageController::class, 'upload']);
});

// ---------- แอดมิน: แจ้งไม่ได้รับสินค้า ----------
Route::middleware('legacy.perm:admin_lost_package')->group(function () {
    Route::get('/admin/lost_package', [AdminLostPackageController::class, 'index'])->defaults('legacy_page', 'admin_lost_package');
    Route::get('/admin/lost_package/create', [AdminLostPackageController::class, 'create'])->defaults('legacy_page', 'admin_lost_package_detail');
    Route::get('/admin/lost_package/status/{status}', [AdminLostPackageController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_lost_package');
    Route::get('/admin/lost_package/{id}', [AdminLostPackageController::class, 'show'])->whereNumber('id')->defaults('legacy_page', 'admin_lost_package_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_lost_package/cancel', [AdminLostPackageController::class, 'adminCancel']);
        Route::post('/_admin/_lost_package/rollback', [AdminLostPackageController::class, 'rollback']);
        Route::post('/_admin/_lost_package/save_approved', [AdminLostPackageController::class, 'saveApproved']);
    });
    // ไฟล์ include ของ cancel/save_approved ที่เดิมเปิดตรงได้ (ไม่ต้องล็อกอิน) — ที่นี่ต้องมีสิทธิ์ และไม่ส่งอะไร
    Route::match(['GET', 'POST'], '/_admin/_lost_package/email_approved', [AdminLostPackageController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_lost_package/email_cancel', [AdminLostPackageController::class, 'emailDirect']);
});
Route::post('/_admin/_lost_package/grid', [AdminLostPackageController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
