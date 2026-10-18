<?php

namespace App\Http\Controllers\Users\Admin;

use App\Domain\Users\PricingMatrix;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * แอดมิน กลุ่มราคา (user_type): _admin/_user_type/*.php เดิม (สิทธิ์ admin_user_type)
 */
class AdminUserTypeController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_user_type/grid';

    public function index()
    {
        return view('users.admin.user_type.index');
    }

    public function grid(Request $request): Response
    {
        if (! perm('admin_user_type')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $page = $this->php($request, 'page');
        $term = '%'.addcslashes($s[0], '%_\\').'%';
        $q = DB::table('user_type')->where('is_delete', 0)->where('child', 0)
            ->where(fn ($w) => $w->where('id', 'like', $term)->orWhere('name', 'like', $term));
        $rows = (clone $q)->count();
        $items = collect();
        $offset = $this->pageOffset($page);
        if ($offset !== null) {
            // นับผู้ใช้ต่อกลุ่ม: subquery เดียวกับเดิม (ไม่กรอง is_delete ของ user)
            $items = $q->orderByDesc('id')->offset($offset)->limit(10)
                ->get(['user_type.*', DB::raw('(SELECT COUNT(id) FROM `user` WHERE user.type = user_type.id) as count')]);
        }

        return response(view('users.admin.user_type.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'rows' => $rows,
            's' => explode('|', $this->php($request, 'txtsearch')),
            'page' => $page,
            'pageJs' => AdminUserController::pageJs($page),
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => (string) Lookup::getPrefix('user_type'),
        ])->render());
    }

    public function form(Request $request)
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = DB::table('user_type')->where('id', $id)->where('is_delete', 0)->first();

        return view('users.admin.user_type.form', [
            'id' => $id,
            'data' => $data,
            'prefix' => (string) Lookup::getPrefix('user_type'),
            'productPrefix' => (string) Lookup::getPrefix('product_type'),
            'm' => PricingMatrix::forUserType($id),
        ]);
    }

    /** POST /_admin/_user_type/save */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $name = $this->in($request, 'name');
        $uid = Legacy::userId();

        if ($id === '') {
            $id = DB::transaction(function () use ($request, $name, $uid) {
                $id = (int) DB::table('user_type')->insertGetId([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'id' => null, 'name' => $name, 'is_default' => '0', 'is_show' => '',
                ]);
                DB::table('user_type_detail')->where('user_type_id', $id)->delete();
                AdminUserController::insertRates($request, $id);

                return $id;
            });

            return response(Alerts::success('window.location.href = "'.Legacy::href('admin/user_type').'";', Lang::get('success_process_no').Lookup::getPrefix('user_type').$id));
        }

        DB::transaction(function () use ($request, $id, $name, $uid) {
            // เดิม UPDATE user_type SET is_default = '0' ทั้งตาราง (1,700+ แถว) → แตะเฉพาะแถวที่ยังเป็นค่าตั้งต้นอยู่ (ผลเหมือนเดิม)
            DB::table('user_type')->where(fn ($w) => $w->where('is_default', '<>', 0)->orWhereNull('is_default'))->update(['is_default' => '0']);
            DB::table('user_type')->where('id', $id)->update([
                'name' => $name, 'is_show' => $this->in($request, 'is_show'), 'is_default' => $this->in($request, 'is_default'),
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            DB::table('user_type_detail')->where('user_type_id', $id)->delete();
            AdminUserController::insertRates($request, $id);
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/user_type').'";', Lang::get('success_process_no').Lookup::getPrefix('user_type').Alerts::js($id)));
    }

    /**
     * POST /_admin/_user_type/delete
     * เดิมนับ `role` (ไม่ใช่ user) ที่ id ตรงกัน แล้วเรียก getError() แบบไม่มีอาร์กิวเมนต์ = PHP fatal (HTTP 500 หน้าว่าง)
     * → กลุ่มที่ id ตรงกับ role ที่มีอยู่ ลบไม่ได้ (500 ว่างเหมือนเดิม) ที่เหลือลบได้; id ที่ไม่ใช่ตัวเลข = เดิม SQL error → ไม่ลบ แต่ตอบสำเร็จ
     */
    public function delete(Request $request): Response
    {
        $ids = AdminUserController::deleteIds($this->in($request, 'id'), $this->in($request, 'arr_id'));
        if ($ids !== null && $ids !== []) {
            if (DB::table('role')->whereIn('id', $ids)->count() !== 0) {
                return response('', 500);
            }
            DB::table('user_type')->whereIn('id', $ids)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);
        }

        return response(Alerts::success());
    }
}
