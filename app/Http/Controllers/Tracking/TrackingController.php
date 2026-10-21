<?php

namespace App\Http\Controllers\Tracking;

use App\Domain\Tracking\Php74;
use App\Domain\Tracking\PricePreview;
use App\Domain\Tracking\ProductTypeSelect;
use App\Domain\Tracking\TrackingLookups;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tracking\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * หน้า tracking ของลูกค้า: _tracking/*.php เดิม
 * ทุก query กรอง `user` = ผู้ที่ล็อกอิน (เดิมบางไฟล์ไม่กรอง: save.php แก้ quote ของคนอื่นได้)
 */
class TrackingController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_tracking/grid';

    /** GET /tracking — _tracking/index.php (PACK: ปุ่ม "เพิ่มรายการ" เฉพาะผู้ใช้ประเภท 2 หรือประเภทลูกของ 2) */
    public function index(): View
    {
        return view('tracking.customer.index', ['canAdd' => self::canAdd()]);
    }

    /**
     * PACK: _tracking/index.php (ปุ่มเพิ่ม) และ _tracking/save.php (แทน getPermission('tracking') ที่ถูก comment ไว้)
     * เดิม: SELECT user.*, user_type.child ... → $data['type'] == 2 || $data['child'] == 2 (เทียบแบบ PHP 7.4)
     * = ลูกค้า "นำเข้าสินค้า" (type 2) หรือประเภทย่อยของมัน (user_type.child = 2) เท่านั้นที่สร้าง/แก้ tracking เองได้
     */
    public static function canAdd(): bool
    {
        $u = DB::table('user')->leftJoin('user_type', 'user_type.id', '=', 'user.type')
            ->where('user.id', Legacy::userId())->first(['user.type', 'user_type.child']);

        return $u !== null && (Php74::eq($u->type, 2) || Php74::eq($u->child, 2));
    }

    /** GET /tracking/create, /tracking/{id} — _tracking/form.php */
    public function form(Request $request): View
    {
        // route parameter อ่านตามชื่อ (defaults legacy_page ก็เป็น parameter ด้วย ห้ามรับแบบตามตำแหน่ง)
        $id = (int) ($request->route('id') ?? 0);
        $uid = Legacy::userId();
        $row = DB::table('tracking')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->where('tracking.id', $id)->where('tracking.is_delete', 0)->where('tracking.user', $uid)
            ->first(['tracking.*', 'lot.name as lot_name', 'lot.type as lot_type', 'product_type.name as product_type_name']);
        $d = (array) ($row ?? []);

        // เดิม: $id == "" เป็นจริงเมื่อ id = 0 (PHP 7.4: 0 == "")
        $isNew = $id === 0;
        $allowEdit = ($row !== null && Php74::eq($d['user'], $uid) && (Php74::eq($d['lot_id'], '0') || Php74::eq($d['lot_id'], '')))
            || $isNew;

        return view('tracking.customer.form', [
            'id' => $id,
            'isNew' => $isNew,
            'd' => $d,
            'allowEdit' => $allowEdit,
            // SR: รูปพัสดุ — เดิม WHERE tracking_id = '<id ของแถวที่พบ>' AND is_delete = 0 AND tracking_id != 0 (ไม่มี ORDER BY → ใช้ id)
            'images' => TrackingLookups::imageList($row !== null ? (int) $row->id : 0),
            'prefix' => TrackingLookups::prefix('tracking'),
            'payPrefix' => TrackingLookups::prefix('payment'),
        ]);
    }

    /** POST /_tracking/grid — _tracking/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('tracking')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        $uid = Legacy::userId();
        $txtsearch = $this->in($request, 'txtsearch');
        $parts = explode('|', $txtsearch);
        $term = $parts[0];
        $status = $parts[1] ?? null;
        $phpParts = explode('|', $this->php($request, 'txtsearch'));

        $q = DB::table(DB::raw('`tracking` FORCE INDEX (`idx_user_del_id`)'))
            ->where('tracking.is_delete', 0)
            ->where('tracking.user', $uid);
        $like = '%'.addcslashes($term, '%_\\').'%';
        $q->where(fn ($w) => $w->where('tracking.tracking', 'like', $like)
            ->orWhere('tracking.order', 'like', $like)
            ->orWhere('tracking.quote', 'like', $like));

        // เดิม: $s != "-1" && $s != "" && $s == N (เทียบแบบ PHP 7.4 — "abc" == 0 เป็นจริง)
        if ($status !== null && Php74::neq($status, '-1') && Php74::neq($status, '')) {
            if (Php74::eq($status, 0)) {
                $q->where('tracking.date_warehouse', '!=', '0000-00-00 00:00:00');
            }
            if (Php74::eq($status, 1)) {
                $q->where('tracking.date_outofchina', '!=', '0000-00-00 00:00:00');
            }
            if (Php74::eq($status, 2)) {
                $q->where('tracking.date_thai', '!=', '0000-00-00 00:00:00');
            }
            if (Php74::eq($status, 3)) {
                $q->where('tracking.payment_id', '0');
            }
        }

        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = TrackingLookups::offset($page);
        $items = $offset === null ? collect() : $q
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('product_type', 'product_type.id', '=', 'tracking.product_type_id')
            ->orderByDesc('tracking.id')
            ->offset($offset)->limit(10)
            ->get(['tracking.*', 'lot.name as lot_name', 'lot.type as lot_type', 'product_type.name as protype_name']);

        $ids = $items->pluck('id')->map(fn ($v) => (int) $v)->all();

        return response(view('tracking.customer.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'orderIds' => TrackingLookups::orderIds($ids),
            'images' => TrackingLookups::images($ids),
            'search' => $phpParts[0],
            'status' => isset($parts[1]) ? $phpParts[1] ?? '' : null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'orderPrefix' => TrackingLookups::prefix('order'),
            'payPrefix' => TrackingLookups::prefix('payment'),
        ])->render());
    }

    /** POST /_tracking/delete — _tracking/delete.php */
    public function delete(Request $request): Response
    {
        $uid = Legacy::userId();
        $arr = $this->in($request, 'arr_id');
        // เดิม: arr_id = ",1,2" (ตัดตัวแรก) หรือ id — ฝังลง IN (...) ตรงๆ; ค่าที่ไม่ใช่ตัวเลข = SQL error = ไม่มีอะไรเกิดขึ้น แต่ยังตอบสำเร็จ
        $ids = Php74::idList($arr !== '' ? substr($arr, 1) : $this->in($request, 'id'));
        if ($ids !== null) {
            $first = DB::table('tracking')->whereIn('id', $ids)->where('user', $uid)->orderBy('id')->first(['id', 'lot_id', 'is_delete']);
            // เดิม: lot_id != 0 และ (tracking ยังไม่ถูกลบ = query join คืนอย่างน้อย 1 แถว) → ห้ามลบ
            if ($first !== null && Php74::neq($first->lot_id, 0) && (int) $first->is_delete === 0) {
                return response("ErrorBox('เมื่อพัสดุเข้าโกดังแล้ว ไม่อนุญาติให้แก้ไข', 'CloseLoading()'); ");
            }
            DB::table('tracking')->whereIn('id', $ids)->where('user', $uid)
                ->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
        }

        return response(Alerts::success());
    }

    /**
     * POST /_tracking/save — _tracking/save.php
     * PACK: ตรวจประเภทผู้ใช้ (canAdd) แทนสิทธิ์ tracking — ไม่ผ่าน = getPermissionAlert('tracking') แบบเดิม;
     * ไม่มี is_takeapic / tracking_check ของ SR; แก้ไขได้ = เปลี่ยนเลขพัสดุตามที่ส่งมา (เหมือน jl)
     */
    public function save(Request $request): Response
    {
        if (! self::canAdd()) {
            return response(Alerts::permission('tracking'));
        }
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $tracking = str_replace(' ', '', $this->in($request, 'tracking'));
        $order = $this->in($request, 'order');
        $quote = $this->in($request, 'quote');
        $ship = $this->in($request, 'ship');
        $isCraft = $this->in($request, 'is_craft');
        $isQc = $this->in($request, 'is_qc');
        $prefix = TrackingLookups::prefix('tracking');
        $done = 'window.location.href = "'.Legacy::href('tracking').'";';

        if ($id === '') {
            $dup = DB::table('tracking')->where('tracking', $tracking)->where('is_delete', 0)->exists();
            if ($dup) {
                return response("ErrorBox('เลขรหัสพัสดุซ้ำ', 'CloseLoading()'); ");
            }
            $newId = DB::table('tracking')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'no' => '0', 'tracking' => $tracking, 'order' => $order,
                'price_kg' => '0.00', 'price_cubic' => '0.00', 'price_shipping' => '0.00', 'price_amount' => '0.00',
                'rate_kg' => null, 'rate_cubic' => null, 'product_type_id' => null, 'lot_id' => null, 'user' => $uid,
                'weight' => '0.0', 'width' => '0.0', 'height' => '0.0', 'long' => '0.0', 'amount' => '1',
                'ship' => $ship, 'payment_id' => '0', 'is_craft' => $isCraft, 'is_qc' => $isQc, 'quote' => $quote,
                'price_qc' => '0.00', 'price_repack' => '0.00', 'price_express' => '0.00', 'price_else' => '0.00', 'price_craft' => '0.00',
                'date_warehouse' => '0000-00-00 00:00:00', 'date_outofchina' => '0000-00-00 00:00:00',
                'date_thai' => '0000-00-00 00:00:00', 'date_finish' => '0000-00-00 00:00:00', 'remark' => null,
            ]);

            return response(Alerts::success($done, Lang::get('success_process_no').$prefix.$newId.'<br />'.Lang::get('tracking').' : '.Alerts::js($tracking)));
        }

        $row = DB::table('tracking')->where('id', $id)->where('is_delete', 0)->where('user', $uid)->first();
        if ($row !== null && (Php74::eq($row->lot_id, '0') || Php74::eq($row->lot_id, ''))) {
            DB::transaction(function () use ($row, $id, $uid, $tracking, $order, $quote, $isCraft, $ship, $isQc) {
                $linked = DB::table('bill_product_on_tracking')->where('tracking_id', $id)->where('user', $uid)->exists();
                $set = ['tracking' => $tracking, 'order' => $order, 'quote' => $quote];
                if (! $linked) {
                    $set += ['is_craft' => $isCraft, 'ship' => $ship, 'is_qc' => $isQc];
                }
                DB::table('tracking')->where('id', $row->id)->update($set);
            });
            // เดิม $tracking = $data['tracking'] โดย $data ถูกแทนด้วยแถว bill_product_on_tracking (ไม่มีคอลัมน์ tracking) → ข้อความท้ายว่างเสมอ
            $shown = '';
        } else {
            // เดิม UPDATE ... WHERE id = '$id' ไม่กรองเจ้าของ = แก้ quote ของ tracking คนอื่นได้ → กรอง user
            DB::table('tracking')->where('id', $id)->where('user', $uid)->update(['quote' => $quote]);
            $shown = (string) ($row->tracking ?? '');
        }

        return response(Alerts::success($done, Lang::get('success_process_no').$prefix.Alerts::js($this->php($request, 'id')).'<br />'.Lang::get('tracking').' : '.Alerts::js($shown)));
    }

    /** POST /_tracking/load_pricing — เดิมไม่ตรวจล็อกอิน/เจ้าของ: ที่นี่ใช้อัตราของ tracking ของตัวเองเท่านั้น */
    public function loadPricing(Request $request): Response
    {
        $type = DB::table('tracking')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->where('tracking.id', $this->in($request, 'id'))->where('tracking.is_delete', 0)
            ->where('tracking.user', Legacy::userId())
            ->value('user.type');

        return response(PricePreview::json($type, $this->pricingInput($request)));
    }

    /** POST /_tracking/load_product_type */
    public function loadProductType(Request $request): Response
    {
        $sel = DB::table('tracking')->where('id', $this->in($request, 'id'))->where('user', Legacy::userId())->value('product_type_id');

        return response(ProductTypeSelect::html($sel, $this->in($request, 'ship'), false));
    }

    /** POST /_tracking/save_quick_quote — แก้ quote ของ tracking ตัวเอง (ตอบว่าง) */
    public function saveQuickQuote(Request $request): Response
    {
        DB::table('tracking')->where('id', $this->in($request, 'ref'))->where('user', Legacy::userId())
            ->update(['quote' => $this->in($request, 'value')]);

        return response('');
    }

    /** @return array<string, string> */
    protected function pricingInput(Request $request): array
    {
        $out = [];
        foreach (['product_type_id', 'weight', 'width', 'height', 'amount', 'long'] as $k) {
            $out[$k] = $this->in($request, $k);
        }

        return $out;
    }
}
