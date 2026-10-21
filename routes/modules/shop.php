<?php

use App\Http\Controllers\Shop\Admin\MallStockController;
use App\Http\Controllers\Shop\ApiController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\TaobaoController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: shop — _cart, _taobao, _api, _admin/_mallstock
| (PACK เหมือน SR: ไม่มีร้านค้า _mall/_admin/_mall/_mall_category; _admin/_mallstock = สำเนา _admin/_balance)
| ไฟล์ที่ PACK ไม่มี = ไม่ทำ route (404 → หน้าแรก): ของ jl _cart/cart, _cart/product_2, _taobao/delete, _api/updateImagePrefix;
| ของ SR _cart/save_takeapic, _findproduct/*, _api/load_product_v2, _api/load_product_v7, _api/send_sms, _api/get_order_number_v3
| _cart/product_bk.php ของ PACK = สำเนาสำรองที่ไม่มีอะไรลิงก์ → ไม่ทำ
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
| รายละเอียด/สิ่งที่ต่างจากเดิม: docs/modules/shop.md
|
| สิทธิ์: ใช้ชื่อโมดูลเดียวกับที่ไฟล์เดิมเรียก getPermission(...) (เช่น _admin/_mallstock/* → admin_balance — คงไว้ตามเดิม)
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
        Route::post('/_cart/save_checkout', [CartController::class, 'saveCheckout'])->middleware('legacy.once');
    });
});
Route::middleware('legacy.auth')->group(function () {
    // เดิมไม่มีสิทธิ์ = getPermissionAlert_Page (<script>) ในผล ajax
    Route::post('/_cart/load_choose_product', [CartController::class, 'loadChooseProduct'])->middleware('legacy.ajax');
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
        Route::post('/_taobao/save', [TaobaoController::class, 'save'])->middleware('legacy.once');
    });
});

// ================================================================ แอดมิน: _admin/_mallstock (เหมือน SR) — getPermission('admin_balance')
// ไม่มีร้านค้า: ทั้งโฟลเดอร์เป็นสำเนาของ _admin/_balance (grid รุ่นเก่า, load_amount/save_amount เหมือนทุกตัวอักษร)
// หน้า admin/mall_stock ไม่มีใน content.php ของ PACK/SR → dead (ไม่ทำ route) — endpoint เปิดตรงด้วย POST ได้แบบเดิม
// load/save ทำงานผ่าน AdminBalanceController (กฎ/การแก้เรื่องเงินชุดเดียวกับ _admin/_balance — ดู MallStockController)
Route::post('/_admin/_mallstock/grid', [MallStockController::class, 'grid'])->middleware(['legacy.auth', 'legacy.ajax']);
Route::middleware(['legacy.perm:admin_balance', 'legacy.ajax'])->group(function () {
    Route::post('/_admin/_mallstock/load_amount', [MallStockController::class, 'loadAmount']);
    Route::post('/_admin/_mallstock/save_amount', [MallStockController::class, 'saveAmount'])->middleware('legacy.once');
});

// ================================================================ _api
Route::middleware('legacy.perm:order')->group(function () {
    // ดึงสินค้าจากลิงก์ (dev: STUB ไม่ยิง crawler ภายนอก — config legacy.external.enabled)
    Route::post('/_api/load_product', [ApiController::class, 'loadProduct']);
});
Route::middleware('legacy.auth')->group(function () {
    // เดิม (PACK/jl) ไม่ตรวจล็อกอิน → ต้องล็อกอิน (แบบ SR checkUser())
    Route::post('/_api/blockblob_ajax', [ApiController::class, 'blockblobAjax']);
    // ไฟล์ไลบรารี / ไฟล์ที่เดิม fatal เสมอ — เปิดตรงได้แต่ไม่มีผล
    Route::match(['get', 'post'], '/_api/blockblob', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/crawl_guard', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/image_shrink', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/useragent', [ApiController::class, 'library']);
    Route::match(['get', 'post'], '/_api/load_product_v5', [ApiController::class, 'loadProductV5']);
});
// token API ของ crawler/cron (สาธารณะเหมือนเดิม ตรวจ token จาก .env — อยู่ใน PUBLIC ของ RouteSecurityTest)
Route::get('/_api/get_order_number_v2', [ApiController::class, 'getOrderNumberV2']);
Route::get('/_api/submit_order_number_v2', [ApiController::class, 'submitOrderNumberV2']);
Route::match(['get', 'post'], '/_api/unban', [ApiController::class, 'unban']);
