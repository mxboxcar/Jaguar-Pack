<?php

namespace App\Http\Controllers\Points\Admin;

use App\Domain\Points\Grid;
use App\Http\Controllers\Controller;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แต้มของลูกค้าทุกคน (แอดมิน): _admin/_point/*.php เดิม (สิทธิ์ admin_point — ไม่มี role ใดได้รับใน DB = เข้าไม่ได้เหมือนเดิม)
 */
class AdminPointController extends Controller
{
    private const AJAX = '_admin/_point/grid';

    /** GET /admin/point — _admin/_point/index.php (?status= ถูกส่งต่อเป็น txtsearch "|<status>") */
    public function index(Request $request): View
    {
        $status = LegacyInput::query($request, 'status');

        return view('points.admin_point.index', ['status' => is_scalar($status) ? (string) $status : '']);
    }

    /** POST /_admin/_point/grid — _admin/_point/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_point')) {
            return response('');
        }
        $s = Grid::search($request);
        $page = Grid::php($request, 'page');

        if (Grid::filterApplies($s['filter'])) {
            // เดิม "AND type = '..'" — คอลัมน์ type มีทั้ง balance_point และ user → MySQL error "ambiguous" → mysqli คืน false = ไม่มีแถว
            [$total, $items] = [0, collect()];
        } else {
            $q = DB::table('balance_point')
                ->leftJoin('balance_type', 'balance_point.type', '=', 'balance_type.id')
                ->leftJoin('prefix', 'balance_point.prefix', '=', 'prefix.id')
                ->leftJoin('user', 'user.id', '=', 'balance_point.user')
                ->where('balance_point.is_delete', '0')
                // เดิมค้นเฉพาะรหัสลูกค้า (LIKE '%%' เมื่อไม่ค้น = ตัดแถวที่ไม่มี user ออก)
                ->where('user.code', 'like', Grid::contains(Grid::stripPrefix('point', $s['term'])));
            [$total, $items] = Grid::page($q, 'balance_point.id', $page, [
                'balance_point.*', 'balance_type.name', 'prefix.link_admin as link', 'user.code', 'user.id as user_id',
                DB::raw('(SELECT SUM(b2.amount) FROM `balance_point` b2 WHERE b2.id < balance_point.id AND b2.`user` = balance_point.`user` AND b2.is_delete = 0) as prev_sum'),
            ]);
        }

        return response(view('points.point.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Grid::prefixText('point'),
            'admin' => true,
        ])->render());
    }
}
