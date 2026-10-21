<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Money\Money;
use App\Domain\Payment\PaymentLookups as P;
use App\Domain\Payment\PaymentTotals;
use App\Domain\Payment\Php74;
use App\Domain\Payment\PinBox;
use App\Domain\Payment\TempUpload;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\Concerns\ReadsLegacyInput;
use App\Support\GridQuery;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Lookup;
use App\Support\Legacy\PointCalc;
use App\Support\Legacy\Pricing;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ชำระเงินค่าขนส่ง (ลูกค้า): _payment/*.php เดิม
 * ทุก query กรอง user = ผู้ที่ล็อกอิน — เดิมหลายไฟล์ไม่กรอง (ดู docs/modules/payment.md)
 */
class PaymentController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_payment/grid';

    /** GET /payment, /payment/status/{status} — _payment/index.php */
    public function index(Request $request): View
    {
        return view('payment.customer.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** POST /_payment/grid — _payment/grid.php (ไม่มีสิทธิ์ = ตอบว่างเหมือนเดิม) */
    public function grid(Request $request): Response
    {
        if (! perm('payment')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;

        // bill_payment ไม่มี index `user` → อ่านตารางรอบเดียว (นับ + id ของหน้า) แล้วโหลดเฉพาะ 10 แถว (GridQuery::idPage)
        $q = DB::table('bill_payment')
            ->where('bill_payment.is_delete', 0)
            ->where('bill_payment.id', 'like', '%'.addcslashes($parts[0], '%_\\').'%')
            ->where('bill_payment.user', Legacy::userId());
        if (Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('bill_payment.status', $status);
        } else {
            $q->where(fn ($w) => $w->where('bill_payment.status', '2')->orWhere('bill_payment.status', '3'));
        }
        $page = $this->php($request, 'page');
        [$total, $ids] = GridQuery::idPage($q, 'bill_payment.id', P::offset($page));
        $items = P::strAll(GridQuery::rowsInOrder(DB::table('bill_payment')
            ->leftJoin('shipping_type', 'bill_payment.shipping_type', '=', 'shipping_type.id'),
            'bill_payment.id', $ids, ['bill_payment.*', 'shipping_type.name as ship_name']));

        return response(view('payment.customer.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $php[0],
            'status' => isset($parts[1]) ? ($php[1] ?? '') : null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => P::prefix('payment'),
        ])->render());
    }

    /** GET /payment/create — _payment/create.php */
    public function create(): View
    {
        return view('payment.customer.create');
    }

    /** POST /_payment/load_select_lot — ล็อตที่มีพัสดุรอชำระ (PACK = jl: ไม่มีช่องค้นหา/รหัสย่อยแบบ SR) */
    public function loadSelectLot(): Response
    {
        $rows = DB::table('tracking')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->where('tracking.is_delete', 0)->where('tracking.user', Legacy::userId())
            ->where('tracking.payment_id', '0')->where('tracking.tracking', '!=', '')
            ->where('tracking.lot_id', '!=', 0)->where('tracking.no', '!=', 0)->where('tracking.header', 0)
            ->groupBy('tracking.lot_id')
            ->orderBy('tracking.lot_id')
            // เดิม GROUP BY lot_id ORDER BY lot_id, no — sql_mode ว่าง (ไม่ ONLY_FULL_GROUP_BY) เหมือนเดิม
            ->get([DB::raw('MIN(lot.id) as id'), DB::raw('MIN(lot.name) as lot_name')]);
        $html = '<select id="search_lot" onchange="loaddata();" style="width:100px">';
        $html .= '<option value="-1">'.Lang::get('all').'</option>';
        foreach ($rows as $r) {
            $html .= '<option value="'.e((string) $r->id).'">'.e((string) $r->lot_name).'</option>';
        }
        $html .= '</select>';

        return response($html."\n\n");
    }

    /** POST /_payment/load_create — รายการพัสดุที่เลือกชำระได้ (PACK: รูปพัสดุ tracking_image, ลิงก์ tracking/search/{tracking}; ไม่มีรหัสย่อย) */
    public function loadCreate(Request $request): Response
    {
        $track = $this->in($request, 'track');
        $quote = $this->in($request, 'quote');
        $searchLot = $this->has($request, 'search_lot') ? $this->in($request, 'search_lot') : null;

        $q = self::trackingQuery()
            ->where('tracking.is_delete', 0)->where('tracking.payment_id', 0)
            ->where('tracking.user', Legacy::userId());
        self::applySearch($q, $searchLot, $track, $quote, $this->in($request, 'admit_thai'));
        $items = P::withHeaderCount(P::strAll($q->orderBy('tracking.lot_id')->orderBy('tracking.no')
            ->get(['tracking.*', 'user.code', 'product_type.name as type_name', 'lot.name as lot_name', 'lot.type as lot_type'])));

        return response(view('payment.customer.load_create', [
            'items' => $items,
            'sum' => PaymentTotals::selectable($items),
            'orderIds' => P::orderIds(P::idsOf($items)),
            'children' => P::children($items->filter(fn ($i) => Php74::neq($i->header_count, 0))->pluck('id')),
            'images' => P::trackingImages(P::idsOf($items)),
            'orderPrefix' => P::prefix('order'),
        ])->render());
    }

    /** POST /_payment/load_create_sum — สรุปต่อล็อต */
    public function loadCreateSum(): Response
    {
        $rows = P::strAll(DB::table('tracking')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->where('tracking.is_delete', 0)->where('tracking.user', Legacy::userId())
            ->where('tracking.payment_id', '0')->where('tracking.tracking', '!=', '')
            ->where('tracking.lot_id', '!=', 0)->where('tracking.no', '!=', 0)->where('tracking.header', 0)
            ->groupBy('tracking.lot_id')->orderBy('tracking.lot_id')
            ->get([
                DB::raw('MIN(lot.id) as id'), DB::raw('MIN(lot.name) as lot_name'), DB::raw('MIN(lot.type) as lot_type'),
                DB::raw('sum(weight * tracking.amount) as weight'),
                DB::raw('sum(((width * height * `long`) /1000000 ) * tracking.amount) as cubic'),
                DB::raw('sum(price_kg) as price_kg'), DB::raw('sum(price_cubic) as price_cubic'),
            ]));

        return response(view('payment.customer.load_create_sum', ['items' => $rows])->render());
    }

    /** POST /_payment/save_create — เก็บ tracking ที่เลือกลง bill_payment_detail_temp แล้วไปหน้า create/{random} */
    public function saveCreate(Request $request): Response
    {
        $raw = $request->request->all()['tracking_id'] ?? null;
        if ($raw === null || $raw === '') {
            return response(Alerts::error('CloseLoading()', Lang::get('no_select_tracking')));
        }
        $uid = Legacy::userId();
        $ids = $this->arr($request, 'tracking_id');
        // เดิม INSERT ทุก id ที่ส่งมา (ของคนอื่นก็ได้) — ที่นี่เก็บเฉพาะ tracking ของตัวเอง
        $own = DB::table('tracking')->whereIn('id', array_map('intval', $ids))->where('user', $uid)->pluck('id')
            ->map(fn ($v) => (string) $v)->all();
        $ran = Format::randd(15);
        $rows = [];
        foreach ($ids as $id) {
            if (in_array((string) (int) $id, $own, true)) {
                $rows[] = ['create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'shipping_id' => $id, 'random' => $ran];
            }
        }
        foreach ($rows as $row) {
            DB::table('bill_payment_detail_temp')->insert($row);
        }

        return response("window.location.href = '".Legacy::href('payment/create/'.$ran)."'");
    }

    /** GET /payment/create/{random} — _payment/create_form.php */
    public function createForm(Request $request): View
    {
        $random = strip_tags(trim((string) $request->route('random')));
        $items = P::withHeaderCount(P::strAll(self::tempTrackings($random)
            ->get(['tracking.*', 'lot.name as lot_name', 'lot.type as lot_type', 'product_type.name as type_name'])));

        $hideOwnSender = $items->contains(fn ($i) => Php74::eq($i->product_type_id, 9) || Php74::eq($i->product_type_id, 10));

        return view('payment.customer.create_form', [
            'random' => $random,
            'user' => Legacy::user(),
            'items' => $items,
            'top' => PaymentTotals::all($items),
            'sum' => PaymentTotals::headerOnly($items),
            'hideOwnSender' => $hideOwnSender,
            'shippingTypes' => P::shippingTypes(),
            'cash' => Lookup::getCurrentCash(),
            // PACK (= jl): ค่าเปลี่ยนผู้ส่ง = setting change_sender (ไม่มี charge_sender_edit ต่อรหัสลูกค้าแบบ SR)
            'senderCharge' => Lookup::getSetting('change_sender'),
            'orderIds' => P::orderIds(P::idsOf($items)),
            'children' => P::children($items->filter(fn ($i) => Php74::neq($i->header_count, 0))->pluck('id')),
            'orderPrefix' => P::prefix('order'),
        ]);
    }

    /**
     * POST /_payment/load_shipping_price — ราคาค่าส่งในไทย (JSON ของ getShippingTypePrice + total + html แถวสรุปท้ายตาราง)
     * PACK: ส่วนลดคูปอง (discount = ชื่อคูปองที่ยังไม่ถูกใช้; ไม่พบ = 0) + VAT แบบ PACK (PaymentTotals::vat)
     */
    public function loadShippingPrice(Request $request): Response
    {
        $ref = $this->ownRandom($this->in($request, 'payment_ref'));
        $disPrice = self::couponPrice(self::coupon($this->in($request, 'discount'))) ?? 0;
        $data = Pricing::getShippingTypePrice($this->in($request, 'id'), $ref, $this->in($request, 'sender'));
        // เดิม $price_shipping ไม่ได้กำหนดค่าเริ่มต้น (null)
        $priceShipping = null;
        foreach (self::tempTrackings($ref)->get(['tracking.*']) as $item) {
            $priceShipping = Php74::num($priceShipping) + P::add(P::str($item), ['price_kg', 'price_cubic', 'price_amount', 'price_qc', 'price_express', 'price_else', 'price_repack', 'price_craft']);
        }
        $total = PaymentTotals::plain($data['price_thai_storage'] ?? null, $priceShipping, $data['price_extened'] ?? null, $data['price_thai_shipping'] ?? null)
            - Php74::num($disPrice);
        $nf = fn ($x) => Format::numberformat($x);

        if (Php74::eq($this->in($request, 'is_vat'), 1)) {
            $v = (string) Lookup::getSetting('vat');
            $t = PaymentTotals::vat($total, $v);
            $html = "\t\n\t\t<tr>\n            <th class=\"text-right \" colspan=\"11\"><span class=\"text-danger\">ส่วนลดท้ายบิล</span>\n            </th>\n"
                .'            <th class="text-right   " colspan="4"><span class="text-danger addcomma">'.$nf($disPrice)."</span></th>\n        </tr>\n\n"
                ."\t\t<tr>\n            <th class=\"text-right \" colspan=\"11\"><span class=\"text-danger\">ภาษีมูลค่าเพิ่ม ".e($v)."%</span>\n            </th>\n"
                .'            <th class="text-right   " colspan="4"><span class="text-danger addcomma">'.$nf($t['vat_before'])."</span></th>\n        </tr>\n\n"
                ."        <tr>\n            <th class=\"text-right \" colspan=\"11\"><span class=\"text-danger\">รวม</span>\n            </th>\n"
                .'            <th class="text-right   " colspan="4"><span class="text-danger addcomma">'.$nf($t['shipvat'])."</th>\n        </tr>\n";
            $data = $data ?? [];
            $data['total'] = $t['shipvat'];
        } else {
            $html = "\n\t\t\t<tr>\n\t            <th class=\"text-right\" colspan=\"11\">ส่วนลดท้ายบิล</th>\n"
                ."\t            <th class=\"text-right price_thai_shipping addcomma\" colspan=\"4\">".$nf($disPrice)."</th>\n\t        </tr>\n"
                ."\t\t\t<tr>\n\t            <th class=\"text-right\" colspan=\"11\">".Lang::get('total_net')."</th>\n"
                ."\t            <th class=\"text-right total_net addcomma\" style=\"font-size: 20px!important\"  colspan=\"4\">".Format::numberformat($data['total'] ?? null, true, false)."</th>\n\t        </tr>";
            $data = $data ?? [];
            $data['total'] = $total;
        }
        $data['html'] = str_replace("\n", "\r\n", $html); // ไฟล์เดิมเป็น CRLF

        return response(json_encode(self::jsonValues($data)));
    }

    /**
     * POST /_payment/load_discoutcode — PACK: ราคาส่วนลดของคูปองที่ยังไม่ถูกใช้ (ตอบตัวเลขดิบ ให้ JS eval)
     * ไม่พบ = ErrorBox('ไม่มีรหัสส่วนลดนี้'); ค่าว่าง = ตอบว่าง
     */
    public function loadDiscoutcode(Request $request): Response
    {
        // เดิม nStr($_POST['code']) — ' ถูกแปลงซ้ำเป็น '' (คูปองที่ชื่อมี ' เก็บเป็น '' ตอนบันทึกด้วย nStr เหมือนกัน)
        $code = str_replace("'", "''", $this->in($request, 'code'));
        if ($code === '') {
            return response("\n");
        }
        $price = self::couponPrice(self::coupon($code));
        if ($price === null) {
            return response(Alerts::error('CloseLoading()', 'ไม่มีรหัสส่วนลดนี้'));
        }

        // ค่า price เป็น decimal(10,2) จาก DB = ตัวเลขล้วนเสมอ (JS เดิม eval ค่านี้)
        return response($price."\n");
    }

    /**
     * POST /_payment/save_submit_payment — สร้างบิล ตัดเงิน และผูก tracking (transaction + lock ต่อผู้ใช้)
     * PACK: ถาม PIN จ่ายเงิน + บิลละ 20 วินาที (เหมือน SR), ค่าเปลี่ยนผู้ส่ง = change_sender (jl),
     * คูปองส่วนลด (หักจากยอดก่อน VAT, ผูกคูปองกับบิล), VAT แบบ PACK (vat_before + shipvat = ยอดสุทธิ)
     */
    public function saveSubmitPayment(Request $request): Response
    {
        $user = Legacy::user();
        if (($pin = PinBox::guard($request, $user)) !== null) {
            return response($pin);
        }
        $uid = Legacy::userId();
        $code = $this->ownRandom($this->in($request, 'id'));
        $refId = $this->in($request, 'ref_id');
        $shippingType = $this->in($request, 'shipping_type');
        $radio = $this->in($request, 'shipping_type_radio');
        $senderType = $this->in($request, 'sender_type');
        $isVat = $this->in($request, 'is_vat');
        $discountCode = $this->in($request, 'discount_code');

        // hack yd: 9 บริษัทจัดส่ง, 10 รับเอง
        if (Php74::eq($radio, 9) || Php74::eq($radio, 10)) {
            $shippingType = $radio;
        }

        return DB::transaction(function () use ($request, $uid, $code, $refId, $shippingType, $senderType, $isVat, $discountCode) {
            // กันกดซ้ำ/ส่งพร้อมกัน: ล็อกแถวผู้ใช้ แล้วอ่านคูปอง/บิลล่าสุด/tracking ที่ยังไม่ผูกบิลภายในล็อก
            DB::table('user')->where('id', $uid)->lockForUpdate()->value('id');
            // PACK: คูปองส่วนลด — เดิมอ่านนอก lock แล้ว UPDATE coupon ท้ายไฟล์ (สองบิลพร้อมกันใช้คูปองเดียวกันได้) → ล็อกแถวคูปอง
            $coupon = null;
            if ($discountCode !== '') {
                $coupon = self::coupon($discountCode, true);
                if ($coupon === null) {
                    return response(Alerts::error('CloseLoading()', 'ไม่พบคูปองส่วนลดนี้'));
                }
            }
            $disPrice = self::couponPrice($coupon) ?? 0;
            // บิลใบล่าสุดของผู้ใช้สร้างไม่ถึง 20 วินาที → รอ (เดิมเทียบเวลา PHP กับ create_date ที่ลงด้วย NOW())
            $since = DB::table('bill_payment')->where('user', (string) $uid)->orderByDesc('create_date')->limit(1)
                ->value(DB::raw('TIMESTAMPDIFF(SECOND, create_date, NOW())'));
            if ($since !== null && (int) $since < 20) {
                return response(Alerts::error('CloseLoading()', 'ไม่สามารถสร้างรายการได้ กรุณารอ '.(20 - (int) $since).' วินาที'));
            }
            $st = DB::table('shipping_type')->where('id', $shippingType)->first();
            if ($st === null) {
                return response(Alerts::error('CloseLoading()', Lang::get('no_shipping_type')));
            }
            $st = P::str($st);
            $items = P::strAll(self::tempTrackings($code)->where('tracking.is_delete', 0)->get(['tracking.*']));

            // ค่าส่งตามน้ำหนักคิดจาก random ของรายการที่จ่ายจริง ($code) — เดิมใช้ ref_id ที่ส่งมาแยก (หน้าเดิมส่งค่าเดียวกัน)
            // ส่ง ref_id ของ random อื่นที่เบากว่า = ค่าส่งถูกลง
            $fees = PaymentTotals::fees($st, $senderType, Pricing::getShippingTypePrice($shippingType, $code, $senderType));
            $priceShipping = 0;
            foreach ($items as $item) {
                $priceShipping = $priceShipping + P::add($item, ['price_kg', 'price_cubic', 'price_amount', 'price_qc', 'price_express', 'price_else', 'price_repack', 'price_craft']);
            }
            $total = PaymentTotals::plain($fees['storage'], $priceShipping, $fees['extened'], $fees['shipping']) - Php74::num($disPrice);
            // เดิม (VAT): $numvat, $shipvatbefore, $shipv ไม่ได้กำหนด → '' → 0 ใน DB
            $vat = ['v' => 0, 'vat_before' => 0, 'vat' => 0, 'shipvat' => 0, 'shipvat_before' => 0, 'shipv' => 0];
            if (Php74::eq($isVat, 1)) {
                $v = Lookup::getSetting('vat');
                $t = PaymentTotals::vat($total, $v);
                $vat = ['v' => $v, 'vat_before' => $t['vat_before'], 'vat' => 0, 'shipvat' => $t['shipvat'], 'shipvat_before' => 0, 'shipv' => 0];
                $total = $t['shipvat'];
            }

            if (Php74::gt(Format::numberformatforsubmit($total), Format::numberformatforsubmit(Lookup::getCurrentCash()))) {
                return response(Alerts::error('CloseLoading()', Lang::get('error_usingcash')));
            }
            if ($items->isEmpty()) {
                // เดิมตั้งใจ claim tracking แบบ atomic แล้วยกเลิกบิลถ้า claim ไม่ได้ (แต่ UPDATE อ้างคอลัมน์ tracking.random ที่ไม่มี → ไม่เคยทำงาน)
                return response(Alerts::success('window.location.href = "'.Legacy::href('export').'";', 'รายการนี้ถูกดำเนินการไปแล้ว'));
            }

            $id = DB::table('bill_payment')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'total' => P::s($total), 'submit_date' => DB::raw('NOW()'), 'submit_remark' => $this->in($request, 'submit_remark'),
                'status' => '1', 'price_shipping' => P::s($priceShipping), 'price_thai_storage' => P::s($fees['storage']),
                'price_thai_shipping' => P::s($fees['shipping']), 'user' => (string) $uid,
                'send_name' => $this->in($request, 'name'), 'send_address' => $this->in($request, 'address'), 'send_phone' => $this->in($request, 'phone'),
                'shipping_type' => $shippingType, 'ref_id' => $refId, 'sender_type' => $senderType,
                'sender_name' => $this->in($request, 'sender_name'), 'sender_address' => $this->in($request, 'sender_address'),
                'sender_phone' => $this->in($request, 'sender_phone'), 'sender_charge' => P::s($fees['sender_charge']),
                // PACK: VAT (เดิมไม่ได้ใส่คอลัมน์ discount → ค่าเริ่มต้น 0.00; ส่วนลดอยู่ใน total และ coupon.payment_id)
                'vat' => P::s($vat['vat']), 'vat_before' => P::s($vat['vat_before']), 'shipvat' => P::s($vat['shipvat']),
                'shipvat_before' => P::s($vat['shipvat_before']), 'is_vat' => $isVat, 'v' => P::s($vat['v']), 'shipv' => P::s($vat['shipv']),
            ]);
            self::attachTrackings($items, $id, $uid);
            if ($coupon !== null) {
                // เดิม UPDATE coupon SET payment_id (ไม่ตั้ง mod_date/mod_by)
                DB::table('coupon')->where('id', $coupon->id)->update(['payment_id' => (string) $id]);
            }

            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => (string) $uid, 'amount' => '-'.P::s($total), 'detail' => 'ชำระเงิน '.P::prefix('payment').$id,
                'type' => 3, 'prefix' => (string) Lookup::getPrefixID('payment'), 'prefix_ref_id' => (string) $id,
            ]);

            return response(Alerts::success('window.location.href = "'.Legacy::href('export').'";', Lang::get('success_process_no').P::prefix('payment').$id));
        });
    }

    /** GET /payment/{id} — _payment/form.php */
    public function form(Request $request): View|Response
    {
        $id = (int) $request->route('id');
        $data = P::str(DB::table('bill_payment')->where('id', $id)->where('user', Legacy::userId())->where('is_delete', 0)->first());
        if ($data === null) {
            return response()->view('legacy.no-permission', ['module' => 'payment']);
        }

        return view('payment.customer.form', self::detailData($data, $id, false));
    }

    /** POST /_payment/save_delete — ลูกค้ายกเลิกบิล + คืนเงิน */
    public function saveDelete(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($uid, $id) {
            $data = DB::table('bill_payment')->where('id', $id)->where('user', $uid)->lockForUpdate()->first();
            // เดิม: status != -1 (รวมสถานะ 0 ที่ยังไม่ตัดเงิน และ 2/3 ที่ส่งของแล้ว) และบิลของคนอื่นก็ได้ (ได้แถว balance 0 บาท)
            // ที่นี่: เฉพาะบิลของตัวเองที่สถานะ 1 (= ปุ่มยกเลิกในหน้าเดิม)
            if ($data === null || (string) $data->status !== '1') {
                return;
            }
            // SR: calPoint($id, '-') ก่อนยกเลิก (เดิมหักแต้มทุกครั้งที่ยกเลิก แม้บิลสถานะ 1 ยังไม่เคยได้แต้ม — คงไว้ ดูรายงาน)
            PointCalc::calPoint($data->id, '-', 'ยกเลิกรายการชำระเงินค่าขนส่งสินค้า');
            DB::table('bill_payment')->where('id', $data->id)->where('user', $uid)
                ->update(['status' => '-1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
            DB::table('tracking')->where('payment_id', $data->id)->where('user', $uid)->update(['payment_id' => '0']);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => (string) $uid, 'amount' => (string) $data->total, 'detail' => 'คืนเงิน '.P::prefix('payment').$data->id,
                'type' => 3, 'prefix' => (string) Lookup::getPrefixID('payment'), 'prefix_ref_id' => (string) $data->id,
            ]);
        });

        // id จาก POST ฝังใน JS ที่หน้าเว็บ eval() — เดิมไม่ escape
        return response(Alerts::success('window.location.href = "'.Legacy::href('payment').'";', Lang::get('success_process_no').P::prefix('payment').Alerts::js(e($id))));
    }

    /** POST /_payment/save — (ไฟล์เดิมคัดลอกมาจาก _refill/save.php: สร้างรายการเติมเงิน ตรวจสิทธิ์ refill) */
    public function save(Request $request): Response
    {
        if ($this->in($request, 'id') !== '') {
            return response('');
        }
        // เหมือน _refill/save: ยอดต้องเป็นตัวเลขบวก (เดิมรับติดลบ/0/ไม่ใช่ตัวเลข) — เจ้าของสั่งแก้
        if (! Money::validRefillAmount($this->in($request, 'amount'))) {
            return response(Money::refuse('กรุณาระบุจำนวนเงินให้ถูกต้อง (ต้องมากกว่า 0)'));
        }
        $uid = Legacy::userId();
        $exp = explode('|', $this->in($request, 'date'));
        $date = Format::getdatesubmit($exp[0]).' '.($exp[1] ?? '');
        $file = $this->in($request, 'upload_file');
        $id = DB::table('refill')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
            'status' => '1', 'user' => (string) $uid, 'amount' => Format::numformat($this->in($request, 'amount')),
            'date' => $date, 'attachment' => $file, 'remark' => $this->in($request, 'remark'), 'approved_remark' => '',
        ]);

        return response(TempUpload::jsIfExists($file, '                ')
            .Alerts::success('window.location.href = "'.Legacy::href('refill').'";', Lang::get('success_process_no').P::prefix('refill').$id));
    }

    /** GET|POST /_payment/view_example — ตัวอย่างใบปะหน้า (iframe) */
    public function viewExample(Request $request): Response
    {
        if (! perm('payment')) {
            return response('ERROR : access denied');
        }
        $g = fn (string $k) => (string) LegacyInput::query($request, $k);
        $ref = $this->ownRandom($g('ref_id'));
        $ids = DB::table('bill_payment_detail_temp')->where('random', $ref)->orderBy('id')->pluck('shipping_id');
        $items = $ids->isEmpty() ? collect() : P::strAll(DB::table('tracking')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->whereIn('tracking.id', $ids->all())->where('tracking.user', Legacy::userId())
            ->orderBy('tracking.id')
            ->get(['tracking.*', 'lot.name as lot_name', 'lot.type as lot_type', 'product_type.name as type_name']));
        $shipping = DB::table('shipping_type')->where('id', $g('ship_type'))->value('name');

        return response(view('payment.customer.view_example', [
            'g' => $g,
            'senderType' => $g('sender_type'),
            'shipName' => $shipping,
            'items' => $items,
            'code' => (string) (Legacy::user()->code ?? ''),
            'prefix' => P::prefix('payment'),
        ])->render());
    }

    // ---------------------------------------------------------------------------------------------

    /** tracking + lot + product_type + user (ฐานของ load_create) */
    public static function trackingQuery(): Builder
    {
        return DB::table('tracking')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id');
    }

    /** เงื่อนไขค้นหาของ load_create (ลูกค้า/แอดมินเหมือนกัน — PACK ไม่มีช่องรหัสย่อยแบบ SR) */
    public static function applySearch(Builder $q, ?string $searchLot, string $track, string $quote, string $admitThai): void
    {
        if (Php74::neq($searchLot, '-1')) {
            $q->where('tracking.lot_id', (string) $searchLot);
        }
        // เดิม: track → " AND ( tracking LIKE .. " + (quote ว่าง ? " ) " : " OR ");  quote → (track ว่าง ? " AND (" : "") + " quote LIKE .. )"
        $lt = '%'.addcslashes($track, '%_\\').'%';
        $lq = '%'.addcslashes($quote, '%_\\').'%';
        if ($track !== '' && $quote !== '') {
            $q->where(fn ($w) => $w->where('tracking.tracking', 'like', $lt)->orWhere('tracking.quote', 'like', $lq));
        } elseif ($track !== '') {
            $q->where('tracking.tracking', 'like', $lt);
        } elseif ($quote !== '') {
            $q->where('tracking.quote', 'like', $lq);
        }
        if (Php74::eq($admitThai, '1')) {
            $q->where('tracking.date_thai', '!=', '0000-00-00 00:00:00');
        }
        $q->where('tracking.payment_id', '0')->where('tracking.tracking', '!=', '')
            ->where('tracking.lot_id', '!=', 0)->where('tracking.no', '!=', 0);
    }

    /** tracking ที่ผูกกับ random ของ bill_payment_detail_temp (เฉพาะของผู้ใช้นี้, ยังไม่ผูกบิล) */
    private static function tempTrackings(string $random): Builder
    {
        return DB::table('tracking')
            ->leftJoin('bill_payment_detail_temp', 'tracking.id', '=', 'bill_payment_detail_temp.shipping_id')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->where('bill_payment_detail_temp.random', $random)
            ->where('tracking.payment_id', '0')
            ->where('tracking.user', Legacy::userId())
            ->orderBy('tracking.lot_id')->orderBy('tracking.no');
    }

    /** random ของ temp ที่ผู้ใช้อื่นสร้าง → '' (เดิมใครรู้ random ก็ดู/จ่ายรายการของคนอื่นได้) */
    private function ownRandom(string $random): string
    {
        if ($random === '') {
            return '';
        }
        $foreign = DB::table('bill_payment_detail_temp')->where('random', $random)->where('create_by', '!=', Legacy::userId())->exists();

        return $foreign ? "\0none" : $random;
    }

    /**
     * PACK: คูปองส่วนลดที่ยังไม่ถูกใช้ตามชื่อ — เดิม SELECT * FROM coupon WHERE name = '<code>' AND payment_id = '0' AND is_delete = '0'
     * (แถวแรก; collation ไม่สนตัวพิมพ์/ช่องว่างท้ายแบบเดิม) — $lock = อ่านแบบล็อกแถว (ตอนผูกคูปองกับบิล)
     */
    public static function coupon(string $code, bool $lock = false): ?object
    {
        if ($code === '') {
            return null;
        }
        $q = DB::table('coupon')->where('name', $code)->where('payment_id', '0')->where('is_delete', '0')->orderBy('id');
        if ($lock) {
            $q->lockForUpdate();
        }

        return $q->first();
    }

    /** ราคาส่วนลดของคูปอง (string แบบ mysqli) หรือ null ถ้าไม่มีคูปอง */
    public static function couponPrice(?object $coupon): ?string
    {
        return $coupon === null ? null : (string) $coupon->price;
    }

    /** PACK: คูปองที่ผูกกับบิลนี้ (หน้า payment/{id}, admin/payment/{id}) — เดิม SELECT * FROM coupon WHERE payment_id = id AND is_delete = '0' */
    public static function billCoupon(int|string $paymentId): ?object
    {
        return P::str(DB::table('coupon')->where('payment_id', (string) $paymentId)->where('is_delete', '0')->orderBy('id')->first());
    }

    /**
     * ผูก tracking กับบิล + คัดลอกลง bill_payment_detail (เดิม INSERT ... SELECT * FROM tracking:
     * คอลัมน์ id ของ bill_payment_detail = tracking.id, tracking_id = AUTO_INCREMENT (PK) — ระบุคอลัมน์ฝั่ง SELECT ให้ชัดแทน *)
     *
     * @param  iterable<object>  $items
     */
    public static function attachTrackings(iterable $items, int|string $paymentId, ?int $by): void
    {
        // PACK: คอลัมน์ของ tracking = คอลัมน์ของ bill_payment_detail (ไม่มี tracking_id) ทุกตัว — เดิม INSERT (รายการคอลัมน์) SELECT * FROM tracking
        $cols = '`create_date`, `create_by`, `mod_date`, `mod_by`, `is_delete`, `id`, `no`, `tracking`, `order`, `price_kg`, `price_cubic`, `price_shipping`, `price_amount`, `rate_kg`, `rate_cubic`, `product_type_id`, `lot_id`, `user`, `weight`, `width`, `height`, `long`, `amount`, `ship`, `payment_id`, `is_craft`, `is_qc`, `quote`, `price_qc`, `price_repack`, `price_express`, `price_else`, `price_craft`, `date_warehouse`, `date_outofchina`, `date_thai`, `date_finish`, `remark`, `remark_shop`, `header`';
        foreach ($items as $item) {
            DB::table('tracking')->where('header', $item->id)->update(['payment_id' => (string) $paymentId, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $by]);
            DB::table('tracking')->where('id', $item->id)->update(['payment_id' => (string) $paymentId, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $by]);
            DB::statement('INSERT INTO `bill_payment_detail` ('.$cols.') SELECT '.$cols.' FROM `tracking` WHERE id = ?', [$item->id]);
        }
    }

    /** ข้อมูลหน้า detail ของบิล (ลูกค้า _payment/form.php / แอดมิน _admin/_payment/form.php) */
    public static function detailData(object $data, int $id, bool $admin): array
    {
        $items = P::strAll(DB::table('bill_payment_detail')
            ->leftJoin('lot', 'lot.id', '=', 'bill_payment_detail.lot_id')
            ->leftJoin('product_type', 'bill_payment_detail.product_type_id', '=', 'product_type.id')
            ->where('bill_payment_detail.payment_id', $id)
            // เดิมไม่มี ORDER BY → MySQL อ่านผ่าน index payment_id = เรียงตาม PK (tracking_id = ลำดับที่ใส่ลงบิล)
            // ไม่ใช่ id (= tracking.id): บน bench 9,414 บิลเรียงต่างกัน (ExportBill::details ใช้ tracking_id อยู่แล้ว)
            ->orderBy('bill_payment_detail.tracking_id')
            ->get(['bill_payment_detail.*', 'lot.name as lot_name', 'lot.type as lot_type', 'product_type.name as type_name']))
            ->pipe(fn ($c) => P::withHeaderCount($c));
        // PACK: แอดมินที่ไม่มี admin_viewprice_on_payment เห็นเฉพาะแถว balance ของ export (ไม่เห็นยอดชำระค่าขนส่ง)
        $payPrefix = ! $admin || perm('admin_viewprice_on_payment');
        $balance = P::strAll(DB::table('balance')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->where(fn ($w) => $payPrefix
                ? $w->where('balance.prefix', (string) Lookup::getPrefixID('payment'))->orWhere('balance.prefix', (string) Lookup::getPrefixID('export'))
                : $w->where('balance.prefix', (string) Lookup::getPrefixID('export')))
            ->where('balance.prefix_ref_id', (string) $id)->where('balance.is_delete', 0)
            ->orderBy('balance.id')
            ->get(['balance.*', ($admin ? 'prefix.link_admin' : 'prefix.link').' as link']));

        return [
            'id' => $id,
            'data' => $data,
            'shipName' => DB::table('shipping_type')->where('id', $data->shipping_type)->value('name'),
            'items' => $items,
            'top' => PaymentTotals::all($items),
            'orderIds' => P::orderIds(P::idsOf($items)),
            'children' => P::children($items->filter(fn ($i) => Php74::neq($i->header_count, 0))->pluck('id')),
            'orderPrefix' => P::prefix('order'),
            'prefix' => P::prefix('payment'),
            'balance' => $balance,
            'coupon' => self::billCoupon($data->id),
        ];
    }

    /**
     * แถว shipping_type จาก PDO มี int จริง — เดิม mysqli คืน string ทุกค่า → ทำให้ JSON เท่าเดิม
     * (ต่างจากเดิม: mysqli_fetch_array เดิมมี key ตัวเลขซ้ำทุกคอลัมน์ด้วย "0":..,"1":.. — JS ใช้แค่ชื่อคอลัมน์ จึงไม่ใส่)
     */
    public static function jsonValues(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_int($v)) {
                $data[$k] = (string) $v;
            }
        }

        return $data;
    }
}
