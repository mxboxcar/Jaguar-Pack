<?php

use App\Http\Controllers\Payment\Admin\AdminAlipayController;
use App\Http\Controllers\Payment\Admin\AdminCouponController;
use App\Http\Controllers\Payment\Admin\AdminPaymentController;
use App\Http\Controllers\Payment\Admin\AdminPaymentCreateController;
use App\Http\Controllers\Payment\AlipayController;
use App\Http\Controllers\Payment\PaymentController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: payment — _payment, _admin/_payment, _alipay, _admin/_alipay, _admin/_coupon (PACK) (+ กล่อง PIN: App\Domain\Payment\PinBox)
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/payment.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม; ไฟล์เดิมที่ไม่ตรวจสิทธิ์ใช้สิทธิ์ของหน้าแม่ (บันทึกในรายงาน)
| grid ajax ตรวจสิทธิ์ใน controller เพราะเดิมตอบว่างเมื่อไม่มีสิทธิ์ (ไม่ใช่ ErrorBox)
*/

// ---------- ลูกค้า: ชำระเงินค่าขนส่ง ----------
Route::middleware('legacy.perm:payment')->group(function () {
    Route::get('/payment', [PaymentController::class, 'index'])->defaults('legacy_page', 'payment');
    Route::get('/payment/create', [PaymentController::class, 'create'])->defaults('legacy_page', 'payment_create');
    Route::get('/payment/create/{random}', [PaymentController::class, 'createForm'])->where('random', '.+')->defaults('legacy_page', 'payment_detail_form');
    Route::get('/payment/status/{status}', [PaymentController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'payment');
    Route::get('/payment/{id}', [PaymentController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'payment_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_payment/load_create', [PaymentController::class, 'loadCreate']);
        Route::post('/_payment/load_create_sum', [PaymentController::class, 'loadCreateSum']);
        Route::post('/_payment/load_select_lot', [PaymentController::class, 'loadSelectLot']);
        Route::post('/_payment/save_create', [PaymentController::class, 'saveCreate'])->middleware('legacy.once');
        Route::post('/_payment/save_submit_payment', [PaymentController::class, 'saveSubmitPayment'])->middleware('legacy.once');
        Route::post('/_payment/save_delete', [PaymentController::class, 'saveDelete']);
        // เดิมไม่ตรวจสิทธิ์/ล็อกอิน → payment
        Route::post('/_payment/load_shipping_price', [PaymentController::class, 'loadShippingPrice']);
        // PACK: ราคาส่วนลดของคูปอง (ไม่มีใน SR/jl)
        Route::post('/_payment/load_discoutcode', [PaymentController::class, 'loadDiscoutcode']);
        // PACK ไม่มี _payment/load_vat, load_receipt_address, save_create_receiptaddress, save_deleteaddress,
        // save_deletereceiptaddress (ที่อยู่ใบกำกับของ jl) → ไม่มี route (404 → หน้าแรก)
    });
});
Route::middleware(['legacy.auth', 'legacy.ajax'])->group(function () {
    Route::post('/_payment/grid', [PaymentController::class, 'grid']);
});
// _payment/save.php เดิม = สำเนาของ _refill/save.php — PACK ตรวจสิทธิ์ payment ก่อน แล้วจึง refill (ต้องมีทั้งสอง)
Route::post('/_payment/save', [PaymentController::class, 'save'])->middleware(['legacy.perm:payment', 'legacy.perm:refill', 'legacy.ajax', 'legacy.once']);
// iframe ตัวอย่างใบปะหน้า: JS เดิม POST ก่อนแล้วเปิดด้วย GET; ไม่มีสิทธิ์ = "ERROR : access denied" (ตรวจใน controller)
Route::match(['GET', 'POST'], '/_payment/view_example', [PaymentController::class, 'viewExample'])->middleware('legacy.auth');

// ---------- แอดมิน: ชำระเงินค่าขนส่ง ----------
Route::middleware('legacy.perm:admin_payment')->group(function () {
    Route::get('/admin/payment', [AdminPaymentController::class, 'index'])->defaults('legacy_page', 'admin_payment');
    Route::get('/admin/payment/status/{status}', [AdminPaymentController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_payment');
    Route::get('/admin/payment/{id}', [AdminPaymentController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_payment_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_payment/save_approved', [AdminPaymentController::class, 'saveApproved']);
        Route::post('/_admin/_payment/save_delete', [AdminPaymentController::class, 'saveDelete']);
        Route::post('/_admin/_payment/save_deletenopush', [AdminPaymentController::class, 'saveDeleteNoPush']);
        // PACK: เปิด/ปิด VAT ของบิล (สูตร VAT แบบ PACK — ไม่มีหัก ณ ที่จ่าย 3% แบบ jl)
        Route::post('/_admin/_payment/save_vat', [AdminPaymentController::class, 'saveVat']);
        Route::post('/_admin/_payment/load_shipping_price', [AdminPaymentController::class, 'loadShippingPrice']);
    });
    // ไฟล์ include ของ save_approved/save_delete ที่เดิมเปิดตรงได้ (ไม่ต้องล็อกอิน) — ที่นี่ต้องมีสิทธิ์ และไม่ส่งอะไร
    Route::match(['GET', 'POST'], '/_admin/_payment/email_approved', [AdminPaymentController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_payment/email_cancel', [AdminPaymentController::class, 'emailDirect']);
});
Route::post('/_admin/_payment/grid', [AdminPaymentController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
// เดิมตรวจ admin_change_sender (ไม่มีใน role_detail = ไม่มีใครใช้ได้ แม้แอดมิน)
Route::post('/_admin/_payment/save_new_sender', [AdminPaymentController::class, 'saveNewSender'])->middleware(['legacy.perm:admin_change_sender', 'legacy.ajax']);

// ---------- แอดมิน: สร้างบิลให้ลูกค้า ----------
Route::middleware('legacy.perm:admin_payment_create')->group(function () {
    Route::get('/admin/payment/create', [AdminPaymentCreateController::class, 'create'])->defaults('legacy_page', 'admin_payment_create');
    Route::get('/admin/payment/create/{user_id}', [AdminPaymentCreateController::class, 'create'])->whereNumber('user_id')->defaults('legacy_page', 'admin_payment_create');
    Route::get('/admin/payment/create/{user_id}/{random}', [AdminPaymentCreateController::class, 'createForm'])->whereNumber('user_id')->where('random', '.+')->defaults('legacy_page', 'admin_payment_create_form');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_payment/load_autocomplete_user', [AdminPaymentCreateController::class, 'loadAutocompleteUser']);
        Route::post('/_admin/_payment/load_user_by_id', [AdminPaymentCreateController::class, 'loadUserById']);
        Route::post('/_admin/_payment/load_create', [AdminPaymentCreateController::class, 'loadCreate']);
        Route::post('/_admin/_payment/save_create', [AdminPaymentCreateController::class, 'saveCreate'])->middleware('legacy.once');
        // เดิมไม่ตรวจสิทธิ์เลย (ใครก็สร้างบิล/ตัดเงินลูกค้าคนไหนก็ได้) → admin_payment_create
        Route::post('/_admin/_payment/save_submit_payment', [AdminPaymentCreateController::class, 'saveSubmitPayment'])->middleware('legacy.once');
    });
});
// เดิมตรวจ admin_payment_create แต่ข้อความแจ้งใช้ชื่อ admin_payment_createpayment → ตรวจใน controller
Route::post('/_admin/_payment/load_select_lot', [AdminPaymentCreateController::class, 'loadSelectLot'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- ลูกค้า: เติมอลีเพล ----------
Route::middleware('legacy.perm:alipay')->group(function () {
    Route::get('/alipay', [AlipayController::class, 'index'])->defaults('legacy_page', 'alipay');
    Route::get('/alipay/create', [AlipayController::class, 'form'])->defaults('legacy_page', 'alipay_detail');
    Route::get('/alipay/status/{status}', [AlipayController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'alipay');
    Route::get('/alipay/{id}', [AlipayController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'alipay_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_alipay/save', [AlipayController::class, 'save'])->middleware('legacy.once');
    });
});
// PACK: _alipay/cancel.php เดิมถูก comment ทั้งไฟล์ (ลูกค้ายกเลิกเองไม่ได้ — หน้า alipay/{id} ไม่มีปุ่ม) → ตอบว่าง ไม่ทำอะไร
Route::post('/_alipay/cancel', [AlipayController::class, 'cancel'])->middleware('legacy.auth');
Route::post('/_alipay/grid', [AlipayController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: เติมอลีเพล ----------
Route::middleware('legacy.perm:admin_alipay')->group(function () {
    Route::get('/admin/alipay', [AdminAlipayController::class, 'index'])->defaults('legacy_page', 'admin_alipay');
    Route::get('/admin/alipay/create', [AdminAlipayController::class, 'form'])->defaults('legacy_page', 'admin_alipay_detail');
    Route::get('/admin/alipay/status/{status}', [AdminAlipayController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_alipay');
    Route::get('/admin/alipay/{id}', [AdminAlipayController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_alipay_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_alipay/save', [AdminAlipayController::class, 'save']);
        Route::post('/_admin/_alipay/cancel', [AdminAlipayController::class, 'cancel']);
        Route::post('/_admin/_alipay/rollback', [AdminAlipayController::class, 'rollback']);
    });
    // email_cancel.php เดิมถูกเรียกด้วย ajax หลังยกเลิก แต่ใช้ $email_id ที่ไม่ได้กำหนด → ไม่เคยส่งได้จริง; email_success.php = include
    Route::match(['GET', 'POST'], '/_admin/_alipay/email_cancel', [AdminAlipayController::class, 'emailDirect']);
    Route::match(['GET', 'POST'], '/_admin/_alipay/email_success', [AdminAlipayController::class, 'emailDirect']);
});
Route::post('/_admin/_alipay/grid', [AdminAlipayController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);

// ---------- แอดมิน: คูปองส่วนลด (PACK เท่านั้น — _admin/_coupon) ----------
// SR: _admin/_receipt (admin/receipt) ไม่มีใน PACK → ไม่มี route (404 → หน้าแรก)
Route::middleware('legacy.perm:admin_coupon')->group(function () {
    Route::get('/admin/coupon', [AdminCouponController::class, 'index'])->defaults('legacy_page', 'admin_coupon');
    // RewriteRule เดิมมี admin/coupon/status/N (ส่ง status ให้ index.php ที่ไม่ได้ใช้)
    Route::get('/admin/coupon/status/{status}', [AdminCouponController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_coupon');
    Route::get('/admin/coupon/create', [AdminCouponController::class, 'form'])->defaults('legacy_page', 'admin_coupon_detail');
    Route::get('/admin/coupon/{id}', [AdminCouponController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_coupon_detail');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_coupon/save', [AdminCouponController::class, 'save'])->middleware('legacy.once');
    });
});
// เดิมไม่มีสิทธิ์ = exit (ตอบว่าง) → ตรวจใน controller
Route::post('/_admin/_coupon/grid', [AdminCouponController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
// delete.php เดิม = สำเนาของ _admin/_product_type/delete.php (ตรวจสิทธิ์ admin_product_type, ลบ product_type แทนคูปอง) → ตอบสำเร็จโดยไม่ลบอะไร
Route::post('/_admin/_coupon/delete', [AdminCouponController::class, 'delete'])->middleware(['legacy.perm:admin_product_type', 'legacy.ajax']);
