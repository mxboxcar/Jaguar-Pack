<?php

namespace App\Domain\Shop;

use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;

/**
 * _api/crawl_guard.php เดิม — จำกัดการดึงสินค้า (ต่อบัญชี/ชั่วโมง, ต่อบัญชี/วันตามจำนวนออเดอร์ที่จ่ายแล้ว, ต่อ IP, ต่อวงเครือข่าย)
 * และกันสมัครซ้ำ (signupCheck/signupLog — กลุ่ม users ใช้ตอนสมัคร)
 * IP: Legacy::clientIp() (เดิม REMOTE_ADDR ที่ nginx แปลงจาก Cloudflare แล้ว — ไม่เชื่อ header จาก client)
 */
final class CrawlGuard
{
    public const USER_HOUR = 45;

    public const IP_DAY = 300;

    public const NET_DAY = 300;

    public const SIGNUP_NET_DAY = 15;

    public const MSG = 'ค้นหาสินค้าเกินจำนวนที่กำหนด กรุณาลองใหม่ภายหลัง';

    /**
     * โควตาต่อบัญชีต่อวัน ตามสัญญาณว่าเป็นลูกค้าจริง (2026-10-21 คุณ MAX ขยายกติกา):
     * $paid = บิลสั่งซื้อสำเร็จ (status 3) · $billed = บิลที่พนักงานอนุมัติ/แจ้งชำระแล้ว (status 1, 2)
     * $arrived = แทร็กกิ้งที่เข้าโกดังแล้ว (ลูกค้าส่งของเองก็นับ) · บิลรอตรวจสอบ (0) และแทร็กที่แค่แจ้งไว้ไม่นับ (บอทสร้างเองได้)
     */
    public static function userDayMax(int $paid, int $billed = 0, int $arrived = 0): int
    {
        if ($paid >= 5 || $arrived >= 10) {
            return 300;
        }

        return ($paid >= 1 || $billed >= 1 || $arrived >= 1) ? 150 : 50;
    }

    public static function ip(): string
    {
        $ip = Legacy::clientIp();

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    public static function net(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $p = explode('.', $ip);

            return $p[0].'.'.$p[1].'.'.$p[2].'.0/24';
        }
        $b = @inet_pton($ip);
        if ($b === false || strlen($b) !== 16) {
            return $ip;
        }

        return inet_ntop(substr($b, 0, 8).str_repeat("\0", 8)).'/64';
    }

    /**
     * crawl_guard_check($uid): บันทึกทุกครั้ง, เกินโควตา = คืน JSON error (เดิม echo แล้ว exit) ; ผ่าน = null
     */
    public static function check(int $uid): ?string
    {
        $ip = self::ip();
        $net = self::net($ip);
        $ua = substr(md5(Legacy::userAgent()), 0, 12);
        $reason = '';
        $b = DB::table('bill_product')->where('user', $uid)->where('is_delete', 0)
            ->selectRaw('COALESCE(SUM(`status` = 3), 0) AS paid, COALESCE(SUM(`status` IN (1, 2)), 0) AS billed')->first();
        $paid = (int) ($b->paid ?? 0);
        $arrived = (int) DB::table('tracking')->where('user', $uid)->where('is_delete', 0)
            ->where('date_warehouse', '<>', '0000-00-00 00:00:00')->count();
        $dmax = self::userDayMax($paid, (int) ($b->billed ?? 0), $arrived);
        $u = DB::table('crawl_guard_log')->where('user', $uid)->where('ok', 1)->whereRaw('`ts` >= CURDATE()')
            ->selectRaw('COUNT(*) AS d, COALESCE(SUM(`ts` >= NOW() - INTERVAL 1 HOUR), 0) AS h')->first();
        if ($u && (int) $u->h >= self::USER_HOUR) {
            $reason = 'user_hour';
        } elseif ($u && (int) $u->d >= $dmax) {
            $reason = 'user_day_'.$dmax;
        } elseif (DB::table('crawl_guard_log')->where('ip', $ip)->where('ok', 1)->whereRaw('`ts` >= CURDATE()')->count() >= self::IP_DAY) {
            $reason = 'ip_day';
        } elseif (DB::table('crawl_guard_log')->where('net', $net)->where('ok', 1)->whereRaw('`ts` >= CURDATE()')->count() >= self::NET_DAY) {
            $reason = 'net_day';
        }
        $ok = $reason === '' ? 1 : 0;
        DB::table('crawl_guard_log')->insert([
            'ts' => DB::raw('NOW()'), 'user' => $uid, 'ip' => $ip, 'net' => $net, 'ua' => $ua,
            'paid_orders' => $paid, 'ok' => $ok, 'reason' => $reason,
        ]);

        return $ok ? null : json_encode(['product_name' => '', 'error' => 'true', 'message' => self::MSG], JSON_UNESCAPED_UNICODE);
    }

    private static function digits(mixed $s): string
    {
        return (string) preg_replace('/\D+/', '', (string) $s);
    }

    /** crawl_guard_signup_check($email, $phone) — ข้อความ error หรือ '' */
    public static function signupCheck(string $email, string $phone): string
    {
        $net = self::net(self::ip());
        if (DB::table('crawl_guard_signup')->where('net', $net)->whereRaw('`ts` >= NOW() - INTERVAL 24 HOUR')->count() >= self::SIGNUP_NET_DAY) {
            return 'สมัครสมาชิกจากเครือข่ายนี้เกินจำนวนที่กำหนด กรุณาติดต่อเจ้าหน้าที่';
        }
        $email = strtolower(trim($email));
        $phone = self::digits($phone);
        foreach (DB::table('user')->where('is_delete', 1)->orWhere('is_approved', -1)->select(['email', 'phone', 'phone2'])->cursor() as $row) {
            if ($email !== '' && strtolower(trim((string) $row->email)) === $email) {
                return 'อีเมลนี้ไม่สามารถใช้สมัครได้ กรุณาติดต่อเจ้าหน้าที่';
            }
            if (strlen($phone) >= 9 && ! preg_match('/^(\d)\1+$/', $phone) && ($phone === self::digits($row->phone) || $phone === self::digits($row->phone2))) {
                return 'เบอร์โทรนี้ไม่สามารถใช้สมัครได้ กรุณาติดต่อเจ้าหน้าที่';
            }
        }

        return '';
    }

    public static function signupLog(int $uid): void
    {
        $ip = self::ip();
        DB::table('crawl_guard_signup')->insert(['ts' => DB::raw('NOW()'), 'user' => $uid, 'ip' => $ip, 'net' => self::net($ip)]);
    }
}
