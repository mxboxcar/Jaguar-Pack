<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Domain\Settings\Input;
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
 * ประเภทสินค้า: _admin/_product_type/*.php เดิม (สิทธิ์ admin_product_type ทุกไฟล์)
 */
class ProductTypeController extends Controller
{
    private const AJAX = '_admin/_product_type/grid';

    /** GET /admin/product_type — index.php */
    public function index(): View
    {
        return view('settings.admin.product_type.index');
    }

    /** POST /_admin/_product_type/grid — grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_product_type')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        $term = explode('|', Input::in($request, 'txtsearch'))[0];
        $like = Input::contains($term);
        $q = DB::table('product_type')->where('is_delete', 0)
            ->where(fn ($w) => $w->where('id', 'like', $like)->orWhere('name', 'like', $like));
        $page = Input::php($request, 'page');
        [$total, $items] = Input::page($q, 'id', $page);

        return response(view('settings.admin.product_type.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'termPhp' => explode('|', Input::php($request, 'txtsearch'))[0],
            'raw' => Input::php($request, 'txtsearch'),
            'page' => $page,
            'total' => $total,
            'prefix' => (string) (Lookup::getPrefix('product_type') ?? ''),
        ])->render());
    }

    /** GET /admin/product_type/create (id 0), /admin/product_type/{id} — form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0); // $_GET['id'] เดิมถูก (int)
        return view('settings.admin.product_type.form', [
            'id' => $id,
            'data' => DB::table('product_type')->where('id', $id)->where('is_delete', 0)->first(),
            'prefix' => (string) (Lookup::getPrefix('product_type') ?? ''),
        ]);
    }

    /** POST /_admin/_product_type/save — save.php (id ว่าง = เพิ่ม, ไม่ว่าง = แก้) */
    public function save(Request $request): Response
    {
        // ตั้งใจต่างจากเดิม: POST ที่ไม่มีช่อง name เลย (ไม่ใช่ฟอร์มจริง) เดิม INSERT แถวว่าง / ล้างทุกคอลัมน์ของ id นั้น → ปฏิเสธ
        if (! Input::has($request, 'name')) {
            return response(Alerts::error('CloseLoading()', 'ไม่มีข้อมูลที่จะบันทึก'));
        }
        $id = Input::in($request, 'id');
        $uid = Legacy::userId();
        $values = [
            'name' => Input::in($request, 'name'),
            'kg' => Input::in($request, 'kg'),
            'cubic' => Input::in($request, 'cubic'),
            'amount' => Input::in($request, 'amount'),
            'sale_kg' => Input::in($request, 'sale_kg'),
            'sale_cubic' => Input::in($request, 'sale_cubic'),
            'sale_amount' => Input::in($request, 'sale_amount'),
            'type' => Input::in($request, 'ship'),
            'is_default' => Input::in($request, 'is_default'),
            'desc' => Input::in($request, 'desc'),
        ];
        if ($id === '') {
            $id = (string) DB::table('product_type')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, ...$values,
            ]);
        } else {
            DB::table('product_type')->where('id', $id)->update([...$values, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
        }

        return response(Alerts::success(
            'window.location.href = "'.Legacy::href('admin/product_type').'";',
            Lang::get('success_process_no').Lookup::getPrefix('product_type').Alerts::js($id),
        ));
    }

    /** POST /_admin/_product_type/delete — delete.php (soft delete; รับ id หรือ arr_id ",1,2") */
    public function delete(Request $request): Response
    {
        $ids = Input::idList($request);
        if ($ids !== []) {
            DB::table('product_type')->whereIn('id', $ids)->update([
                'is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
            ]);
        }

        return response(Alerts::success());
    }
}
