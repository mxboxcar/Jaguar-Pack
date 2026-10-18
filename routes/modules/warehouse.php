<?php

use App\Http\Controllers\Warehouse\Admin\AdminGoodsconfirmController;
use App\Http\Controllers\Warehouse\Admin\WarehouseController;
use App\Http\Controllers\Warehouse\Admin\WarehouseSectionController;
use App\Http\Controllers\Warehouse\GoodsconfirmController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: warehouse — _admin/_warehouse, _admin/_warehouse_section, _admin/_warehouse_section_move, _goodsconfirm, _admin/_goodsconfirm
| (_admin/_section ในเว็บเดิมเป็นโฟลเดอร์ว่าง) — รายการ URL/ไฟล์เดิมใน docs/url-map.md, รายงานใน docs/modules/warehouse.md
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

// ---------- แอดมิน: รับสินค้าเข้าโกดัง ----------
Route::middleware('legacy.perm:admin_warehouse')->group(function () {
    Route::get('/admin/warehouse', [WarehouseController::class, 'index'])->defaults('legacy_page', 'admin_warehouse');
    Route::get('/admin/warehouse/create', [WarehouseController::class, 'form'])->defaults('legacy_page', 'admin_warehouse_detail');
    Route::get('/admin/warehouse/{id}', [WarehouseController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_warehouse_detail');
});
Route::middleware(['legacy.ajax', 'legacy.perm:admin_warehouse'])->group(function () {
    Route::post('/_admin/_warehouse/load_editshelf', [WarehouseController::class, 'loadEditShelf']);
    Route::post('/_admin/_warehouse/load_lot_detail', [WarehouseController::class, 'loadLotDetail']);
    Route::post('/_admin/_warehouse/load_lot_detail_thai', [WarehouseController::class, 'loadLotDetailThai']);
    Route::post('/_admin/_warehouse/load_tracking', [WarehouseController::class, 'loadTracking']);
    Route::post('/_admin/_warehouse/load_tracking_edit', [WarehouseController::class, 'loadTrackingEdit']);
    Route::post('/_admin/_warehouse/save_cancel_warehouse_calculate', [WarehouseController::class, 'saveCancelWarehouseCalculate']);
    Route::post('/_admin/_warehouse/save_editshelf', [WarehouseController::class, 'saveEditShelf']);
    Route::post('/_admin/_warehouse/save_new_warehouse', [WarehouseController::class, 'saveNewWarehouse'])->middleware('legacy.once:lock');
    Route::post('/_admin/_warehouse/save_push_track', [WarehouseController::class, 'savePushTrack']);
    Route::post('/_admin/_warehouse/save_track_weight', [WarehouseController::class, 'saveTrackWeight']);
    // JS เดิม (go_loadDetail) เรียกด้วย GET — เขียนข้อมูล จึงบังคับ X-Requested-With (legacy.ajax) แทนการตรวจ Origin
    // กดซ้อน (ดับเบิลคลิก ConfirmBox) = คำนวณพร้อมกัน 2 คำขอ → lock ต่อผู้ใช้ (คำขอหลังได้ ErrorBox)
    Route::get('/_admin/_warehouse/save_lot_detail', [WarehouseController::class, 'saveLotDetail'])->middleware('legacy.once:lock');
});
// ไฟล์สำเนาที่เขียน warehouse_section (ตรวจสิทธิ์ admin_warehouse_section เหมือนเดิม)
Route::post('/_admin/_warehouse/save', [WarehouseController::class, 'save'])->middleware(['legacy.ajax', 'legacy.perm:admin_warehouse_section']);
// grid เดิมตรวจ admin_lot แล้วตอบว่าง (ตรวจใน controller)
Route::post('/_admin/_warehouse/grid', [WarehouseController::class, 'grid'])->middleware(['legacy.ajax', 'legacy.auth']);

// ---------- แอดมิน: ชั้นวาง / ประวัติย้ายชั้นวาง ----------
Route::middleware('legacy.perm:admin_warehouse_section')->group(function () {
    Route::get('/admin/warehouse_section', [WarehouseSectionController::class, 'index'])->defaults('legacy_page', 'admin_warehouse_section');
    Route::get('/admin/warehouse_section/create', [WarehouseSectionController::class, 'form'])->defaults('legacy_page', 'admin_warehouse_section_detail');
    Route::get('/admin/warehouse_section/{id}', [WarehouseSectionController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_warehouse_section_detail');
    Route::get('/admin/warehouse_section_move', [WarehouseSectionController::class, 'moveIndex'])->defaults('legacy_page', 'admin_warehouse_section_move');
});
Route::middleware(['legacy.ajax', 'legacy.perm:admin_warehouse_section'])->group(function () {
    Route::post('/_admin/_warehouse_section/save', [WarehouseSectionController::class, 'save'])->middleware('legacy.once');
});
// delete เดิมตรวจสิทธิ์ admin_product_type (ไม่ใช่ admin_warehouse_section) — คงไว้
Route::post('/_admin/_warehouse_section/delete', [WarehouseSectionController::class, 'delete'])->middleware(['legacy.ajax', 'legacy.perm:admin_product_type']);
Route::middleware(['legacy.ajax', 'legacy.auth'])->group(function () {
    Route::post('/_admin/_warehouse_section/grid', [WarehouseSectionController::class, 'grid']);
    Route::post('/_admin/_warehouse_section_move/grid', [WarehouseSectionController::class, 'moveGrid']);
});
