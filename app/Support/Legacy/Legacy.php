<?php

namespace App\Support\Legacy;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * จุดรวมค่าคงที่/สถานะของเว็บเดิมที่โมดูลต้องใช้ (แทน define(), $_COOKIE, getUserID() เดิม)
 *
 *   Legacy::href()        = href          (ลงท้ายด้วย / เสมอ)
 *   Legacy::hrefBlob()    = href_blob
 *   Legacy::lang()        = $cookie ภาษา (th|en|cn)
 *   Legacy::userId()      = getUserID()   (int|null)
 *   Legacy::user()        = checkUser()   (App\Models\User|null)
 *   Legacy::isMobile()    = $detect->isMobile()
 *   Legacy::clientIp()    = get_client_ip()
 */
final class Legacy
{
    public const LANGS = ['th', 'en', 'cn'];

    public static function href(string $path = ''): string
    {
        $base = (string) config('legacy.href');
        if ($base === '' && app()->isProduction()) {
            // production ห้ามสร้าง URL จาก Host header ของ client (host header injection → redirect ไปโดเมนอื่น)
            $base = (string) config('app.url');
        }
        if ($base === '') {
            $req = self::request();
            $base = $req ? $req->getSchemeAndHttpHost().$req->getBasePath() : (string) config('app.url');
        }

        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    public static function hrefBlob(string $path = ''): string
    {
        return rtrim((string) config('legacy.href_blob'), '/').'/'.ltrim($path, '/');
    }

    /** ภาษาจาก cookie jlcargo-lang (ค่าอื่นนอกจาก th/en/cn → th; เดิม include ไฟล์ตามค่า cookie ตรงๆ = LFI) */
    public static function lang(): string
    {
        $v = (string) (self::request()?->cookies->get(self::cookie('lang')) ?? '');

        return in_array($v, self::LANGS, true) ? $v : 'th';
    }

    public static function cookie(string $key): string
    {
        return (string) config('legacy.cookies.'.$key);
    }

    /** ค่า cookie ดิบของเว็บเดิม (ไม่เข้ารหัส — อยู่ใน except ของ EncryptCookies) เช่น rawCookie('admin_menu') */
    public static function rawCookie(string $key): string
    {
        return (string) (self::request()?->cookies->get(self::cookie($key)) ?? '');
    }

    public static function userId(): ?int
    {
        $id = Auth::guard('legacy')->id();

        return $id === null ? null : (int) $id;
    }

    public static function user(): ?User
    {
        /** @var User|null $u */
        $u = Auth::guard('legacy')->user();

        return $u;
    }

    public static function isMobile(): bool
    {
        $req = self::request();
        if (! $req) {
            return false;
        }

        if (! app()->bound('legacy.mobile')) {
            app()->instance('legacy.mobile', (new \Mobile_Detect($req->server->all(), $req->userAgent()))->isMobile());
        }

        return (bool) app('legacy.mobile');
    }

    /** get_client_ip() เดิม: X-Real-IP (nginx ตั้งเอง) → REMOTE_ADDR ที่เป็น public → REMOTE_ADDR */
    public static function clientIp(): string
    {
        $s = self::request()?->server;
        if (! $s) {
            return '0.0.0.0';
        }
        $remote = (string) $s->get('REMOTE_ADDR', '');
        // X-Real-IP เชื่อได้เฉพาะเมื่อ request มาจาก proxy ในเครื่อง/วงใน (nginx → frankenphp ฯลฯ)
        // เดิมเชื่อ header จาก client ตรงๆ → ปลอม IP หลบ jail / ทำให้ IP คนอื่นโดนแบนได้
        $real = (string) $s->get('HTTP_X_REAL_IP', '');
        if ($real !== '' && self::isTrustedProxy($remote) && filter_var($real, FILTER_VALIDATE_IP) !== false) {
            return $real;
        }
        if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            return $remote;
        }

        return $remote !== '' ? $remote : '0.0.0.0';
    }

    /** REMOTE_ADDR เป็น loopback/private (proxy ในเครื่อง) หรืออยู่ใน LEGACY_TRUSTED_PROXIES (คั่นด้วย ,) */
    private static function isTrustedProxy(string $remote): bool
    {
        if ($remote === '' || filter_var($remote, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        $extra = array_filter(array_map('trim', explode(',', (string) config('legacy.trusted_proxies', ''))));
        if (in_array($remote, $extra, true)) {
            return true;
        }

        return filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    public static function userAgent(): string
    {
        return (string) (self::request()?->server->get('HTTP_USER_AGENT') ?? '');
    }

    /** ค่า '0000-00-00...' / null / '' = ไม่มีวันที่ */
    public static function isZeroDate(mixed $v): bool
    {
        if ($v instanceof \DateTimeInterface) {
            return false;
        }

        return $v === null || $v === '' || str_starts_with((string) $v, '0000-00-00');
    }

    private static function request(): ?Request
    {
        return app()->bound('request') ? app('request') : null;
    }
}
