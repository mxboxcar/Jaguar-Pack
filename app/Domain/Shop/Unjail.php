<?php

namespace App\Domain\Shop;

use Illuminate\Support\Facades\DB;

/**
 * check_unjail() เดิม (system_status_function.php) — เรียกจาก _api/unban (cron ด้วย token)
 * ปลดแบน IP/ผู้ใช้ที่ครบกำหนด, ลบประวัติล็อกอินผิดของ IP นั้น, ลบ session ว่างในตาราง sessions (ตารางเก่า ไม่ใช่ sessions_v2)
 * (CONVENTIONS ข้อ 8: เจ้าของคือกลุ่ม settings — พอร์ตไว้ที่นี่เพราะ endpoint อยู่ในกลุ่ม shop)
 */
final class Unjail
{
    public static function run(): void
    {
        $now = time();
        foreach (DB::table('ips_ban')->whereRaw('`unban_date` <= NOW()')->where('is_clear', '0')->get() as $row) {
            if ($now > strtotime((string) $row->unban_date)) {
                DB::table('ips_ban')->where('id', $row->id)->update(['is_clear' => '1']);
                // เดิม LIKE 'ip%' ต่อ string ตรง — escape ตัวพิเศษของ LIKE
                DB::table('ips')->where('text', 'like', addcslashes((string) $row->ip, '%_\\').'%')->delete();
            }
        }
        foreach (DB::table('ips_ban_user')->whereRaw('`unban_date` <= NOW()')->where('is_clear', '0')->get() as $row) {
            if ($now > strtotime((string) $row->unban_date)) {
                DB::table('ips_ban_user')->where('id', $row->id)->update(['is_clear' => '1']);
            }
        }
        DB::table('sessions')->where('data', '')->delete();
    }
}
