<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Domain\Settings\Input;
use App\Domain\Settings\Php74;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แลกเงินหยวนเป็นเงินบาท (balance_withdraw_cn): _admin/_exchange/*.php เดิม
 *
 * สิทธิ์ตามไฟล์เดิม (ชื่อโมดูลไม่ตรงกันเองในเว็บเดิม — คงไว้):
 *   index / form / save_approved = admin_exchange, grid = admin_withdrawcash (เดิม exit() เงียบ), cancel = admin_withdrawpoint
 */
class ExchangeController extends Controller
{
    private const AJAX = '_admin/_exchange/grid';

    /** GET /admin/exchange, /admin/exchange/status/{status} — _admin/_exchange/index.php */
    public function index(Request $request): View
    {
        // route defaults (legacy_page) ถูกส่งเป็นพารามิเตอร์ตามตำแหน่งด้วย → อ่านจาก route() ตรงๆ
        return view('settings.admin.exchange.index', ['status' => (string) $request->route('status', '')]);
    }

    /** POST /_admin/_exchange/grid — _admin/_exchange/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_withdrawcash')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        $sql = explode('|', Input::in($request, 'txtsearch'));
        $php = explode('|', Input::php($request, 'txtsearch'));
        $term = $sql[0];
        $status = $sql[1] ?? null;

        $like = Input::contains($term);
        $q = DB::table('balance_withdraw_cn')
            ->leftJoin('user', 'balance_withdraw_cn.user', '=', 'user.id')
            ->where('balance_withdraw_cn.is_delete', 0)
            ->where(fn ($w) => $w->where('balance_withdraw_cn.id', 'like', $like)->orWhere('user.code', 'like', $like));
        // เดิม: if($txtSearch[1] != "-2" && $txtSearch[1] != "")
        if ($status !== null && ! Php74::eq($status, '-2') && ! Php74::eq($status, '')) {
            $q->where('balance_withdraw_cn.status', $status);
        }
        $page = Input::php($request, 'page');
        [$total, $items] = Input::page($q, 'balance_withdraw_cn.id', $page, ['balance_withdraw_cn.*', 'user.code']);

        return response(view('settings.admin.exchange.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'termPhp' => $php[0],
            'status' => $php[1] ?? null,
            'raw' => Input::php($request, 'txtsearch'),
            'page' => $page,
            'total' => $total,
            'prefix' => (string) (Lookup::getPrefix('exchange') ?? ''),
        ])->render());
    }

    /** GET /admin/exchange/{id} — _admin/_exchange/form.php (รายการ balance_cn อ้างอิง: query เดียวแบบเดิม) */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0); // $_GET['id'] เดิมถูก (int)
        $data = DB::table('balance_withdraw_cn')
            ->leftJoin('user', 'balance_withdraw_cn.user', '=', 'user.id')
            ->where('balance_withdraw_cn.id', $id)->where('balance_withdraw_cn.is_delete', 0)
            ->first(['balance_withdraw_cn.*', 'user.code', 'user.id as user_id']);

        $balances = DB::table('balance_cn')
            ->leftJoin('prefix', 'balance_cn.prefix', '=', 'prefix.id')
            ->where('balance_cn.prefix', (string) (Lookup::getPrefixID('exchange') ?? ''))
            ->where('balance_cn.prefix_ref_id', $id)
            ->where('balance_cn.is_delete', 0)
            ->get(['balance_cn.*', 'prefix.link_admin as link']);

        return view('settings.admin.exchange.form', [
            'id' => $id,
            'data' => $data,
            'balances' => $balances,
            'exchangeRate' => Lookup::getSetting('exchange_rate'),
            'prefix' => (string) (Lookup::getPrefix('exchange') ?? ''),
        ]);
    }

    /**
     * POST /_admin/_exchange/save_approved — _admin/_exchange/save_approved.php
     * เดิม: อัปเดต remark/upload ทุกครั้ง แล้วถ้า status != 2 → status 2 + เพิ่มเงินบาท amount × exchange_rate
     * แก้: รายการที่ยกเลิกแล้ว (status -1 ซึ่งคืนเงินหยวนไปแล้ว) อนุมัติซ้ำไม่ได้ — เดิมได้ทั้งเงินหยวนคืนและเงินบาท
     * อีเมลแจ้ง: เดิม include_once('email_success.php') ซึ่งไม่มีไฟล์ในโฟลเดอร์ = ไม่เคยส่ง → ไม่ส่งเหมือนเดิม
     */
    public function saveApproved(Request $request): Response
    {
        $id = Input::in($request, 'id');
        $remark = Input::in($request, 'approved_remark');
        $upload = Input::in($request, 'approved_upload');
        $uid = Legacy::userId();
        $prefixText = (string) (Lookup::getPrefix('exchange') ?? '');

        $blocked = DB::transaction(function () use ($id, $remark, $upload, $uid, $prefixText) {
            $row = DB::table('balance_withdraw_cn')->where('id', $id)->lockForUpdate()->first();
            if ($row === null) {
                return false; // เดิม UPDATE ไม่โดนแถวใด แต่ INSERT balance ขยะ (user '', amount 0) — ไม่ทำ
            }
            if (Php74::eq($row->status, -1)) {
                return true;
            }
            DB::table('balance_withdraw_cn')->where('id', $id)->update([
                'approved_remark' => $remark, 'approved_upload' => $upload, 'approved_date' => DB::raw('NOW()'),
            ]);
            if (! Php74::eq($row->status, 2)) {
                DB::table('balance_withdraw_cn')->where('id', $id)->update(['status' => '2', 'approved_date' => DB::raw('NOW()')]);
                $row = DB::table('balance_withdraw_cn')->where('id', $id)->first();
                $rate = Lookup::getSetting('exchange_rate');
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'user' => (string) ($row->user ?? ''),
                    'amount' => Format::phpFloat((float) (Php74::num($row->amount ?? null) * Php74::num($rate))),
                    'detail' => 'แลกเงินหยวนเป็นเงินบาท '.$prefixText.$id, 'type' => 13,
                    'prefix' => (string) (Lookup::getPrefixID('exchange') ?? ''), 'prefix_ref_id' => $id,
                ]);
            }

            return false;
        });
        if ($blocked) {
            return response(Alerts::error('CloseLoading()', 'รายการนี้ถูกยกเลิกแล้ว อนุมัติไม่ได้'));
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/exchange').'";', Lang::get('success_process_no').$prefixText.Alerts::js($id)));
    }

    /**
     * POST /_admin/_exchange/cancel — _admin/_exchange/cancel.php (สิทธิ์ admin_withdrawpoint ตามเดิม)
     * เดิมเทียบ $data['status'] ก่อนอ่านแถว ($data ยังไม่มีค่า → เงื่อนไขจริงเสมอ) = กดยกเลิกซ้ำได้เงินหยวนคืนซ้ำทุกครั้ง
     * แก้: ทำเฉพาะรายการที่ยังไม่ยกเลิก (status != -1) และยังไม่อนุมัติ (status != 2, เดิมคืนหยวนหลังได้เงินบาทไปแล้ว)
     */
    public function cancel(Request $request): Response
    {
        $id = Input::in($request, 'id');
        $remark = Input::in($request, 'cancel_remark');
        $uid = Legacy::userId();
        $code = (string) (Legacy::user()->code ?? '');
        $prefixText = (string) (Lookup::getPrefix('exchange') ?? '');

        DB::transaction(function () use ($id, $remark, $uid, $code, $prefixText) {
            $row = DB::table('balance_withdraw_cn')->where('id', $id)->lockForUpdate()->first();
            if ($row === null || Php74::eq($row->status, -1) || Php74::eq($row->status, 2)) {
                return;
            }
            DB::table('balance_withdraw_cn')->where('id', $id)->update([
                'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.$code, 'approved_date' => DB::raw('NOW()'),
            ]);
            DB::table('balance_cn')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => (string) ($row->user ?? ''), 'amount' => (string) ($row->amount ?? ''),
                'detail' => 'ยกเลิก แลกเงินหยวนเป็นเงินบาท '.$prefixText.$id, 'type' => 13,
                'prefix' => (string) (Lookup::getPrefixID('exchange') ?? ''), 'prefix_ref_id' => $id,
            ]);
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/exchange').'";'));
    }
}
