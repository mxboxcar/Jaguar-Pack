<?php

namespace App\Domain\Tracking;

use Illuminate\Support\Facades\DB;

/**
 * SR: ตาราง tracking_check = รายการรอเช็ค (หน้า admin/check) — tracking ที่ลูกค้าขอ QC / ไม้กันกระแทก / ถ่ายรูป
 *  - _tracking/save.php เดิม: สร้างใหม่ (QC/ไม้) → INSERT; แก้ไข (QC/ไม้/ถ่ายรูป) → ถ้ามีแถวที่เช็คแล้ว (is_checked = 1) ตั้งกลับเป็น 0
 *    ไม่งั้น INSERT แถวใหม่ (แม้มีแถวที่ยังรอเช็คอยู่แล้ว = แถวซ้ำในหน้า admin/check — คงไว้เหมือนเดิม)
 *  - _admin/_check/save_clear.php / save_clearall.php: ตั้ง is_checked = 1
 */
final class TrackingChecks
{
    public static function insert(int $trackingId, ?int $uid): int
    {
        return (int) DB::table('tracking_check')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            'is_delete' => 0, 'tracking_id' => $trackingId, 'is_checked' => '0',
        ]);
    }

    /** แก้ไข tracking: แถวที่เช็คแล้วแถวแรก → รอเช็คอีกครั้ง, ไม่มี → INSERT */
    public static function requeue(int $trackingId, ?int $uid): void
    {
        $checked = DB::table('tracking_check')->where('tracking_id', $trackingId)->where('is_checked', '1')
            ->orderBy('id')->value('id');
        if ($checked !== null) {
            DB::table('tracking_check')->where('id', $checked)->update(['is_checked' => '0']);

            return;
        }
        self::insert($trackingId, $uid);
    }

    /**
     * หน้า admin/check: tracking_check ที่ยังไม่เช็ค + tracking ที่ยังมีอยู่ (tracking.tracking IS NOT NULL)
     * เดิมไม่มี ORDER BY → ใช้ tracking_check.id
     */
    public static function pending(): \Illuminate\Support\Collection
    {
        return DB::table('tracking_check')
            ->leftJoin('tracking', 'tracking.id', '=', 'tracking_check.tracking_id')
            ->leftJoin('user', 'user.id', '=', 'tracking.user')
            ->where('tracking_check.is_checked', 0)
            ->whereNotNull('tracking.tracking')
            ->orderBy('tracking_check.id')
            ->get(['tracking_check.id as main_id', 'tracking.id', 'tracking.tracking', 'tracking.is_qc', 'tracking.is_craft', 'user.code']);
    }

    /** save_clear.php: แถวเดียวที่ยังไม่เช็ค */
    public static function clear(string $id): void
    {
        $row = DB::table('tracking_check')->where('id', $id)->where('is_checked', '0')->value('id');
        if ($row !== null) {
            DB::table('tracking_check')->where('id', $row)->update(['is_checked' => '1']);
        }
    }

    /**
     * save_clearall.php: id = ", 1 , 2 " (ตัวอักษรแรกถูกตัด) — เดิมฝังลง IN (...) ตรงๆ (SQL injection)
     * → รับเฉพาะรายการที่เป็นตัวเลขทั้งหมด (ไม่ใช่ตัวเลข = เดิม SQL error = ไม่มีอะไรเปลี่ยน)
     *
     * @param  list<int>  $ids
     */
    public static function clearAll(array $ids): void
    {
        if ($ids !== []) {
            DB::table('tracking_check')->whereIn('id', $ids)->update(['is_checked' => '1']);
        }
    }
}
