<?php

namespace App\Http\Middleware;

use App\Support\Legacy\Legacy;
use App\Support\SubmitGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * legacy.once — กันกดซ้ำ/ส่งซ้อนของ endpoint สร้างรายการ (ดู App\Support\SubmitGuard)
 *   legacy.once            = lock ต่อผู้ใช้+route + หน้าต่างกัน payload ซ้ำ
 *   legacy.once:lock       = lock อย่างเดียว (ส่ง payload เดิมซ้ำได้โดยตั้งใจ)
 *   legacy.once:html       = คำตอบปฏิเสธเป็น <script>ErrorBox(...)</script> (หน้าเว็บแทรกผลเป็น HTML ไม่ได้ eval)
 * ไม่มีผู้ใช้ (ยังไม่ login) = ปล่อยผ่าน ให้ middleware/controller เดิมตอบแบบเดิม
 */
class LegacySubmitOnce
{
    public function handle(Request $request, Closure $next, string ...$options): Response
    {
        $uid = Legacy::userId();
        if ($uid === null) {
            return $next($request);
        }
        $html = in_array('html', $options, true);
        $action = $request->route()?->uri() ?? $request->path();

        return SubmitGuard::run(
            $uid,
            $action,
            SubmitGuard::payloadHash($request),
            ! in_array('lock', $options, true),
            fn (string $text) => response(SubmitGuard::refusal($text, $html)),
            fn () => $next($request),
        );
    }
}
