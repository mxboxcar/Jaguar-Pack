<?php

use App\Http\Controllers\Settings\Admin\ExchangeController;
use App\Http\Controllers\Settings\Admin\ProductTypeController;
use App\Http\Controllers\Settings\Admin\SettingController;
use App\Http\Controllers\Settings\Admin\ShippingTypeController;
use App\Http\Controllers\Settings\PriceController;
use App\Http\Controllers\Settings\PublicPageController;
use App\Http\Controllers\Settings\QuickSearchController;
use App\Http\Controllers\Settings\UploadController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: settings — _admin/_setting, _admin/_exchange, _admin/_product_type, _admin/_shipping_type,
|   get_price, get_resource, load_quick_search, gateway, index_close, system_upload(_2)
| ดูรายการ URL/ไฟล์เดิมใน docs/url-map.md และรายงานใน docs/modules/settings.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| สิทธิ์ = getPermission() ของไฟล์เดิม; ajax ทุกตัวเดิม require system_check_ajax.php → legacy.ajax ก่อนตรวจสิทธิ์
| grid.php เดิม exit() เงียบเมื่อไม่มีสิทธิ์ → ตรวจใน controller (legacy.auth ที่ route)
*/

// ---------- ตั้งค่าระบบ ----------
Route::get('/admin/setting', [SettingController::class, 'index'])->middleware('legacy.perm:admin_setting')->defaults('legacy_page', 'admin_setting');
// save.php เดิมไม่ตรวจสิทธิ์ (ช่องโหว่) → ใส่สิทธิ์ของหน้าแม่
Route::post('/_admin/_setting/save', [SettingController::class, 'save'])->middleware(['legacy.ajax', 'legacy.perm:admin_setting']);

// ---------- แลกเงินหยวน → บาท ----------
Route::middleware('legacy.perm:admin_exchange')->group(function () {
    Route::get('/admin/exchange', [ExchangeController::class, 'index'])->defaults('legacy_page', 'admin_exchange');
    Route::get('/admin/exchange/status/{status}', [ExchangeController::class, 'index'])->whereNumber('status')->defaults('legacy_page', 'admin_exchange');
    Route::get('/admin/exchange/{id}', [ExchangeController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_exchange_detail');
});
Route::post('/_admin/_exchange/grid', [ExchangeController::class, 'grid'])->middleware(['legacy.ajax', 'legacy.auth']);
Route::post('/_admin/_exchange/save_approved', [ExchangeController::class, 'saveApproved'])->middleware(['legacy.ajax', 'legacy.perm:admin_exchange']);
Route::post('/_admin/_exchange/cancel', [ExchangeController::class, 'cancel'])->middleware(['legacy.ajax', 'legacy.perm:admin_withdrawpoint']);

// ---------- ประเภทสินค้า ----------
Route::middleware('legacy.perm:admin_product_type')->group(function () {
    Route::get('/admin/product_type', [ProductTypeController::class, 'index'])->defaults('legacy_page', 'admin_product_type');
    Route::get('/admin/product_type/create', [ProductTypeController::class, 'form'])->defaults('legacy_page', 'admin_product_type_detail');
    Route::get('/admin/product_type/{id}', [ProductTypeController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_product_type_detail');
});
Route::post('/_admin/_product_type/grid', [ProductTypeController::class, 'grid'])->middleware(['legacy.ajax', 'legacy.auth']);
Route::middleware(['legacy.ajax', 'legacy.perm:admin_product_type'])->group(function () {
    Route::post('/_admin/_product_type/save', [ProductTypeController::class, 'save']);
    Route::post('/_admin/_product_type/delete', [ProductTypeController::class, 'delete']);
});

// ---------- ประเภทการจัดส่ง ----------
Route::middleware('legacy.perm:admin_shipping_type')->group(function () {
    Route::get('/admin/shipping_type', [ShippingTypeController::class, 'index'])->defaults('legacy_page', 'admin_shipping_type');
    Route::get('/admin/shipping_type/create', [ShippingTypeController::class, 'form'])->defaults('legacy_page', 'admin_shipping_type_detail');
    Route::get('/admin/shipping_type/{id}', [ShippingTypeController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_shipping_type_detail');
});
Route::post('/_admin/_shipping_type/grid', [ShippingTypeController::class, 'grid'])->middleware(['legacy.ajax', 'legacy.auth']);
Route::middleware(['legacy.ajax', 'legacy.perm:admin_shipping_type'])->group(function () {
    Route::post('/_admin/_shipping_type/save', [ShippingTypeController::class, 'save']);
    Route::post('/_admin/_shipping_type/save_listno', [ShippingTypeController::class, 'saveListno']);
    Route::post('/_admin/_shipping_type/delete', [ShippingTypeController::class, 'delete']);
    Route::post('/_admin/_shipping_type/delete_cal', [ShippingTypeController::class, 'deleteCal']);
    Route::post('/_admin/_shipping_type/load_cal_1', [ShippingTypeController::class, 'loadCal1']);
    Route::post('/_admin/_shipping_type/load_cal_2', [ShippingTypeController::class, 'loadCal2']);
    Route::post('/_admin/_shipping_type/save_new_price_by_kg', [ShippingTypeController::class, 'saveNewPriceByKg']);
    Route::post('/_admin/_shipping_type/save_new_price_by_cubic', [ShippingTypeController::class, 'saveNewPriceByCubic']);
});

// ---------- ajax ที่ layout ใช้ทุกหน้า ----------
// load_quick_search เดิมไม่ตรวจล็อกอิน (ไม่ล็อกอิน = ค้นแถว user = 0 ได้) → ต้องล็อกอิน
Route::post('/load_quick_search', QuickSearchController::class)->middleware(['legacy.ajax', 'legacy.auth']);
// get_price: สิทธิ์ view_bol ไม่มีใน role_detail → ไม่มีใครผ่าน (เหมือนเดิม)
Route::post('/get_price', PriceController::class)->middleware(['legacy.ajax', 'legacy.perm:view_bol']);

// ---------- อัปโหลดไฟล์ชั่วคราว (getupload*/dropper) — เดิมไม่ตรวจล็อกอิน ----------
Route::middleware(['legacy.ajax', 'legacy.auth'])->group(function () {
    Route::post('/system_upload', [UploadController::class, 'upload']);
    Route::post('/system_upload_2', [UploadController::class, 'upload2']);
});

// ---------- หน้าสาธารณะที่ไม่มีข้อมูล (PUBLIC ใน RouteSecurityTest) ----------
Route::get('/gateway', [PublicPageController::class, 'gateway']);
Route::get('/index_close', [PublicPageController::class, 'indexClose']);
Route::get('/get_resource', [PublicPageController::class, 'resource']);
