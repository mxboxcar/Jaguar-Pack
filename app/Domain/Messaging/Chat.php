<?php

namespace App\Domain\Messaging;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use Illuminate\Support\Facades\DB;

/**
 * แชทลูกค้า ↔ แอดมิน (ตาราง chat / chat_dept) — _message/*.php และ _admin/_message/*.php เดิม
 *
 * ทิศทางข้อความ: ลูกค้าส่ง = from=<user>, to=0 · แอดมินส่ง = from=0, to=<user>
 * ค่า id/dept/user ที่ส่งมาจาก browser ถูก bind เป็น string เหมือนที่เดิมฝังใน '...' (MySQL แปลงเป็นตัวเลขแบบเดียวกัน)
 */
final class Chat
{
    /**
     * MakePaging($rows, $page) เดิม: "{10*page-10} , 10" (page เป็น string จาก POST — 7.4 แปลงเป็นตัวเลขแบบหลวม)
     * คืน [ข้อความ paging แบบเดิม, offset ที่ใช้ได้ หรือ null ถ้าเดิมเป็น SQL error (ติดลบ/ทศนิยม)]
     *
     * @return array{0: string, 1: int|null}
     */
    public static function paging(mixed $page, bool $adminFix = false): array
    {
        $start = 10 * Php74::num($page) - 10;
        $text = Php74::str($start);
        if ($adminFix) {
            // _admin/_message/load.php: str_replace("-10", "0", $sql) ทั้งคำสั่ง
            $text = str_replace('-10', '0', $text);
        }
        $offset = preg_match('/^\d+$/', $text) ? (int) $text : null;

        return [$text.' , 10', $offset];
    }

    /** แถวของห้องแชท (10 แถวล่าสุดตาม offset — เดิมนับ mysqli_num_rows ด้วยแต่ไม่ได้ใช้ จึงไม่นับ) */
    public static function rows(\Closure $where, ?int $offset, bool $withFromCode = false): array
    {
        if ($offset === null) {
            return [];
        }
        $q = DB::table('chat')->leftJoin('user as t3', 'chat.create_by', '=', 't3.id');
        $cols = ['chat.*', 't3.code as owner'];
        if ($withFromCode) {
            $q->leftJoin('user as t4', 'chat.from', '=', 't4.id');
            $cols[] = 't4.code as from_code';
        }
        $where($q);

        return $q->orderByDesc('chat.id')->offset($offset)->limit(10)->get($cols)->all();
    }

    /** วันที่/เวลาเหนือข้อความ: getthaidate($exp[0])."<br />".$exp[1] */
    public static function stamp(mixed $createDate): string
    {
        $exp = explode(' ', (string) $createDate);

        return Format::getthaidate($exp[0]).'<br />'.($exp[1] ?? '');
    }

    /** ไฟล์เดิมเป็น CRLF: ข้อความ HTML หลายบรรทัดในไฟล์ได้ \r\n — ทำให้ผล (ที่อยู่ใน JSON) ตรงกันทุก byte */
    public static function crlf(string $html): string
    {
        return (string) preg_replace('/(?<!\r)\n/', "\r\n", $html);
    }

    /** ปุ่ม "โหลดเพิ่ม" ที่อยู่หัวรายการ */
    public static function loadMore(mixed $page): string
    {
        return '<li class="clearfix odd text-center load_more_btn">
                <button class="btn btn-default waves-effect waves-light" onclick="Load('.Php74::str(Php74::num($page) + 1).', false)">'.Lang::get('load_more').'</button>
            </li>';
    }
}
