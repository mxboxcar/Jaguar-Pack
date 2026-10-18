<?php

namespace App\Domain\Users;

use Illuminate\Support\Facades\RateLimiter;

/**
 * จำกัดการเดา PIN ที่ _profile/pin (method=verified) และ App\Domain\Payment\PinBox::guard() (ตัวนับเดียวกัน)
 *
 * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): เดิมเดา PIN 6 หลักได้ไม่จำกัด (มีแค่แถว ips ที่ jail นับ)
 *   - ผิดครบ max_attempts ครั้ง ภายใน decay_minutes นาที ต่อ (ผู้ใช้ + IP) → ล็อกจนครบเวลา (ใส่ PIN ถูกก็ไม่ผ่าน)
 *   - กันเปลี่ยน IP หนี: ต่อผู้ใช้ (ทุก IP รวมกัน) ล็อกเมื่อผิดครบ max_attempts_per_user ครั้ง ในช่วงเวลาเดียวกัน
 *   - ใส่ PIN ถูก (ก่อนถูกล็อก) = ล้างตัวนับทั้งสองของผู้ใช้นั้น
 * ค่าตั้ง: config legacy.pin_throttle · ใช้ cache store ของ Laravel (production = file — ร่วมกันทุก worker ของ php-fpm)
 */
final class PinThrottle
{
    private static function cfg(string $k, int $default): int
    {
        return max(1, (int) config('legacy.pin_throttle.'.$k, $default));
    }

    private static function keys(int $uid, string $ip): array
    {
        return ['profile-pin:'.$uid.':'.sha1($ip), 'profile-pin:'.$uid];
    }

    /** วินาทีที่ยังถูกล็อก (0 = ไม่ถูกล็อก) */
    public static function lockedFor(int $uid, string $ip): int
    {
        [$pair, $user] = self::keys($uid, $ip);
        $wait = 0;
        if (RateLimiter::tooManyAttempts($pair, self::cfg('max_attempts', 5))) {
            $wait = RateLimiter::availableIn($pair);
        }
        if (RateLimiter::tooManyAttempts($user, self::cfg('max_attempts_per_user', 10))) {
            $wait = max($wait, RateLimiter::availableIn($user));
        }

        return $wait;
    }

    public static function fail(int $uid, string $ip): void
    {
        $decay = self::cfg('decay_minutes', 15) * 60;
        foreach (self::keys($uid, $ip) as $k) {
            RateLimiter::hit($k, $decay);
        }
    }

    public static function clear(int $uid, string $ip): void
    {
        foreach (self::keys($uid, $ip) as $k) {
            RateLimiter::clear($k);
        }
    }

    /** ข้อความล็อก (รูปแบบเดียวกับข้อความ error อื่นของ pin.php) */
    public static function message(int $seconds): string
    {
        return 'ใส่ PIN ผิดเกินจำนวนครั้งที่กำหนด<br />กรุณาลองใหม่อีกครั้งใน '.max(1, (int) ceil($seconds / 60)).' นาที';
    }
}
