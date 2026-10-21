<?php

use App\Http\Controllers\MoneyMove\Admin\AdminRefillController;
use App\Http\Controllers\MoneyMove\Admin\AdminTransferController;
use App\Http\Controllers\MoneyMove\Admin\AdminWithdrawCashController;
use App\Http\Controllers\MoneyMove\RefillController;
use App\Http\Controllers\MoneyMove\TransferController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: money-move — _refill, _admin/_refill, _transfer, _admin/_transfer, _admin/_withdrawcash
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/money-move.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม; grid ajax ตรวจสิทธิ์ใน controller เพราะเดิมตอบว่างเมื่อไม่มีสิทธิ์ (ไม่ใช่ ErrorBox)
*/

// ---------- ลูกค้า: แจ้งเติมเงิน ----------
Route::middleware('legacy.perm:refill')->group(function () {
    Route::get('/refill', [RefillController::class, 'index'])->defaults('legacy_page', 'refill');
    Route::get('/refill/create', [RefillController::class, 'form'])->defaults('legacy_page', 'refill_detail');
    Route::get('/refill/status/{status}', [RefillController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'refill');
    Route::get('/refill/{id}', [RefillController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'refill_detail');
    Route::post('/_refill/save', [RefillController::class, 'save'])->middleware(['legacy.ajax', 'legacy.once']);
});
Route::post('/_refill/grid', [RefillController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- ลูกค้า: โอนเงินไปจีน ----------
Route::middleware('legacy.perm:transfer')->group(function () {
    Route::get('/transfer', [TransferController::class, 'index'])->defaults('legacy_page', 'transfer');
    Route::get('/transfer/create', [TransferController::class, 'form'])->defaults('legacy_page', 'transfer_detail');
    Route::get('/transfer/status/{status}', [TransferController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'transfer');
    Route::get('/transfer/{id}', [TransferController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'transfer_detail');
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_transfer/save', [TransferController::class, 'save'])->middleware('legacy.once');
        // PACK: _transfer/cancel.php ถูก comment ทั้งไฟล์ → ตอบว่าง ไม่เขียนอะไร
        Route::post('/_transfer/cancel', [TransferController::class, 'cancel']);
    });
});
Route::post('/_transfer/grid', [TransferController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: เติมเงิน ----------
Route::middleware('legacy.perm:admin_refill')->group(function () {
    Route::get('/admin/refill', [AdminRefillController::class, 'index'])->defaults('legacy_page', 'admin_refill');
    Route::get('/admin/refill/create', [AdminRefillController::class, 'form'])->defaults('legacy_page', 'admin_refill_detail');
    Route::get('/admin/refill/status/{status}', [AdminRefillController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_refill');
    Route::get('/admin/refill/{id}', [AdminRefillController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_refill_detail');
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_refill/save', [AdminRefillController::class, 'save'])->middleware('legacy.once');
        Route::post('/_admin/_refill/save_approved', [AdminRefillController::class, 'saveApproved']);
        Route::post('/_admin/_refill/cancel', [AdminRefillController::class, 'cancel']);
    });
    // ไฟล์ include ที่เดิมเปิดตรงได้โดยไม่ต้องล็อกอิน — ที่นี่ต้องมีสิทธิ์ และไม่ส่งอะไร
    Route::match(['GET', 'POST'], '/_admin/_refill/email_approved', [AdminRefillController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_refill/email_cancel', [AdminRefillController::class, 'emailDirect']);
});
Route::post('/_admin/_refill/grid', [AdminRefillController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: โอนเงินไปจีน ----------
Route::middleware('legacy.perm:admin_transfer')->group(function () {
    Route::get('/admin/transfer', [AdminTransferController::class, 'index'])->defaults('legacy_page', 'admin_transfer');
    Route::get('/admin/transfer/status/{status}', [AdminTransferController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_transfer');
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_transfer/save', [AdminTransferController::class, 'save']);
        Route::post('/_admin/_transfer/cancel', [AdminTransferController::class, 'cancel']);
        Route::post('/_admin/_transfer/rollback', [AdminTransferController::class, 'rollback']);
    });
    Route::match(['GET', 'POST'], '/_admin/_transfer/email_success', [AdminTransferController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_transfer/email_cancel', [AdminTransferController::class, 'emailDirect']);
});
// form.php: PACK (= jl) ตรวจแค่ getPermission('transfer') (ลูกค้ามีทุกคน = IDOR) → ต้องมีทั้ง transfer และ admin_transfer (กติกา jl port)
// ตรวจ transfer ก่อน: ผู้ไม่มี transfer ได้ ErrorBox โมดูล "โอนเงินไปจีน" เหมือนเดิม (PACK role 3/5/6);
// ผู้มี transfer แต่ไม่มี admin_transfer (ลูกค้า role 2/8, role 7/9) ได้ ErrorBox "ผู้ดูแล - โอนเงินไปจีน" (ต่างจากเดิมโดยตั้งใจ — เดิมเห็นรายการของทุกคน)
Route::middleware(['legacy.perm:transfer', 'legacy.perm:admin_transfer'])->group(function () {
    Route::get('/admin/transfer/create', [AdminTransferController::class, 'form'])->defaults('legacy_page', 'admin_transfer_detail');
    Route::get('/admin/transfer/{id}', [AdminTransferController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_transfer_detail');
});
Route::post('/_admin/_transfer/grid', [AdminTransferController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: ถอนเงินออกจากระบบ ----------
// SR: index.php / save_approved.php / cancel.php ตรวจ admin_withdrawpoint (index แจ้งปฏิเสธด้วยชื่อ admin_withdrawcash),
//     form.php / grid.php ตรวจ admin_withdrawcash — ตามไฟล์เดิม (bench: admin_withdrawpoint = role 1, admin_withdrawcash = role 1/12/13)
Route::middleware('legacy.auth')->group(function () {
    Route::get('/admin/withdrawcash', [AdminWithdrawCashController::class, 'index'])->defaults('legacy_page', 'admin_withdrawcash');
    Route::get('/admin/withdrawcash/status/{status}', [AdminWithdrawCashController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_withdrawcash');
});
Route::middleware('legacy.perm:admin_withdrawcash')->group(function () {
    Route::get('/admin/withdrawcash/{id}', [AdminWithdrawCashController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_withdrawcash_detail');
});
Route::middleware(['legacy.perm:admin_withdrawpoint', 'legacy.ajax'])->group(function () {
    Route::post('/_admin/_withdrawcash/save_approved', [AdminWithdrawCashController::class, 'saveApproved']);
    Route::post('/_admin/_withdrawcash/cancel', [AdminWithdrawCashController::class, 'cancel']);
});
Route::post('/_admin/_withdrawcash/grid', [AdminWithdrawCashController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
