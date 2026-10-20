<?php

use App\Http\Controllers\Points\Admin\AdminPointController;
use App\Http\Controllers\Points\Admin\AdminShopCashbackController;
use App\Http\Controllers\Points\Admin\AdminWithdrawPointController;
use App\Http\Controllers\Points\PointController;
use App\Http\Controllers\Points\ShopCashbackController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: points — _point, _admin/_point, _admin/_withdrawpoint, _shop_cashback, _admin/_shop_cashback
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/points.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม
|   grid / load_withdraw / save_withdraw ตรวจสิทธิ์ใน controller เพราะเดิม exit() เงียบๆ (ไม่ใช่ ErrorBox)
|   ajax อื่นเดิมตอบ getPermissionAlert() = สิ่งที่ legacy.perm ตอบให้อยู่แล้ว
| SR: admin_point / admin_withdrawpoint มีเฉพาะ role 1 ใน role_on_detail — ใช้ตามที่ DB ถือ (ไม่มี fallback ใน SR)
*/

// ---------- ลูกค้า: แต้ม ----------
Route::get('/point', [PointController::class, 'index'])->middleware('legacy.perm:point')->defaults('legacy_page', 'point');
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_point/grid', [PointController::class, 'grid']);
    Route::post('/_point/load_withdraw', [PointController::class, 'loadWithdraw']);
    Route::post('/_point/save_withdraw', [PointController::class, 'saveWithdraw'])->middleware('legacy.once');
});

// ---------- แอดมิน: แต้ม ----------
Route::get('/admin/point', [AdminPointController::class, 'index'])->middleware('legacy.perm:admin_point')->defaults('legacy_page', 'admin_point');
Route::post('/_admin/_point/grid', [AdminPointController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: คำขอแลกแต้ม ----------
Route::middleware('legacy.perm:admin_withdrawpoint')->group(function () {
    Route::get('/admin/withdrawpoint', [AdminWithdrawPointController::class, 'index'])->defaults('legacy_page', 'admin_withdrawpoint');
    Route::get('/admin/withdrawpoint/status/{status}', [AdminWithdrawPointController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_withdrawpoint');
    Route::get('/admin/withdrawpoint/{id}', [AdminWithdrawPointController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_withdrawpoint_detail');
});
Route::post('/_admin/_withdrawpoint/grid', [AdminWithdrawPointController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
Route::middleware(['legacy.ajax', 'legacy.perm:admin_withdrawpoint'])->group(function () {
    Route::post('/_admin/_withdrawpoint/cancel', [AdminWithdrawPointController::class, 'cancel']);
    Route::post('/_admin/_withdrawpoint/save_approved', [AdminWithdrawPointController::class, 'saveApproved']);
});

// ---------- ลูกค้า: แจ้งร้านค้าคืนเงิน ----------
Route::middleware('legacy.perm:shop_cashback')->group(function () {
    Route::get('/shop_cashback', [ShopCashbackController::class, 'index'])->defaults('legacy_page', 'shop_cashback');
    Route::get('/shop_cashback/create', [ShopCashbackController::class, 'form'])->defaults('legacy_page', 'shop_cashback_detail');
    Route::get('/shop_cashback/{id}', [ShopCashbackController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'shop_cashback_detail');
});
Route::post('/_shop_cashback/grid', [ShopCashbackController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
Route::middleware(['legacy.ajax', 'legacy.perm:shop_cashback'])->group(function () {
    Route::post('/_shop_cashback/load_calculator', [ShopCashbackController::class, 'loadCalculator']);
    Route::post('/_shop_cashback/load_new_line', [ShopCashbackController::class, 'loadNewLine']);
    Route::post('/_shop_cashback/save', [ShopCashbackController::class, 'save'])->middleware('legacy.once');
    Route::post('/_shop_cashback/save_remark', [ShopCashbackController::class, 'saveRemark']);
});

// ---------- แอดมิน: แจ้งร้านค้าคืนเงิน ----------
Route::middleware('legacy.perm:admin_shop_cashback')->group(function () {
    Route::get('/admin/shop_cashback', [AdminShopCashbackController::class, 'index'])->defaults('legacy_page', 'admin_shop_cashback');
    Route::get('/admin/shop_cashback/status/{status}', [AdminShopCashbackController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_shop_cashback');
    Route::get('/admin/shop_cashback/create', [AdminShopCashbackController::class, 'form'])->defaults('legacy_page', 'admin_shop_cashback_detail');
    Route::get('/admin/shop_cashback/{id}', [AdminShopCashbackController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_shop_cashback_detail');
    // email_*.php = include ของ save_approved/save_cancel — SR hotfix 2026-10-18: เปิดตรงจาก URL = 403 ว่าง (ก่อน hotfix เรียกได้โดยไม่ล็อกอิน)
    Route::match(['GET', 'POST'], '/_admin/_shop_cashback/email_approved', [AdminShopCashbackController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_shop_cashback/email_cancel', [AdminShopCashbackController::class, 'emailDirect']);
});
Route::post('/_admin/_shop_cashback/grid', [AdminShopCashbackController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
Route::middleware(['legacy.ajax', 'legacy.perm:admin_shop_cashback'])->group(function () {
    Route::post('/_admin/_shop_cashback/save', [AdminShopCashbackController::class, 'save']);
    Route::post('/_admin/_shop_cashback/save_approved', [AdminShopCashbackController::class, 'saveApproved']);
    Route::post('/_admin/_shop_cashback/save_cancel', [AdminShopCashbackController::class, 'saveCancel']);
    Route::post('/_admin/_shop_cashback/save_remark', [AdminShopCashbackController::class, 'saveRemark']);
});
