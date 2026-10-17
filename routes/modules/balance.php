<?php

use App\Http\Controllers\Balance\Admin\AdminBalanceCnController;
use App\Http\Controllers\Balance\Admin\AdminBalanceCompanyController;
use App\Http\Controllers\Balance\Admin\AdminBalanceController;
use App\Http\Controllers\Balance\BalanceCnController;
use App\Http\Controllers\Balance\BalanceController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: balance — _balance, _balance_cn, _admin/_balance, _admin/_balance_cn, _admin/_balance_company
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/balance.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม
| grid / load_withdraw / save_withdraw ตรวจสิทธิ์ใน controller เพราะเดิม exit() เงียบๆ เมื่อไม่มีสิทธิ์ (ไม่ใช่ ErrorBox)
| load_amount / save_amount เดิมตอบ getPermissionAlert() = สิ่งที่ legacy.perm ตอบให้อยู่แล้ว
*/

// ---------- ลูกค้า: บัญชีเงินบาท ----------
Route::get('/balance', [BalanceController::class, 'index'])->middleware('legacy.perm:balance')->defaults('legacy_page', 'balance');
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_balance/grid', [BalanceController::class, 'grid']);
    Route::post('/_balance/load_withdraw', [BalanceController::class, 'loadWithdraw']);
    Route::post('/_balance/save_withdraw', [BalanceController::class, 'saveWithdraw']);
});

// ---------- ลูกค้า: บัญชีเงินหยวน ----------
Route::get('/balance_cn', [BalanceCnController::class, 'index'])->middleware('legacy.perm:balance_cn')->defaults('legacy_page', 'balance_cn');
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_balance_cn/grid', [BalanceCnController::class, 'grid']);
    Route::post('/_balance_cn/load_withdraw', [BalanceCnController::class, 'loadWithdraw']);
    Route::post('/_balance_cn/save_withdraw', [BalanceCnController::class, 'saveWithdraw']);
});

// ---------- แอดมิน: บัญชีเงินบาท ----------
Route::get('/admin/balance', [AdminBalanceController::class, 'index'])->middleware('legacy.perm:admin_balance')->defaults('legacy_page', 'admin_balance');
Route::post('/_admin/_balance/grid', [AdminBalanceController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
Route::middleware(['legacy.ajax', 'legacy.perm:admin_balance'])->group(function () {
    Route::post('/_admin/_balance/load_amount', [AdminBalanceController::class, 'loadAmount']);
    Route::post('/_admin/_balance/save_amount', [AdminBalanceController::class, 'saveAmount']);
});

// ---------- แอดมิน: บัญชีเงินหยวน ----------
Route::get('/admin/balance_cn', [AdminBalanceCnController::class, 'index'])->middleware('legacy.perm:admin_balance_cn')->defaults('legacy_page', 'admin_balance_cn');
Route::post('/_admin/_balance_cn/grid', [AdminBalanceCnController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
Route::middleware(['legacy.ajax', 'legacy.perm:admin_balance_cn'])->group(function () {
    Route::post('/_admin/_balance_cn/load_amount', [AdminBalanceCnController::class, 'loadAmount']);
    Route::post('/_admin/_balance_cn/save_amount', [AdminBalanceCnController::class, 'saveAmount']);
});

// ---------- แอดมิน: บัญชีบริษัท (สิทธิ์ admin_balance_company ยังไม่มี role ใดได้รับ = เข้าไม่ได้เหมือนเดิม) ----------
Route::get('/admin/balance_company', [AdminBalanceCompanyController::class, 'index'])->middleware('legacy.perm:admin_balance_company')->defaults('legacy_page', 'admin_balance_company');
Route::post('/_admin/_balance_company/grid', [AdminBalanceCompanyController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
Route::middleware(['legacy.ajax', 'legacy.perm:admin_balance_company'])->group(function () {
    Route::post('/_admin/_balance_company/load_amount', [AdminBalanceCompanyController::class, 'loadAmount']);
    Route::post('/_admin/_balance_company/save_amount', [AdminBalanceCompanyController::class, 'saveAmount']);
});
