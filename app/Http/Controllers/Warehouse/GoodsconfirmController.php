<?php

namespace App\Http\Controllers\Warehouse;

use App\Domain\Warehouse\Php74;
use App\Domain\Warehouse\UploadBox;
use App\Domain\Warehouse\WarehouseLookups as W;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Warehouse\Admin\AdminGoodsconfirmController;
use App\Http\Controllers\Warehouse\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ยืนยันเจ้าของพัสดุ (ลูกค้า): _goodsconfirm/*.php เดิม — สิทธิ์ goodsconfirm
 *
 * ลูกค้าเห็นได้เฉพาะรายการ "ไม่มีเจ้าของ" (status 0) หรือรายการของตัวเอง (เหมือนหน้า form ของ jl)
 * SR: grid แสดงเฉพาะ status 0; หน้า form ของ SR ตัดการตรวจ "status != 0 และไม่ใช่ของตัวเอง" ออก (ดูรายการที่คนอื่นยึดแล้วได้ = IDOR)
 *     → คงการตรวจของ jl ไว้ (ช่องโหว่เดิม) · save_approved / load_tracking_by_id เดิมไม่ตรวจเจ้าของ (IDOR) → ใช้เงื่อนไขเดียวกัน
 */
class GoodsconfirmController extends Controller
{
    use ReadsLegacyInput;

    /** GET /goodsconfirm */
    public function index(): View
    {
        // เดิม $_GET['status'] แต่ RewriteRule ไม่ส่งค่านี้ → ว่างเสมอ
        return view('warehouse.customer.goodsconfirm.index', ['status' => '']);
    }

    /** POST /_goodsconfirm/grid (เดิมไม่มีสิทธิ์ = ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('goodsconfirm')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;
        $page = $this->php($request, 'page');

        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        // SR: แสดงเฉพาะรายการ "ไม่มีเจ้าของ" (status 0) เสมอ ไม่กรอง user / ไม่ตัดแถวที่ไม่มี tracking
        // (jl: status อื่น = รายการของตัวเอง) — ตัวกรองสถานะอื่นที่ไม่ใช่ -2/ว่าง จึงได้ "ไม่มีข้อมูล" (status 0 AND status = x)
        $q = DB::table('goodsconfirm')
            ->leftJoin('tracking', 'tracking.id', '=', 'goodsconfirm.tracking_id')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->where('goodsconfirm.is_delete', 0)->where('goodsconfirm.status', 0)
            ->where(fn ($w) => $w->where('goodsconfirm.id', 'like', $like)
                ->orWhere('tracking.tracking', 'like', $like)
                ->orWhere('goodsconfirm.submit_remark', 'like', $like));
        if ($status !== null && Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('goodsconfirm.status', $status);
        }
        $total = (clone $q)->count();
        $offset = W::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('goodsconfirm.id')->offset($offset)->limit(10)
            ->get(['goodsconfirm.*', 'tracking.tracking', 'tracking.lot_id', 'tracking.no', 'lot.name as lot_name']);
        $items = $items->map(fn ($r) => W::strings($r));

        return response(view('warehouse.customer.goodsconfirm.grid', [
            'ajaxpage' => '_goodsconfirm/grid',
            'items' => $items,
            'images' => AdminGoodsconfirmController::firstImages($items->pluck('id')->all()),
            'prefix' => W::prefix('goodsconfirm'),
            'search' => $phpParts[0],
            'status' => $phpParts[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** รายการที่ลูกค้าคนนี้เห็นได้: ยังไม่มีเจ้าของ (status 0) หรือเป็นของตัวเอง */
    private static function visible(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->where('goodsconfirm.status', 0)->orWhere('goodsconfirm.user', (int) Legacy::userId()));
    }

    /** GET /goodsconfirm/{id} */
    public function form(Request $request): Response|View
    {
        $id = (int) $request->route('id');
        $data = W::strings(DB::table('goodsconfirm')
            ->leftJoin('tracking', 'tracking.id', '=', 'goodsconfirm.tracking_id')
            ->where('goodsconfirm.id', $id)->where('goodsconfirm.is_delete', 0)
            ->first(['goodsconfirm.*', 'tracking.tracking']));
        // เดิม: ไม่พบ (และ id != "" ซึ่ง 7.4 ถือว่า 0 == "") → ไม่มีสิทธิ์
        // (status != 0 และไม่ใช่ของตัวเอง) → ไม่มีสิทธิ์: มีใน jl, SR ตัดออก (ลูกค้าดูรายการ/ไฟล์แนบที่คนอื่นยึดแล้วได้) — คงไว้กัน IDOR
        if (($data === null && $id !== 0)
            || ($data !== null && Php74::neq($data['status'], 0) && Php74::neq($data['user'], Legacy::userId()))) {
            return response()->view('legacy.no-permission', ['module' => 'goodsconfirm']);
        }
        $d = $data ?? [];

        return view('warehouse.customer.goodsconfirm.form', [
            'id' => $id,
            'd' => $d,
            'prefix' => W::prefix('goodsconfirm'),
            'images' => DB::table('goodsconfirm_detail')->where('goodsconfirm_id', $d['id'] ?? '')
                ->where('is_delete', 0)->orderBy('id')->pluck('image'),
            'approvedUpload' => UploadBox::single('file', $d['approved_upload'] ?? '', Php74::neq($d['id'] ?? null, 0)),
        ]);
    }

    /** POST /_goodsconfirm/load_tracking_by_id (เดิมตอบ getPermissionAlert_Page = <script>) */
    public function loadTrackingById(Request $request): Response
    {
        if (! perm('goodsconfirm')) {
            return response(Alerts::permissionPage('goodsconfirm'));
        }
        $tracking = $this->in($request, 'data');
        // แก้ IDOR: เดิมดูวันที่/ล็อตของ tracking ใดก็ได้ → เฉพาะ tracking ที่มีรายการยืนยันที่ลูกค้าคนนี้เห็นได้
        $allowed = self::visible(DB::table('goodsconfirm')->join('tracking', 'tracking.id', '=', 'goodsconfirm.tracking_id')
            ->where('goodsconfirm.is_delete', 0)->where('tracking.tracking', $tracking))->exists();

        return response(AdminGoodsconfirmController::trackingInfo($tracking, 'col-md-4', 'col-xs-4', $allowed));
    }

    /** POST /_goodsconfirm/save_approved — ลูกค้ากด "เป็นเจ้าของรายการนี้" */
    public function saveApproved(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $file = $this->in($request, 'attachment');
        if (Php74::eq($this->php($request, 'attachment'), '')) {
            return response("ErrorBox('".Lang::get('error_save_goodsconfirm')."', 'CloseLoading();')");
        }
        $out = '';
        $denied = false;
        DB::transaction(function () use ($id, $file, &$out, &$denied) {
            // แก้: เดิมไม่กรอง is_delete → ลูกค้ายึดรายการที่ถูกลบแล้วได้ (หน้า form ไม่แสดงรายการที่ลบ) — ไม่พบ = ไม่ทำอะไร (เดิม UPDATE ไม่ตรงแถวใด)
            $row = W::strings(DB::table('goodsconfirm')->where('id', $id)->where('is_delete', 0)->lockForUpdate()->first(['id', 'status', 'user']));
            // เดิม: ไม่ตรวจเจ้าของ (status != 1 → เขียนทับ user ได้ แม้เป็นรายการที่อนุมัติให้คนอื่นแล้ว)
            if ($row !== null && Php74::neq($row['status'], 0) && Php74::neq($row['user'], Legacy::userId())) {
                $denied = true;

                return;
            }
            if ($row !== null && Php74::neq($row['status'], 1)) {
                // เดิม approved_remark มาจาก extract($_GET) → ว่าง
                DB::table('goodsconfirm')->where('id', $row['id'])->where('is_delete', 0)->update([
                    'status' => '1', 'approved_upload' => $file, 'approved_remark' => '', 'user' => (int) Legacy::userId(),
                ]);
                if (W::tempFileExists($file)) {
                    $out .= W::blobJs($file, "\t         \t\t", "\t         \t\t\t");
                }
            }
        });
        if ($denied) {
            return response(Alerts::permission('goodsconfirm'));
        }

        return response($out.Alerts::success(
            'window.location.href = "'.Legacy::href('goodsconfirm').'";',
            Lang::get('success_process_no').W::prefix('goodsconfirm').Alerts::js($id),
        ));
    }
}
