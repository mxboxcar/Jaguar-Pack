<?php

namespace App\Http\Controllers\Points;

use App\Domain\Payment\PinBox;
use App\Domain\Points\Grid;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แต้มของลูกค้า: _point/*.php เดิม (สิทธิ์ point) — ทุก query กรอง user = ผู้ที่ล็อกอิน
 */
class PointController extends Controller
{
    private const AJAX = '_point/grid';

    public const NOT_OPEN = 'ยังไม่เปิดบริการให้แลก';

    /** GET /point — _point/index.php */
    public function index(): View
    {
        return view('points.point.index');
    }

    /** POST /_point/grid — _point/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('point')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        $uid = Legacy::userId();
        $s = Grid::search($request);

        $q = DB::table('balance_point')
            ->leftJoin('balance_type', 'balance_point.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance_point.prefix', '=', 'prefix.id')
            ->where('balance_point.is_delete', '0')
            ->where('balance_point.user', $uid);
        if ($s['term'] !== '') {
            $like = Grid::contains(Grid::stripPrefix('point', $s['term']));
            $q->where(fn ($w) => $w->where('balance_point.id', 'like', $like)->orWhere('balance_point.detail', 'like', $like));
        }
        if (Grid::filterApplies($s['filter'])) {
            $q->where('balance_point.type', $s['filter']);
        }

        $page = Grid::php($request, 'page');
        // ยอดคงเหลือก่อนหน้า = subquery ในคำสั่งเดียว (เดิม SUM 1 query ต่อแถว)
        [$total, $items] = Grid::page($q, 'balance_point.id', $page, [
            'balance_point.*', 'balance_type.name', 'prefix.link',
            DB::raw('(SELECT SUM(b2.amount) FROM `balance_point` b2 WHERE b2.id < balance_point.id AND b2.`user` = balance_point.`user` AND b2.is_delete = 0) as prev_sum'),
        ]);

        return response(view('points.point.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Grid::prefixText('point'),
            'admin' => false,
        ])->render());
    }

    /** POST /_point/load_withdraw — _point/load_withdraw.php */
    public function loadWithdraw(): Response
    {
        if (! perm('point')) {
            return response('');
        }

        return response(view('points.point.load_withdraw', [
            'current' => Lookup::getCurrentPoint(Legacy::userId()),
        ])->render());
    }

    /**
     * POST /_point/save_withdraw — _point/save_withdraw.php ของ SR
     * SR: ตรวจ PIN แล้วตอบ getError("CloseLoading()", 'ยังไม่เปิดบริการให้แลก') + exit() ทุกครั้ง — โค้ดแลกแต้ม (INSERT point/balance_point)
     * ที่ตามมาไม่เคยทำงาน → ไม่พอร์ต (ไม่เขียน DB เลย) · PIN ผ่าน PinBox::guard() (hash_equals + PinThrottle) แทน != หลวมของเดิม
     */
    public function saveWithdraw(Request $request): Response
    {
        if (! perm('point')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        if (($js = PinBox::guard($request, Legacy::user(), '.cashback_form', 0, 'withdrawpointing()')) !== null) {
            return response($js);
        }

        return response(Alerts::error('CloseLoading()', self::NOT_OPEN));
    }
}
