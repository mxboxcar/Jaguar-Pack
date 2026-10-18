<?php

namespace App\Http\Controllers\Reports\Admin;

use App\Domain\Reports\Rows;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * แอดมิน: พัสดุถึงไทยเกิน 15 วันที่ยังไม่ชำระ (_admin/_nobilling/index.php — เดิมเปิดได้แค่ ?page=admin_nobilling)
 */
class AdminNobillingController extends Controller
{
    /** จำนวนพัสดุต่อหน้า — เดิมแสดงทุกแถวในหน้าเดียว (หลักพันแถว) */
    public const PER_PAGE = 100;

    public function index(Request $request): View
    {
        // SQL เดิมทุกตัวอักษร (ไม่มีค่าจากผู้ใช้) → แผน query/ลำดับแถวเท่าเดิม
        $rows = Rows::strAll(DB::select("SELECT tracking.*, user.id as user_id, lot.name as lot_no ,user.code FROM tracking
		        LEFT JOIN `user` ON tracking.user = user.id
		        LEFT JOIN `lot` ON tracking.lot_id = lot.id
		        WHERE `tracking`.date_thai != '0000-00-00 00:00:00'
		        AND `tracking`.date_thai + INTERVAL 15 DAY <= NOW()
		        AND `tracking`.date_finish = '0000-00-00 00:00:00'
		        AND `payment_id` = '0'  AND tracking.`is_delete` = '0'
		        AND `lot`.id != '0'"));
        // เดิม array_unique(code) แล้ววนทุกแถวต่อ code (O(n²)) — จัดกลุ่มครั้งเดียว ลำดับเท่าเดิม (code ที่เจอก่อนขึ้นก่อน)
        // เทียบ code แบบ == ของเดิม: code เป็นข้อความ (null = '' กลุ่มเดียวกัน)
        $groups = [];
        foreach ($rows as $r) {
            $groups[(string) $r->code][] = $r;
        }

        // แบ่งหน้าตามจำนวนพัสดุ (ตั้งใจให้ต่างจากเดิม — เจ้าของสั่ง 2026-10-18): เรียงตามรหัสลูกค้าให้ลำดับคงที่ข้ามหน้า
        // กลุ่มที่ข้ามหน้าจะแสดงหัวรหัสซ้ำในหน้าถัดไป
        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        $flat = [];
        foreach ($groups as $code => $items) {
            foreach ($items as $r) {
                $flat[] = [(string) $code, $r];
            }
        }
        $totalRows = count($flat);
        $pages = max(1, (int) ceil($totalRows / self::PER_PAGE));
        $page = $request->query('page');
        $page = is_string($page) && ctype_digit($page) ? min(max(1, (int) $page), $pages) : 1;
        $pageGroups = [];
        foreach (array_slice($flat, ($page - 1) * self::PER_PAGE, self::PER_PAGE) as [$code, $r]) {
            $pageGroups[$code][] = $r;
        }

        return view('reports.nobilling.index', [
            'groups' => $pageGroups,
            'page' => $page,
            'pages' => $pages,
            'totalGroups' => count($groups),
            'totalRows' => $totalRows,
        ]);
    }
}
