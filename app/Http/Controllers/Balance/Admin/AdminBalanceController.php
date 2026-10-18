<?php

namespace App\Http\Controllers\Balance\Admin;

use App\Domain\Balance\Ledger;
use App\Domain\Balance\Php74;
use App\Domain\Money\Money;
use App\Http\Controllers\Controller;
use App\Support\GridQuery;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แอดมิน บัญชีเงินบาทของลูกค้าทุกคน: _admin/_balance/*.php เดิม (สิทธิ์ admin_balance)
 */
class AdminBalanceController extends Controller
{
    private const AJAX = '_admin/_balance/grid';

    /** GET /admin/balance — _admin/_balance/index.php */
    public function index(): View
    {
        return view('balance.admin.index');
    }

    /** POST /_admin/_balance/grid — _admin/_balance/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_balance')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        $s = Ledger::search($request);

        // ค้นหา/กรองประเภทใช้แค่คอลัมน์ของ balance → นับด้วย GridQuery::liveCount (เดิม COUNT ผ่าน LEFT JOIN 5 ตาราง ≈ 2 วินาที)
        $ids = $s['term'] !== '' ? $this->searchIds($s['term']) : null;
        $filter = function (Builder $q) use ($ids, $s) {
            if ($ids !== null) {
                // whereIntegerInRaw: ids เป็น int ทั้งหมด (cast ใน searchIds) — whereIn ปกติ bind ทีละตัว
                // แล้วพังเมื่อเกิน 65,535 placeholder (MySQL 1390) เช่นค้น "JL" บน production ได้ ~600k แถว
                $q->whereIntegerInRaw('balance.id', $ids);
            }
            if (Ledger::typeFilterApplies($s['type'])) {
                $q->where('balance.type', $s['type']);
            }
        };
        $q = DB::table('balance')
            ->leftJoin('balance_type', 'balance.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->leftJoin('user', 'user.id', '=', 'balance.user')
            ->where('balance.is_delete', '0');
        $filter($q);

        // ผู้ทำรายการ / รูปสลิปถอนเงิน — เดิม query ต่อแถว
        $q->leftJoin('user as cb', 'cb.id', '=', 'balance.create_by')
            ->leftJoin('balance_withdraw as bw', function ($j) {
                $j->on('bw.id', '=', 'balance.prefix_ref_id')->where('balance.type', '=', 12);
            });
        $page = Ledger::php($request, 'page');
        [$total, $items] = Ledger::page($q, 'balance', $page, [
            'balance.*', 'balance_type.name', 'prefix.link_admin as link', 'user.code', 'user.id as user_id',
            'cb.code as create_code', 'bw.approved_upload as w_upload',
        ], GridQuery::liveCount('balance', $filter));
        // ยอดสะสมก่อนหน้าของลูกค้าแต่ละแถว (เดิม SUM ต่อแถว) — b2.user = IFNULL(user.id, 0) แบบเดิม
        GridQuery::attachPrevSums('balance', $items, fn ($item) => (int) ($item->user_id ?? 0));

        return response(view('balance.admin.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'types' => Ledger::activeTypes(),
            'allTypes' => Ledger::allTypes(),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Ledger::prefixText('balance'),
        ])->render());
    }

    /**
     * ค้นหาเดิม: ตัด prefix + ตัด ' % _ แล้ว UNION ของ (รหัสลูกค้าขึ้นต้นด้วย, detail ขึ้นต้นด้วย, id/prefix_ref_id ตรงตัว)
     * คงรูปแบบ 2 ขั้น (หา id ก่อนแล้ว IN) เหมือนเดิมเพื่อให้ใช้ index ได้ — ทุกค่า bind
     *
     * @return list<int>
     */
    private function searchIds(string $term): array
    {
        $t = str_replace(["'", '%', '_'], '', Ledger::stripPrefix('balance', $term));
        $num = (string) preg_replace('/^[A-Za-z]+-/', '', $t);

        $q = DB::table('balance')->select('id')->where('is_delete', 0)
            ->whereIn('user', DB::table('user')->select('id')->where('code', 'like', $t.'%'))
            ->union(DB::table('balance')->select('id')->where('is_delete', 0)->where('detail', 'like', $t.'%'));
        if (Php74::isNumeric($num)) {
            $q->union(DB::table('balance')->select('id')->where('is_delete', 0)->where('id', $num))
                ->union(DB::table('balance')->select('id')->where('is_delete', 0)->where('prefix_ref_id', $num));
        }
        $ids = $q->pluck('id')->map(fn ($v) => (int) $v)->all();

        return $ids === [] ? [0] : $ids;
    }

    /** POST /_admin/_balance/load_amount — _admin/_balance/load_amount.php (JSON แบบ mysqli_fetch_array) */
    public function loadAmount(Request $request): Response
    {
        $row = DB::table('balance')->leftJoin('user', 'user.id', '=', 'balance.user')
            ->where('balance.id', Ledger::in($request, 'id'))
            ->first(['balance.*', 'user.code']);

        return response(Ledger::mysqliJson($row));
    }

    /** POST /_admin/_balance/save_amount — _admin/_balance/save_amount.php (id ว่าง = เพิ่ม, ไม่ว่าง = แก้) */
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
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => $userId, 'amount' => $amount, 'detail' => $detail, 'type' => $type,
                'prefix' => '', 'prefix_ref_id' => '',
            ]);
        } else {
            // เดิมไม่อัปเดต mod_date/mod_by — คงไว้
            DB::table('balance')->where('id', $id)->update([
                'user' => $userId, 'detail' => $detail, 'type' => $type, 'amount' => $amount,
            ]);
        }

        return response(Alerts::success());
    }
}
