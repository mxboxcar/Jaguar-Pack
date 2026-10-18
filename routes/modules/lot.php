<?php

use App\Http\Controllers\Lot\Admin\AdminLotController;
use App\Http\Controllers\Lot\Admin\AdminLotPrintController;
use App\Http\Controllers\Lot\Admin\AdminLotSaveController;
use App\Http\Controllers\Lot\LotController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: lot — _lot, _lot/_message, _admin/_lot (ดู docs/url-map.md และรายงาน docs/modules/lot.md)
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม:
|   หน้า/ajax ที่อ่านอย่างเดียว: admin_lot_view || admin_lot   → legacy.perm:admin_lot,admin_lot_view
|   ทุก endpoint ที่เขียนข้อมูล (save*, delete, save_verified, print, print_receive, email_lot): admin_lot เท่านั้น
|     (แก้จากเดิมที่ save* ยอม admin_lot_view — admin_lot_view = ดูอย่างเดียว; load_check_tracking / print_all
|      ของผู้ถือ admin_lot_view แสดงผลได้แต่ไม่เขียน is_checked / is_print — ดู docs/modules/lot.md)
|   cancel.php (ยกเลิก refill ที่อยู่ในโฟลเดอร์นี้): admin_refill
| ไฟล์เดิมที่ไม่มีสิทธิ์แล้ว exit() เงียบ จะได้ ErrorBox ของ legacy.perm แทน (เฉพาะผู้ไม่มีสิทธิ์)
*/

// ---------- ลูกค้า ----------
Route::middleware('legacy.perm:lot')->group(function () {
    Route::get('/lot', [LotController::class, 'index'])->defaults('legacy_page', 'lot');
    Route::get('/lot/{id}', [LotController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'lot_detail');
    Route::post('/_lot/load_lot_detail', [LotController::class, 'loadLotDetail'])->middleware('legacy.ajax');
});
// grid เดิมตอบว่างเมื่อไม่มีสิทธิ์ (ไม่ใช่ ErrorBox) → ตรวจใน controller
Route::post('/_lot/grid', [LotController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// _lot/_message/*.php: สำเนาของ _message ที่ require '../system_config.php' ผิด path → เดิม HTTP 500 ทุกครั้ง (ไม่เคยใช้งานได้)
// คง URL ไว้แต่ตอบ 404 (ไม่เปิดช่องให้ส่ง/อ่านแชทผ่าน path นี้) — แชทจริงอยู่ที่กลุ่ม messaging (/_message/*)
Route::middleware('legacy.auth')->group(function () {
    foreach (['index', 'load', 'load_dept', 'send'] as $f) {
        Route::match(['GET', 'POST'], '/_lot/_message/'.$f, fn () => abort(404));
    }
});

// ---------- แอดมิน ----------
Route::middleware('legacy.perm:admin_lot,admin_lot_view')->group(function () {
    Route::get('/admin/lot', [AdminLotController::class, 'index'])->defaults('legacy_page', 'admin_lot');
    Route::get('/admin/lot/create', [AdminLotController::class, 'form'])->defaults('legacy_page', 'admin_lot_detail');
    Route::get('/admin/lot/{id}', [AdminLotController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_lot_detail');

    Route::get('/_admin/_lot/print_all', [AdminLotPrintController::class, 'printAll']);

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_lot/grid', [AdminLotController::class, 'grid']);
        Route::post('/_admin/_lot/load_autocomplete_tracking', [AdminLotController::class, 'autocompleteTracking']);
        Route::post('/_admin/_lot/load_autocomplete_user', [AdminLotController::class, 'autocompleteUser']);
        Route::post('/_admin/_lot/load_check_tracking', [AdminLotController::class, 'loadCheckTracking']);
        Route::post('/_admin/_lot/load_lastest_lot', [AdminLotController::class, 'loadLastestLot']);
        Route::post('/_admin/_lot/load_lot_detail', [AdminLotController::class, 'loadLotDetail']);
        Route::post('/_admin/_lot/load_lot_memo', [AdminLotController::class, 'loadLotMemo']);
        Route::post('/_admin/_lot/load_ref_print', [AdminLotController::class, 'loadRefPrint']);
        Route::post('/_admin/_lot/load_tracking', [AdminLotController::class, 'loadTracking']);
        Route::post('/_admin/_lot/load_user_rate', [AdminLotController::class, 'loadUserRate']);
    });
});

Route::middleware('legacy.perm:admin_lot')->group(function () {
    Route::get('/_admin/_lot/print', [AdminLotPrintController::class, 'print']);
    Route::get('/_admin/_lot/print_receive', [AdminLotPrintController::class, 'printReceive']);
    Route::get('/_admin/_lot/email_lot', [AdminLotPrintController::class, 'emailLot']);
    // เดิมเปิดผ่านเว็บได้โดยไม่ล็อกอิน → ต้องมีสิทธิ์ และไม่ทำงาน (worker = php artisan lot:mail-worker)
    Route::match(['GET', 'POST'], '/_admin/_lot/cron_lot_mail', [AdminLotPrintController::class, 'cliOnly']);
    Route::match(['GET', 'POST'], '/_admin/_lot/email', [AdminLotPrintController::class, 'cliOnly']);

    Route::middleware('legacy.ajax')->group(function () {
        // แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): admin_lot_view = ดูอย่างเดียว → endpoint เขียนทุกตัวต้องมี admin_lot
        // (เดิมทุกไฟล์ save_* ยอม admin_lot_view || admin_lot) — ผู้ถือ admin_lot_view ได้ ErrorBox สิทธิ์แบบ getPermissionAlert เดิม
        Route::post('/_admin/_lot/save', [AdminLotSaveController::class, 'save']);
        Route::post('/_admin/_lot/save_delete_lot_memo', [AdminLotSaveController::class, 'saveDeleteLotMemo']);
        Route::post('/_admin/_lot/save_file_lot', [AdminLotSaveController::class, 'saveFileLot']);
        Route::post('/_admin/_lot/save_lot_memo', [AdminLotSaveController::class, 'saveLotMemo']);
        Route::post('/_admin/_lot/save_lot_name', [AdminLotSaveController::class, 'saveLotName']);
        Route::post('/_admin/_lot/save_lot_name_admin', [AdminLotSaveController::class, 'saveLotNameAdmin']);
        Route::post('/_admin/_lot/save_lot_ship_amount', [AdminLotSaveController::class, 'saveLotShipAmount']);
        Route::post('/_admin/_lot/save_new_lot', [AdminLotSaveController::class, 'saveNewLot'])->middleware('legacy.once:lock');
        Route::post('/_admin/_lot/save_print', [AdminLotSaveController::class, 'savePrint']);
        Route::post('/_admin/_lot/save_remove_tracking', [AdminLotSaveController::class, 'saveRemoveTracking']);
        Route::post('/_admin/_lot/save_status_lot', [AdminLotSaveController::class, 'saveStatusLot']);
        Route::post('/_admin/_lot/save_status_lot_date', [AdminLotSaveController::class, 'saveStatusLotDate']);
        Route::post('/_admin/_lot/save_status_reset_tracking', [AdminLotSaveController::class, 'saveStatusResetTracking']);
        Route::post('/_admin/_lot/save_status_tracking', [AdminLotSaveController::class, 'saveStatusTracking']);
        Route::post('/_admin/_lot/delete', [AdminLotSaveController::class, 'delete']);
        Route::post('/_admin/_lot/save_verified', [AdminLotSaveController::class, 'saveVerified']);
    });
});

Route::post('/_admin/_lot/cancel', [AdminLotSaveController::class, 'cancel'])->middleware(['legacy.perm:admin_refill', 'legacy.ajax']);
