<?php

namespace App\Http\Controllers\Lot\Admin;

use App\Domain\Lot\LotLookups;
use App\Domain\Lot\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Lot\Concerns\ReadsLegacyInput;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * หน้าแอดมินล็อตและ ajax ที่อ่านข้อมูล: _admin/_lot/index.php, form.php, grid.php, load_*.php เดิม
 * สิทธิ์ = admin_lot หรือ admin_lot_view (ตรวจที่ middleware legacy.perm เหมือน getPermission เดิม)
 */
class AdminLotController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_lot/grid';

    /** GET /admin/lot — _admin/_lot/index.php */
    public function index(Request $request): View
    {
        return view('lot.admin.index', ['status' => (string) LegacyInput::query($request, 'status')]);
    }

    /** GET /admin/lot/create, /admin/lot/{id} — _admin/_lot/form.php */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $lot = DB::table('lot')->where('id', $id)->where('is_delete', 0)->first();
        $lastNo = DB::table('tracking')->where('lot_id', $id)->where('is_delete', '0')->orderByDesc('no')->value('no');

        return view('lot.admin.form', [
            'id' => $id,
            'd' => (array) ($lot ?? []),
            'prefix' => LotLookups::prefix('lot'),
            'nextNo' => Php74::num($lastNo) + 1,
            // เดิม: $permission_lot_view = true เมื่อมี admin_lot_view (แม้มี admin_lot ด้วย) → ซ่อนช่องราคา
            'lotView' => perm('admin_lot_view'),
        ]);
    }

    /** POST /_admin/_lot/grid — _admin/_lot/grid.php */
    public function grid(Request $request): Response
    {
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $type = $parts[1] ?? null;

        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        $q = DB::table('lot')->where('lot.is_delete', 0)
            ->where(fn ($w) => $w->where('lot.id', 'like', $like)->orWhere('lot.name', 'like', $like));
        if ($type !== null && Php74::neq($type, '-2') && Php74::neq($type, '')) {
            $q->where('lot.type', $type);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = LotLookups::offset($page);
        $items = $offset === null ? collect() : $q->leftJoin('user', 'lot.create_by', '=', 'user.id')
            ->orderByDesc('lot.id')->offset($offset)->limit(10)
            ->get(['lot.*', 'user.code']);

        // เดิม: LEFT JOIN (COUNT ทั้งตาราง tracking GROUP BY lot_id) + 3 query ต่อแถว (tracking แรกของล็อต)
        // → นับเฉพาะล็อตในหน้านี้ และดึง tracking แรก (id น้อยสุด รวมที่ถูกลบ) ของทุกล็อตใน query เดียว
        $ids = $items->pluck('id')->map(fn ($v) => (int) $v)->all();
        $counts = [];
        $first = [];
        if ($ids !== []) {
            $counts = DB::table('tracking')->where('is_delete', 0)->whereIn('lot_id', $ids)
                ->groupBy('lot_id')->pluck(DB::raw('COUNT(id)'), 'lot_id')->all();
            $min = DB::table('tracking')->select('lot_id', DB::raw('MIN(id) as mid'))->whereIn('lot_id', $ids)->groupBy('lot_id');
            $first = DB::table('tracking as t')->joinSub($min, 'm', 'm.mid', '=', 't.id')
                ->get(['t.lot_id', 't.date_warehouse', 't.date_outofchina', 't.date_thai'])
                ->keyBy(fn ($r) => (int) $r->lot_id)->all();
        }

        return response(view('lot.admin.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'counts' => $counts,
            'first' => $first,
            'search' => $phpParts[0],
            'type' => isset($parts[1]) ? ($phpParts[1] ?? '') : null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** POST /_admin/_lot/load_lot_detail — _admin/_lot/load_lot_detail.php */
    public function loadLotDetail(Request $request): Response
    {
        $search = $this->in($request, 'search');
        if ($this->php($request, 'search') === 'undefined') {
            $search = '';
        }
        $q = DB::table('tracking')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->leftJoin('bill_product_on_tracking as t', 't.tracking_id', '=', 'tracking.id')
            ->leftJoin('bill_product_detail as t2', 't.bill_product_detail_id', '=', 't2.id')
            ->where('tracking.lot_id', $this->in($request, 'id'))
            ->where('tracking.is_delete', 0)
            ->whereRaw('(tracking.header = 0 OR tracking.header IS NULL)');
        if ($search !== '') {
            $q->where(fn ($w) => $w->where('user.code', $search)->orWhere('tracking.tracking', $search));
        }
        // GROUP BY tracking.id แบบเดิม (sql_mode ว่าง: เลือก bill_product_id แถวแรก)
        $rows = $q->groupBy('tracking.id')->orderByDesc('tracking.no')
            ->get(['tracking.*', 'user.code', 'product_type.name as type_name', 't2.bill_product_id']);

        return response(view('lot.admin.detail', [
            'rows' => $rows,
            // ค่าในช่องค้นหา = ตัวแปร PHP เดิม (' เป็น '')
            'search' => $this->php($request, 'search') === 'undefined' ? '' : $this->php($request, 'search'),
            'orderPrefix' => LotLookups::prefix('order'),
        ])->render());
    }

    /** POST /_admin/_lot/load_lastest_lot — _admin/_lot/load_lastest_lot.php */
    public function loadLastestLot(Request $request): Response
    {
        $no = DB::table('tracking')->where('lot_id', $this->in($request, 'id'))->where('is_delete', '0')->orderByDesc('no')->value('no');

        return response(Php74::str(Php74::num($no) + 1));
    }

    /** POST /_admin/_lot/load_tracking — _admin/_lot/load_tracking.php (JSON แบบ mysqli_fetch_array เดิม) */
    public function loadTracking(Request $request): Response
    {
        $tracking = str_replace(' ', '', $this->in($request, 'tracking'));
        $lot = DB::table('lot')->where('id', $this->in($request, 'lot_id'))->first();
        $data = DB::table('tracking')->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->where('tracking.is_delete', 0)->where('tracking.tracking', $tracking)
            ->first(['tracking.*', 'user.code']);

        $types = DB::table('product_type')->where('is_delete', 0)->where('type', (string) ($lot->type ?? ''))->orderBy('id')->get();
        $html = '<select name="product_type_id" onchange="ontempcal()" class="form-control " >';
        foreach ($types as $row) {
            $sel = Php74::eq($data->product_type_id ?? null, $row->id) ? 'selected="selected" ' : '';
            $html .= '<option '.$sel.'value="'.$row->id.'">'.e((string) $row->name).'</option>';
        }
        $html .= '</select>';

        $diff = $data === null ? null : DB::table('user')->where('id', $data->user)->value('diff');
        $calc = Pricing::getCalPrice($data->id ?? null);
        $exp = explode('|', $calc);
        $choose = 0;
        if (Php74::gt($exp[1], $exp[0]) && Php74::gt(Php74::num($exp[1]) - Php74::num($exp[0]), $diff)) {
            $choose = 1;
        }

        $craft = [[]];
        if ($data !== null && Php74::eq($data->is_craft, 1)) {
            foreach (DB::table('tracking')->where('header', (string) $data->id)->orderBy('id')->get() as $row) {
                $craft[] = LotLookups::both($row);
            }
        }

        $res = [
            LotLookups::both($data),
            $html,
            $calc,
            ($data->price_kg ?? '').'|'.($data->price_cubic ?? ''),
            $choose,
            $craft,
            LotLookups::both($lot),
        ];

        return response(json_encode($res));
    }

    /** POST /_admin/_lot/load_user_rate — _admin/_lot/load_user_rate.php */
    public function loadUserRate(Request $request): Response
    {
        $code = $this->in($request, 'code');
        $types = DB::table('product_type')->where('is_delete', 0)->orderBy('id')->get(['id', 'kg', 'cubic']);
        $current = DB::table('user_type_detail')
            ->leftJoin('user', 'user.type', '=', 'user_type_detail.user_type_id')
            ->where('user.code', $code)->where('user_type_detail.is_delete', 0)->where('user.is_delete', 0)
            ->orderBy('user_type_detail.id')
            ->get(['user_type_detail.product_type_id', 'user_type_detail.kg', 'user_type_detail.cubic']);

        $str = null;
        foreach ($types as $row) {
            $hit = $current->first(fn ($it) => Php74::eq($it->product_type_id, $row->id));
            $src = $hit ?? $row;
            $pid = $hit ? $hit->product_type_id : $row->id;
            $str .= "<input type='hidden' class='product_type_rate_".$pid."' kg='".$src->kg."' cubic='".$src->cubic."' />";
        }
        $diff = DB::table('user')->where('code', $code)->value('diff');

        return response(json_encode([$str, $diff === null ? null : (string) $diff]));
    }

    /** POST /_admin/_lot/load_autocomplete_user — _admin/_lot/load_autocomplete_user.php */
    public function autocompleteUser(Request $request): Response
    {
        $codes = DB::table('user')->where('code', 'like', '%'.addcslashes($this->in($request, 'query'), '%_\\').'%')
            ->where('is_enable', '1')->where('is_delete', 0)->orderBy('id')->pluck('code')
            ->map(fn ($v) => $v === null ? null : (string) $v)->all();

        return response(json_encode(['code' => $codes === [] ? null : $codes]));
    }

    /** POST /_admin/_lot/load_autocomplete_tracking — _admin/_lot/load_autocomplete_tracking.php (JS ที่เรียกถูก comment ไว้) */
    public function autocompleteTracking(Request $request): Response
    {
        $list = DB::table('tracking')->where('tracking', 'like', '%'.addcslashes($this->in($request, 'query'), '%_\\').'%')
            ->where('is_delete', 0)->orderBy('id')->pluck('tracking')
            ->map(fn ($v) => $v === null ? null : (string) $v)->all();

        return response(json_encode(['code' => $list === [] ? null : $list]));
    }

    /** POST /_admin/_lot/load_ref_print — _admin/_lot/load_ref_print.php */
    public function loadRefPrint(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $lots = DB::table('lot')
            ->where(fn ($w) => $w->where('id', $id)->orWhere('name', 'like', '%'.addcslashes($this->in($request, 'search'), '%_\\').'%'))
            ->where('is_delete', 0)->orderByDesc('id')->limit(10)->get(['id', 'type', 'name']);

        return response(view('lot.admin.ref_print', ['lots' => $lots, 'id' => $this->php($request, 'id')])->render());
    }

    /** POST /_admin/_lot/load_check_tracking — _admin/_lot/load_check_tracking.php (รับพัสดุเข้าไทย) */
    public function loadCheckTracking(Request $request): Response
    {
        $tracking = str_replace(' ', '', trim($this->in($request, 'tracking')));
        $rows = collect();
        // เดิม: ช่องว่าง = LIKE '%%' → ดึง tracking ทั้งระบบ (~470k แถว + 2 query ต่อแถว) จนหน้าค้าง — ที่นี่ถือว่าไม่พบข้อมูล
        if ($tracking !== '') {
            $rows = DB::table('tracking')
                ->leftJoin('user', 'tracking.user', '=', 'user.id')
                ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
                ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
                ->where('tracking.is_delete', 0)
                ->where('tracking.tracking', 'like', '%'.addcslashes($tracking, '%_\\').'%')
                ->where('tracking.tracking', '!=', '-2')
                ->orderBy('tracking.lot_id')->orderBy('tracking.no')
                ->get(['tracking.*', 'user.code', 'product_type.name as product_type_name', 'lot.name as lot_name', 'lot.type as lot_type']);
        }
        if ($rows->count() === 1) {
            DB::table('tracking')->where('id', $rows[0]->id)->update(['is_checked' => '1', 'checked_by' => (int) \App\Support\Legacy\Legacy::userId()]);
        }

        // เดิม 2 query ต่อแถว (เลขออเดอร์ในระบบ, admin_quote) → ดึงครั้งเดียว
        $ids = $rows->pluck('id')->map(fn ($v) => (int) $v)->all();
        $orders = [];
        $quotes = [];
        if ($ids !== []) {
            $links = DB::table('bill_product_on_tracking as t')
                ->leftJoin('bill_product_detail as t2', 't.bill_product_detail_id', '=', 't2.id')
                ->leftJoin('bill_product as t3', 't2.bill_product_id', '=', 't3.id')
                ->whereIn('t.tracking_id', $ids)->orderBy('t.id')
                ->get(['t.tracking_id', 't2.bill_product_id', 't3.admin_quote']);
            foreach ($links as $l) {
                $k = (int) $l->tracking_id;
                if ($l->bill_product_id !== null && (string) $l->bill_product_id !== '') {
                    $orders[$k][] = $l->bill_product_id;
                }
                if (! array_key_exists($k, $quotes)) {
                    $quotes[$k] = $l->admin_quote;
                }
            }
        }

        return response(view('lot.admin.check_tracking', [
            'rows' => $rows,
            'orders' => $orders,
            'quotes' => $quotes,
            'orderPrefix' => LotLookups::prefix('order'),
            'payPrefix' => LotLookups::prefix('payment'),
        ])->render());
    }

    /** POST /_admin/_lot/load_lot_memo — _admin/_lot/load_lot_memo.php */
    public function loadLotMemo(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $memos = DB::table('lot_memo')->leftJoin('user', 'lot_memo.user', '=', 'user.id')
            ->where('lot_memo.lot_id', $id)->where('lot_memo.is_delete', 0)
            ->orderByDesc('lot_memo.no')
            ->get(['lot_memo.*', 'user.code', 'user.id as user_id']);
        $max = DB::table('lot_memo')->where('lot_id', $id)->where('is_delete', 0)->orderByDesc('no_max')->value('no_max');

        return response(view('lot.admin.memo', ['memos' => $memos, 'nextNo' => Php74::num($max) + 1])->render());
    }
}
