<?php

namespace App\Http\Controllers\Points\Admin;

use App\Domain\Points\Grid;
use App\Domain\Points\Php74;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * คำขอแลกแต้มเป็นเงิน (แอดมิน): _admin/_withdrawpoint/*.php เดิม
 * สิทธิ์ admin_withdrawpoint — ไม่มี role ใดได้รับใน DB = เข้าไม่ได้แม้แอดมิน (เหมือนเดิม)
 */
class AdminWithdrawPointController extends Controller
{
    private const AJAX = '_admin/_withdrawpoint/grid';

    /** GET /admin/withdrawpoint, /admin/withdrawpoint/status/{status} — _admin/_withdrawpoint/index.php */
    public function index(): View
    {
        return view('points.admin_withdrawpoint.index');
    }

    /** POST /_admin/_withdrawpoint/grid — _admin/_withdrawpoint/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_withdrawpoint')) {
            return response('');
        }
        $s = Grid::search($request);
        $like = Grid::contains($s['term']);
        $q = DB::table('point')
            ->leftJoin('user', 'point.user', '=', 'user.id')
            ->where('point.is_delete', 0)
            ->where(fn ($w) => $w->where('point.id', 'like', $like)->orWhere('user.code', 'like', $like));
        if (Grid::filterApplies($s['filter'])) {
            $q->where('point.status', $s['filter']);
        }
        $page = Grid::php($request, 'page');
        [$total, $items] = Grid::page($q, 'point.id', $page, ['point.*', 'user.code']);

        return response(view('points.admin_withdrawpoint.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Grid::prefixText('withdrawpoint'),
        ])->render());
    }

    /** GET /admin/withdrawpoint/{id} — _admin/_withdrawpoint/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $data = DB::table('point')->leftJoin('user', 'point.user', '=', 'user.id')
            ->where('point.id', $id)->where('point.is_delete', 0)
            ->first(['point.*', 'user.code', 'user.id as user_id']);

        // รายการบัญชีที่อ้างถึงคำขอนี้ (เดิม 2 query: balance_point แล้ว balance — ลำดับเดิมคงไว้)
        $pointPrefix = Grid::prefixId('point');
        $withdrawPrefix = Grid::prefixId('withdrawpoint');
        $points = DB::table('balance_point')->leftJoin('prefix', 'balance_point.prefix', '=', 'prefix.id')
            ->whereIn('balance_point.prefix', [$withdrawPrefix, $pointPrefix])
            ->where('balance_point.prefix_ref_id', $id)->where('balance_point.is_delete', 0)
            ->orderBy('balance_point.id')
            ->get(['balance_point.*', 'prefix.link_admin as link'])
            ->map(fn ($r) => tap($r, fn ($r) => $r->kind = 'point'));
        $cash = DB::table('balance')->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->whereIn('balance.prefix', [$pointPrefix, $withdrawPrefix])
            ->where('balance.prefix_ref_id', $id)->where('balance.is_delete', 0)
            ->orderBy('balance.id')
            ->get(['balance.*', 'prefix.link_admin as link'])
            ->map(fn ($r) => tap($r, fn ($r) => $r->kind = 'balance'));

        return view('points.admin_withdrawpoint.form', [
            'id' => $id,
            'data' => $data,
            'prefix' => Grid::prefixText('withdrawpoint'),
            'ledger' => $points->concat($cash),
        ]);
    }

    /** POST /_admin/_withdrawpoint/cancel — _admin/_withdrawpoint/cancel.php */
    public function cancel(Request $request): Response
    {
        $id = Grid::in($request, 'id');
        $remark = Grid::in($request, 'cancel_remark').' ,ยกเลิกโดย '.(string) (Legacy::user()->code ?? '');
        $uid = Legacy::userId();

        DB::transaction(function () use ($id, $remark, $uid) {
            $row = DB::table('point')->where('id', $id)->lockForUpdate()->first();
            // เดิมไม่ตรวจสถานะ: ยกเลิกซ้ำ = คืนแต้มซ้ำทุกครั้ง, ยกเลิกรายการที่อนุมัติแล้ว = ได้ทั้งเงินและแต้มคืน,
            // id ที่ไม่มีจริง = INSERT balance_point user ว่าง → ทำเฉพาะรายการที่รออนุมัติ (ปุ่มยกเลิกเดิมแสดงเฉพาะ status 1)
            if ($row === null || ! Php74::eq($row->status, 1)) {
                return;
            }
            DB::table('point')->where('id', $row->id)->update([
                'status' => '-1', 'cancel_remark' => $remark, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            DB::table('balance_point')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => $row->user, 'amount' => $row->amount,
                'detail' => 'ยกเลิกรายการแลกแต้ม รายการแลกแต้ม '.Grid::prefixText('withdrawpoint').$row->id,
                'type' => 1, 'prefix' => Grid::prefixId('withdrawpoint'), 'prefix_ref_id' => $row->id,
            ]);
        });
        // เดิม include_once('email_cancel.php') — ไม่มีไฟล์นี้ใน _admin/_withdrawpoint (warning แล้วทำงานต่อ) = ไม่มีอีเมล

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/withdrawpoint').'";'));
    }

    /** POST /_admin/_withdrawpoint/save_approved — _admin/_withdrawpoint/save_approved.php */
    public function saveApproved(Request $request): Response
    {
        $id = Grid::in($request, 'id');
        $uid = Legacy::userId();

        DB::transaction(function () use ($id, $uid) {
            $row = DB::table('point')->where('id', $id)->lockForUpdate()->first();
            // เดิม: if(status != 2) → อนุมัติได้แม้ถูกยกเลิกแล้ว (ได้แต้มคืน + เงิน) และ id ที่ไม่มีจริงก็ INSERT balance
            // → อนุมัติเฉพาะรายการที่รออนุมัติ (ปุ่มอนุมัติเดิมแสดงเฉพาะ status 1)
            if ($row === null || ! Php74::eq($row->status, 1)) {
                return;
            }
            DB::table('point')->where('id', $row->id)->update(['status' => '2', 'approved_date' => DB::raw('NOW()')]);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => $row->user, 'amount' => $row->amount,
                'detail' => 'แลกแต้ม รายการแลกแต้ม '.Grid::prefixText('withdrawpoint').$row->id,
                'type' => 1, 'prefix' => Grid::prefixId('withdrawpoint'), 'prefix_ref_id' => $row->id,
            ]);
        });
        // เดิม include_once('email_approved.php') — ไม่มีไฟล์นี้ใน _admin/_withdrawpoint = ไม่มีอีเมล

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/withdrawpoint').'";',
            Lang::get('success_process_no').Grid::prefixText('withdrawpoint').Alerts::js($id)));
    }
}
