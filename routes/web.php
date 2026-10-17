<?php

use App\Http\Controllers\LegacyTrackingBenchController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ต้นแบบ: ยังไม่มีระบบ login ร่วมกับเว็บเดิม จึงเปิดเฉพาะตอนไม่ใช่ production (vhost ฟังแค่ 127.0.0.1)
if (! app()->isProduction()) {
    Route::get('/dev/tracking/{userId}', [TrackingController::class, 'index'])
        ->whereNumber('userId')
        ->name('dev.tracking');

    // วัดผลเทียบโค้ดเดิม: query ชุดเดียวกับ _tracking/grid.php ทุกตัว
    Route::get('/dev/tracking-legacy/{userId}', [LegacyTrackingBenchController::class, 'index'])
        ->whereNumber('userId')
        ->name('dev.tracking-legacy');
}
