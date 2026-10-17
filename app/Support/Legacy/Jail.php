<?php

namespace App\Support\Legacy;

use App\Auth\LegacySessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * check_jail_ip_v3() เดิม — ทำงานทุก request ของเว็บเดิม (system_status_function.php บรรทัด 3)
 *  1) IP อยู่ใน ips_ban และยังไม่ถึง unban_date (เผื่อ 2 นาที) → 403
 *  2) ผู้ใช้ที่ล็อกอินอยู่ใน ips_ban_user ยังไม่ถึงเวลา → 403
 *  3) IP ใน setting.whitelist_ip (คั่นด้วย [--]) → ข้ามทั้งหมด
 *  4) ไม่โดนแบน แต่ ips (บันทึกล็อกอินพลาด) ของ IP นี้ >= 5 แถว → แบน IP 2 นาที (ครั้งต่อไป x2) และแบน user ด้วย
 * ใช้ผ่าน middleware legacy.jail
 */
final class Jail
{
    public const FORBIDDEN_BODY = "\n\t\t\t\t<h1>Forbidden</h1>\n\t\t\t\t<p>You dont have permission to access on this server</p>";

    /** true = ต้องตอบ 403 */
    public static function check(): bool
    {
        $ip = Legacy::clientIp();
        $banned = false;
        $fromBan = false;
        $limit = date('Y-m-d H:i:s', time() - 120);

        $ban = DB::table('ips_ban')->where('ip', $ip)->first();
        if ($ban && strtotime($limit) < (int) strtotime((string) $ban->unban_date)) {
            $banned = $fromBan = true;
        }

        $uid = self::sessionUserId();
        $userBan = null;
        if (! $banned && $uid !== null) {
            $userBan = DB::table('ips_ban_user')->where('user', $uid)->first();
            if ($userBan && strtotime($limit) < (int) strtotime((string) $userBan->unban_date)) {
                $banned = $fromBan = true;
            }
        }

        $white = (string) Lookup::getSetting('whitelist_ip');
        foreach (explode('[--]', $white) as $item) {
            if (trim($item) !== '' && $item === $ip) {
                return false;
            }
        }

        if ($banned) {
            return true;
        }
        if (! $fromBan) {
            $fails = DB::table('ips')->where('text', 'like', addcslashes($ip, '%_\\').'%')->count();
            if ($fails >= 5) {
                self::escalate('ips_ban', 'ip', $ip, DB::table('ips_ban')->where('ip', $ip)->first());
                if ($uid !== null) {
                    self::escalate('ips_ban_user', 'user', $uid, DB::table('ips_ban_user')->where('user', $uid)->first());
                }
            }
        }

        return false;
    }

    private static function escalate(string $table, string $col, mixed $value, ?object $row): void
    {
        if ($row) {
            $m = (int) ((float) $row->time * 2);
            DB::table($table)->where('id', $row->id)->update([
                'unban_date' => DB::raw('DATE_ADD(NOW(), INTERVAL '.$m.' MINUTE)'),
                'time' => $m,
                'is_clear' => '0',
            ]);
        } else {
            DB::table($table)->insert([
                'unban_date' => DB::raw('DATE_ADD(NOW(), INTERVAL 2 MINUTE)'),
                $col => $value, 'time' => '2', 'is_clear' => '0',
            ]);
        }
    }

    /** getUserIDS(): id จาก session ดิบ (ไม่ตรวจสถานะบัญชี) */
    private static function sessionUserId(): ?string
    {
        $g = Auth::guard('legacy');
        $id = $g instanceof LegacySessionGuard ? $g->legacyUserId() : null;

        return ($id === null || $id === '') ? null : $id;
    }

    /** บันทึกการล็อกอิน/PIN ผิด ลงตาราง ips (ตัวนับของข้อ 4) — ไม่เก็บรหัสผ่านที่พิมพ์ (เดิมเก็บ plaintext) */
    public static function recordFailure(string $text, string $username, string $kind = 'login'): void
    {
        DB::table('ips')->insert([
            'create_date' => DB::raw('NOW()'), 'create_by' => 0, 'mod_date' => DB::raw('NOW()'), 'mod_by' => 0,
            'is_delete' => 0, 'text' => $text, 'username' => $username, 'password' => $kind === 'pin' ? 'pin' : '***',
        ]);
    }
}
