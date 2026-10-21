<?php

namespace App\Http\Middleware;

use App\Support\Legacy\Legacy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * legacy.auth_or_signup — system_upload: ล็อกอินแล้วผ่าน / ยังไม่ล็อกอินผ่านได้เฉพาะกล่องแนบบัตรประชาชนในหน้าสมัคร
 *
 * 2026-10-21: legacy.auth ทำให้หน้า /register แนบสำเนาบัตรไม่ได้ (ได้ 302 ไป login → dropper แสดงหน้า login ใน "หยุดการทำงาน!")
 * ยังไม่ล็อกอิน: Referer ต้องเป็น /register ของเว็บนี้ + จำกัดต่อ IP (ชนิดไฟล์ตรวจจากเนื้อไฟล์ใน UploadController อยู่แล้ว)
 * นอกนั้นเหมือน legacy.auth (302 ไป /login)
 */
class LegacyAuthOrSignupUpload
{
    /** อัปโหลดต่อ IP ต่อชั่วโมง ตอนยังไม่ล็อกอิน (สมัคร 1 ครั้งแนบบัตร 1 ไฟล์ เผื่อเลือกใหม่) */
    public const MAX_PER_HOUR = 20;

    public function handle(Request $request, Closure $next): Response
    {
        if (Legacy::userId() !== null) {
            return $next($request);
        }
        if (! self::fromSignupPage($request)) {
            return redirect()->to(Legacy::href('login'));
        }
        $key = 'signup-upload:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            return response('error: อัพโหลดเกินจำนวนที่กำหนด กรุณาลองใหม่ภายหลัง', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        RateLimiter::hit($key, 3600);

        return $next($request);
    }

    private static function fromSignupPage(Request $request): bool
    {
        $ref = parse_url((string) $request->headers->get('referer', ''));
        if (! is_array($ref) || ! isset($ref['host'], $ref['path'])) {
            return false;
        }

        return strcasecmp($ref['host'], $request->getHost()) === 0
            && in_array(rtrim($ref['path'], '/'), ['/register', '/register.php'], true);
    }
}
