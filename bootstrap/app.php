<?php

use App\Http\Middleware\LegacyAjaxOnly;
use App\Http\Middleware\LegacyAuthenticate;
use App\Http\Middleware\LegacyCsrf;
use App\Http\Middleware\LegacyJail;
use App\Http\Middleware\LegacyPermission;
use App\Http\Middleware\LegacySessionMiddleware;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // routes/modules/*.php = 1 ไฟล์ต่อ 1 กลุ่ม (โหลดอัตโนมัติ เรียงตามชื่อไฟล์) อยู่ใต้ middleware group "legacy"
        then: function () {
            Route::middleware('legacy')->group(base_path('routes/legacy.php'));
            foreach (glob(base_path('routes/modules/*.php')) ?: [] as $file) {
                Route::middleware('legacy')->group($file);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // cookie ของเว็บเดิมเป็นค่าดิบ ต้องไม่ถูก Laravel เข้ารหัส/ถอดรหัส
        $middleware->encryptCookies(except: [
            'PHPSESSID', 'jlcargo', 'jlcargo-role', 'jlcargo-lang', 'jlcargo-menu',
            'jl-cargo-admin-menu', 'jlcargo-cart', 'jlcargo-portal',
        ]);

        // group สำหรับทุกหน้าที่พอร์ตจากเว็บเดิม: ไม่ใช้ session/CSRF token ของ Laravel
        // (session = sessions_v2 ของเว็บเดิม, CSRF = ตรวจ Origin/Referer ใน LegacyCsrf)
        $middleware->group('legacy', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            LegacySessionMiddleware::class,
            LegacyJail::class,
            LegacyCsrf::class,
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'legacy.auth' => LegacyAuthenticate::class,
            'legacy.perm' => LegacyPermission::class,
            'legacy.ajax' => LegacyAjaxOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
