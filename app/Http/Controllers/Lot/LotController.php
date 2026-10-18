<?php

namespace App\Http\Controllers\Lot;

use App\Domain\Lot\LotLookups;
use App\Domain\Lot\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Lot\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * หน้าล็อตของลูกค้า: _lot/index.php, _lot/form.php, _lot/grid.php, _lot/load_lot_detail.php เดิม
 * ลูกค้าเห็นเฉพาะล็อตที่มี tracking ของตัวเอง และเฉพาะ tracking ของตัวเอง
 */
class LotController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_lot/grid';

    /** GET /lot — _lot/index.php (สิทธิ์ lot ตรวจที่ middleware) */
    public function index(Request $request): View
    {
        // เดิม $_GET['status'] (ไม่มี RewriteRule ที่ส่ง status มา แต่ ?status= ยังใช้ได้)
        return view('lot.customer.index', ['status' => (string) LegacyInput::query($request, 'status')]);
    }

    /** GET /lot/{id} — _lot/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        // เดิมแสดงหัวล็อตของ id ใดก็ได้ — ที่นี่เฉพาะล็อตที่มี tracking ของผู้ใช้ (เหมือนที่หน้า list แสดง)
        $lot = DB::table('lot')->where('id', $id)->where('is_delete', 0)
            ->whereExists(fn ($q) => $q->from('tracking')->whereColumn('tracking.lot_id', 'lot.id')
                ->where('tracking.is_delete', 0)->where('tracking.user', Legacy::userId()))
            ->first();

        return view('lot.customer.form', [
            'id' => $id,
            'd' => (array) ($lot ?? []),
            'prefix' => LotLookups::prefix('lot'),
        ]);
    }

    /** POST /_lot/grid — _lot/grid.php (ไม่มีสิทธิ์ lot = ตอบว่างแบบเดิม) */
    public function grid(Request $request): Response
    {
        if (! perm('lot')) {
            return response('');
        }
        $uid = Legacy::userId();
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;

        // เดิม: subquery COUNT ต่อแถว + HAVING count != 0 → join กับจำนวน tracking ของผู้ใช้ต่อล็อต
        $mine = DB::table('tracking')->select('lot_id', DB::raw('COUNT(id) as `count`'))
            ->where('is_delete', 0)->where('user', $uid)->groupBy('lot_id');
        $q = DB::table('lot as x')
            ->joinSub($mine, 'c', 'c.lot_id', '=', 'x.id')
            ->where('x.is_delete', 0)
            ->where('x.name', 'like', '%'.addcslashes($parts[0], '%_\\').'%');
        if ($status !== null && Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('x.status', $status);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = LotLookups::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('x.id')->offset($offset)->limit(10)
            ->get(['x.*', 'c.count']);

        return response(view('lot.customer.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $phpParts[0],
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** POST /_lot/load_lot_detail — _lot/load_lot_detail.php */
    public function loadLotDetail(Request $request): Response
    {
        $rows = DB::table('tracking')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->where('tracking.lot_id', $this->in($request, 'id'))
            ->where('tracking.is_delete', 0)
            ->where('tracking.user', Legacy::userId())
            ->orderByDesc('tracking.no')
            ->get(['tracking.*', 'product_type.name as type_name']);

        return response(view('lot.customer.detail', [
            'rows' => $rows,
            'payPrefix' => LotLookups::prefix('payment'),
        ])->render());
    }
}
