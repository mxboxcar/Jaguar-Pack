<?php

namespace App\Http\Controllers\Messaging\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * รายการรอส่ง (bill_payment.status = 2): _admin/_send/index.php เดิม — สิทธิ์ admin_export
 * เดิม getPermissionAlert_Page() แล้วไม่ exit = ผู้ไม่มีสิทธิ์ยังได้ตารางทั้งหมดใน HTML (ที่นี่ไม่ส่งข้อมูลเลย)
 * SR: admin/send ไม่รวมประเภทส่ง 43/53 (รับเอง) — ประเภทนั้นอยู่ที่ admin/sendself (_admin/_send/index_self.php)
 */
class AdminSendController extends Controller
{
    /** GET /admin/send */
    public function index(): View
    {
        return view('messaging.admin.send.index', ['rows' => $this->rows(false)]);
    }

    /** GET /admin/sendself (?page=admin_sendself → _admin/_send/index_self.php) — SR: shipping_type 43 หรือ 53 */
    public function sendself(): View
    {
        return view('messaging.admin.send.index', ['rows' => $this->rows(true)]);
    }

    /**
     * GET /_admin/_send/index_self — เปิดไฟล์ตรงๆ (เดิม fatal error เพราะไม่ได้โหลด system_config)
     * → ตอบเป็นชิ้น HTML ไม่มี layout (หน้าเต็มคือ /admin/sendself)
     */
    public function indexSelf(): Response
    {
        return response(view('messaging.admin.send.table', ['rows' => $this->rows(true)])->render());
    }

    private function rows(bool $self): array
    {
        $q = DB::table('bill_payment')
            ->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->leftJoin('shipping_type', 'bill_payment.shipping_type', '=', 'shipping_type.id')
            ->where('bill_payment.status', '2');
        if ($self) {
            $q->whereIn('bill_payment.shipping_type', ['43', '53']);
        } else {
            $q->where('bill_payment.shipping_type', '!=', '43')->where('bill_payment.shipping_type', '!=', '53');
        }

        return $q->get(['bill_payment.*', 'user.code', 'shipping_type.name as ship_name'])->all();
    }
}
