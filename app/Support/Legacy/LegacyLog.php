<?php

namespace App\Support\Legacy;

/**
 * goLog()/goLogMessage() เดิม: เขียนไฟล์ logs/YYYY-MM-DD.log บรรทัดละรายการ
 *   goLog       : "[Y-m-d H:i:s] BY-{uid} ::: {detail} ::: {ip} ::: {cmd} ::: "
 *   goLogMessage: "[Y-m-d H:i:s] {detail} ::: {ip} ::: {message} ::: {user agent}"
 * ที่อยู่ไฟล์ = config('legacy.log_dir') (dev: storage/logs/legacy)
 * หมายเหตุ: เว็บเดิมเขียนไว้ใต้ public_html/logs (กันด้วย .htaccess เท่านั้น — nginx ต้องปิดเอง)
 */
final class LegacyLog
{
    public static function goLog(string $detail, string $cmd): void
    {
        self::write('BY-'.(Legacy::userId() ?? '').' ::: '.$detail.' ::: '.Legacy::clientIp().' ::: '.$cmd.' ::: ');
    }

    public static function goLogMessage(string $detail, string $message): void
    {
        self::write($detail.' ::: '.Legacy::clientIp().' ::: '.$message.' ::: '.Legacy::userAgent());
    }

    private static function write(string $line): void
    {
        $dir = (string) config('legacy.log_dir');
        // CLI (artisan/tinker รันด้วย root) ไม่เขียน: ไฟล์จะกลายเป็นของ root แล้ว php-fpm เขียนต่อไม่ได้
        if ($dir === '' || (app()->runningInConsole() && ! app()->runningUnitTests())) {
            return;
        }
        if (! is_dir($dir)) {
            @mkdir($dir, 02750, true);
        }
        @file_put_contents($dir.'/'.date('Y-m-d').'.log', date('[Y-m-d H:i:s]').' '.$line.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** แทน mysqlquerys(): คำสั่งที่ไม่มีคำว่า SELECT/select ถูกบันทึก (เรียกจาก DB::listen ใน AppServiceProvider) */
    public static function query(string $sql, array $bindings): void
    {
        if (str_contains($sql, 'SELECT') || str_contains($sql, 'select')) {
            return;
        }
        // ไม่บันทึก session ของ Laravel/เว็บเดิมเอง (เดิม session handler ใช้ mysqli ตรง ไม่ผ่าน mysqlquerys)
        if (str_contains($sql, 'sessions_v2')) {
            return;
        }
        // เดิมบันทึกรหัสผ่าน/PIN (encrypt แบบถอดกลับได้) ลง log ด้วย — ที่นี่ปิดค่าทั้งคำสั่งถ้าแตะคอลัมน์เหล่านี้
        if (preg_match('/`?(password|pin|user_role)`?\s*(=|,|\))/i', $sql)) {
            $bindings = array_fill(0, count($bindings), '***');
        }
        $full = $sql;
        foreach ($bindings as $b) {
            $v = $b === null ? 'NULL' : (is_bool($b) ? (string) (int) $b : "'".str_replace("'", "''", (string) $b)."'");
            $full = preg_replace('/\?/', str_replace(['\\', '$'], ['\\\\', '\\$'], $v), $full, 1) ?? $full;
        }
        $uri = app()->bound('request') ? (string) app('request')->getRequestUri() : 'cli';
        self::goLog($uri, str_replace("'", "''", $full));
    }
}
