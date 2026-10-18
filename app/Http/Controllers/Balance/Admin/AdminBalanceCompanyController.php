<?php

namespace App\Http\Controllers\Balance\Admin;

use App\Domain\Balance\Ledger;
use App\Domain\Balance\Php74;
use App\Domain\Money\Money;
use App\Http\Controllers\Controller;
use App\Support\GridQuery;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แอดมิน บัญชีบริษัท: _admin/_balance_company/*.php เดิม
 * สิทธิ์ admin_balance_company (+ admin_balance_company_delete สำหรับยอดบวก/แก้ไข) — ใน DB ยังไม่มี role ไหนได้สิทธิ์นี้
 */
class AdminBalanceCompanyController extends Controller
{
    private const AJAX = '_admin/_balance_company/grid';

    /** GET /admin/balance_company — _admin/_balance_company/index.php */
    public function index(): View
    {
        return view('balance.company.index');
    }

    /** POST /_admin/_balance_company/grid — _admin/_balance_company/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_balance_company')) {
            return response('');
        }
        $s = Ledger::search($request);

        $q = DB::table('balance_company')
            ->leftJoin('user', 'user.id', '=', 'balance_company.create_by')
            ->where('balance_company.is_delete', '0');
        if ($s['term'] !== '') {
            $like = Ledger::contains(Ledger::stripPrefix('balance_company', $s['term']));
            $q->where(fn ($w) => $w->where('balance_company.id', 'like', $like)
                ->orWhere('balance_company.detail', 'like', $like)
                ->orWhere('user.code', 'like', $like));
        }
        // เดิม: ไม่มีการกรองตามประเภท (dropdown แสดงเฉยๆ)
        $page = Ledger::php($request, 'page');
        [$total, $items] = Ledger::page($q, 'balance_company', $page, [
            'balance_company.*', 'user.code', 'user.id as user_id',
        ]);
        // ยอดสะสมก่อนหน้า (เดิม SUM ต่อแถว, ทั้งบริษัทไม่แยก user) → query เดียว
        GridQuery::attachPrevSums('balance_company', $items, null);

        return response(view('balance.company.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'types' => Ledger::activeTypes(),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Ledger::prefixText('balance_company'),
        ])->render());
    }

    /** POST /_admin/_balance_company/load_amount — _admin/_balance_company/load_amount.php */
    public function loadAmount(Request $request): Response
    {
        $row = DB::table('balance_company')->where('balance_company.id', Ledger::in($request, 'id'))->first(['balance_company.*']);

        return response(Ledger::mysqliJson($row));
    }

    /** POST /_admin/_balance_company/save_amount — _admin/_balance_company/save_amount.php */
    public function saveAmount(Request $request): Response
    {
        $id = Ledger::in($request, 'id');
        $detail = Ledger::in($request, 'detail');
        // แก้: เดิมลงยอดดิบ ("1,000" = 1.00, "abc" = 0.00) → ตัวเลขเท่านั้น (ดู Money::adminAmount)
        $amount = Money::adminAmount(Ledger::in($request, 'amount'));
        if ($amount === null) {
            return response(Money::refuse(Money::BAD_ADMIN_AMOUNT));
        }

        // เดิม: if($amount > 0) ต้องมี admin_balance_company_delete (เทียบแบบ PHP 7.4 — 8.x เทียบ "abc" > 0 เป็น string)
        if (Php74::cmp($amount, 0) > 0 && ! perm('admin_balance_company_delete')) {
            return response(Alerts::permission('admin_balance_company_delete'));
        }
        $uid = Legacy::userId();
        if ($id === '') {
            DB::table('balance_company')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'amount' => $amount, 'detail' => $detail,
            ]);
        } else {
            if (! perm('admin_balance_company_delete')) {
                return response(Alerts::permission('admin_balance_company_delete'));
            }
            DB::table('balance_company')->where('id', $id)->update([
                'detail' => $detail, 'amount' => $amount, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
        }

        return response(Alerts::success());
    }
}
