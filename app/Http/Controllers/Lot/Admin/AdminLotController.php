<?php

namespace App\Http\Controllers\Lot\Admin;

use App\Domain\Lot\LotLookups;
use App\Domain\Lot\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Lot\Concerns\ReadsLegacyInput;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * หน้าแอดมินล็อตและ ajax ที่อ่านข้อมูล: _admin/_lot/index.php, form.php, grid.php, load_*.php เดิม
 * สิทธิ์ = admin_lot หรือ admin_lot_view (ตรวจที่ middleware legacy.perm เหมือน getPermission เดิม)
 */
class AdminLotController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_lot/grid';

    /** GET /admin/lot — _admin/_lot/index.php */
    public function index(Request $request): View
    {
        // PACK: ปุ่ม "เพิ่มรายการใหม่" (สร้างล็อต) แสดงเฉพาะ user id 5 (checkUser()['id'] == 5 เดิม)
        return view('lot.admin.index', ['status' => (string) LegacyInput::query($request, 'status'), 'allowEdit' => LotLookups::allowEdit()]);
    }

    /** GET /admin/lot/create, /admin/lot/{id} — _admin/_lot/form.php */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $lot = DB::table('lot')->where('id', $id)->where('is_delete', 0)->first();
        $lastNo = DB::table('tracking')->where('lot_id', $id)->where('is_delete', '0')->orderByDesc('no')->value('no');

        return view('lot.admin.form', [
            'id' => $id,
            'd' => (array) ($lot ?? []),
            'prefix' => LotLookups::prefix('lot'),
            'nextNo' => Php74::num($lastNo) + 1,
            // เดิม: $permission_lot_view = true เมื่อมี admin_lot_view (แม้มี admin_lot ด้วย) → ซ่อนช่องราคา
            'lotView' => perm('admin_lot_view'),
            // PACK: ฟอร์มเพิ่ม/แก้ tracking, ช่องแก้หัวล็อต, ปุ่มตั้งวันที่/พิมพ์ เฉพาะ user id 5 (คนอื่นเห็นหัวล็อตแบบอ่านอย่างเดียว)
            'allowEdit' => LotLookups::allowEdit(),
        ]);
    }

    /** POST /_admin/_lot/grid — _admin/_lot/grid.php */
    public function grid(Request $request): Response
    {
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $type = $parts[1] ?? null;

        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        $q = DB::table('lot')->where('lot.is_delete', 0)
            ->where(fn ($w) => $w->where('lot.id', 'like', $like)->orWhere('lot.name', 'like', $like));
        if ($type !== null && Php74::neq($type, '-2') && Php74::neq($type, '')) {
            $q->where('lot.type', $type);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = LotLookups::offset($page);
        $items = $offset === null ? collect() : $q->leftJoin('user', 'lot.create_by', '=', 'user.id')
            ->orderByDesc('lot.id')->offset($offset)->limit(10)
            ->get(['lot.*', 'user.code']);

        // เดิม: LEFT JOIN (COUNT ทั้งตาราง tracking GROUP BY lot_id) + 4 query ต่อแถว → ดึงเฉพาะล็อตในหน้านี้ครั้งเดียวต่อเรื่อง
        // SR: วันที่ 3 ช่อง = tracking ที่ยังไม่ลบ วันเข้าโกดังน้อยสุดของล็อต (ORDER BY date_warehouse ASC LIMIT 1 เดิม — เสมอกันใช้ id)
        //     + คอลัมน์ "ค้างจ่าย" = จำนวนลูกค้า (GROUP BY user) ที่มี tracking payment_id = 0 ในล็อต
        $ids = $items->pluck('id')->map(fn ($v) => (int) $v)->all();
        $counts = [];
        $first = [];
        $unpaid = [];
        if ($ids !== []) {
            $counts = DB::table('tracking')->where('is_delete', 0)->whereIn('lot_id', $ids)
                ->groupBy('lot_id')->pluck(DB::raw('COUNT(id)'), 'lot_id')->all();
            $ranked = DB::table('tracking')->where('is_delete', 0)->whereIn('lot_id', $ids)
                ->select(['lot_id', 'date_warehouse', 'date_outofchina', 'date_thai',
                    DB::raw('ROW_NUMBER() OVER (PARTITION BY lot_id ORDER BY date_warehouse ASC, id ASC) AS rn')]);
            $first = DB::query()->fromSub($ranked, 'x')->where('rn', 1)
                ->get(['lot_id', 'date_warehouse', 'date_outofchina', 'date_thai'])
                ->keyBy(fn ($r) => (int) $r->lot_id)->all();
            // GROUP BY user เดิมนับ user ที่เป็น NULL เป็นกลุ่มหนึ่งด้วย → COALESCE
            $unpaid = DB::table('tracking')->where('payment_id', '0')->where('is_delete', 0)->whereIn('lot_id', $ids)
                ->groupBy('lot_id')->pluck(DB::raw('COUNT(DISTINCT COALESCE(`user`, -2147483648))'), 'lot_id')
                ->map(fn ($v) => (int) $v)->all();
        }

        return response(view('lot.admin.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'counts' => $counts,
            'first' => $first,
            'unpaid' => $unpaid,
            'search' => $phpParts[0],
            'type' => isset($parts[1]) ? ($phpParts[1] ?? '') : null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /**
     * POST /_admin/_lot/load_lot_detail — _admin/_lot/load_lot_detail.php
     * PACK = แม่แบบ jl: ทั้งตารางในคำขอเดียว (ช่องค้นหา + หัว/ท้ายตาราง + ผลรวม hidden) — tracking ระดับบนของล็อต (ไม่ใช่แถวลูก craft)
     * + ค้นหา รหัสลูกค้า/เลขพัสดุ (GROUP BY tracking.id แบบเดิม — sql_mode ว่าง: เลือก bill_product_id แถวแรก)
     */
    public function loadLotDetail(Request $request): Response
    {
        $search = $this->php($request, 'search') === 'undefined' ? '' : $this->in($request, 'search');
        $q = DB::table('tracking')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->leftJoin('bill_product_on_tracking as t', 't.tracking_id', '=', 'tracking.id')
            ->leftJoin('bill_product_detail as t2', 't.bill_product_detail_id', '=', 't2.id')
            ->where('tracking.lot_id', $this->in($request, 'id'))
            ->where('tracking.is_delete', 0)
            ->whereRaw('(tracking.header = 0 OR tracking.header IS NULL)');
        if ($search !== '') {
            $q->where(fn ($w) => $w->where('user.code', $search)->orWhere('tracking.tracking', $search));
        }
        $rows = $q->groupBy('tracking.id')->orderByDesc('tracking.no')
            ->get(['tracking.*', 'user.code', 'product_type.name as type_name', 't2.bill_product_id']);

        // รูปพัสดุต่อแถว: เดิม JS loadImage() ยิง load_image ทีละ tracking (N ajax ต่อหน้า) → ใส่มาพร้อมแถวใน query เดียว
        // (div.img_track ที่มีเนื้อหาแล้ว JS เดิมจะข้าม) — load_image ของ PACK ต้องมี admin_lot จึงใส่ให้เฉพาะผู้ถือ admin_lot
        $prefill = perm('admin_lot');

        return response(view('lot.admin.detail', [
            'rows' => $rows,
            'images' => $prefill ? LotLookups::trackingImages($rows->pluck('id')->all()) : [],
            'prefill' => $prefill,
            // ค่าในช่องค้นหา = ตัวแปร PHP เดิม (' เป็น '')
            'search' => $this->php($request, 'search') === 'undefined' ? '' : $this->php($request, 'search'),
            'orderPrefix' => LotLookups::prefix('order'),
            'allowEdit' => LotLookups::allowEdit(),
            // เดิม if(!getPermission('admin_lot_view')) รอบคอลัมน์ราคา kg/คิว (ผู้ถือ admin_lot_view ไม่เห็น แม้มี admin_lot)
            'lotView' => perm('admin_lot_view'),
        ])->render());
    }

    /** POST /_admin/_lot/load_image — รูปพัสดุเล็กๆ ข้างเลข tracking (คงไว้ให้ JS เดิม; แถวจาก load_lot_detail มีรูปมาแล้ว) — PACK: สิทธิ์ admin_lot */
    public function loadImage(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $list = ctype_digit($id) ? (LotLookups::trackingImages([(int) $id])[(int) $id] ?? []) : [];

        return response(view('lot.admin.img_track', ['list' => $list])->render());
    }

    /** POST /_admin/_lot/load_quickfile — กล่องแนบรูปพัสดุ (modal) ของ tracking หนึ่งรายการ — PACK: สิทธิ์ admin_lot */
    public function loadQuickfile(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $data = ctype_digit($id) ? DB::table('tracking')->where('is_delete', 0)->where('id', $id)->first(['id', 'tracking', 'no']) : null;
        // เดิม tracking ไม่พบ → tracking_id = '' (= 0) แสดงรูปที่ไม่มีเจ้าของ → ที่นี่ไม่แสดง
        $list = $data === null ? [] : (LotLookups::trackingImages([(int) $data->id])[(int) $data->id] ?? []);

        return response(view('lot.admin.quickfile', ['data' => $data, 'list' => $list])->render());
    }

    /** POST /_admin/_lot/load_unpaid — รายการลูกค้าค้างจ่ายในล็อต (SQL เดิม — ค่าที่ฝังเปลี่ยนเป็น placeholder) */
    public function loadUnpaid(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $rows = DB::select("SELECT user.code, (SELECT count(id) as c
					from tracking as t where t.lot_id = ?
					AND t.payment_id = '0' AND t.is_delete = 0 AND t.user = user.id) as counts FROM `tracking`
				left join `user` on `user`.id = tracking.user
				WHERE `lot_id` = ? AND tracking.`is_delete` = '0' and tracking.`payment_id` = '0' group by user", [$id, $id]);

        return response(view('lot.admin.unpaid', ['rows' => $rows])->render());
    }

    /** POST /_admin/_lot/load_lastest_lot — _admin/_lot/load_lastest_lot.php */
    public function loadLastestLot(Request $request): Response
    {
        $no = DB::table('tracking')->where('lot_id', $this->in($request, 'id'))->where('is_delete', '0')->orderByDesc('no')->value('no');

        return response(Php74::str(Php74::num($no) + 1));
    }

    /** POST /_admin/_lot/load_tracking — _admin/_lot/load_tracking.php (JSON แบบ mysqli_fetch_array เดิม) */
    public function loadTracking(Request $request): Response
    {
        $tracking = str_replace(' ', '', $this->in($request, 'tracking'));
        $lot = DB::table('lot')->where('id', $this->in($request, 'lot_id'))->first();
        $data = DB::table('tracking')->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->where('tracking.is_delete', 0)->where('tracking.tracking', $tracking)
            ->first(['tracking.*', 'user.code']);

        $types = DB::table('product_type')->where('is_delete', 0)->where('type', (string) ($lot->type ?? ''))->orderBy('id')->get();
        $html = '<select name="product_type_id" onchange="ontempcal()" class="form-control " >';
        foreach ($types as $row) {
            $sel = Php74::eq($data->product_type_id ?? null, $row->id) ? 'selected="selected" ' : '';
            $html .= '<option '.$sel.'value="'.$row->id.'">'.e((string) $row->name).'</option>';
        }
        $html .= '</select>';

        $diff = $data === null ? null : DB::table('user')->where('id', $data->user)->value('diff');
        // SR GetCalPrice() แก้ product_type_id ของ tracking ให้ตรงประเภทล็อตด้วย → ผู้ถือ admin_lot_view (ดูอย่างเดียว) ได้ราคาเท่ากันแต่ไม่เขียน
        $calc = Pricing::getCalPrice($data->id ?? null, perm('admin_lot'));
        $exp = explode('|', $calc);
        $choose = 0;
        if (Php74::gt($exp[1], $exp[0]) && Php74::gt(Php74::num($exp[1]) - Php74::num($exp[0]), $diff)) {
            $choose = 1;
        }

        // SR: $data_craft = '' แล้วเป็น array เมื่อมีแถวลูก (jl เริ่มด้วย [[]]) → JSON "" หรือ [แถวลูก...]
        $craft = '';
        if ($data !== null && Php74::eq($data->is_craft, 1)) {
            foreach (DB::table('tracking')->where('header', (string) $data->id)->orderBy('id')->get() as $row) {
                $craft = is_array($craft) ? $craft : [];
                $craft[] = LotLookups::both($row);
            }
        }
        // SR: ช่องแนบรูปพัสดุ (data[7]) — เดิม tracking ไม่พบ = tracking_id '' (= 0) แสดงรูปที่ไม่มีเจ้าของ → ไม่แสดง
        $imageHtml = view('lot.admin.attachment', [
            'list' => $data === null ? [] : (LotLookups::trackingImages([(int) $data->id])[(int) $data->id] ?? []),
            'wrap' => false,
        ])->render();

        $res = [
            LotLookups::both($data),
            $html,
            $calc,
            ($data->price_kg ?? '').'|'.($data->price_cubic ?? ''),
            $choose,
            $craft,
            LotLookups::both($lot),
            $imageHtml,
        ];

        return response(json_encode($res));
    }

    /** POST /_admin/_lot/load_user_rate — _admin/_lot/load_user_rate.php */
    public function loadUserRate(Request $request): Response
    {
        $code = $this->in($request, 'code');
        $types = DB::table('product_type')->where('is_delete', 0)->orderBy('id')->get(['id', 'kg', 'cubic']);
        $current = DB::table('user_type_detail')
            ->leftJoin('user', 'user.type', '=', 'user_type_detail.user_type_id')
            ->where('user.code', $code)->where('user_type_detail.is_delete', 0)->where('user.is_delete', 0)
            ->orderBy('user_type_detail.id')
            ->get(['user_type_detail.product_type_id', 'user_type_detail.kg', 'user_type_detail.cubic']);

        $str = null;
        foreach ($types as $row) {
            $hit = $current->first(fn ($it) => Php74::eq($it->product_type_id, $row->id));
            $src = $hit ?? $row;
            $pid = $hit ? $hit->product_type_id : $row->id;
            $str .= "<input type='hidden' class='product_type_rate_".$pid."' kg='".$src->kg."' cubic='".$src->cubic."' />";
        }
        $diff = DB::table('user')->where('code', $code)->value('diff');

        return response(json_encode([$str, $diff === null ? null : (string) $diff]));
    }

    /** POST /_admin/_lot/load_autocomplete_user — _admin/_lot/load_autocomplete_user.php */
    public function autocompleteUser(Request $request): Response
    {
        $codes = DB::table('user')->where('code', 'like', '%'.addcslashes($this->in($request, 'query'), '%_\\').'%')
            ->where('is_enable', '1')->where('is_delete', 0)->orderBy('id')->pluck('code')
            ->map(fn ($v) => $v === null ? null : (string) $v)->all();

        return response(json_encode(['code' => $codes === [] ? null : $codes]));
    }

    /** POST /_admin/_lot/load_autocomplete_tracking — _admin/_lot/load_autocomplete_tracking.php (JS ที่เรียกถูก comment ไว้) */
    public function autocompleteTracking(Request $request): Response
    {
        $list = DB::table('tracking')->where('tracking', 'like', '%'.addcslashes($this->in($request, 'query'), '%_\\').'%')
            ->where('is_delete', 0)->orderBy('id')->pluck('tracking')
            ->map(fn ($v) => $v === null ? null : (string) $v)->all();

        return response(json_encode(['code' => $list === [] ? null : $list]));
    }

    /** POST /_admin/_lot/load_ref_print — _admin/_lot/load_ref_print.php */
    public function loadRefPrint(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $lots = DB::table('lot')
            ->where(fn ($w) => $w->where('id', $id)->orWhere('name', 'like', '%'.addcslashes($this->in($request, 'search'), '%_\\').'%'))
            // PACK = jl: LIMIT 10 (SR ไม่มี)
            ->where('is_delete', 0)->orderByDesc('id')->limit(10)->get(['id', 'type', 'name']);

        return response(view('lot.admin.ref_print', ['lots' => $lots, 'id' => $this->php($request, 'id')])->render());
    }

    /** POST /_admin/_lot/load_lot_memo — _admin/_lot/load_lot_memo.php */
    public function loadLotMemo(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $memos = DB::table('lot_memo')->leftJoin('user', 'lot_memo.user', '=', 'user.id')
            ->where('lot_memo.lot_id', $id)->where('lot_memo.is_delete', 0)
            ->orderByDesc('lot_memo.no')
            ->get(['lot_memo.*', 'user.code', 'user.id as user_id']);
        $max = DB::table('lot_memo')->where('lot_id', $id)->where('is_delete', 0)->orderByDesc('no_max')->value('no_max');

        return response(view('lot.admin.memo', ['memos' => $memos, 'nextNo' => Php74::num($max) + 1])->render());
    }
}
