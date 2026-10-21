<?php

use App\Http\Controllers\Order\Admin\AdminOrderController;
use App\Http\Controllers\Order\Admin\AdminOrderDetailController;
use App\Http\Controllers\Order\Admin\AdminTaobaoController;
use App\Http\Controllers\Order\OrderController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: order — _order, _admin/_order, _admin/_taobao
| PACK: _admin/_order/grid + load_paging + load_total_order แบบ jl (cache 10 นาที); ไม่มี _order/load_paging, _order/print, _admin/_order/save_vat ของ jl
|       และไม่มีไฟล์เฉพาะ SR (_admin/_printorder/*, delete_withcashback, load_changeuser, save_changeuser, save_newrate, save_takeapic) → 404 → หน้าแรก
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/order.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม; ไฟล์เดิมที่ไม่ตรวจสิทธิ์ใช้สิทธิ์ของหน้าแม่ (บันทึกในรายงาน)
| ajax ที่เดิม "exit() เงียบ" เมื่อไม่มีสิทธิ์ (grid, load_paging, save_quote ...) ตรวจสิทธิ์ใน controller แล้วตอบว่างแบบเดิม
*/

// ---------- ลูกค้า: รายการสั่งซื้อ ----------
Route::middleware('legacy.perm:order')->group(function () {
    Route::get('/order', [OrderController::class, 'index'])->defaults('legacy_page', 'order');
    Route::get('/order/status/{status}', [OrderController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'order');
    Route::get('/order/{id}', [OrderController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'order_detail');
    Route::get('/order/{id}/{div}', [OrderController::class, 'form'])->whereNumber('id')->where('div', '.+')->defaults('legacy_page', 'order_detail');

    Route::post('/_order/delete', [OrderController::class, 'delete'])->middleware('legacy.ajax');
});
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_order/grid', [OrderController::class, 'grid']);
    Route::post('/_order/load_total_order', [OrderController::class, 'loadTotalOrder']);
    Route::post('/_order/save_payment', [OrderController::class, 'savePayment'])->middleware('legacy.once');
    Route::post('/_order/save_quote', [OrderController::class, 'saveQuote']);
    Route::post('/_order/save_quote_tracking', [OrderController::class, 'saveQuoteTracking']);
    Route::post('/_order/save_remark', [OrderController::class, 'saveRemark']);
});

// ---------- แอดมิน: รายการสั่งซื้อ ----------
Route::middleware('legacy.perm:admin_order')->group(function () {
    // RewriteRule เดิม ^admin/dashboard$ → ?page=order_admin (หน้าแรกของแอดมิน = รายการสั่งซื้อ)
    Route::get('/admin/dashboard', [AdminOrderController::class, 'index'])->defaults('legacy_page', 'order_admin');
    Route::get('/admin/order', [AdminOrderController::class, 'index'])->defaults('legacy_page', 'order_admin');
    Route::get('/admin/order/status/{status}', [AdminOrderController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'order_admin');
    Route::get('/admin/order/{id}', [AdminOrderController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'order_admin_detail');
    Route::get('/admin/order/{id}/{div}', [AdminOrderController::class, 'form'])->whereNumber('id')->where('div', '.+')->defaults('legacy_page', 'order_admin_detail');

    Route::middleware('legacy.ajax')->group(function () {
        // เดิมไม่ตรวจสิทธิ์ (ใครก็ได้นับจำนวนใบสั่งซื้อทั้งระบบ) → admin_order
        Route::post('/_admin/_order/load_total_order', [AdminOrderController::class, 'loadTotalOrder']);
        Route::post('/_admin/_order/delete', [AdminOrderController::class, 'delete']);
        Route::post('/_admin/_order/save_approved', [AdminOrderController::class, 'saveApproved']);
        Route::post('/_admin/_order/save_finish', [AdminOrderController::class, 'saveFinish']);
        Route::post('/_admin/_order/save_lock', [AdminOrderController::class, 'saveLock']);
        Route::post('/_admin/_order/save_remark', [AdminOrderController::class, 'saveRemark']);
        Route::post('/_admin/_order/save_quote_admin', [AdminOrderController::class, 'saveQuoteAdmin']);
        Route::post('/_admin/_order/load_paging', [AdminOrderController::class, 'loadPaging']);
        // ปุ่ม "เปลี่ยนเป็นส่งเรือ/รถ" (ผู้ใช้ id 84 / 3 ตายตัวในฟอร์มเดิม) — ไฟล์เดิมตรวจแค่ admin_order
        Route::post('/_admin/_order/save_change_send_type', [AdminOrderController::class, 'saveChangeSendType']);

        Route::post('/_admin/_order/load_detail', [AdminOrderDetailController::class, 'loadDetail']);
        Route::post('/_admin/_order/load_calculate_cashback', [AdminOrderDetailController::class, 'loadCalculateCashback']);
        Route::post('/_admin/_order/load_color_form', [AdminOrderDetailController::class, 'loadDetailRow']);
        Route::post('/_admin/_order/load_remark_product', [AdminOrderDetailController::class, 'loadDetailRow']);
        Route::post('/_admin/_order/load_remark_shop', [AdminOrderDetailController::class, 'loadRemarkShop']);
        // เดิมไม่ตรวจสิทธิ์และไม่ตรวจล็อกอินเลย (ใครก็แก้จำนวน/ยอดรวมใบสั่งซื้อได้) → admin_order
        Route::post('/_admin/_order/save_amount', [AdminOrderDetailController::class, 'saveAmount']);
        Route::post('/_admin/_order/save_price', [AdminOrderDetailController::class, 'savePrice']);
        Route::post('/_admin/_order/save_shipping', [AdminOrderDetailController::class, 'saveShipping']);
        Route::post('/_admin/_order/save_cashback', [AdminOrderDetailController::class, 'saveCashback']);
        Route::post('/_admin/_order/save_cashback_cancel', [AdminOrderDetailController::class, 'saveCashbackCancel']);
        Route::post('/_admin/_order/save_color', [AdminOrderDetailController::class, 'saveColor']);
        Route::post('/_admin/_order/save_craft', [AdminOrderDetailController::class, 'saveCraft']);
        Route::post('/_admin/_order/save_qc', [AdminOrderDetailController::class, 'saveQc']);
        Route::post('/_admin/_order/save_remark_product', [AdminOrderDetailController::class, 'saveRemarkProduct']);
        Route::post('/_admin/_order/save_remark_shop', [AdminOrderDetailController::class, 'saveRemarkShop']);
        Route::post('/_admin/_order/save_product_type', [AdminOrderDetailController::class, 'saveProductType']);
        Route::post('/_admin/_order/save_order_number', [AdminOrderDetailController::class, 'saveOrderNumber']);
        Route::post('/_admin/_order/save_tracking', [AdminOrderDetailController::class, 'saveTracking']);
        Route::post('/_admin/_order/save_quote', [AdminOrderDetailController::class, 'saveQuote']);
        Route::post('/_admin/_order/save_valid_track', [AdminOrderDetailController::class, 'saveValidTrack']);
        Route::post('/_admin/_order/save_add_new_track', [AdminOrderDetailController::class, 'saveAddNewTrack'])->middleware('legacy.once:lock,html');
        Route::post('/_admin/_order/save_remove_track', [AdminOrderDetailController::class, 'saveRemoveTrack']);
    });
    // ไฟล์อีเมลเดิมเรียกตรงได้โดยไม่ตรวจอะไร แต่ไม่มีค่า $email_id → ไม่ส่งอะไรที่ใช้ได้; ตัวส่งจริงเรียกจาก save_* ภายใน
    Route::match(['GET', 'POST'], '/_admin/_order/email_approved', [AdminOrderController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_order/email_cancel', [AdminOrderController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_order/email_cashback', [AdminOrderController::class, 'emailDirect']);
});
// grid เดิม exit() เงียบเมื่อไม่มีสิทธิ์ → ตรวจใน controller
Route::post('/_admin/_order/grid', [AdminOrderController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: ฝากจ่ายเถาเปา ----------
Route::middleware('legacy.perm:admin_taobao')->group(function () {
    Route::get('/admin/taobao', [AdminTaobaoController::class, 'index'])->defaults('legacy_page', 'admin_taobao');
    Route::get('/admin/taobao/create', [AdminTaobaoController::class, 'form'])->defaults('legacy_page', 'admin_taobao_detail');
    Route::get('/admin/taobao/status/{status}', [AdminTaobaoController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_taobao');
    Route::get('/admin/taobao/{id}', [AdminTaobaoController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_taobao_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_taobao/save', [AdminTaobaoController::class, 'save']);
        Route::post('/_admin/_taobao/save_remark', [AdminTaobaoController::class, 'saveRemark']);
        Route::post('/_admin/_taobao/save_cancel', [AdminTaobaoController::class, 'saveCancel']);
    });
    Route::match(['GET', 'POST'], '/_admin/_taobao/email_approved', [AdminTaobaoController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_taobao/email_cancel', [AdminTaobaoController::class, 'emailDirect']);
});
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_admin/_taobao/grid', [AdminTaobaoController::class, 'grid']);
    // เดิมตรวจ admin_taobao แต่ข้อความแจ้งเป็นของ admin_shop_cashback → ตรวจใน controller ให้ข้อความเหมือนเดิม
    Route::post('/_admin/_taobao/save_approved', [AdminTaobaoController::class, 'saveApproved']);
});
// ไฟล์เดิมตรวจสิทธิ์คนละตัวกับหน้าแม่
Route::middleware(['legacy.perm:taobao', 'legacy.ajax'])->group(function () {
    Route::post('/_admin/_taobao/load_calculator', [AdminTaobaoController::class, 'loadCalculator']);
    Route::post('/_admin/_taobao/load_new_line', [AdminTaobaoController::class, 'loadNewLine']);
});
Route::post('/_admin/_taobao/rollback', [AdminTaobaoController::class, 'rollback'])->middleware(['legacy.perm:admin_alipay', 'legacy.ajax']);
