<?php

use App\Http\Controllers\Www\WwwController;
use Illuminate\Support\Facades\Route;

/*
| เว็บหน้าแรก jlcargoth.com / www.jlcargoth.com (ย้ายจาก PHP 7.4 แยก) — ผูกกับโดเมน ไม่ชนกับ route ของ portal
| URL เดิม: /, /index.php, /<หน้า>.php — และแบบไม่มี .php
*/
foreach ((array) config('legacy.www.hosts', ['jlcargoth.com', 'www.jlcargoth.com']) as $i => $host) {
    Route::domain($host)->group(function () use ($i) {
        Route::get('/', [WwwController::class, 'show'])->name('www.'.$i.'.home');
        Route::get('/index.php', [WwwController::class, 'show']);
        foreach (WwwController::PAGES as $page) {
            Route::get('/'.$page.'.php', [WwwController::class, 'show'])->defaults('page', $page);
            if ($page !== 'index') {
                Route::get('/'.$page, [WwwController::class, 'show'])->defaults('page', $page);
            }
        }
    });
}
