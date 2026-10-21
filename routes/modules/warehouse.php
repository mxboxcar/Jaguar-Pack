<?php

use App\Http\Controllers\Warehouse\Admin\AdminGoodsconfirmController;
use App\Http\Controllers\Warehouse\GoodsconfirmController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: warehouse — _goodsconfirm, _admin/_goodsconfirm
| (PACK: _admin/_warehouse, _admin/_warehouse_section, _admin/_warehouse_section_move = dead ดูท้ายไฟล์; _admin/_section เป็นโฟลเดอร์ว่าง) — รายการ URL/ไฟล์เดิมใน docs/url-map.md, รายงานใน docs/modules/warehouse.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม
| ajax: legacy.ajax มาก่อน legacy.perm (เดิม system_check_ajax.php ทำงานก่อนตรวจสิทธิ์)
| grid / load_tracking_by_id ตรวจสิทธิ์ใน controller เพราะเดิมตอบว่าง / <script> (ไม่ใช่ ErrorBox แบบ ajax)
*/

// ---------- ลูกค้า: ยืนยันเจ้าของพัสดุ ----------
Route::middleware('legacy.perm:goodsconfirm')->group(function () {
    Route::get('/goodsconfirm', [GoodsconfirmController::class, 'index'])->defaults('legacy_page', 'goodsconfirm');
    Route::get('/goodsconfirm/{id}', [GoodsconfirmController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'goodsconfirm_detail');
});
Route::middleware(['legacy.ajax', 'legacy.perm:goodsconfirm'])->group(function () {
    Route::post('/_goodsconfirm/save_approved', [GoodsconfirmController::class, 'saveApproved']);
});
Route::middleware(['legacy.ajax', 'legacy.auth'])->group(function () {
    Route::post('/_goodsconfirm/grid', [GoodsconfirmController::class, 'grid']);
    Route::post('/_goodsconfirm/load_tracking_by_id', [GoodsconfirmController::class, 'loadTrackingById']);
});

// ---------- แอดมิน: ยืนยันเจ้าของพัสดุ ----------
Route::middleware('legacy.perm:admin_goodsconfirm')->group(function () {
    Route::get('/admin/goodsconfirm', [AdminGoodsconfirmController::class, 'index'])->defaults('legacy_page', 'admin_goodsconfirm');
    Route::get('/admin/goodsconfirm/create', [AdminGoodsconfirmController::class, 'form'])->defaults('legacy_page', 'admin_goodsconfirm_detail');
    Route::get('/admin/goodsconfirm/status/{status}', [AdminGoodsconfirmController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_goodsconfirm');
    Route::get('/admin/goodsconfirm/{id}', [AdminGoodsconfirmController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_goodsconfirm_detail');
});
Route::middleware(['legacy.ajax', 'legacy.perm:admin_goodsconfirm'])->group(function () {
    Route::post('/_admin/_goodsconfirm/cancel', [AdminGoodsconfirmController::class, 'cancel']);
    Route::post('/_admin/_goodsconfirm/load_tracking', [AdminGoodsconfirmController::class, 'loadTracking']);
    Route::post('/_admin/_goodsconfirm/load_upload', [AdminGoodsconfirmController::class, 'loadUpload']);
    Route::post('/_admin/_goodsconfirm/save', [AdminGoodsconfirmController::class, 'save'])->middleware('legacy.once');
    Route::post('/_admin/_goodsconfirm/save_approved', [AdminGoodsconfirmController::class, 'saveApproved']);
    Route::post('/_admin/_goodsconfirm/save_image', [AdminGoodsconfirmController::class, 'saveImage']);
    Route::post('/_admin/_goodsconfirm/save_unapproved', [AdminGoodsconfirmController::class, 'saveUnapproved']);
});
Route::middleware(['legacy.ajax', 'legacy.auth'])->group(function () {
    Route::post('/_admin/_goodsconfirm/grid', [AdminGoodsconfirmController::class, 'grid']);
    Route::post('/_admin/_goodsconfirm/load_tracking_by_id', [AdminGoodsconfirmController::class, 'loadTrackingById']);
});

// ---------- PACK: รับสินค้าเข้าโกดัง / ชั้นวาง / ประวัติย้ายชั้นวาง = dead (ไม่ทำ route — docs/modules/warehouse.md) ----------
// - content.php ของ PACK ไม่มี page admin_warehouse(_detail), admin_warehouse_section(_detail), admin_warehouse_section_move
//   → URL admin/warehouse*, admin/warehouse_section* บนเว็บเดิมได้ layout เปล่า (เทียบ :8114 แล้ว) · เมนูไม่มีลิงก์
// - ตาราง warehouse, warehouse_detail, warehouse_section, warehouse_section_detail ไม่มีใน DB ของ PACK
// - สิทธิ์ admin_warehouse ไม่มี role ใดถือ → ajax _admin/_warehouse/* เดิมตอบ "ไม่มีสิทธิ์" กับทุกคน; ไม่มีหน้าไหนเรียก _admin/_warehouse*
