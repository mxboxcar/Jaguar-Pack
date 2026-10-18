<?php

namespace App\Domain\Users;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * crawl_guard_signup_check() / crawl_guard_signup_log() ของ _api/crawl_guard.php เดิม (หน้าสมัคร)
 * - กันอีเมล/เบอร์ของบัญชีที่ถูกลบหรือไม่อนุมัติ และสมัครจากวงเครือข่ายเดียวกันเกิน 15 บัญชีใน 24 ชม.
 * - IP = REMOTE_ADDR แบบเดิม (nginx แปลงจาก Cloudflare แล้ว ห้ามเชื่อ header จาก client)
 *
 * วนเทียบใน PHP เหมือนเดิม (ลำดับข้อความ = แถวแรกที่ตรงอีเมลหรือเบอร์) — query เดียว bind ค่า
 */
final class SignupGuard
{
    private const SIGNUP_NET_DAY = 15;

    public static function ip(Request $request): string
    {
        $ip = (string) $request->server('REMOTE_ADDR', '');

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

    private static function digits(mixed $s): string
    {
        return (string) preg_replace('/\D+/', '', (string) $s);
    }

    /** คืนข้อความ error หรือ '' */
    public static function check(Request $request, string $email, string $phone): string
    {
        $net = self::net(self::ip($request));
        $recent = DB::table('crawl_guard_signup')->where('net', $net)
            ->where('ts', '>=', DB::raw('NOW() - INTERVAL 24 HOUR'))->count();
        if ($recent >= self::SIGNUP_NET_DAY) {
            return 'สมัครสมาชิกจากเครือข่ายนี้เกินจำนวนที่กำหนด กรุณาติดต่อเจ้าหน้าที่';
        }
        $email = strtolower(trim($email));
        $phone = self::digits($phone);
        $checkPhone = strlen($phone) >= 9 && ! preg_match('/^(\d)\1+$/', $phone);

        $rows = DB::table('user')->where(fn ($q) => $q->where('is_delete', 1)->orWhere('is_approved', -1))
            ->orderBy('id')->get(['email', 'phone', 'phone2']);
        foreach ($rows as $row) {
            // strtolower ของ 7.4 = เฉพาะ A-Z (ไม่ขึ้นกับ locale เพราะ locale เดิมเป็น C)
            if ($email !== '' && strtolower(trim((string) $row->email)) === $email) {
                return 'อีเมลนี้ไม่สามารถใช้สมัครได้ กรุณาติดต่อเจ้าหน้าที่';
            }
            if ($checkPhone && ($phone === self::digits($row->phone) || $phone === self::digits($row->phone2))) {
                return 'เบอร์โทรนี้ไม่สามารถใช้สมัครได้ กรุณาติดต่อเจ้าหน้าที่';
            }
        }

        return '';
    }

    public static function log(Request $request, int $uid): void
    {
        $ip = self::ip($request);
        DB::table('crawl_guard_signup')->insert([
            'ts' => DB::raw('NOW()'), 'user' => $uid, 'ip' => $ip, 'net' => self::net($ip),
        ]);
    }
}
