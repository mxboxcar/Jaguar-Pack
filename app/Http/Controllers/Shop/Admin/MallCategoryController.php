<?php

namespace App\Http\Controllers\Shop\Admin;

use App\Domain\Shop\CartRepo;
use App\Http\Controllers\Shop\ShopController;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _admin/_mall_category/*.php เดิม — หมวดหมู่สินค้าในร้านค้า
 *
 * ของเดิมพังหลายจุด (คงผลเดิมไว้ทั้งหมด — รายงานให้เจ้าของตัดสินใจใน docs/modules/shop.md):
 *  - index/grid/save ตรวจสิทธิ์ admin_mall_category ซึ่งไม่มีใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน)
 *  - form.php / delete.php เป็นสำเนาของ _admin/_user_type (ตรวจ admin_user_type, อ่าน/ลบตาราง user_type ไม่ใช่ mall_category)
 *  - save.php แก้ไข: SQL ผิด (ขาด ') → UPDATE ไม่เคยสำเร็จ แต่ตอบ "สำเร็จ"
 */
class MallCategoryController extends ShopController
{
    /** admin/mall_category = index.php (ตรวจสิทธิ์ใน view แบบเดิม) */
    public function index(): View
    {
        return view('shop.admin.mall_category.index');
    }

    /** _admin/_mall_category/grid — ไม่มีสิทธิ์ = ตอบว่าง; จำนวนสินค้าต่อหมวด: เดิม subquery ต่อแถว → GROUP BY */
    public function grid(Request $request): Response
    {
        if (! perm('admin_mall_category')) {
            return $this->out('');
        }
        $ajaxpage = '_admin/_mall_category/grid';
        $txt = $this->txtSearch($request);
        $like = self::like($txt[0]);
        $q = DB::table('mall_category')->where('is_delete', 0)
            ->where(fn ($q) => $q->where('id', 'like', $like)->orWhere('name', 'like', $like));
        [$offset, $page] = $this->pageOffset($request);
        [$data, $rows] = $this->gridRows($q, $offset, fn ($q) => $q->orderByDesc('id'));
        $count = $data === [] ? collect() : DB::table('mall')->whereIn('category_id', array_map(fn ($r) => $r->id, $data))
            ->groupBy('category_id')->selectRaw('category_id, COUNT(id) as c')->pluck('c', 'category_id');

        return $this->out(view('shop.admin.mall_category.grid', [
            'data' => $data, 'rows' => $rows, 'Page' => $page, 'ajaxpage' => $ajaxpage, 'count' => $count,
            'prefix' => (string) Lookup::getPrefix('mall_category'),
            'txtSearch' => $this->txtSearch($request, true), 'txtsearchRaw' => $this->postPhp($request, 'txtsearch'),
        ])->render());
    }

    /** admin/mall_category/create|{id} = form.php — สำเนาฟอร์ม user_type (สิทธิ์ admin_user_type, อ่าน user_type) */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0);

        return view('shop.admin.mall_category.form', [
            'id' => $id,
            'data' => DB::table('user_type')->where('id', $id)->where('is_delete', 0)->first(),
            'prefix' => (string) Lookup::getPrefix('mall_category'),
            'prefixProductType' => (string) Lookup::getPrefix('product_type'),
        ]);
    }

    /** _admin/_mall_category/save */
    public function save(Request $request): Response
    {
        $uid = $this->uid();
        $id = $this->post($request, 'id');
        $prefix = (string) Lookup::getPrefix('mall_category');
        if ($id === '') {
            $now = DB::raw('NOW()');
            $id = DB::table('mall_category')->insertGetId([
                'create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0,
                'name' => $this->post($request, 'name'),
            ]);
        }
        // else: UPDATE เดิมเป็น SQL ผิดไวยากรณ์ (`name` = '$name , ...) → ไม่เคยแก้ได้ — คงไว้ (ไม่ทำอะไร)

        return $this->out(Alerts::success('window.location.href = "'.legacy_href('admin/mall_category').'";',
            Lang::get('success_process_no').$prefix.Alerts::js((string) $id)));
    }

    /**
     * _admin/_mall_category/delete — สำเนาของ _admin/_user_type/delete (สิทธิ์ admin_user_type, ลบ user_type)
     * ถ้ามี role ที่ id ตรงกัน เดิมเรียก getError() ไม่มีอาร์กิวเมนต์ → fatal 500
     */
    public function delete(Request $request): Response
    {
        $id = $this->post($request, 'id');
        $arr = $this->post($request, 'arr_id');
        $ids = CartRepo::parseIdList($arr !== '' ? mb_substr($arr, 1) : $id);
        if ($ids === null) {
            // เดิม SQL error ทั้งสองคำสั่ง (count = null → ไม่ error) แล้วตอบสำเร็จ
            return $this->out(Alerts::success());
        }
        if (DB::table('role')->whereIn('id', $ids)->count('id') != 0) {
            return $this->out('', 500);
        }
        DB::table('user_type')->whereIn('id', $ids)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $this->uid()]);

        return $this->out(Alerts::success());
    }
}
