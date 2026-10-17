<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * ฟังก์ชันอ่านค่าจาก DB ของเว็บเดิม (ทุกตัว bind ค่า — เดิมต่อ string ตรง)
 * getSetting ถูก cache ต่อ request (layout เรียกซ้ำหลายครั้ง)
 */
final class Lookup
{
    /** @var array<string, mixed> */
    private static array $settings = [];

    public static function flush(): void
    {
        self::$settings = [];
    }

    /** getSetting($key): value ของแถวแรกใน `setting` (ไม่มี → null) */
    public static function getSetting(string $key): mixed
    {
        if (! array_key_exists($key, self::$settings)) {
            self::$settings[$key] = DB::table('setting')->where('key', $key)->value('value');
        }

        return self::$settings[$key];
    }

    public static function getPrefix(string $module): mixed
    {
        return DB::table('prefix')->where('name', $module)->value('prefix');
    }

    public static function getPrefixID(string $module): mixed
    {
        return DB::table('prefix')->where('name', $module)->value('id');
    }

    public static function getCurrentCash(mixed $id = ''): mixed
    {
        return self::rawSum('balance', $id);
    }

    public static function getCurrentCashCN(mixed $id = ''): mixed
    {
        return self::rawSum('balance_cn', $id);
    }

    public static function getCurrentPoint(mixed $id = ''): mixed
    {
        return self::rawSum('balance_point', $id);
    }

    /** ค่าดิบแบบ mysqli (string ของ DECIMAL เช่น "1234.50") ไม่ใช่ float — ให้แสดงผลตรงกับเดิม */
    private static function rawSum(string $table, mixed $id): mixed
    {
        $id = ($id === null || $id === '') ? Legacy::userId() : $id;
        $v = DB::table($table)->where('user', $id)->where('is_delete', '0')->selectRaw('SUM(amount) as c')->value('c');

        return ($v === null || $v === '') ? 0 : $v;
    }

    public static function getStock2(mixed $mallId): mixed
    {
        $v = DB::table('mall_stock')->where('mall_id', $mallId)->selectRaw('SUM(amount) as c')->value('c');

        return ($v === null || $v === '') ? 0 : $v;
    }

    public static function getStock(mixed $mallId, mixed $mallDetailId): mixed
    {
        $v = DB::table('mall_stock')->where('mall_id', $mallId)->where('mall_detail_id', $mallDetailId)
            ->selectRaw('SUM(amount) as c')->value('c');

        return ($v === null || $v === '') ? 0 : $v;
    }

    /**
     * convertchina($thai, $china) เดิม: checkUser()['role'] == china_role — แต่ไม่เคย define china_role
     * PHP 7.4 จึงเทียบกับ string 'china_role' (= 0 เมื่อเทียบกับ int) → ผู้ใช้ role 0 ได้ข้อความจีน (ไม่ล็อกอิน = null → ไทย)
     * คงผลเดิมไว้ (เดิม echo — ที่นี่คืนค่า)
     */
    public static function convertchina(string $thai, string $china): string
    {
        $role = Legacy::user()?->role;

        return ($role !== null && (int) $role === 0) ? $china : $thai;
    }

    /**
     * SaveLog($id, $text, $module, $to) เดิม: INSERT INTO log (id_record, user, to_user, text, module, date)
     * แต่ตาราง `log` ไม่มีคอลัมน์เหล่านี้ → mysqli คืน false เงียบๆ = ไม่เคยบันทึกอะไรเลย
     * คงพฤติกรรมเดิม (no-op) ไว้ ไม่ให้ Laravel โยน exception
     */
    public static function saveLog(mixed $id, string $text, string $module, mixed $to): void
    {
        // ตั้งใจว่างไว้ — ดูหมายเหตุด้านบน
    }
}
