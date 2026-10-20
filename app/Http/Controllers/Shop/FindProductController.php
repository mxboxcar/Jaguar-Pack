<?php

namespace App\Http\Controllers\Shop;

use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _findproduct/*.php — SR เท่านั้น ("จัดหาสินค้า") ตาราง find_product
 * โค้ดเดิมเป็นสำเนาของ _admin/_product_type ที่ยังทำไม่เสร็จ และสิทธิ์ `findproduct` ไม่มีแถวใน role_detail ของ SR
 * → เว็บเดิมไม่มีใครใช้ได้ (ทุกคนเห็น getPermissionAlert_Page) — พอร์ตด้วยชื่อสิทธิ์เดิม ไม่มี fallback (ถามเจ้าของ)
 *   - index: แสดงหัวหน้าแล้วตรวจสิทธิ์ใน view (แบบเดิม)
 *   - grid: ไม่มีสิทธิ์ = exit() เงียบ; ค้น id/name LIKE '%…%' (bind + escape)
 *   - form: ตรวจสิทธิ์ก่อน (route legacy.perm:findproduct)
 *   - save / delete: require_once('../../system_check_ajax.php') path ผิด (อยู่ลึกชั้นเดียว) → fatal 500 ทุกครั้ง
 *     ก่อนถึงโค้ดข้างใน (ซึ่งจะ INSERT/UPDATE/ลบ product_type ด้วยสิทธิ์ admin_product_type) → ตอบ 500 ว่าง ไม่แตะข้อมูล
 */
class FindProductController extends ShopController
{
    /** findproduct = _findproduct/index.php */
    public function index(): View
    {
        return view('shop.findproduct.index');
    }

    /** _findproduct/grid */
    public function grid(Request $request): Response
    {
        if (! perm('findproduct')) {
            return $this->out('');
        }
        $ajaxpage = '_findproduct/grid';
        $txt = $this->txtSearch($request);
        $like = self::like($txt[0]);
        $q = DB::table('find_product')->where('is_delete', 0)
            ->where(fn ($w) => $w->where('id', 'like', $like)->orWhere('name', 'like', $like));
        [$offset, $page] = $this->pageOffset($request);
        [$data, $rows] = $this->gridRows($q, $offset, fn ($q) => $q->orderByDesc('id'));

        return $this->out(view('shop.findproduct.grid', [
            'data' => $data, 'rows' => $rows, 'Page' => $page, 'ajaxpage' => $ajaxpage,
            'prefix' => (string) Lookup::getPrefix('product_type'),
            'txtSearch' => $this->txtSearch($request, true), 'txtsearchRaw' => $this->postPhp($request, 'txtsearch'),
        ])->render());
    }

    /** findproduct/create (id=0), findproduct/{id} = _findproduct/form.php */
    public function form(Request $request): View
    {
        $id = (string) $request->route('id', '0');
        $data = DB::table('find_product')->where('id', $id)->where('is_delete', 0)->first();

        return view('shop.findproduct.form', [
            'id' => $id,
            'data' => $data,
            'prefixFind' => (string) Lookup::getPrefix('findproduct'),
            'prefixType' => (string) Lookup::getPrefix('product_type'),
        ]);
    }

    /** _findproduct/save, _findproduct/delete — เดิม fatal (require path ผิด) ทุกครั้ง: 500 ว่าง ไม่เขียนอะไร */
    public function broken(): Response
    {
        return $this->out('', 500);
    }
}
