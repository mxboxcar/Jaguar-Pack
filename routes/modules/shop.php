<?php

use App\Http\Controllers\Shop\Admin\MallAdminController;
use App\Http\Controllers\Shop\Admin\MallCategoryController;
use App\Http\Controllers\Shop\Admin\MallStockController;
use App\Http\Controllers\Shop\ApiController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\MallController;
use App\Http\Controllers\Shop\TaobaoController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: shop — _cart, _taobao, _api, _mall, _admin/_mall, _admin/_mall_category, _admin/_mallstock
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
| รายละเอียด/สิ่งที่ต่างจากเดิม: docs/modules/shop.md
|
| สิทธิ์: ใช้ชื่อโมดูลเดียวกับที่ไฟล์เดิมเรียก getPermission(...) (บางไฟล์เดิมใช้ชื่อแปลก เช่น _mall/grid → refill,
| _admin/_mall/delete → admin_product_type, _admin/_mall_category/form → admin_user_type — คงไว้ตามเดิม)
| ไฟล์ที่เดิมตรวจสิทธิ์แล้ว "ไม่ตอบอะไร" (exit เงียบ) หรือตอบ <script> ใน ajax: ตรวจใน controller ด้วย legacy.auth
*/

// ================================================================ ตะกร้า (_cart) — getPermission('order')
Route::middleware('legacy.perm:order')->group(function () {
    Route::get('/cart/checkout', [CartController::class, 'checkout'])->defaults('legacy_page', 'checkout');
    Route::get('/cart/checkout/{ids}', [CartController::class, 'checkout'])->where('ids', '.+')->defaults('legacy_page', 'checkout');
    // ^cart/product/([^/]+)$ ?page=checkout_product&url=$1 [QSA] — ไม่มี segment = มาจาก ?page=checkout_product&l=... (routes/legacy.php)
    Route::get('/cart/product/{url?}', [CartController::class, 'product'])->defaults('legacy_page', 'checkout_product')
        ->name('page.checkout_product');

    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_cart/load_cart', [CartController::class, 'loadCart']);
        Route::post('/_cart/load_full_cart', [CartController::class, 'loadFullCart']);
        Route::post('/_cart/save_product', [CartController::class, 'saveProduct']);
        Route::post('/_cart/delete_cart', [CartController::class, 'deleteCart']);
        Route::post('/_cart/delete_carts', [CartController::class, 'deleteCarts']);
        Route::post('/_cart/save_amount', [CartController::class, 'saveAmount']);
        Route::post('/_cart/save_craft', [CartController::class, 'saveCraft']);
        Route::post('/_cart/save_qc', [CartController::class, 'saveQc']);
        Route::post('/_cart/save_checkout', [CartController::class, 'saveCheckout']);
    });
});
Route::middleware('legacy.auth')->group(function () {
    // เดิมไม่มีสิทธิ์ = getPermissionAlert_Page (<script>) ในผล ajax
    Route::post('/_cart/load_choose_product', [CartController::class, 'loadChooseProduct'])->middleware('legacy.ajax');
    // ชิ้นส่วน HTML ที่เปิดตรงได้ (เดิมไม่ต้องล็อกอิน — ไม่มีหน้าไหนใช้แล้ว)
    Route::match(['get', 'post'], '/_cart/cart', [CartController::class, 'cartFragment']);
});

// ================================================================ ฝากจ่าย Taobao (_taobao) — getPermission('taobao')
Route::middleware('legacy.auth')->group(function () {
    // index ตรวจสิทธิ์ใน view (เดิมแสดงหัวหน้าแล้วค่อยเด้ง), grid ไม่มีสิทธิ์ = ตอบว่าง
    Route::get('/taobao', [TaobaoController::class, 'index'])->defaults('legacy_page', 'taobao');
    Route::post('/_taobao/grid', [TaobaoController::class, 'grid'])->middleware('legacy.ajax');
});
Route::middleware('legacy.perm:taobao')->group(function () {
    Route::get('/taobao/create', [TaobaoController::class, 'form'])->defaults('legacy_page', 'taobao_detail');
    Route::get('/taobao/{id}', [TaobaoController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'taobao_detail');
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_taobao/load_new_line', [TaobaoController::class, 'loadNewLine']);
        Route::post('/_taobao/load_calculator', [TaobaoController::class, 'loadCalculator']);
        Route::post('/_taobao/save_remark', [TaobaoController::class, 'saveRemark']);
        Route::post('/_taobao/save', [TaobaoController::class, 'save']);
    });
});
// _taobao/delete.php เดิม fatal ทุกครั้ง (require path ผิด) — คงผลเดิม 500 (ต้องล็อกอิน)
Route::post('/_taobao/delete', [TaobaoController::class, 'delete'])->middleware('legacy.auth');

// ================================================================ ร้านค้าในระบบ (_mall)
// .htaccess เดิมเรียง ^mall/(.+)$ (ค้นหา) ก่อน mall/create, mall/cart, mall/{id} — ที่นี่ให้ path เฉพาะมาก่อน (ดู docs/modules/shop.md)
Route::middleware('legacy.auth')->group(function () {
    Route::get('/mall', [MallController::class, 'index'])->defaults('legacy_page', 'mall');
    Route::get('/mall/create', [MallController::class, 'detail'])->defaults('legacy_page', 'mall_detail');
    Route::get('/mall/cart', [MallController::class, 'formCart'])->defaults('legacy_page', 'mall_cart');
    Route::get('/mall/{id}', [MallController::class, 'detail'])->whereNumber('id')->defaults('legacy_page', 'mall_detail');
    Route::get('/mall/{search}', [MallController::class, 'index'])->where('search', '.+')->defaults('legacy_page', 'mall');
    // _mall/grid เดิมตรวจสิทธิ์ refill แล้วตอบว่าง — ตรวจใน controller
    Route::post('/_mall/grid', [MallController::class, 'grid'])->middleware('legacy.ajax');
});
Route::middleware(['legacy.perm:mall', 'legacy.ajax'])->group(function () {
    // load_detail / addtocart เดิมไม่ตรวจสิทธิ์ (addtocart ตัดเงินได้) → ต้องมีสิทธิ์ mall
    Route::post('/_mall/load_detail', [MallController::class, 'loadDetail']);
    Route::post('/_mall/addtocart', [MallController::class, 'addToCart']);
    Route::post('/_mall/delete_cart', [MallController::class, 'deleteCart']);
    Route::post('/_mall/save', [MallController::class, 'save']);
});

// ================================================================ แอดมิน: สินค้าร้านค้า (_admin/_mall) — admin_mall
Route::middleware('legacy.perm:admin_mall')->group(function () {
    Route::get('/admin/mall', [MallAdminController::class, 'index'])->defaults('legacy_page', 'admin_mall');
    Route::get('/admin/mall/create', [MallAdminController::class, 'form'])->defaults('legacy_page', 'admin_mall_detail');
    Route::get('/admin/mall/{id}', [MallAdminController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_mall_detail');
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_mall/grid', [MallAdminController::class, 'grid']);
        Route::post('/_admin/_mall/load_detail', [MallAdminController::class, 'loadDetail']);
        Route::post('/_admin/_mall/load_sizecolor', [MallAdminController::class, 'loadSizeColor']);
        Route::post('/_admin/_mall/load_stock', [MallAdminController::class, 'loadStock']);
        Route::post('/_admin/_mall/save', [MallAdminController::class, 'save']);
        Route::post('/_admin/_mall/save_sizecolor', [MallAdminController::class, 'saveSizeColor']);
        Route::post('/_admin/_mall/save_delsizecolor', [MallAdminController::class, 'saveDelSizeColor']);
        Route::post('/_admin/_mall/save_isshow', [MallAdminController::class, 'saveIsShow']);
    });
});
// delete.php เดิมตรวจ admin_product_type (ไม่ใช่ admin_mall) — คงไว้
Route::post('/_admin/_mall/delete', [MallAdminController::class, 'delete'])->middleware(['legacy.perm:admin_product_type', 'legacy.ajax']);

// ================================================================ แอดมิน: หมวดหมู่ร้านค้า (_admin/_mall_category)
// admin_mall_category ไม่มี role ใดถือ (เว็บเดิมไม่มีใครเข้าได้) → Laravel ได้จาก fallback ← admin_mall (CONVENTIONS 3.1)
// form/delete เดิมเป็นสำเนาของ user_type (admin_user_type)
Route::middleware('legacy.perm:admin_mall_category')->group(function () {
    Route::get('/admin/mall_category', [MallCategoryController::class, 'index'])->defaults('legacy_page', 'admin_mall_category');
    Route::post('/_admin/_mall_category/grid', [MallCategoryController::class, 'grid'])->middleware('legacy.ajax');
    Route::post('/_admin/_mall_category/save', [MallCategoryController::class, 'save'])->middleware('legacy.ajax');
});
Route::middleware('legacy.perm:admin_user_type')->group(function () {
    Route::get('/admin/mall_category/create', [MallCategoryController::class, 'form'])->defaults('legacy_page', 'admin_mall_category_detail');
    Route::get('/admin/mall_category/{id}', [MallCategoryController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_mall_category_detail');
    Route::post('/_admin/_mall_category/delete', [MallCategoryController::class, 'delete'])->middleware('legacy.ajax');
});

// ================================================================ แอดมิน: สต๊อกร้านค้า (_admin/_mallstock) — admin_mall_stock
Route::middleware('legacy.perm:admin_mall_stock')->group(function () {
    Route::get('/admin/mall_stock', [MallStockController::class, 'index'])->defaults('legacy_page', 'admin_mall_stock');
    Route::post('/_admin/_mallstock/grid', [MallStockController::class, 'grid'])->middleware('legacy.ajax');
});
// load_amount / save_amount เดิมเป็นสำเนาของ _admin/_balance (สิทธิ์ admin_balance, เขียนแถว balance ใดก็ได้)
// แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): สิทธิ์ admin_mall_stock และแตะเฉพาะตาราง mall_stock (ดู MallStockController)
Route::middleware(['legacy.perm:admin_mall_stock', 'legacy.ajax'])->group(function () {
    Route::post('/_admin/_mallstock/load_amount', [MallStockController::class, 'loadAmount']);
    Route::post('/_admin/_mallstock/save_amount', [MallStockController::class, 'saveAmount']);
});

// ================================================================ _api
Route::middleware('legacy.perm:order')->group(function () {
    // ดึงสินค้าจากลิงก์ (dev: STUB ไม่ยิง crawler ภายนอก — config legacy.external.enabled)
    Route::post('/_api/load_product', [ApiController::class, 'loadProduct']);
    // ตัวดึงรุ่นเก่า ไม่มีหน้าไหนเรียก (เดิมไม่ต้องล็อกอิน)
    Route::post('/_api/load_product_v2', [ApiController::class, 'loadProductV2']);
});
Route::middleware('legacy.auth')->group(function () {
    // เดิมไม่ตรวจล็อกอิน (ใครก็สั่งอัปไฟล์ใน upload/temp ขึ้นที่เก็บถาวรได้)
    Route::post('/_api/blockblob_ajax', [ApiController::class, 'blockblobAjax']);
    // ไฟล์ไลบรารี / ไฟล์ที่เดิม fatal เสมอ — เปิดตรงได้แต่ไม่มีผล
    Route::match(['get', 'post'], '/_api/blockblob', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/crawl_guard', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/image_shrink', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/useragent', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/load_product_v5', [ApiController::class, 'loadProductV5']);
    Route::match(['get', 'post'], '/_api/load_product_v7', [ApiController::class, 'loadProductV7']);
    Route::match(['get', 'post'], '/_api/send_sms', [ApiController::class, 'sendSms']);
});
// token API ของ crawler/cron (สาธารณะเหมือนเดิม ตรวจ token จาก .env — อยู่ใน PUBLIC ของ RouteSecurityTest)
Route::get('/_api/get_order_number_v2', [ApiController::class, 'getOrderNumberV2']);
Route::get('/_api/get_order_number_v3', [ApiController::class, 'getOrderNumberV3']);
Route::get('/_api/submit_order_number_v2', [ApiController::class, 'submitOrderNumberV2']);
Route::match(['get', 'post'], '/_api/updateImagePrefix', [ApiController::class, 'updateImagePrefix']);
Route::match(['get', 'post'], '/_api/unban', [ApiController::class, 'unban']);
