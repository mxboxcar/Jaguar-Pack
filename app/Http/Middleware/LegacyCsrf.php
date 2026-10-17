<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * legacy.csrf — กัน CSRF แบบไม่ต้องมี token (JS เดิมไม่ส่ง token และเราไม่ใช้ session ของ Laravel)
 *
 * คำขอที่เปลี่ยนข้อมูล (POST/PUT/PATCH/DELETE) ผ่านได้เมื่อ:
 *   - มี Origin และ host ตรงกับ host ของ request (หรืออยู่ใน LEGACY_TRUSTED_ORIGINS), หรือ
 *   - ไม่มี Origin แต่ Referer เป็น host เดียวกัน, หรือ
 *   - ไม่มีทั้งคู่ แต่มี X-Requested-With (เบราว์เซอร์ส่งข้ามโดเมนไม่ได้ถ้าไม่ผ่าน CORS preflight)
 * นอกนั้น 403 — เว็บเดิมไม่มีการกัน CSRF เลย (ช่องโหว่เดิม)
 */
class LegacyCsrf
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true) || $this->trusted($request)) {
            return $next($request);
        }

        return response('Forbidden (cross-site request)', 403);
    }

    private function trusted(Request $request): bool
    {
        $origin = (string) $request->headers->get('Origin', '');
        if ($origin !== '' && $origin !== 'null') {
            return $this->sameHost($request, $origin);
        }
        $referer = (string) $request->headers->get('Referer', '');
        if ($referer !== '') {
            return $this->sameHost($request, $referer);
        }

        return $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
    }

    private function sameHost(Request $request, string $url): bool
    {
        $p = parse_url($url);
        if (! isset($p['host'])) {
            return false;
        }
        // เทียบเฉพาะชื่อ host (ไม่สน port: dev ใช้ 127.0.0.1:8100/:82xx)
        if (strtolower($p['host']) === strtolower($request->getHost())) {
            return true;
        }
        $trusted = array_filter(array_map('trim', explode(',', (string) config('legacy.trusted_origins'))));
        foreach ($trusted as $t) {
            $tp = parse_url(str_contains($t, '://') ? $t : 'https://'.$t);
            if (isset($tp['host']) && strtolower($tp['host']) === strtolower($p['host'])) {
                return true;
            }
        }

        return false;
    }
}
