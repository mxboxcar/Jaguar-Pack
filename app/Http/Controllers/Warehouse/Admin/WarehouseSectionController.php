<?php

namespace App\Http\Controllers\Warehouse\Admin;

use App\Domain\Warehouse\Php74;
use App\Domain\Warehouse\UploadBox;
use App\Domain\Warehouse\WarehouseLookups as W;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Warehouse\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ชั้นวาง: _admin/_warehouse_section/*.php และ _admin/_warehouse_section_move/*.php เดิม
 * สิทธิ์ = admin_warehouse_section (delete เดิมตรวจ admin_product_type — คงไว้)
 */
class WarehouseSectionController extends Controller
{
    use ReadsLegacyInput;

    /** GET /admin/warehouse_section */
    public function index(): View
    {
        return view('warehouse.admin.section.index');
    }

    /** POST /_admin/_warehouse_section/grid (เดิมไม่มีสิทธิ์ = ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_warehouse_section')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $page = $this->php($request, 'page');
        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        $q = DB::table('warehouse_section')->where('is_delete', 0)
            ->where(fn ($w) => $w->where('id', 'like', $like)->orWhere('name', 'like', $like));
        $total = (clone $q)->count();
        $offset = W::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('id')->offset($offset)->limit(10)->get();

        return response(view('warehouse.admin.section.grid', [
            'ajaxpage' => '_admin/_warehouse_section/grid',
            'items' => $items->map(fn ($r) => W::strings($r)),
            'prefix' => W::prefix('warehouse_section'),
            'search' => $phpParts[0],
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** GET /admin/warehouse_section/create, /admin/warehouse_section/{id} */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $d = W::strings(DB::table('warehouse_section')->where('id', $id)->where('is_delete', 0)->first()) ?? [];

        return view('warehouse.admin.section.form', [
            'id' => $id,
            'd' => $d,
            'prefix' => W::prefix('warehouse_section'),
            'upload' => UploadBox::single('file', $d['attachment'] ?? '', true),
        ]);
    }

    /** POST /_admin/_warehouse_section/save */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $file = $this->in($request, 'upload_file');
        $uid = (int) Legacy::userId();
        $out = '';
        // เดิม file_exists('../../upload/temp/'.$upload_file) (path traversal) แล้วตอบ JS ให้เบราว์เซอร์เรียก _api/blockblob_ajax
        if (W::tempFileExists($file)) {
            $out .= W::blobJs($file, "\t \t\t", "\t \t\t\t");
        }
        $vals = [
            'name' => $this->in($request, 'name'), 'width' => $this->in($request, 'width'),
            'height' => $this->in($request, 'height'), 'long' => $this->in($request, 'long'), 'attachment' => $file,
        ];
        if (Php74::eq($this->php($request, 'id'), '')) {
            $id = DB::table('warehouse_section')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                ...$vals,
            ]);
        } else {
            DB::table('warehouse_section')->where('id', $id)->update([...$vals, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
        }

        return response($out.Alerts::success(
            'window.location.href = "'.Legacy::href('admin/warehouse_section').'";',
            Lang::get('success_process_no').Alerts::js(W::prefix('warehouse_section').$id),
        ));
    }

    /** POST /_admin/_warehouse_section/delete (สิทธิ์ admin_product_type เหมือนเดิม) */
    public function delete(Request $request): Response
    {
        $arr = $this->in($request, 'arr_id');
        $csv = $arr !== '' ? substr($arr, 1, Format::utf8_strlen($arr)) : $this->in($request, 'id');
        // เดิมต่อ id ลง IN (...) ตรงๆ — ค่าที่ไม่ใช่ตัวเลข = SQL error = ไม่ลบ (แต่ยังตอบสำเร็จ)
        $ids = Php74::idList($csv);
        if ($ids !== null) {
            DB::table('warehouse_section')->whereIn('id', $ids)
                ->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId()]);
        }

        return response(Alerts::success());
    }

    /** GET /admin/warehouse_section_move */
    public function moveIndex(): View
    {
        return view('warehouse.admin.section_move.index');
    }

    /** POST /_admin/_warehouse_section_move/grid (เดิมไม่มีสิทธิ์ = ตอบว่าง) */
    public function moveGrid(Request $request): Response
    {
        if (! perm('admin_warehouse_section')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $page = $this->php($request, 'page');
        $q = DB::table('warehouse_section_detail')
            ->leftJoin('warehouse_section', 'warehouse_section.id', '=', 'warehouse_section_detail.section_id')
            ->where('warehouse_section_detail.is_delete', '0');
        if ($parts[0] !== '') {
            $term = str_replace(W::prefix('balance'), '', $parts[0]);
            $q->where('warehouse_section.id', 'like', '%'.addcslashes($term, '%_\\').'%');
        }
        $total = (clone $q)->count();
        $offset = W::offset($page);
        // เดิม ORDER BY id (ไม่ระบุตาราง) → MySQL ใช้ id ของ warehouse_section_detail.* ในรายการ SELECT
        $items = $offset === null ? collect() : $q->orderByDesc('warehouse_section_detail.id')->offset($offset)->limit(10)
            ->get(['warehouse_section_detail.*', 'warehouse_section.name as name']);

        return response(view('warehouse.admin.section_move.grid', [
            // เดิม: ajaxpage ชี้ไปไฟล์ที่ไม่มีอยู่ (_warehouse_section_detail/grid) → ปุ่มเปลี่ยนหน้าใช้ไม่ได้ (คงไว้)
            'ajaxpage' => '_admin/_warehouse_section_detail/grid',
            'items' => $items->map(fn ($r) => W::strings($r)),
            'prefix' => W::prefix('warehouse_section_move'),
            'types' => DB::table('balance_type')->get(['id', 'name']),
            'search' => $phpParts[0],
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }
}
