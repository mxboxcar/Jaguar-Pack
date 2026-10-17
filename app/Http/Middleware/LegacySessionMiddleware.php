<?php

namespace App\Http\Middleware;

use App\Support\Legacy\LayoutData;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacySession;
use App\Support\Legacy\Lookup;
use App\Support\Legacy\Notify;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * เปิด session ของเว็บเดิม (PHPSESSID → sessions_v2) ก่อน controller, เขียนกลับ + ต่ออายุ cookie หลัง controller
 * ตั้ง locale ของ Laravel ตาม cookie jlcargo-lang ด้วย
 */
class LegacySessionMiddleware
{
    public function __construct(private LegacySession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        Lookup::flush();
        LayoutData::flush();
        Notify::flush();
        app()->setLocale(Legacy::lang());
        app()->instance('legacy.mobile', (new \Mobile_Detect($request->server->all(), $request->userAgent()))->isMobile());
        $this->session->start($request);

        $response = $next($request);

        $this->session->save();
        if ($cookie = $this->session->cookie()) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
