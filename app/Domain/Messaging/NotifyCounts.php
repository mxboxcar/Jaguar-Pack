<?php

namespace App\Domain\Messaging;

use Illuminate\Support\Facades\DB;

/**
 * notify_counts() เดิม: ตัวเลขคิวรออนุมัติทั้งระบบ 12 ช่อง (ไม่ผูกผู้ใช้) อ่านจากไฟล์ที่ notify-server เขียน
 * ไฟล์หาย/เก่าเกิน/พัง → ยิง query เอง — เดิม 12 query ที่นี่รวมเป็น 1 query (subquery 12 ตัว) ผลเท่ากัน
 * ลำดับต้องตรงกับ notifications.php
 */
final class NotifyCounts
{
    /** [ตาราง, คอลัมน์/ค่าเงื่อนไข] ตามลำดับ notify_counts_sql() เดิม */
    public const SOURCES = [
        ['user', 'is_approved', '0'],
        ['bill_product', 'status', '0'],
        ['bill_product', 'status', '2'],
        ['refill', 'status', '1'],
        ['bill_payment', 'status', '1'],
        ['bill_transfer', 'status', '1'],
        ['bill_alipay', 'status', '1'],
        ['lost_package', 'status', '1'],
        ['taobao_detail', 'status', '1'],
        ['point', 'status', '1'],
        ['shop_cashback_detail', 'status', '1'],
        ['bill_payment', 'status', '2'],
    ];

    private static ?array $cached = null;

    /** @return list<int> */
    public static function get(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        $fromFile = self::fromFile();
        if ($fromFile !== null) {
            return self::$cached = $fromFile;
        }
        $q = DB::query();
        foreach (self::SOURCES as $i => [$table, $col, $val]) {
            $q->selectSub(fn ($s) => $s->from($table)->selectRaw('COUNT(id)')->where('is_delete', '0')->where($col, $val), 'c'.$i);
        }
        $row = (array) $q->first();

        return self::$cached = array_map(fn ($i) => (int) ($row['c'.$i] ?? 0), array_keys(self::SOURCES));
    }

    public static function flush(): void
    {
        self::$cached = null;
    }

    /** ไฟล์ {ts, c:[...]} — ใช้ได้ถ้าอายุไม่เกิน counts_stale_max (เดิมเช็ค MAX_AGE ก่อนแล้ว STALE_MAX = ผลเหมือนกัน) */
    private static function fromFile(): ?array
    {
        $file = (string) config('legacy.notify.counts_file');
        $raw = $file !== '' ? @file_get_contents($file) : false;
        if ($raw === false) {
            return null;
        }
        $j = json_decode($raw, true);
        if (is_array($j) && isset($j['ts'], $j['c']) && is_array($j['c'])
            && count($j['c']) === count(self::SOURCES)
            && (time() - (int) $j['ts']) <= (int) config('legacy.notify.counts_stale_max', 600)) {
            return array_map('intval', array_values($j['c']));
        }

        return null;
    }
}
