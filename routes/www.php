<?php

use App\Http\Controllers\Www\WwwController;
use Illuminate\Support\Facades\Route;

/*
| เว็บหน้าแรก pack-cargo.net / www.pack-cargo.net (เดิม WordPress บน PHP 7.4 — ย้าย 2026-10-22) ผูกกับโดเมน ไม่ชนกับ route ของ portal
| ทุก path ทุก method ของโดเมนนี้เข้า WwwController (POST ไปหน้าเดิม WordPress ก็ตอบหน้าเดียวกับ GET) — route ของ portal จึงเข้าจากโดเมนนี้ไม่ได้
*/
foreach ((array) config('legacy.www.hosts', ['pack-cargo.net', 'www.pack-cargo.net']) as $host) {
    Route::domain($host)->group(function () {
        Route::any('/{any?}', [WwwController::class, 'show'])->where('any', '.*');
    });
}
