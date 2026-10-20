<?php

use App\Http\Controllers\Reports\Admin\AdminExportController;
use App\Http\Controllers\Reports\Admin\AdminLogController;
use App\Http\Controllers\Reports\Admin\AdminNobillingController;
use App\Http\Controllers\Reports\Admin\AdminReceiptController;
use App\Http\Controllers\Reports\Admin\AdminReportController;
use App\Http\Controllers\Reports\DashboardController;
use App\Http\Controllers\Reports\DownloadFileController;
use App\Http\Controllers\Reports\ExportController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: reports — _dashboard, _export, _admin/_export, _admin/_report, _admin/_log, _admin/_nobilling, download_file.php
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/reports.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม; ไฟล์เดิมที่ไม่ตรวจสิทธิ์ใช้สิทธิ์ของหน้าแม่ (บันทึกในรายงาน)
| grid ajax / ไฟล์รายงานที่เปิดตรง ตรวจสิทธิ์ใน controller เพราะเดิมตอบว่าง/alert ใน HTML เปล่า (ไม่ใช่ ErrorBox)
| หน้า (GET) ตั้งชื่อ "page.<page เดิม>" ให้ /?page=<page> ของ routes/legacy.php ส่งต่อได้; / แสดง page.dashboard
*/

// ---------- ลูกค้า ----------
Route::middleware('legacy.auth')->group(function () {
    // เดิมไม่ตรวจสิทธิ์ (ทุกคนที่ล็อกอินเห็น dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->defaults('legacy_page', 'dashboard')->name('page.dashboard');

    Route::middleware('legacy.ajax')->group(function () {
        // เดิม: ไม่มีสิทธิ์ payment = exit() (ตอบว่าง) → ตรวจใน controller
        Route::post('/_export/grid', [ExportController::class, 'grid']);
    });
});
Route::middleware('legacy.perm:export')->group(function () {
    Route::get('/export', [ExportController::class, 'index'])->defaults('legacy_page', 'export')->name('page.export');
    Route::get('/export/{id}', [ExportController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'export_detail')->name('page.export_detail');
});
// save_approved / save_delete เดิมตรวจ getPermission('payment')
Route::middleware(['legacy.perm:payment', 'legacy.ajax'])->group(function () {
    Route::post('/_export/save_approved', [ExportController::class, 'saveApproved'])->middleware('legacy.once');
    Route::post('/_export/save_delete', [ExportController::class, 'saveDelete']);
});

// ---------- แอดมิน: รายการส่งสินค้า ----------
Route::middleware('legacy.perm:admin_export')->group(function () {
    Route::get('/admin/export', [AdminExportController::class, 'index'])->defaults('legacy_page', 'admin_export')->name('page.admin_export');
    Route::get('/admin/export/status/{status}', [AdminExportController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_export');
    Route::post('/_admin/_export/save_approved', [AdminExportController::class, 'saveApproved'])->middleware('legacy.ajax');
    // เปิดตรงเดิม = ไม่มี $email_id → ไม่ส่งอะไร (เดิมไม่ตรวจสิทธิ์ → admin_export)
    Route::match(['GET', 'POST'], '/_admin/_export/email_success', [AdminExportController::class, 'emailNoop']);
    Route::match(['GET', 'POST'], '/_admin/_export/email_cancel', [AdminExportController::class, 'emailNoop']);
});
Route::middleware('legacy.perm:admin_payment')->group(function () {
    Route::get('/admin/export/{id}', [AdminExportController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_export_detail')->name('page.admin_export_detail');
    Route::post('/_admin/_export/save_delete', [AdminExportController::class, 'saveDelete'])->middleware('legacy.ajax');
});
Route::middleware('legacy.auth')->group(function () {
    // ไม่มีสิทธิ์ admin_export: grid ตอบว่าง, print ตอบ "ERROR : access denied" (ตรวจใน controller)
    Route::post('/_admin/_export/grid', [AdminExportController::class, 'grid'])->middleware('legacy.ajax');
    Route::get('/_admin/_export/print', [AdminExportController::class, 'print']);
    // SR: บิลชำระเงิน (printprice.php + print_3.php) — สิทธิ์/ผลแบบ print.php
    Route::get('/_admin/_export/printprice', [AdminExportController::class, 'printPrice']);
    // SR: พิมพ์ใบเสร็จ / รายงานภาษีขาย — ไม่มีสิทธิ์ admin_export ตอบ "ERROR : access denied" (ตรวจใน controller)
    Route::get('/_admin/_export/print_receipt', [AdminReceiptController::class, 'print']);
    Route::get('/_admin/_report/printtax', [AdminReceiptController::class, 'printtax']);
});

// ---------- SR: ใบเสร็จรับเงิน/ใบกำกับภาษี (bill_payment.receipt_*) ----------
Route::middleware('legacy.perm:admin_payment')->group(function () {
    Route::get('/admin/export/receipt/{id}', [AdminReceiptController::class, 'form'])->whereNumber('id')
        ->defaults('legacy_page', 'admin_export_receipt')->name('page.admin_export_receipt');
    Route::post('/_admin/_export/receipt_1', [AdminReceiptController::class, 'table1'])->middleware('legacy.ajax');
    Route::post('/_admin/_export/receipt_2', [AdminReceiptController::class, 'table2'])->middleware('legacy.ajax');
});
Route::middleware(['legacy.perm:admin_export', 'legacy.ajax'])->group(function () {
    Route::post('/_admin/_export/calvat', [AdminReceiptController::class, 'calvat']);
    // บันทึกซ้ำได้ (แก้ข้อมูลใบเสร็จ) → lock อย่างเดียว ไม่กัน payload ซ้ำ
    Route::post('/_admin/_export/save_receipt', [AdminReceiptController::class, 'save'])->middleware('legacy.once:lock');
});

// ---------- แอดมิน: รายงาน ----------
Route::middleware('legacy.perm:admin_report')->group(function () {
    // index.php เดิมไม่ตรวจสิทธิ์ (เมนูแสดงเมื่อมี admin_report)
    Route::get('/admin/report', [AdminReportController::class, 'index'])->defaults('legacy_page', 'admin_report')->name('page.admin_report');
    Route::get('/admin/report/{id}', [AdminReportController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_report_detail')->name('page.admin_report_detail');
});
Route::middleware('legacy.auth')->group(function () {
    Route::post('/_admin/_report/grid', [AdminReportController::class, 'grid'])->middleware('legacy.ajax');
    // ไฟล์รายงานเปิดในแท็บใหม่ (window.open / ลิงก์) — ไม่มีสิทธิ์ admin_report ตอบแบบเดิมใน controller
    Route::get('/_admin/_report/file', [AdminReportController::class, 'file']);
    Route::get('/_admin/_report/else', [AdminReportController::class, 'else']);
    Route::get('/_admin/_report/else_preorder_working', [AdminReportController::class, 'elsePreorderWorking']);
    Route::get('/_admin/_report/refill_5', [AdminReportController::class, 'refill5']);
    Route::get('/_admin/_report/refill_6', [AdminReportController::class, 'refill6']);
    Route::get('/_admin/_report/refill_7', [AdminReportController::class, 'refill7']);
    Route::get('/_admin/_report/shipping', [AdminReportController::class, 'shipping']);
    Route::get('/_admin/_report/cashmove', [AdminReportController::class, 'cashmove']);
    Route::get('/_admin/_report/current_cash', [AdminReportController::class, 'currentCash']);
    Route::get('/_admin/_report/current_cash_cn', [AdminReportController::class, 'currentCashCn']);
});

// ---------- แอดมิน: log ----------
Route::get('/admin/log', [AdminLogController::class, 'index'])->middleware('legacy.perm:admin_log')->defaults('legacy_page', 'admin_log')->name('page.admin_log');
// grid.php เดิมตรวจ getPermission('admin_balance') แล้ว exit() (ตอบว่าง) → ตรวจใน controller
Route::post('/_admin/_log/grid', [AdminLogController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: ยังไม่ชำระเกิน 15 วัน ----------
// เดิมไม่มี RewriteRule (เปิดได้ด้วย ?page=admin_nobilling; ลิงก์ admin/nobilling ใน notification.php เดิมเป็น 404) → ให้ URL นี้
Route::get('/admin/nobilling', [AdminNobillingController::class, 'index'])->middleware('legacy.perm:admin_nobilling')
    ->defaults('legacy_page', 'admin_nobilling')->name('page.admin_nobilling');

// ---------- download_file.php (ลิงก์ไฟล์แนบในหน้า admin lot) — เดิมไม่ต้องล็อกอิน ----------
Route::get('/download_file', DownloadFileController::class)->middleware('legacy.perm:admin_lot,admin_lot_view');
