<?php

namespace App\Http\Controllers\Balance;

use App\Domain\Balance\Ledger;
use App\Domain\Balance\Withdraw;
use App\Domain\Money\Money;
use App\Http\Controllers\Controller;
use App\Support\GridQuery;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * บัญชีเงินหยวนของลูกค้า: _balance_cn/*.php เดิม (สิทธิ์ balance_cn)
 * ทุก query กรอง user = ผู้ที่ล็อกอิน
 */
class BalanceCnController extends Controller
{
    private const AJAX = '_balance_cn/grid';

    /** GET /balance_cn — _balance_cn/index.php */
    public function index(): View
    {
        return view('balance.cn.index');
    }

    /** POST /_balance_cn/grid — _balance_cn/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('balance_cn')) {
            return response('');
        }
        $uid = Legacy::userId();
        $s = Ledger::search($request);

        $q = DB::table('balance_cn')
            ->leftJoin('balance_type', 'balance_cn.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance_cn.prefix', '=', 'prefix.id')
            ->where('balance_cn.is_delete', '0')
            ->where('balance_cn.user', $uid);
        if ($s['term'] !== '') {
            $like = Ledger::contains(Ledger::stripPrefix('balance_cn', $s['term']));
            $q->where(fn ($w) => $w->where('balance_cn.id', 'like', $like)->orWhere('balance_cn.detail', 'like', $like));
        }
        if (Ledger::typeFilterApplies($s['type'])) {
            $q->where('balance_cn.type', $s['type']);
        }
        $page = Ledger::php($request, 'page');
        [$total, $items] = Ledger::page($q, 'balance_cn', $page, [
            'balance_cn.*', 'balance_type.name', 'prefix.link',
        ]);
        // ยอดสะสมก่อนหน้า (เดิม SUM ต่อแถว) → query เดียว
        GridQuery::attachPrevSums('balance_cn', $items, fn () => (int) $uid);

        return response(view('balance.cn.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'types' => Ledger::activeTypes(),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Ledger::prefixText('balance_cn'),
        ])->render());
    }

    /** POST /_balance_cn/load_withdraw — _balance_cn/load_withdraw.php */
    public function loadWithdraw(): Response
    {
        if (! perm('balance_cn')) {
            return response('');
        }

        return response(view('balance.cn.load_withdraw', [
            'rate' => Lookup::getSetting('exchange_rate'),
            'current' => Withdraw::currentCashCn(Legacy::userId()),
        ])->render());
    }

    /** POST /_balance_cn/save_withdraw — _balance_cn/save_withdraw.php (แลกหยวนเป็นบาท: รอแอดมินอนุมัติ) */
    public function saveWithdraw(Request $request): Response
    {
        if (! perm('balance_cn')) {
            return response('');
        }
        $uid = Legacy::userId();
        $wantPhp = Withdraw::amount(Ledger::php($request, 'wantcash'));
        $wantSql = Withdraw::amount(Ledger::in($request, 'wantcash'));
        if ($wantPhp === null || $wantSql === null) {
            return response(Alerts::error('CloseLoading()', Money::BAD_AMOUNT));
        }

        // เดิม $bank_no/$branch_bank/$branch_name ไม่ได้กำหนดในไฟล์นี้ → '' (เว้นแต่ส่งมาทาง GET ผ่าน extract($_GET) — ไม่เลียนแบบ)
        $ok = Withdraw::submit($uid, $wantPhp, $wantSql, fn () => Withdraw::currentCashCn($uid), ['', '', ''],
            'balance_withdraw_cn', 'balance_cn', 'exchange', 'แลกเงินหยวนเป็นเงินบาท', 13);
        if (! $ok) {
            return response(Withdraw::errorJs());
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('balance_cn').'";', Lang::get('success_exchange')));
    }
}
