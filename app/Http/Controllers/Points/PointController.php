<?php

namespace App\Http\Controllers\Points;

use App\Domain\Points\Grid;
use App\Domain\Points\PinBox;
use App\Domain\Points\Rules;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
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

    /** POST /_point/save_withdraw — _point/save_withdraw.php */
    public function saveWithdraw(Request $request): Response
    {
        if (! perm('point')) {
            return response('');
        }
        if (($js = PinBox::guard($request, Legacy::user(), '.cashback_form', 0, 'withdrawpointing()')) !== null) {
            return response($js);
        }

        $uid = Legacy::userId();
        $wantPhp = str_replace('-', '', Grid::php($request, 'wantcash'));
        $wantSql = str_replace('-', '', Grid::in($request, 'wantcash'));

        // เดิมตรวจยอดแล้ว INSERT 2 ตารางแยกกัน ไม่มี transaction/lock → ห่อ transaction + ล็อกแถว user แล้วตรวจยอดภายใน
        $id = DB::transaction(function () use ($uid, $wantPhp, $wantSql) {
            DB::table('user')->where('id', $uid)->lockForUpdate()->value('id');
            $current = Lookup::getCurrentPoint($uid);
            if (Rules::withdrawInvalid($wantPhp, $current)) {
                return null;
            }
            $id = DB::table('point')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'status' => '1', 'user' => $uid, 'amount' => $wantSql, 'date' => DB::raw('NOW()'),
                'attachment' => '', 'remark' => '', 'approved_remark' => '',
            ]);
            DB::table('balance_point')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => $uid, 'amount' => '-'.$wantSql,
                'detail' => 'แลกแต้ม รายการแลกแต้ม '.Grid::prefixText('withdrawpoint').$id,
                'type' => 1, 'prefix' => Grid::prefixId('withdrawpoint'), 'prefix_ref_id' => (string) $id,
            ]);

            return $id;
        });
        if ($id === null) {
            return response("ErrorBox('".Lang::get('error_point')."', 'CloseLoading()'); ");
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('point').'";', Lang::get('success_point')));
    }
}
