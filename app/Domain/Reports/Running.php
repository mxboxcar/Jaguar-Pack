<?php

namespace App\Domain\Reports;

use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;

/**
 * SR: getRunning() / updateRunning() เดิม (system_status_function.php) — เลขรันตามชื่อในตาราง `running`
 * ใช้ที่เดียวใน SR: _admin/_export/save_receipt.php (เลขใบเสร็จ = ปี พ.ศ. 2 หลัก + เลข 5 หลัก เช่น 6900001)
 *
 * ต่างจากเดิม: next() ต้องเรียกใน DB::transaction — อ่านแถวด้วย lockForUpdate แล้วเพิ่ม no ในคราวเดียว
 * (เดิม getRunning() แล้ว updateRunning() แยกกัน ไม่มี lock → บันทึกใบเสร็จ 2 ใบพร้อมกันได้เลขซ้ำ)
 * ตาราง running ไม่มี index ที่ name → SELECT ... FOR UPDATE ล็อกทุกแถวที่สแกน (และช่องว่าง) = แถวใหม่ชื่อเดียวกันถูกสร้างได้ครั้งเดียว
 */
final class Running
{
    /** getRunning($name, $needdate, $position) + updateRunning($name, $needdate) */
    public static function next(string $name, bool $needDate = true, int $position = 5): string
    {
        if ($needDate) {
            $name .= date('ymd');
        }
        $uid = Legacy::userId();
        $row = DB::table('running')->where('is_delete', '0')->where('name', $name)->orderBy('id')->lockForUpdate()->first();
        if ($row === null) {
            DB::table('running')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'name' => $name, 'no' => 1, 'position' => $position,
            ]);
            $no = '1';
        } else {
            $no = (string) $row->no;
            $position = (int) $row->position;
        }
        // updateRunning(): no + 1 ของแถวแรกที่ชื่อตรง (แถวที่เพิ่งสร้างก็ถูกเพิ่มเป็น 2 เหมือนเดิม)
        DB::table('running')->where('is_delete', '0')->where('name', $name)->orderBy('id')->limit(1)
            ->update(['no' => DB::raw('`no` + 1')]);

        return $name.str_repeat('0', max(0, $position - strlen($no))).$no;
    }
}
