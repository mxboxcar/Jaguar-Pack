<?php

use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: shop — ดูรายการ URL/ไฟล์เดิมที่กลุ่มนี้รับผิดชอบใน docs/url-map.md
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
|
| ตัวอย่าง:
|   Route::middleware('legacy.auth')->group(function () {                 // หน้าลูกค้า
|       Route::get('/tracking', [TrackingController::class, 'index'])->defaults('legacy_page', 'tracking');
|       Route::post('/_tracking/grid', [TrackingController::class, 'grid'])->middleware('legacy.ajax');
|   });
|   Route::middleware('legacy.perm:admin_tracking')->group(function () {   // หน้าแอดมิน
|       Route::get('/admin/tracking', [AdminTrackingController::class, 'index'])->defaults('legacy_page', 'admin_tracking');
|   });
*/
