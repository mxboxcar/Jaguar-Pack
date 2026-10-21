<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Domain\Payment\PaymentLookups as P;
use App\Domain\Payment\PaymentTotals;
use App\Domain\Payment\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\Concerns\ReadsLegacyInput;
use App\Http\Controllers\Payment\PaymentController;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use App\Support\Legacy\Pricing;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แอดมินสร้างบิลค่าขนส่งให้ลูกค้า: _admin/_payment/{create,create_form,load_*,save_create,save_submit_payment}.php เดิม
 */
class AdminPaymentCreateController extends Controller
{
    use ReadsLegacyInput;

    private const PRICE_KEYS = ['price_kg', 'price_cubic', 'price_amount', 'price_qc', 'price_express', 'price_else', 'price_repack', 'price_craft'];

    private const NEGATIVE_PRICE = 'ราคาไม่ถูกต้อง (ห้ามติดลบ) กรุณาตรวจสอบราคาอีกครั้ง';

    /** มีค่าราคาที่ติดลบ (อ่านแบบตัวเลขของ PHP 7.4 หลังตัด , — ค่าว่าง/ไม่ใช่ตัวเลข = 0 แบบเดิม) */
    private static function hasNegative(array $values): bool
    {
        foreach ($values as $v) {
            if (Php74::num(str_replace(',', '', (string) ($v ?? ''))) < 0) {
                return true;
            }
        }

        return false;
    }

    /** GET /admin/payment/create[/{user_id}] — _admin/_payment/create.php */
    public function create(Request $request): View
    {
        $userId = (string) ($request->route('user_id') ?? '');
        $code = '-';
        if ($userId !== '') {
            $code = (string) DB::table('user')->where('id', $userId)->where('is_delete', '0')->value('code');
        }

        return view('payment.admin.create', [
            'userId' => $userId,
            'code' => $code,
            // เดิม getCurrentCash($user_id): $user_id มาจาก extract($_GET) ใน system_config.php = ยอดเงินของลูกค้าใน URL
            // (ไม่มี user_id = ว่าง → ยอดของผู้ที่ล็อกอิน)
            'cash' => Lookup::getCurrentCash($userId),
        ]);
    }

    /** POST /_admin/_payment/load_autocomplete_user — {"code": [...]} */
    public function loadAutocompleteUser(Request $request): Response
    {
        $codes = DB::table('user')->where('code', 'like', '%'.addcslashes($this->in($request, 'query'), '%_\\').'%')
            ->where('is_delete', 0)->orderBy('id')->pluck('code')->map(fn ($v) => $v === null ? null : (string) $v)->all();

        return response(json_encode(['code' => $codes === [] ? null : $codes]));
    }

    /** POST /_admin/_payment/load_user_by_id — id ของรหัสลูกค้า (ไม่เจอ = ว่าง) */
    public function loadUserById(Request $request): Response
    {
        return response((string) DB::table('user')->where('code', $this->in($request, 'code'))->where('is_delete', 0)->orderBy('id')->value('id'));
    }

    /** POST /_admin/_payment/load_select_lot */
    public function loadSelectLot(Request $request): Response
    {
        if (! perm('admin_payment_create')) {
            return response(Alerts::permission('admin_payment_createpayment'));
        }
        $rows = DB::table('tracking')->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->where('tracking.is_delete', 0)->where('tracking.user', $this->in($request, 'user_id'))
            ->where('tracking.payment_id', '0')->where('tracking.tracking', '!=', '')
            ->where('tracking.lot_id', '!=', 0)->where('tracking.no', '!=', 0)->where('tracking.header', 0)
            ->groupBy('tracking.lot_id')->orderBy('tracking.lot_id')
            ->get([DB::raw('MIN(lot.id) as id'), DB::raw('MIN(lot.name) as lot_name')]);
        $html = '<select id="search_lot" onchange="loaddata();" style="width:100px">';
        $html .= '<option value="-1">'.Lang::get('all').'</option>';
        foreach ($rows as $r) {
            $html .= '<option value="'.e((string) $r->id).'">'.e((string) $r->lot_name).'</option>';
        }
        $html .= '</select>';

        return response($html);
    }

    /** POST /_admin/_payment/load_create — รายการพัสดุของลูกค้า (PACK = SR: ไม่มีตารางเรท, ไม่มีรหัสย่อยใต้รหัสลูกค้า) */
    public function loadCreate(Request $request): Response
    {
        $userId = $this->in($request, 'user_id');
        $q = PaymentController::trackingQuery()->where('tracking.is_delete', 0)->where('tracking.payment_id', 0)->where('tracking.user', $userId);
        PaymentController::applySearch($q, $this->has($request, 'search_lot') ? $this->in($request, 'search_lot') : null,
            $this->in($request, 'track'), $this->in($request, 'quote'), $this->in($request, 'admit_thai'));
        $items = P::withHeaderCount(P::strAll($q->orderBy('tracking.lot_id')->orderBy('tracking.no')
            ->get(['tracking.*', 'user.code', 'product_type.name as type_name', 'lot.name as lot_name', 'lot.type as lot_type'])));

        return response(view('payment.admin.load_create', [
            'items' => $items,
            'sum' => PaymentTotals::selectable($items),
            'orderIds' => P::orderIds(P::idsOf($items)),
            'children' => P::children($items->filter(fn ($i) => Php74::neq($i->header_count, 0))->pluck('id')),
            'orderPrefix' => P::prefix('order'),
        ])->render());
    }

    /** POST /_admin/_payment/save_create — เก็บ tracking + ราคาที่แก้ลง bill_payment_detail_temp_admin */
    public function saveCreate(Request $request): Response
    {
        $raw = $request->request->all()['tracking_id'] ?? null;
        if ($raw === null || $raw === '') {
            return response(Alerts::error('CloseLoading()', Lang::get('no_select_tracking')));
        }
        $ids = $this->arr($request, 'tracking_id');
        $userId = $this->in($request, 'user_id');
        $out = '';
        if ($userId === '') {
            $out .= "ErrorBox('".Lang::get('no_user')."', 'CloseLoading()'); ";
        }
        $prices = substr($this->in($request, 'prices'), 1);
        $spt = explode('|', $prices);
        // เดิมรับราคาติดลบได้ (บิลยอดติดลบ + pay_now = เพิ่มเงินให้ลูกค้า) — ใหม่ (เจ้าของสั่งแก้): บล็อกการบันทึก + แจ้งเตือน
        foreach ($ids as $i => $id) {
            if (self::hasNegative(explode('*', (string) ($spt[$i] ?? '')))) {
                return response(Alerts::error('CloseLoading()', self::NEGATIVE_PRICE));
            }
        }
        $ran = Format::randd(15);
        $uid = Legacy::userId();
        foreach ($ids as $i => $id) {
            $in = explode('*', (string) ($spt[$i] ?? ''));
            DB::table('bill_payment_detail_temp_admin')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'shipping_id' => (string) $id, 'random' => $ran,
                'kg' => $in[0] ?? '', 'cubic' => $in[1] ?? '', 'qc' => $in[2] ?? '', 'repack' => $in[3] ?? '',
                'express' => $in[4] ?? '', 'else' => $in[5] ?? '', 'craft' => $in[6] ?? '',
            ]);
        }

        return response($out."window.location.href = '".Legacy::href('admin/payment/create/'.$userId.'/'.$ran)."'");
    }

    /** GET /admin/payment/create/{user_id}/{random} — _admin/_payment/create_form.php */
    public function createForm(Request $request): View
    {
        $userId = (string) $request->route('user_id');
        $random = strip_tags(trim((string) $request->route('random')));
        $user = P::str(DB::table('user')->where('id', $userId)->first());
        $items = P::strAll(self::tempItems($random, $userId)->get(self::tempColumns()));

        return view('payment.admin.create_form', [
            'userId' => $userId,
            'random' => $random,
            'user' => $user,
            'items' => $items,
            'sum' => PaymentTotals::adminCreate($items),
            'hideOwnSender' => $items->contains(fn ($i) => Php74::eq($i->product_type_id, 9) || Php74::eq($i->product_type_id, 10)),
            'shippingTypes' => P::shippingTypes(),
            'cash' => Lookup::getCurrentCash($userId),
            'changeSender' => Lookup::getSetting('change_sender'),
            'orderIds' => P::orderIds(P::idsOf($items)),
            'orderPrefix' => P::prefix('order'),
        ]);
    }

    /** POST /_admin/_payment/save_submit_payment — สร้างบิลให้ลูกค้า (pay_now = ตัดเงินทันที) */
    public function saveSubmitPayment(Request $request): Response
    {
        $userId = $this->in($request, 'user_id');
        $code = $this->in($request, 'ref_id');
        $shippingType = $this->in($request, 'shipping_type');
        $senderType = $this->in($request, 'sender_type');
        $payNow = $this->in($request, 'pay_now');
        // hack yd (9 บริษัทจัดส่ง, 10 รับเอง) เปิดใช้ในฝั่งแอดมินด้วย (PACK = SR; jl comment ไว้)
        $radio = $this->in($request, 'shipping_type_radio');
        if (Php74::eq($radio, 9) || Php74::eq($radio, 10)) {
            $shippingType = $radio;
        }

        $st = P::str(DB::table('shipping_type')->where('id', $shippingType)->first());
        if ($st === null) {
            return response(Alerts::error('CloseLoading()', Lang::get('no_shipping_type')));
        }

        return DB::transaction(function () use ($request, $userId, $code, $shippingType, $senderType, $payNow, $st) {
            DB::table('user')->where('id', $userId)->lockForUpdate()->value('id');
            $items = P::strAll(self::tempItems($code, $userId)->where('tracking.is_delete', 0)->get(array_merge(self::tempColumns(), [
                'tracking.order', 'tracking.rate_kg', 'tracking.rate_cubic', 'tracking.ship', 'tracking.payment_id', 'tracking.is_craft',
                'tracking.is_qc', 'tracking.date_warehouse', 'tracking.date_outofchina', 'tracking.date_thai', 'tracking.date_finish',
                'tracking.remark', 'tracking.remark_shop', 'tracking.header', 'tracking.price_shipping', 'tracking.price_amount',
                'tracking.lot_id', 'tracking.user', 'tracking.no',
            ])));

            $fees = PaymentTotals::fees($st, $senderType, Pricing::getShippingTypePrice($shippingType, $code, $senderType));
            $priceShipping = 0;
            foreach ($items as $item) {
                $priceShipping = $priceShipping + P::add($item, self::PRICE_KEYS);
            }
            $total = PaymentTotals::plain($fees['storage'], $priceShipping, $fees['extened'], $fees['shipping']);
            // กันซ้ำฝั่งสร้างบิล (temp ที่บันทึกไว้ก่อนแก้ / ยิงตรง): ราคารายการหรือยอดรวมติดลบ = ไม่สร้างบิล
            foreach ($items as $item) {
                if (self::hasNegative(array_map(fn ($k) => $item->{$k} ?? null, self::PRICE_KEYS))) {
                    return response(Alerts::error('CloseLoading()', self::NEGATIVE_PRICE));
                }
            }
            if (Php74::num($total) < 0) {
                return response(Alerts::error('CloseLoading()', self::NEGATIVE_PRICE));
            }
            $status = 0;
            if (Php74::eq($payNow, 1)) {
                $status = 1;
                if (Php74::gt(Format::numberformatforsubmit($total), Format::numberformatforsubmit(Lookup::getCurrentCash($userId)))) {
                    return response(Alerts::error('CloseLoading()', Lang::get('error_usingcash')));
                }
            }
            if ($items->isEmpty()) {
                // กันกดซ้ำ: tracking ถูกผูกบิลไปแล้ว (เดิมสร้างบิลเปล่า + ตัดค่าส่งซ้ำ)
                return response(Alerts::success('window.location.href = "'.Legacy::href('admin/payment').'";', 'รายการนี้ถูกดำเนินการไปแล้ว'));
            }
            $uid = Legacy::userId();
            $id = DB::table('bill_payment')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'total' => P::s($total), 'submit_date' => DB::raw('NOW()'), 'submit_remark' => $this->in($request, 'submit_remark'),
                'status' => (string) $status, 'price_shipping' => P::s($priceShipping), 'price_thai_storage' => P::s($fees['storage']),
                'price_thai_shipping' => P::s($fees['shipping']), 'user' => $userId,
                'send_name' => $this->in($request, 'name'), 'send_address' => $this->in($request, 'address'), 'send_phone' => $this->in($request, 'phone'),
                'shipping_type' => $shippingType, 'ref_id' => $code, 'sender_type' => $senderType,
                'sender_name' => $this->in($request, 'sender_name'), 'sender_address' => $this->in($request, 'sender_address'),
                'sender_phone' => $this->in($request, 'sender_phone'), 'sender_charge' => P::s($fees['sender_charge']),
                // PACK: INSERT `discount` = '$discount' ที่ไม่ได้กำหนด (บรรทัดคำนวณถูก comment) → '' = 0.00
                'discount' => '0',
            ]);
            foreach ($items as $item) {
                DB::table('tracking')->where('header', $item->id)->update(['payment_id' => (string) $id, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
                DB::table('tracking')->where('id', $item->id)->update(['payment_id' => (string) $id, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
                $row = ['create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'id' => $item->id, 'tracking_id' => null, 'payment_id' => (string) $id];
                foreach (['no', 'tracking', 'order', 'price_kg', 'price_cubic', 'price_shipping', 'price_amount', 'rate_kg', 'rate_cubic',
                    'product_type_id', 'lot_id', 'user', 'weight', 'width', 'height', 'long', 'amount', 'ship', 'is_craft', 'is_qc', 'quote',
                    'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft', 'date_warehouse', 'date_outofchina',
                    'date_thai', 'date_finish', 'remark', 'remark_shop', 'header'] as $k) {
                    $row[$k] = P::s($item->{$k} ?? null);
                }
                // tracking_id = AUTO_INCREMENT (PK), id = tracking.id (ซ้ำได้ถ้า tracking เคยอยู่ในบิลที่ยกเลิก)
                DB::table('bill_payment_detail')->insert($row);
            }
            if (Php74::eq($payNow, 1)) {
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => $userId, 'amount' => '-'.P::s($total), 'detail' => 'ชำระเงิน '.P::prefix('payment').$id,
                    'type' => 3, 'prefix' => (string) Lookup::getPrefixID('payment'), 'prefix_ref_id' => (string) $id,
                ]);
            }

            return response(Alerts::success('window.location.href = "'.Legacy::href('admin/payment').'";', Lang::get('success_process_no').P::prefix('payment').$id));
        });
    }

    /** tracking + ราคาที่แอดมินแก้ (bill_payment_detail_temp_admin) ของ random — เฉพาะลูกค้าคนนี้ */
    private static function tempItems(string $random, string $userId): Builder
    {
        return DB::table('tracking')
            ->leftJoin('bill_payment_detail_temp_admin as t1', 'tracking.id', '=', 't1.shipping_id')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->where('t1.random', $random)->where('tracking.payment_id', '0')
            ->where('tracking.user', $userId)
            ->orderBy('tracking.lot_id')->orderBy('tracking.no');
    }

    /** @return list<string> */
    private static function tempColumns(): array
    {
        return ['tracking.id', 'tracking.tracking', 'tracking.quote', 'tracking.amount', 'tracking.weight', 'tracking.width', 'tracking.height',
            'tracking.long', 'tracking.product_type_id', 't1.kg as price_kg', 't1.cubic as price_cubic', 't1.qc as price_qc',
            't1.repack as price_repack', 't1.express as price_express', 't1.else as price_else', 't1.craft as price_craft',
            'lot.name as lot_name', 'lot.type as lot_type', 'product_type.name as type_name'];
    }
}
