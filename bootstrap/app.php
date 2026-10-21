<?php

use App\Http\Middleware\LegacyAjaxOnly;
use App\Http\Middleware\LegacyAuthenticate;
use App\Http\Middleware\LegacyCsrf;
use App\Http\Middleware\LegacyJail;
use App\Http\Middleware\LegacyOwnUserOrPermission;
use App\Http\Middleware\LegacyPermission;
use App\Http\Middleware\LegacySessionMiddleware;
use App\Http\Middleware\LegacySubmitOnce;
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
            'PHPSESSID', 'srshippingsystem', 'srshippingsystem-role', 'srshippingsystem-lang', 'srshippingsystem-menu',
            'srshippingsystem-admin-menu', 'srshippingsystem-cart', 'srshippingsystem-portal',
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

        // เว็บเดิมไม่ trim / ไม่แปลง '' เป็น null — ค่าที่ LegacyInput อ่านต้องเป็นค่าดิบ (เช่น "abc " หรือ "|1|| " ต้องลง DB/แสดงผลเหมือนเดิม)
        $middleware->remove([
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        $middleware->alias([
            'legacy.auth' => LegacyAuthenticate::class,
            'legacy.auth_or_signup' => \App\Http\Middleware\LegacyAuthOrSignupUpload::class,
            'legacy.perm' => LegacyPermission::class,
            'legacy.ajax' => LegacyAjaxOnly::class,
            'legacy.own' => LegacyOwnUserOrPermission::class,
            'legacy.once' => LegacySubmitOnce::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // เว็บเดิม: nginx error_page 404 → 302 กลับหน้าแรก (ไม่มีหน้า 404) — เจ้าของสั่งให้คงแบบเดิม (2026-10-19)
        // ใช้กับ 404 และ method ไม่ตรง route (เช่น GET ไปที่ grid ที่รับแค่ POST) · เก็บ log ไว้ตามหาหน้าที่ขาด
        $exceptions->dontReport([
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        ]);
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            $status = $e->getStatusCode();
            if ($status !== 404 && $status !== 405) {
                return null;
            }
            \Illuminate\Support\Facades\Log::warning('legacy: '.$status.' redirected to home', [
                'method' => $request->method(), 'path' => '/'.ltrim($request->path(), '/'),
            ]);

            return redirect()->to(legacy_href());
        });
    })->create();
