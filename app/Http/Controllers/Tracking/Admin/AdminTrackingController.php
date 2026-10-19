<?php

namespace App\Http\Controllers\Tracking\Admin;

use App\Domain\Tracking\Php74;
use App\Domain\Tracking\PricePreview;
use App\Domain\Tracking\ProductTypeSelect;
use App\Domain\Tracking\TempUpload;
use App\Domain\Tracking\TrackingLookups;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tracking\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Paging;
use App\Support\Legacy\Pricing;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * หน้า tracking ของแอดมิน: _admin/_tracking/*.php เดิม (สิทธิ์ admin_tracking)
 */
class AdminTrackingController extends Controller
{
    use ReadsLegacyInput;

    public const LOCK_REFUSED = 'tracking นี้อยู่ในบิลแล้ว หรือถูกลบแล้ว ไม่สามารถล็อกได้';

    private const AJAX = '_admin/_tracking/grid';

    /** GET /admin/tracking, /admin/tracking/status/{status} — _admin/_tracking/index.php (status ไม่ถูกใช้ในหน้าเดิม) */
    public function index(): View
    {
        return view('tracking.admin.index');
    }

    /** GET /admin/tracking/create, /admin/tracking/{id} — _admin/_tracking/form.php */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $row = DB::table('tracking')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->where('tracking.id', $id)->where('tracking.is_delete', 0)
            ->first(['tracking.*', 'lot.name as lot_name', 'lot.type as lot_type', 'user.code']);

        return view('tracking.admin.form', [
            'id' => $id,
            'd' => (array) ($row ?? []),
            'images' => TrackingLookups::imageList($row !== null ? (int) $row->id : 0),
            'prefix' => TrackingLookups::prefix('tracking'),
            'payPrefix' => TrackingLookups::prefix('payment'),
        ]);
    }

    /**
     * เงื่อนไขค้นหาของ grid/load_paging เดิม: [0]=เลขแทรค (SR: LIKE '%x%') [1]=ship [2]=ออเดอร์ (LIKE 'x%') [3]=รหัสลูกค้า (เท่ากับ)
     *
     * @param  list<string|null>  $s
     */
    private function applySearch(Builder $q, array $s): Builder
    {
        $member = DB::table('user')->where('code', (string) ($s[3] ?? ''))->value('id');
        $ship = $s[1] ?? null;
        if ($ship !== null && Php74::neq($ship, '-1') && Php74::neq($ship, '')) {
            $q->where('tracking.ship', $ship);
        }
        $or = [];
        if (($s[0] ?? '') !== '') {
            // SR: เลขแทรคค้นแบบมีคำนี้ (LIKE '%x%') — jl เป็นขึ้นต้นด้วย (x%)
            $or[] = fn (Builder $w) => $w->orWhere('tracking.tracking', 'like', '%'.addcslashes((string) $s[0], '%_\\').'%');
        }
        if (($s[2] ?? '') !== '') {
            $or[] = fn (Builder $w) => $w->orWhere('tracking.order', 'like', addcslashes((string) $s[2], '%_\\').'%');
        }
        if ($member !== null) {
            $or[] = fn (Builder $w) => $w->orWhere('tracking.user', $member);
        }
        if ($or !== []) {
            $q->where(function (Builder $w) use ($or) {
                foreach ($or as $f) {
                    $f($w);
                }
            });
        }

        return $q;
    }

    /** POST /_admin/_tracking/grid */
    public function grid(Request $request): Response
    {
        if (! perm('admin_tracking')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $page = $this->php($request, 'page');
        $offset = TrackingLookups::offset($page);

        $items = collect();
        if ($offset !== null) {
            $q = DB::table('tracking')
                ->leftJoin('user', 'user.id', '=', 'tracking.user')
                ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
                ->leftJoin('product_type', 'product_type.id', '=', 'tracking.product_type_id')
                ->where('tracking.is_delete', 0);
            $items = $this->applySearch($q, $s)
                ->orderByDesc('tracking.date_warehouse')->orderByDesc('tracking.id')
                ->offset($offset)->limit(10)
                // SR: ชื่อล็อตอ่านจาก lot ทีละแถว (ลิงก์ใช้ lot.id ที่พบ — ไม่พบ = ลิงก์ admin/lot/ ว่าง) → LEFT JOIN แทน
                ->get(['tracking.*', 'user.code', 'lot.id as lot_real_id', 'lot.name as lot_name', 'product_type.name as product_type_name']);
        }
        $ids = $items->pluck('id')->map(fn ($v) => (int) $v)->all();

        return response(view('tracking.admin.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'orderIds' => TrackingLookups::orderIds($ids),
            'quotes' => TrackingLookups::adminQuotes($ids),
            'goods' => TrackingLookups::goodsconfirmIds($ids),
            'sections' => TrackingLookups::sectionNames($ids),
            'images' => TrackingLookups::images($ids),
            's' => $sp,
            'page' => $page,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'orderPrefix' => TrackingLookups::prefix('order'),
            'payPrefix' => TrackingLookups::prefix('payment'),
            'goodsPrefix' => TrackingLookups::prefix('goodsconfirm'),
        ])->render());
    }

    /** POST /_admin/_tracking/load_paging — นับจำนวนแถวแยก (SR: นับสดทุกครั้ง ไม่มี cache Redis / ไม่ตัด zero-width space แบบ jl) */
    public function loadPaging(Request $request): Response
    {
        $s = explode('|', $this->in($request, 'txtsearch'));
        $total = $this->applySearch(DB::table('tracking')->where('tracking.is_delete', 0), $s)->count('tracking.id');

        return response(Paging::getpaging($this->php($request, 'page'), $total, 10, self::AJAX));
    }

    /** POST /_admin/_tracking/delete */
    public function delete(Request $request): Response
    {
        $arr = $this->in($request, 'arr_id');
        $ids = Php74::idList($arr !== '' ? substr($arr, 1) : $this->in($request, 'id'));
        if ($ids !== null) {
            DB::table('tracking')->whereIn('id', $ids)
                ->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);
        }

        return response(Alerts::success());
    }

    /**
     * POST /_admin/_tracking/save_lock — เดิมไม่ตรวจสิทธิ์เลย (ใส่ admin_tracking)
     * เดิมไม่สนค่า value ที่ส่งมา (ปุ่มล็อก -2 / ปลดล็อก 0) ตั้ง payment_id = -3 (claim) เสมอ — คงไว้ (เจ้าของยืนยันว่าตั้งใจ)
     * ต่างจากเดิม (เจ้าของอนุมัติ): เดิมเขียนทับ tracking ที่อยู่ในบิลแล้ว (payment_id > 0 = หลุดจากบิล) หรือที่ลบแล้วได้
     * → เฉพาะแถวที่ยังไม่ลบและ payment_id <= 0 (0 / -2 / -3) ใน transaction + lock แถว; ไม่เข้าเงื่อนไข = ErrorBox ไม่เปลี่ยนอะไร
     */
    public function saveLock(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $ok = DB::transaction(function () use ($id) {
            $row = DB::table('tracking')->where('id', $id)->lockForUpdate()->first(['id', 'is_delete', 'payment_id']);
            if ($row === null || (int) ($row->is_delete ?? 0) !== 0 || (int) ($row->payment_id ?? 0) > 0) {
                return false;
            }
            DB::table('tracking')->where('id', $row->id)
                ->update(['payment_id' => '-3', 'date_thai' => '0000-00-00 00:00:00']);

            return true;
        });
        if (! $ok) {
            return response(Alerts::error('CloseLoading()', self::LOCK_REFUSED));
        }

        return response('');
    }

    /** POST /_admin/_tracking/load_pricing — เดิมไม่ตรวจสิทธิ์ (ใส่ admin_tracking) */
    public function loadPricing(Request $request): Response
    {
        $type = DB::table('tracking')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->where('tracking.id', $this->in($request, 'id'))->where('tracking.is_delete', 0)
            ->value('user.type');
        $in = [];
        foreach (['product_type_id', 'weight', 'width', 'height', 'amount', 'long'] as $k) {
            $in[$k] = $this->in($request, $k);
        }

        return response(PricePreview::json($type, $in));
    }

    /** POST /_admin/_tracking/load_product_type — เดิมไม่ตรวจสิทธิ์ (ใส่ admin_tracking) */
    public function loadProductType(Request $request): Response
    {
        $sel = DB::table('tracking')->where('id', $this->in($request, 'id'))->value('product_type_id');

        return response(ProductTypeSelect::html($sel, $this->in($request, 'ship'), true));
    }

    /**
     * SR: รูปพัสดุหลังบันทึก — เดิม: ลบ (is_delete = 1) รูปทั้งหมดของ tracking แล้ว INSERT ใหม่ตาม upload_upload_track_mul[]
     * (ทั้งรูปเดิมที่ยังไม่กดลบและรูปที่อัปใหม่) ไฟล์ที่ยังอยู่ใน temp = ตอบ JS ให้เบราว์เซอร์ย้ายขึ้น storage (_api/blockblob_ajax)
     * ต่างจากเดิม: รับเฉพาะชื่อไฟล์ธรรมดา (เดิมบันทึก/ส่งต่อทุกค่า = path traversal + ฝัง JS ได้)
     *
     * @param  list<string>  $images
     */
    private static function syncImages(int|string $trackingId, array $images, ?int $uid): string
    {
        DB::table('tracking_image')->where('tracking_id', $trackingId)->update(['is_delete' => '1']);
        $js = '';
        foreach ($images as $img) {
            if (TempUpload::exists($img)) {
                $js .= TempUpload::blobJs($img);
            }
            DB::table('tracking_image')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'tracking_id' => $trackingId, 'attachment' => $img,
            ]);
        }

        return $js;
    }

    /** POST /_admin/_tracking/save */
    public function save(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $code = str_replace(' ', '', $this->in($request, 'code'));
        $member = DB::table('user')->where('code', $code)->value('id');
        if ($member === null) {
            return response("ErrorBox('".Lang::get('no_user')."', 'CloseLoading()'); ");
        }
        $num = fn (string $k) => str_replace(',', '', $this->in($request, $k));
        $date = function (string $k) use ($request) {
            $exp = explode('|', $this->in($request, $k));

            return Format::getdatesubmit($exp[0]).($exp[1] ?? '');
        };
        $v = [
            'tracking' => str_replace(' ', '', $this->in($request, 'tracking')),
            'order' => $this->in($request, 'order'),
            'ship' => $this->in($request, 'ship'),
            'product_type_id' => $this->in($request, 'shipping_type'),
            'is_craft' => $this->in($request, 'is_craft'),
            'is_qc' => $this->in($request, 'is_qc'),
            'remark' => $this->in($request, 'remark'),
            // SR: รหัสย่อย — เดิมตัดช่องว่างแล้ว nStr() ซ้ำกับการแปลง ' ของ POST เดิม = ' ถูกเก็บเป็น '' ใน DB
            'subuser' => Format::nStr(str_replace(' ', '', $this->in($request, 'subuser'))),
            'is_takeapic' => $this->in($request, 'is_takeapic'),
        ];
        foreach (['weight', 'width', 'height', 'long', 'amount', 'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft'] as $k) {
            $v[$k] = $num($k);
        }
        $auto = $this->in($request, 'is_auto_price') === '1';
        $images = TempUpload::names(LegacyInput::post($request, 'upload_upload_track_mul', []));
        $prefix = TrackingLookups::prefix('tracking');
        $done = 'window.location.href = "'.Legacy::href('admin/tracking').'";';

        if ($id === '') {
            if (DB::table('tracking')->where('tracking', $v['tracking'])->exists()) {
                return response("ErrorBox('เลขรหัสพัสดุซ้ำ', 'CloseLoading()'); ");
            }
            $js = '';
            $newId = DB::transaction(function () use ($v, $uid, $member, $auto, $images, &$js) {
                $newId = DB::table('tracking')->insertGetId($v + [
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'no' => '0', 'price_kg' => '0.00', 'price_cubic' => '0.00', 'price_shipping' => '0.00',
                    'price_amount' => '0.00', 'rate_kg' => '0', 'rate_cubic' => '0', 'lot_id' => '0', 'user' => $member,
                    'payment_id' => '0', 'quote' => '',
                ]);
                if ($auto) {
                    Pricing::calPriceAutoDiff($newId);
                }
                $js = self::syncImages($newId, $images, $uid);

                return $newId;
            });

            return response($js.Alerts::success($done, Lang::get('success_process_no').$prefix.$newId));
        }

        $js = '';
        DB::transaction(function () use ($v, $id, $request, $num, $date, $member, $auto, $images, $uid, &$js) {
            DB::table('tracking')->where('id', $id)->update($v + [
                'quote' => $this->in($request, 'quote'),
                'price_kg' => $num('price_kg'), 'price_cubic' => $num('price_cubic'),
                'date_warehouse' => $date('date_warehouse'), 'date_outofchina' => $date('date_outofchina'),
                'user' => $member, 'date_thai' => $date('date_arrive'),
            ]);
            if ($auto) {
                Pricing::calPriceAutoDiff($id);
            }
            // เดิม: code = nobody (ค่าคงที่ที่ไม่เคย define → string 'nobody')
            $nobody = DB::table('user')->where('code', 'nobody')->value('id');
            if (Php74::neq($member, $nobody)) {
                DB::table('goodsconfirm')->where('tracking_id', $id)->update(['status' => 2]);
            }
            $js = self::syncImages($id, $images, $uid);
        });

        return response($js.Alerts::success($done, Lang::get('success_process_no').$prefix.Alerts::js($this->php($request, 'id'))));
    }
}
