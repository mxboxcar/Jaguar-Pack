<?php

namespace App\Http\Controllers\Warehouse\Admin;

use App\Domain\Warehouse\LotDetailRenderer;
use App\Domain\Warehouse\Php74;
use App\Domain\Warehouse\ShelfAllocator;
use App\Domain\Warehouse\WarehouseLookups as W;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Warehouse\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * รับสินค้าเข้าโกดัง: _admin/_warehouse/*.php เดิม
 * สิทธิ์ = admin_warehouse (ตรวจที่ middleware) ยกเว้น grid (admin_lot, ตอบว่าง) และ save (admin_warehouse_section)
 */
class WarehouseController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_warehouse/grid';

    /** GET /admin/warehouse — _admin/_warehouse/index.php */
    public function index(): View
    {
        // เดิม $_GET['status'] แต่ RewriteRule ไม่ส่งค่านี้ (และไม่มี QSA) → ว่างเสมอ
        return view('warehouse.admin.warehouse.index', ['status' => '']);
    }

    /** POST /_admin/_warehouse/grid — _admin/_warehouse/grid.php (เดิมตรวจ admin_lot แล้ว exit() เงียบ) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_lot')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $type = $parts[1] ?? null;
        $page = $this->php($request, 'page');

        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        $q = DB::table('warehouse')->where('warehouse.is_delete', 0)
            ->where(fn ($w) => $w->where('warehouse.id', 'like', $like)->orWhere('warehouse.remark', 'like', $like));

        // เดิม: กรองด้วย "lot.type" ซึ่งไม่มีใน query → SQL error → ไม่มีแถวและไม่มีหน้า
        $broken = $type !== null && Php74::neq($type, '-2') && Php74::neq($type, '');
        $total = $broken ? 0 : (clone $q)->count();
        $offset = W::offset($page);
        $items = ($broken || $offset === null) ? collect() : $q->leftJoin('user', 'warehouse.mod_by', '=', 'user.id')
            ->orderByDesc('warehouse.id')->offset($offset)->limit(10)->get(['warehouse.*', 'user.code']);

        // เดิม: 3 query ต่อแถว → รวมเป็น 2 query สำหรับทั้งหน้า
        $ids = $items->pluck('id')->map(fn ($v) => (int) $v)->all();
        $lots = [];
        $counts = [];
        if ($ids !== []) {
            foreach (DB::table('warehouse_detail')->whereIn('warehouse_id', $ids)->where('is_delete', '0')
                ->orderBy('id')->get(['warehouse_id', 'lot_id']) as $r) {
                $lots[(int) $r->warehouse_id][] = (string) $r->lot_id;
            }
            // จำนวนในจีน = จำนวนกลุ่ม GROUP BY tracking_id, ถึงไทย = กลุ่ม GROUP BY tracking.id ที่ date_section ไม่ว่าง
            $counts = DB::table('warehouse_section_detail as t')
                ->leftJoin('tracking', 'tracking.id', '=', 't.tracking_id')
                ->whereIn('t.warehouse_id', $ids)->where('t.is_delete', 0)
                ->groupBy('t.warehouse_id')
                ->get([
                    't.warehouse_id',
                    DB::raw('COUNT(DISTINCT t.tracking_id) as cn'),
                    DB::raw("COUNT(DISTINCT CASE WHEN tracking.date_section != '0000-00-00 00:00:00' THEN tracking.id END) as th"),
                ])->keyBy(fn ($r) => (int) $r->warehouse_id)->all();
        }

        return response(view('warehouse.admin.warehouse.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'lots' => $lots,
            'counts' => $counts,
            'prefix' => W::prefix('warehouse'),
            'search' => $phpParts[0],
            'type' => $phpParts[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** GET /admin/warehouse/create, /admin/warehouse/{id} — _admin/_warehouse/form.php */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = W::strings(DB::table('warehouse')->where('id', $id)->where('is_delete', 0)->first()) ?? [];
        $lotIds = DB::table('warehouse_detail')->where('warehouse_id', $data['id'] ?? '')->where('is_delete', '0')
            ->orderBy('id')->pluck('lot_id')->map(fn ($v) => (string) $v)->all();

        return view('warehouse.admin.warehouse.form', [
            'id' => $id,
            'd' => $data,
            'prefix' => W::prefix('warehouse'),
            'strInit' => implode(',', $lotIds),
        ]);
    }

    /** POST /_admin/_warehouse/load_lot_detail — รายการที่ยังไม่ถึงไทย (date_section ว่าง) */
    public function loadLotDetail(Request $request): Response
    {
        return response(LotDetailRenderer::render($this->inArray($request, 'selected_print'), false));
    }

    /** POST /_admin/_warehouse/load_lot_detail_thai — รายการที่ถึงไทยแล้ว */
    public function loadLotDetailThai(Request $request): Response
    {
        return response(LotDetailRenderer::render($this->inArray($request, 'selected_print'), true));
    }

    /**
     * GET /_admin/_warehouse/save_lot_detail?id=&prints= — เลือกล็อตแล้วจัดชั้นวางอัตโนมัติ (JS เดิมเรียกด้วย GET)
     * GET ที่เขียนข้อมูล: route นี้ต้องมี X-Requested-With (legacy.ajax) ซึ่งเว็บอื่นส่งข้ามโดเมนไม่ได้ = กัน CSRF
     */
    public function saveLotDetail(Request $request): Response
    {
        $prints = LegacyInput::query($request, 'prints');

        return response((new ShelfAllocator((int) LegacyInput::query($request, 'id'), is_string($prints) ? $prints : 'Array'))->run());
    }

    /** POST /_admin/_warehouse/load_tracking —ค้นด้วยเลข tracking เพื่อรับเข้า (ยังไม่ถึงไทย) */
    public function loadTracking(Request $request): Response
    {
        $tracking = preg_replace('/\s+/', '', $this->in($request, 'tracking'));
        $noData = "ErrorBox('".Lang::get('no_data')."', 'CloseLoading()'); ";
        if ($tracking === '') {
            return response($noData);
        }
        $data = $this->trackingQuery()
            ->where('tracking.date_section', '0000-00-00 00:00:00')
            ->where('tracking.tracking', $tracking)
            ->groupBy('tracking.id')->first($this->trackingColumns());
        if ($data === null) {
            return response($noData);
        }

        return response(view('warehouse.admin.warehouse.tracking_box', [
            'd' => W::strings($data),
            'warehouseId' => $this->php($request, 'warehouse_id'),
            'weightValue' => '',
        ])->render());
    }

    /** POST /_admin/_warehouse/load_tracking_edit — แก้น้ำหนักไทยของรายการที่ถึงไทยแล้ว */
    public function loadTrackingEdit(Request $request): Response
    {
        $data = $this->trackingQuery()->where('tracking.id', $this->in($request, 'id'))->first($this->trackingColumns());
        if ($data === null) {
            return response("ErrorBox('".Lang::get('no_data')."', 'CloseLoading()'); ");
        }
        $d = W::strings($data);

        return response(view('warehouse.admin.warehouse.tracking_box', [
            'd' => $d,
            'warehouseId' => $this->php($request, 'warehouse_id'),
            'weightValue' => (string) ($d['weight_thai'] ?? ''),
        ])->render());
    }

    /** POST /_admin/_warehouse/load_editshelf — ย้ายชั้นวางของทุกรายการของลูกค้าคนนี้ในใบรับเข้า */
    public function loadEditShelf(Request $request): Response
    {
        $warehouseId = $this->in($request, 'warehouse_id');
        $data = $this->trackingQuery()->where('tracking.id', $this->in($request, 'id'))
            ->where('t3.warehouse_id', $warehouseId)
            ->first([...$this->trackingColumns(), 'user.id as user_id']);
        if ($data === null) {
            return response("ErrorBox('".Lang::get('no_data')."', 'CloseLoading()'); ");
        }
        $d = W::strings($data);

        $waiting = DB::table('warehouse_section_detail as t')
            ->leftJoin('tracking', 'tracking.id', '=', 't.tracking_id')
            ->where('t.user', $d['user_id'] ?? '')->where('t.is_delete', '0')->where('tracking.is_delete', 0)
            ->where('t.warehouse_id', $warehouseId)
            ->whereRaw('(tracking.header = 0 OR tracking.header IS NULL)')
            ->whereNull('t.weight_thai')
            ->count('t.id');
        $current = DB::table('warehouse_section_detail as t')
            ->leftJoin('warehouse_section as t2', 't2.id', '=', 't.section_id')
            ->where('t.tracking_id', $d['id'])->where('t2.is_delete', 0)->where('t.is_delete', 0)
            ->value('t2.id');
        $sections = DB::table('warehouse_section')->where('is_delete', 0)->orderBy('floors')->get(['id', 'name']);

        return response(view('warehouse.admin.warehouse.editshelf_box', [
            'd' => $d,
            'warehouseId' => $this->php($request, 'warehouse_id'),
            'waiting' => $waiting,
            'current' => $current === null ? null : (string) $current,
            'sections' => $sections,
        ])->render());
    }

    /** POST /_admin/_warehouse/save_editshelf */
    public function saveEditShelf(Request $request): Response
    {
        $ids = DB::table('warehouse_section_detail as t')
            ->leftJoin('tracking', 'tracking.id', '=', 't.tracking_id')
            ->where('t.user', $this->in($request, 'user_id'))->where('tracking.is_delete', 0)
            ->where('t.warehouse_id', $this->in($request, 'master_warehouse_id'))
            ->whereRaw('(tracking.header = 0 OR tracking.header IS NULL)')
            ->whereNull('t.weight_thai')
            ->pluck('t.id')->all();
        // เดิม: ไม่มีแถว → "IN ()" = SQL error → ไม่แก้อะไร
        if ($ids !== []) {
            DB::table('warehouse_section_detail')->whereIn('id', $ids)
                ->update(['section_id' => $this->in($request, 'warehouse_id')]);
        }

        return response(' $(\'.modal\').modal(\'hide\'); go_init(); ');
    }

    /** POST /_admin/_warehouse/save_new_warehouse — สร้างใบรับเข้าใหม่แล้วไปหน้าแก้ไข */
    public function saveNewWarehouse(): Response
    {
        $uid = (int) Legacy::userId();
        $id = DB::table('warehouse')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            'is_delete' => 0, 'remark' => '',
        ]);

        return response('window.location.href = "'.Legacy::href('admin/warehouse/'.$id).'";');
    }

    /** POST /_admin/_warehouse/save_cancel_warehouse_calculate — ยกเลิกล็อตทั้งหมดของใบรับเข้า */
    public function saveCancelWarehouseCalculate(Request $request): Response
    {
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($id) {
            DB::table('warehouse_detail')->where('warehouse_id', $id)->update(['is_delete' => '1']);
            DB::table('warehouse_section_detail')->where('warehouse_id', $id)->update(['is_delete' => '1']);
        });

        return response(Alerts::success('go_init();go_init_thai();'));
    }

    /** POST /_admin/_warehouse/save_push_track — รับเข้าไทยด้วยเลข tracking (ไม่มีหน้าใดเรียกแล้ว) */
    public function savePushTrack(Request $request): Response
    {
        $tracking = preg_replace('/\s+/', '', $this->in($request, 'tracking'));
        $id = DB::table('tracking')->where('is_delete', 0)->where('tracking', $tracking)->value('id');
        if ($id === null) {
            return response("ErrorBox('".Lang::get('no_data')."', 'CloseLoading()'); ");
        }
        DB::table('tracking')->where('id', $id)->update(['date_section' => DB::raw('NOW()'), 'date_thai' => DB::raw('NOW()')]);

        return response(Alerts::success("go_init();go_init_thai();$(\\'#search_tracking\\').val(\\'\\');$(\\'#search_tracking\\').focus();"));
    }

    /** POST /_admin/_warehouse/save_track_weight — บันทึกน้ำหนักไทย + รับเข้าไทย แล้วคำนวณราคาใหม่ */
    public function saveTrackWeight(Request $request): Response
    {
        $id = DB::table('tracking')->where('is_delete', 0)->where('id', $this->in($request, 'id'))->value('id');
        if ($id === null) {
            return response("ErrorBox('".Lang::get('no_data')."', 'CloseLoading()'); ");
        }
        $set = ['date_section' => DB::raw('NOW()'), 'weight' => $this->in($request, 'weight'), 'date_thai' => DB::raw('NOW()')];
        // เดิม: go_receive() ไม่ส่ง tracking มา → เลข tracking ถูกเขียนทับเป็น '' (ข้อมูลหาย) — แก้: ไม่ส่งมา = ไม่แก้
        if (array_key_exists('tracking', $request->request->all())) {
            $set['tracking'] = str_replace(' ', '', $this->in($request, 'tracking'));
        }
        DB::transaction(function () use ($id, $set) {
            DB::table('tracking')->where('id', $id)->update($set);
            Pricing::calPriceAutoDiff($id);
        });

        return response("go_init();go_init_thai();$('#search_tracking').val('');$('#search_tracking').focus();$('.modal').modal('hide');");
    }

    /**
     * POST /_admin/_warehouse/save — สำเนาของ _admin/_warehouse_section/save.php ที่ไม่มีหน้าไหนเรียก (สิทธิ์ admin_warehouse_section)
     * เดิม $name มาจาก extract($_GET) (ไม่ใช่ POST) → จากฟอร์ม/ajax ได้ค่าว่างเสมอ
     */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $uid = (int) Legacy::userId();
        $vals = ['name' => '', 'width' => $this->in($request, 'width'), 'height' => $this->in($request, 'height'), 'long' => $this->in($request, 'long')];
        if (Php74::eq($this->php($request, 'id'), '')) {
            $id = DB::table('warehouse_section')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                ...$vals,
            ]);
        } else {
            DB::table('warehouse_section')->where('id', $id)->update([...$vals, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
        }

        return response(Alerts::success(
            'window.location.href = "'.Legacy::href('admin/warehouse_section').'";',
            Lang::get('success_process_no').Alerts::js(W::prefix('warehouse_section').$id),
        ));
    }

    private function trackingQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('tracking')
            ->leftJoin('user', 'user.id', '=', 'tracking.user')
            ->leftJoin('warehouse_section_detail as t3', 't3.tracking_id', '=', 'tracking.id')
            ->leftJoin('warehouse_section as t4', 't4.id', '=', 't3.section_id')
            ->where('tracking.is_delete', 0)->where('t3.is_delete', 0);
    }

    /** @return list<string> */
    private function trackingColumns(): array
    {
        return ['tracking.*', 'user.code', 't3.weight_thai', 't4.name as section_name'];
    }
}
