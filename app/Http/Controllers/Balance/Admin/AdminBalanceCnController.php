<?php

namespace App\Http\Controllers\Balance\Admin;

use App\Domain\Balance\Ledger;
use App\Domain\Money\Money;
use App\Http\Controllers\Controller;
use App\Support\GridQuery;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แอดมิน บัญชีเงินหยวนของลูกค้าทุกคน: _admin/_balance_cn/*.php เดิม (สิทธิ์ admin_balance_cn, เพิ่มรายการ = admin_balance_cn_add)
 */
class AdminBalanceCnController extends Controller
{
    private const AJAX = '_admin/_balance_cn/grid';

    /** GET /admin/balance_cn — _admin/_balance_cn/index.php */
    public function index(): View
    {
        return view('balance.admin_cn.index');
    }

    /** POST /_admin/_balance_cn/grid — _admin/_balance_cn/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_balance_cn')) {
            return response('');
        }
        $s = Ledger::search($request);

        $q = DB::table('balance_cn')
            ->leftJoin('balance_type', 'balance_cn.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance_cn.prefix', '=', 'prefix.id')
            ->leftJoin('user', 'user.id', '=', 'balance_cn.user')
            ->where('balance_cn.is_delete', '0');
        if ($s['term'] !== '') {
            $like = Ledger::contains(Ledger::stripPrefix('balance_cn', $s['term']));
            $q->where(fn ($w) => $w->where('balance_cn.id', 'like', $like)
                ->orWhere('balance_cn.detail', 'like', $like)
                ->orWhere('user.code', 'like', $like));
        }
        if (Ledger::typeFilterApplies($s['type'])) {
            $q->where('balance_cn.type', $s['type']);
        }
        $q->leftJoin('user as cb', 'cb.id', '=', 'balance_cn.create_by');
        $page = Ledger::php($request, 'page');
        [$total, $items] = Ledger::page($q, 'balance_cn', $page, [
            'balance_cn.*', 'balance_type.name', 'prefix.link_admin as link', 'user.code', 'user.id as user_id',
            'cb.code as create_code',
        ]);
        // ยอดสะสมก่อนหน้า (เดิม SUM ต่อแถว) — b2.user = IFNULL(user.id, 0) แบบเดิม
        GridQuery::attachPrevSums('balance_cn', $items, fn ($item) => (int) ($item->user_id ?? 0));

        return response(view('balance.admin_cn.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'types' => Ledger::activeTypes(),
            'allTypes' => Ledger::allTypes(),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Ledger::prefixText('balance_cn'),
        ])->render());
    }

    /** POST /_admin/_balance_cn/load_amount — _admin/_balance_cn/load_amount.php */
    public function loadAmount(Request $request): Response
    {
        $row = DB::table('balance_cn')->leftJoin('user', 'user.id', '=', 'balance_cn.user')
            ->where('balance_cn.id', Ledger::in($request, 'id'))
            ->first(['balance_cn.*', 'user.code']);

        return response(Ledger::mysqliJson($row));
    }

    /** POST /_admin/_balance_cn/save_amount — _admin/_balance_cn/save_amount.php */
    public function saveAmount(Request $request): Response
    {
        $type = Ledger::in($request, 'type');
        $id = Ledger::in($request, 'id');
        $detail = Ledger::in($request, 'detail');
        $amount = Ledger::in($request, 'amount');

        $userId = DB::table('user')->where('code', Ledger::in($request, 'code'))->value('id');
        if ($userId === null) {
            return response("ErrorBox('".Lang::get('no_user')."', 'CloseLoading()'); ");
        }
        // แก้: เดิมลงยอดดิบ ("1,000" = 1.00, "abc" = 0.00, แก้แถวเดิมด้วยค่าผิด = ยอดหาย) → ตัวเลขเท่านั้น (ดู Money::adminAmount)
        $amount = Money::adminAmount($amount);
        if ($amount === null) {
            return response(Money::refuse(Money::BAD_ADMIN_AMOUNT));
        }
        $uid = Legacy::userId();
        if ($id === '') {
            // เดิม: ไม่มีสิทธิ์ admin_balance_cn_add → ไม่บันทึก แต่ยังตอบสำเร็จ (คงไว้)
            if (perm('admin_balance_cn_add')) {
                DB::table('balance_cn')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'user' => $userId, 'amount' => $amount, 'detail' => $detail, 'type' => $type,
                    'prefix' => '', 'prefix_ref_id' => '',
                ]);
            }
        } else {
            DB::table('balance_cn')->where('id', $id)->update([
                'user' => $userId, 'detail' => $detail, 'type' => $type, 'amount' => $amount,
            ]);
        }

        return response(Alerts::success());
    }
}
