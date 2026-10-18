<?php

namespace App\Http\Controllers\Users\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * แอดมิน บทบาท/สิทธิ์ (role, role_on_detail): _admin/_role/*.php เดิม (สิทธิ์ admin_role)
 */
class AdminRoleController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_role/grid';

    public function index()
    {
        return view('users.admin.role.index');
    }

    public function grid(Request $request): Response
    {
        if (! perm('admin_role')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $page = $this->php($request, 'page');
        $term = '%'.addcslashes($s[0], '%_\\').'%';
        $q = DB::table('role')->where('role.is_delete', 0)
            ->where(fn ($w) => $w->where('role.id', 'like', $term)->orWhere('role.role_name', 'like', $term));
        $rows = (clone $q)->count();
        $items = collect();
        $offset = $this->pageOffset($page);
        if ($offset !== null) {
            $items = $q->orderByDesc('id')->offset($offset)->limit(10)
                ->get(['role.*', DB::raw('(SELECT COUNT(id) FROM `user` WHERE `role`.id = user.role) as count')]);
        }

        return response(view('users.admin.role.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'rows' => $rows,
            's' => explode('|', $this->php($request, 'txtsearch')),
            'page' => $page,
            'pageJs' => AdminUserController::pageJs($page),
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => (string) Lookup::getPrefix('role'),
        ])->render());
    }

    public function form(Request $request)
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = DB::table('role')->where('id', $id)->where('is_delete', 0)->first();
        // เดิมวน role_on_detail ต่อแถว role_detail → map ด้วย id
        $active = DB::table('role_on_detail')->where('role_id', $id)->where('is_delete', 0)->pluck('role_detail_id')
            ->map(fn ($v) => (string) $v)->flip();

        return view('users.admin.role.form', [
            'id' => $id,
            'data' => $data,
            'prefix' => (string) Lookup::getPrefix('role'),
            'modules' => DB::table('role_detail')->orderBy('role_detail_name')->orderBy('id')->get(['id', 'role_detail_name']),
            'active' => $active,
        ]);
    }

    /** POST /_admin/_role/save — role[] = role_detail_id ที่เลือก */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $name = $this->in($request, 'name');
        $role = LegacyInput::post($request, 'role', null);
        $uid = Legacy::userId();

        $id = DB::transaction(function () use ($id, $name, $role, $uid) {
            if ($id === '') {
                $id = (string) DB::table('role')->insertGetId([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'id' => null, 'role_name' => $name,
                ]);
            } else {
                DB::table('role')->where('id', $id)->update(['role_name' => $name, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
            }
            DB::table('role_on_detail')->where('role_id', $id)->delete();
            // เดิม foreach ($role as $a) — ไม่ใช่ array (ไม่ได้เลือกเลย) = ไม่เพิ่มอะไร
            $rows = [];
            foreach (is_array($role) ? $role : [] as $a) {
                $rows[] = [
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'id' => null, 'role_id' => $id, 'role_detail_id' => is_scalar($a) ? (string) $a : 'Array', 'permission' => '1',
                ];
            }
            if ($rows !== []) {
                DB::table('role_on_detail')->insert($rows);
            }

            return $id;
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/role').'";', Lang::get('success_process_no').Lookup::getPrefix('role').Alerts::js($id)));
    }

    /**
     * POST /_admin/_role/delete
     * เดิมนับ role ที่ id ตรงกัน (ตัวมันเอง) แล้วเรียก getError() แบบไม่มีอาร์กิวเมนต์ = PHP fatal → role ที่มีอยู่จริงลบไม่ได้เลย (HTTP 500 ว่าง)
     * คงผลเดิม: มี role ตรง → 500 ว่าง, ไม่มี → UPDATE (ไม่โดนแถวใด) แล้วตอบสำเร็จ
     */
    public function delete(Request $request): Response
    {
        $ids = AdminUserController::deleteIds($this->in($request, 'id'), $this->in($request, 'arr_id'));
        if ($ids !== null && $ids !== []) {
            if (DB::table('role')->whereIn('id', $ids)->count() !== 0) {
                return response('', 500);
            }
            DB::table('role')->whereIn('id', $ids)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);
        }

        return response(Alerts::success());
    }
}
