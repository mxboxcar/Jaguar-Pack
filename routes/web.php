<?php

use App\Http\Controllers\LegacyTrackingBenchController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

/*
| ไฟล์นี้อยู่ใต้ middleware group "web" (session/CSRF ของ Laravel) — ใช้เฉพาะหน้า dev ที่ไม่เกี่ยวกับเว็บเดิม
| login/logout/ภาษา อยู่ที่ routes/legacy.php, หน้าของแต่ละกลุ่มอยู่ที่ routes/modules/<group>.php
| (ทั้งสองแบบอยู่ใต้ group "legacy" = session เว็บเดิม)
*/

// ต้นแบบเดิม: เปิดเฉพาะตอนไม่ใช่ production (vhost ฟังแค่ 127.0.0.1)
if (! app()->isProduction()) {
    Route::get('/dev/tracking/{userId}', [TrackingController::class, 'index'])
        ->whereNumber('userId')
        ->name('dev.tracking');

    // วัดผลเทียบโค้ดเดิม: query ชุดเดียวกับ _tracking/grid.php ทุกตัว
    Route::get('/dev/tracking-legacy/{userId}', [LegacyTrackingBenchController::class, 'index'])
        ->whereNumber('userId')
        ->name('dev.tracking-legacy');
}
