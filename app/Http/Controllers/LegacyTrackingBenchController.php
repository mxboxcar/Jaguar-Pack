<?php

namespace App\Http\Controllers;

use App\Models\Tracking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ใช้วัดผลเท่านั้น: ยิง query ชุดเดียวกับ _tracking/grid.php เดิมทุกตัว
 * (ดึงทุกแถวมานับ, LIMIT 20, แล้ว query ทีละแถว) เพื่อแยกผลของ framework/PHP ออกจากผลของการแก้ query
 */
class LegacyTrackingBenchController extends Controller
{
    public function index(int $userId): View
    {
        // getPermission('tracking') เดิม = 3 query (checkUser + role + role_on_detail)
        DB::select('SELECT * FROM `user` WHERE `id` = ?', [$userId]);
        $role = DB::selectOne('SELECT role.id FROM `user` LEFT JOIN `role` ON `role`.id = `user`.role WHERE `user`.id = ?', [$userId]);
        DB::select("SELECT role_detail.role_detail_name, role_on_detail.permission FROM `role_on_detail`
            LEFT JOIN `role_detail` ON `role_detail`.id = `role_on_detail`.role_detail_id
            WHERE `role_on_detail`.`role_id` = ? AND role_detail.`role_detail_name` = 'tracking' AND role_on_detail.is_delete = 0", [$role->id ?? 0]);

        $sql = "SELECT tracking.* , user.code, lot.name as lot_name , lot_id as lot_id, product_type.name as protype_name , lot.type as lot_type
            FROM `tracking` FORCE INDEX (`idx_user_del_id`)
            LEFT JOIN `user` ON `user`.id = tracking.user
            LEFT JOIN `lot` ON lot.id = tracking.lot_id
            LEFT JOIN `product_type` ON product_type.id = tracking.product_type_id
            WHERE tracking.is_delete = 0
            AND (`tracking` LIKE '%%' OR `order` LIKE '%%' OR `quote` LIKE '%%') AND `user` = ?";

        // เหมือน mysqli_num_rows เดิม: ดึงผลแบบ buffered มานับ โดยไม่สร้าง object ทีละแถว
        $stmt = DB::getPdo()->prepare($sql);
        $stmt->execute([$userId]);
        $total = $stmt->rowCount();
        $stmt->closeCursor();
        $rows = DB::select($sql.' ORDER BY tracking.id DESC LIMIT 0,20', [$userId]);

        $orderIds = [];
        $items = [];
        foreach ($rows as $row) {
            $ids = DB::select('select t2.bill_product_id from tracking
                LEFT JOIN bill_product_on_tracking as t ON t.tracking_id = tracking.id
                LEFT JOIN bill_product_detail as t2 ON t.bill_product_detail_id = t2.id
                WHERE tracking.id = ? AND tracking.is_delete = 0', [$row->id]);
            $orderIds[$row->id] = array_values(array_filter(array_column($ids, 'bill_product_id')));

            $t = (new Tracking)->forceFill((array) $row);
            $t->setRelation('lot', $row->lot_name !== null ? (object) ['name' => $row->lot_name] : null);
            $t->setRelation('productType', $row->protype_name !== null ? (object) ['name' => $row->protype_name] : null);
            $items[] = $t;
        }

        return view('tracking.index', [
            'trackings' => new LengthAwarePaginator($items, $total, 20, 1, ['path' => request()->url()]),
            'orderIds' => $orderIds,
            'q' => '',
            'status' => -1,
        ]);
    }
}
