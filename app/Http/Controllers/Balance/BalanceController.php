<?php

namespace App\Http\Controllers\Balance;

use App\Domain\Balance\Ledger;
use App\Domain\Balance\Withdraw;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Blocklist;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * บัญชีเงินบาทของลูกค้า: _balance/*.php เดิม (สิทธิ์ balance)
 * ทุก query กรอง user = ผู้ที่ล็อกอิน
 */
class BalanceController extends Controller
{
    private const AJAX = '_balance/grid';

    /** GET /balance — _balance/index.php */
    public function index(): View
    {
        return view('balance.customer.index');
    }

    /** POST /_balance/grid — _balance/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('balance')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        $uid = Legacy::userId();
        $s = Ledger::search($request);

        $q = DB::table('balance')
            ->leftJoin('balance_type', 'balance.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->where('balance.is_delete', '0')
            ->where('balance.user', $uid);
        if ($s['term'] !== '') {
            $like = Ledger::contains(Ledger::stripPrefix('balance', $s['term']));
            $q->where(fn ($w) => $w->where('balance.id', 'like', $like)->orWhere('balance.detail', 'like', $like));
        }
        if (Ledger::typeFilterApplies($s['type'])) {
            $q->where('balance.type', $s['type']);
        }

        // รูปสลิปของรายการถอน (type 12) + ยอดสะสมก่อนหน้า = join/subquery (เดิม 2 query ต่อแถว)
        $q->leftJoin('balance_withdraw as bw', function ($j) {
            $j->on('bw.id', '=', 'balance.prefix_ref_id')->where('balance.type', '=', 12);
        });
        $page = Ledger::php($request, 'page');
        [$total, $items] = Ledger::page($q, 'balance', $page, [
            'balance.*', 'balance_type.name', 'prefix.link', 'bw.approved_upload as w_upload',
            DB::raw('(SELECT SUM(b2.amount) FROM `balance` b2 WHERE b2.id < balance.id AND b2.`user` = '.(int) $uid.' AND b2.is_delete = 0) as prev_sum'),
        ]);

        return response(view('balance.customer.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'types' => Ledger::activeTypes(),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Ledger::prefixText('balance'),
        ])->render());
    }

    /** POST /_balance/load_withdraw — _balance/load_withdraw.php */
    public function loadWithdraw(): Response
    {
        if (! perm('balance')) {
            return response('');
        }

        return response(view('balance.customer.load_withdraw', [
            'current' => Withdraw::currentCash(Legacy::userId()),
        ])->render());
    }

    /** POST /_balance/save_withdraw — _balance/save_withdraw.php */
    public function saveWithdraw(Request $request): Response
    {
        if (! perm('balance')) {
            return response('');
        }
        $uid = Legacy::userId();
        $wantPhp = Withdraw::cleanAmount(Ledger::php($request, 'wantcash'));
        $wantSql = Withdraw::cleanAmount(Ledger::in($request, 'wantcash'));
        $bank = [Ledger::in($request, 'bank_no'), Ledger::in($request, 'branch_bank'), Ledger::in($request, 'branch_name')];

        // บัญชีดำมิจฉาชีพ — เทียบชื่อ+นามสกุลตรงทั้งคู่
        if (Blocklist::guard(Ledger::php($request, 'branch_name'), 'withdraw_thb', 'ธนาคาร='.Ledger::php($request, 'branch_bank')) !== false) {
            return response(Alerts::error('CloseLoading()', 'ไม่สามารถบันทึกข้อมูลบัญชีนี้ได้ กรุณาติดต่อเจ้าหน้าที่'));
        }
        // เดิมนับรายการถอนในสัปดาห์นี้ + bypass_withdraw แต่ส่วนที่ใช้ผลถูก comment ไว้ (ไม่มีผล) → ไม่ทำ

        $ok = Withdraw::submit($uid, $wantPhp, $wantSql, fn () => Withdraw::currentCash($uid), $bank,
            'balance_withdraw', 'balance', 'withdrawcash', 'โอนเงินออกจากระบบ', 12);
        if (! $ok) {
            return response(Withdraw::errorJs());
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('balance').'";', Lang::get('success_cash')));
    }
}
