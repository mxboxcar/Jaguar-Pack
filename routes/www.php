<?php

use App\Http\Controllers\Www\WwwController;
use Illuminate\Support\Facades\Route;

/*
| เว็บหน้าแรก jlcargoth.com / www.jlcargoth.com (ย้ายจาก PHP 7.4 แยก) — ผูกกับโดเมน ไม่ชนกับ route ของ portal
| URL ใหม่ (เจ้าของสั่ง 2026-10-19): /, /how-to, /how-to/<หัวข้อ>, /contact, /terms
| URL เดิม (/index.php, /how_to_order.php, /page_02.php, ... และแบบไม่มี .php) → 301 ไป URL ใหม่
*/
foreach ((array) config('legacy.www.hosts', ['jlcargoth.com', 'www.jlcargoth.com']) as $i => $host) {
    Route::domain($host)->group(function () use ($i) {
        foreach (WwwController::URLS as $page => $url) {
            $route = Route::get($url, [WwwController::class, 'show'])->defaults('page', $page);
            if ($i === 0) {
                $route->name('www.'.$page);
            }
            $old = $page === 'index' ? ['/index.php'] : ['/'.$page.'.php', '/'.$page];
            foreach ($old as $o) {
                Route::get($o, [WwwController::class, 'legacyRedirect'])->defaults('page', $page);
            }
        }
    });
}
