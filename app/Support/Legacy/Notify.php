<?php

namespace App\Support\Legacy;

/**
 * notify_token() เดิม: token ให้เบราว์เซอร์ต่อ WebSocket กับ notify-server
 *   "{exp}.{uid}.{site}.{hmac_sha256(payload, secret)}" อายุ 12 ชั่วโมง
 * secret อ่านจากไฟล์นอก repo (LEGACY_NOTIFY_SECRET_FILE) — ไม่มีไฟล์ = '' (หน้าเว็บ fallback เป็น polling)
 */
final class Notify
{
    private static ?string $cached = null;

    public static function token(): string
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        $file = (string) config('legacy.notify.secret_file');
        $secret = $file !== '' ? @file_get_contents($file) : false;
        if ($secret === false || trim($secret) === '') {
            return self::$cached = '';
        }
        $payload = (time() + 43200).'.'.(int) Legacy::userId().'.'.config('legacy.notify.site');

        return self::$cached = $payload.'.'.hash_hmac('sha256', $payload, trim($secret));
    }

    public static function flush(): void
    {
        self::$cached = null;
    }
}
