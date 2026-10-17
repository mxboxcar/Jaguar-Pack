<?php

namespace App\Http\Controllers\Shop\Admin;

use App\Domain\Shop\Php74;
use App\Domain\Shop\Upload;
use App\Http\Controllers\Shop\ShopController;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _admin/_mall/*.php เดิม — จัดการสินค้าในร้านค้า (สิทธิ์ admin_mall; delete เดิมใช้ admin_product_type)
 */
class MallAdminController extends ShopController
{
    /** admin/mall = _admin/_mall/index.php */
    public function index(): View
    {
        return view('shop.admin.mall.index');
    }

    /** _admin/_mall/grid — สต๊อกคงเหลือ: เดิม getStock2() ทีละแถว → GROUP BY ครั้งเดียว */
    public function grid(Request $request): Response
    {
        $ajaxpage = '_admin/_mall/grid';
        $txt = $this->txtSearch($request);
        $like = self::like($txt[0]);
        $q = DB::table('mall')->where('is_delete', 0)
            ->where(fn ($q) => $q->where('id', 'like', $like)->orWhere('name', 'like', $like));
        [$offset, $page] = $this->pageOffset($request);
        [$data, $rows] = $this->gridRows($q, $offset, fn ($q) => $q->orderByDesc('id'));
        $stock = $data === [] ? collect() : DB::table('mall_stock')->whereIn('mall_id', array_map(fn ($r) => $r->id, $data))
            ->groupBy('mall_id')->selectRaw('mall_id, SUM(amount) as c')->pluck('c', 'mall_id');

        return $this->out(view('shop.admin.mall.grid', [
            'data' => $data, 'rows' => $rows, 'Page' => $page, 'ajaxpage' => $ajaxpage, 'stock' => $stock,
            'prefix' => (string) Lookup::getPrefix('mall'),
            'txtSearch' => $this->txtSearch($request, true), 'txtsearchRaw' => $this->postPhp($request, 'txtsearch'),
        ])->render());
    }

    /** admin/mall/create, admin/mall/{id} = _admin/_mall/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0);
        $data = DB::table('mall')->where('id', $id)->where('is_delete', 0)->first();

        return view('shop.admin.mall.form', [
            'id' => $id, 'data' => $data, 'prefix' => (string) Lookup::getPrefix('mall'),
            'categories' => DB::table('mall_category')->where('is_delete', '0')->orderBy('id')->get()->all(),
            'upload' => Upload::getupload('file', (string) ($data->image ?? ''), true),
        ]);
    }

    /** _admin/_mall/load_detail — ตารางสี/ขนาดของสินค้า (สต๊อก: เดิม getStock ทีละแถว → GROUP BY) */
    public function loadDetail(Request $request): Response
    {
        $id = $this->post($request, 'id');
        $items = DB::table('mall_detail')->where('mall_id', $id)->where('is_delete', 0)->orderByDesc('id')->get()->all();
        $stock = $items === [] ? collect() : DB::table('mall_stock')
            ->whereIn('mall_detail_id', array_map(fn ($r) => $r->id, $items))
            ->groupBy('mall_id', 'mall_detail_id')->selectRaw('mall_id, mall_detail_id, SUM(amount) as c')->get()
            ->mapWithKeys(fn ($r) => [$r->mall_id.':'.$r->mall_detail_id => $r->c]);

        return $this->out(view('shop.admin.mall.load_detail', ['items' => $items, 'stock' => $stock])->render());
    }

    /** _admin/_mall/load_sizecolor — ฟอร์มเพิ่ม/แก้ สี-ขนาด */
    public function loadSizeColor(Request $request): Response
    {
        $id = $this->post($request, 'id');
        $mallId = $this->post($request, 'mall_id');
        $data = DB::table('mall_detail')->where('id', $id)->first();
        if ($mallId == '0') {
            return $this->out('');
        }

        return $this->out(view('shop.admin.mall.load_sizecolor', [
            'id' => $this->postPhp($request, 'id'), 'mallId' => $this->postPhp($request, 'mall_id'), 'data' => $data,
            'stock' => Lookup::getStock($mallId, $data->id ?? ''),
        ])->render());
    }

    /**
     * _admin/_mall/load_stock — ประวัติสต๊อก
     * เดิมเรียก getPayType() ซึ่งไม่มีอยู่จริง → fatal error ที่แถวแรก: ได้ HTML ครึ่งเดียว + 500
     * (jQuery ไม่เรียก success → ตารางว่างเสมอเมื่อมีประวัติ) — คงผลเดิมไว้ ดู docs/modules/shop.md
     */
    public function loadStock(Request $request): Response
    {
        $id = $this->post($request, 'id');
        $first = DB::table('mall_stock')
            ->leftJoin('mall_detail', 'mall_detail.id', '=', 'mall_stock.mall_detail_id')
            ->where('mall_detail.mall_id', $id)->where('mall_stock.is_delete', 0)
            ->orderByDesc('mall_stock.id')
            ->select('mall_stock.*', 'mall_detail.color', 'size')->first();

        return $this->out(view('shop.admin.mall.load_stock', [
            'item' => $first, 'prefix' => (string) Lookup::getPrefix('mall_stock'),
        ])->render(), $first === null ? 200 : 500);
    }

    /** _admin/_mall/save — เพิ่ม/แก้สินค้า */
    public function save(Request $request): Response
    {
        $uid = $this->uid();
        $id = $this->post($request, 'id');
        $image = $this->post($request, 'upload_file');
        $fields = [
            'name' => $this->post($request, 'name'), 'image' => $image, 'code' => $this->post($request, 'code'),
            'category_id' => $this->post($request, 'category_id'), 'weight' => $this->post($request, 'weight'),
            'width' => $this->post($request, 'width'), 'height' => $this->post($request, 'height'),
            'long' => $this->post($request, 'long'), 'detail' => $this->post($request, 'editor'), 'desc' => $this->post($request, 'desc'),
        ];
        $js = $image !== '' ? Upload::blobJs($image) : '';
        $now = DB::raw('NOW()');
        $prefix = (string) Lookup::getPrefix('mall');

        if ($id === '') {
            $newId = DB::table('mall')->insertGetId([
                'create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0,
                'tracking' => $this->post($request, 'tracking'),
            ] + $fields);

            return $this->out($js.Alerts::success('window.location.href = "'.legacy_href('admin/mall').'";',
                Lang::get('success_process_no').$prefix.$newId));
        }

        DB::table('mall')->where('id', $id)->update($fields + ['mod_date' => $now, 'mod_by' => $uid]);
        // เดิมตอนแก้ไข echo JS อัปโหลดซ้ำอีกรอบ (ไม่เช็คว่าชื่อไฟล์ว่าง)
        $js .= Upload::blobJs($image);

        return $this->out($js.Alerts::success('window.location.href = "'.legacy_href('admin/mall').'";',
            Lang::get('success_process_no').$prefix.Alerts::js($id)));
    }

    /** _admin/_mall/save_sizecolor — เพิ่ม/แก้ สี-ขนาด แล้วปรับสต๊อกให้เท่าที่กรอก */
    public function saveSizeColor(Request $request): Response
    {
        $uid = $this->uid();
        $id = $this->post($request, 'id');
        $mallId = $this->post($request, 'mall_id');
        $now = DB::raw('NOW()');
        $vals = [
            'price' => $this->post($request, 'price'), 'point' => $this->post($request, 'point'),
            'color' => $this->post($request, 'color'), 'size' => $this->post($request, 'size'),
        ];

        return DB::transaction(function () use ($request, $uid, $id, $mallId, $now, $vals) {
            if ($id == '0') {
                $id = DB::table('mall_detail')->insertGetId([
                    'create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0,
                    'mall_id' => $mallId, 'image' => $this->post($request, 'image'),
                ] + $vals);
            } else {
                DB::table('mall_detail')->where('id', $id)->update($vals + ['mod_date' => $now, 'mod_by' => $uid]);
            }
            $remain = Lookup::getStock($mallId, $id);
            $adj = Php74::num($this->post($request, 'stock')) - Php74::num($remain);
            DB::table('mall_stock')->insert([
                'create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0,
                'mall_id' => $mallId, 'mall_detail_id' => $id, 'amount' => Php74::str($adj), 'date' => $now,
                'detail' => 'ปรับสต๊อคโดย '.(Legacy::user()->code ?? ''), 'user' => '', 'pay_type' => '0',
            ]);

            return $this->out(Alerts::success('on_loaddetail();on_loadstock();CloseLoading();$(".modal").modal("hide");'));
        });
    }

    /** _admin/_mall/save_delsizecolor — ลบ สี-ขนาด (id 0 = ไม่ทำอะไร ไม่ตอบ) */
    public function saveDelSizeColor(Request $request): Response
    {
        $id = $this->post($request, 'id');
        if ($id == '0') {
            return $this->out('');
        }
        DB::table('mall_detail')->where('id', $id)->update(['is_delete' => '1']);

        return $this->out(Alerts::success('on_loaddetail();on_loadstock();CloseLoading();$(".modal").modal("hide");'));
    }

    /** _admin/_mall/save_isshow — สินค้าแนะนำ (ไม่ตอบอะไร) */
    public function saveIsShow(Request $request): Response
    {
        DB::table('mall')->where('id', $this->post($request, 'id'))
            ->update(['is_show' => $this->post($request, 'value'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => $this->uid()]);

        return $this->out('');
    }

    /**
     * _admin/_mall/delete — สิทธิ์ admin_product_type แบบเดิม
     * id / arr_id (",1,2") เดิมต่อ string ใน IN (...) = SQL injection → รับเฉพาะเลข
     * mall_stock ถูกลบตาม id เดี่ยวเท่านั้น (เดิม: arr_id ใช้ $id ว่าง → ไม่ลบ stock) — คงไว้
     */
    public function delete(Request $request): Response
    {
        $id = $this->post($request, 'id');
        $arr = $this->post($request, 'arr_id');
        $ids = \App\Domain\Shop\CartRepo::parseIdList($arr !== '' ? mb_substr($arr, 1) : $id);
        if ($ids !== null) {
            DB::transaction(function () use ($ids, $id) {
                DB::table('mall')->whereIn('id', $ids)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $this->uid()]);
                DB::table('mall_stock')->where('mall_id', $id)->update(['is_delete' => '1']);
            });
        }

        return $this->out(Alerts::success());
    }
}
