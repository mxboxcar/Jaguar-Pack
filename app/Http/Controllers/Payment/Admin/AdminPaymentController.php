<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Domain\Money\Money;
use App\Domain\Payment\PaymentLookups as P;
use App\Domain\Payment\PaymentMailer;
use App\Domain\Payment\PaymentTotals;
use App\Domain\Payment\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\Concerns\ReadsLegacyInput;
use App\Http\Controllers\Payment\PaymentController;
use App\Support\GridQuery;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use App\Support\Legacy\PointCalc;
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ชำระเงินค่าขนส่ง (แอดมิน): _admin/_payment/{index,grid,form,save_*,load_shipping_price}.php เดิม
 * งานที่ย้ายเงิน/สถานะ ห่อ transaction + lock แถวบิล (เดิมไม่มี: กดซ้ำพร้อมกัน = คืนเงินซ้ำ)
 */
class AdminPaymentController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_payment/grid';

    public const SENDER_CANCELLED = 'บิลนี้ถูกยกเลิกแล้ว ไม่สามารถแก้ไขผู้ส่ง/ประเภทการส่งได้';

    /** GET /admin/payment, /admin/payment/status/{status} */
    public function index(Request $request): View
    {
        return view('payment.admin.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** POST /_admin/_payment/grid (ไม่มีสิทธิ์ = ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_payment')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;
        $idTerm = (string) preg_replace('/^[A-Za-z]+-/', '', $parts[0]);
        // เดิม LEFT JOIN user แล้ว OR user.code LIKE → join ทุกแถวของตาราง 2 รอบ (นับ + หน้า)
        // ที่นี่ user.code กรองด้วย subquery (ผลเท่ากัน: join 1:1 ด้วย PK, ไม่มี user = code NULL ไม่ตรง)
        // และอ่าน bill_payment รอบเดียว (GridQuery::idPage) แล้วโหลดเฉพาะ 10 แถวพร้อม join
        $q = DB::table('bill_payment')
            ->where('bill_payment.is_delete', 0)
            ->where(fn ($w) => $w->where('bill_payment.id', 'like', '%'.addcslashes($idTerm, '%_\\').'%')
                ->orWhereIn('bill_payment.user', DB::table('user')->select('user.id')
                    ->where('user.code', 'like', addcslashes(strtolower($parts[0]), '%_\\'))));
        if (Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('bill_payment.status', $status);
        }
        $page = $this->php($request, 'page');
        [$total, $ids] = GridQuery::idPage($q, 'bill_payment.id', P::offset($page));
        $items = P::strAll(GridQuery::rowsInOrder(DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id'),
            'bill_payment.id', $ids, ['bill_payment.*', 'user.code']));

        return response(view('payment.admin.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $php[0],
            'status' => isset($parts[1]) ? ($php[1] ?? '') : null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => P::prefix('payment'),
            // PACK: คอลัมน์ยอดเงินแสดงเฉพาะผู้มีสิทธิ์ admin_viewprice_on_payment
            'viewPrice' => perm('admin_viewprice_on_payment'),
        ])->render());
    }

    /** GET /admin/payment/{id} — _admin/_payment/form.php */
    public function form(Request $request): View|Response
    {
        $id = (int) $request->route('id');
        $data = P::str(DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.id', $id)->where('bill_payment.is_delete', 0)->first(['bill_payment.*', 'user.code']));
        if ($data === null) {
            return response()->view('legacy.no-permission', ['module' => 'admin_payment']);
        }
        $canChange = perm('admin_change_sender');

        return view('payment.admin.form', PaymentController::detailData($data, $id, true) + [
            // PACK: ราคา kg/คิว + ยอดรวมท้ายตาราง แสดงเฉพาะผู้มีสิทธิ์ admin_viewprice_on_payment
            'viewPrice' => perm('admin_viewprice_on_payment'),
            'canChangeSender' => $canChange,
            'shippingTypes' => $canChange ? P::strAll(DB::table('shipping_type')->where('is_delete', 0)->where('is_quick_show', 0)->orderBy('no')->get()) : collect(),
        ]);
    }

    /** POST /_admin/_payment/save_approved — ยืนยันบิล (สถานะ 1 → 2) + อีเมลแจ้งลูกค้า */
    public function saveApproved(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $approved = DB::transaction(function () use ($id) {
            $data = DB::table('bill_payment')->where('id', $id)->lockForUpdate()->first();
            if ($data === null || ! Php74::eq((string) $data->status, 1)) {
                return null;
            }
            // เดิมไม่ได้ตั้ง mod_date
            DB::table('bill_payment')->where('id', $data->id)->update(['status' => '2', 'approved_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);
            // SR: calPoint() ทำงานจริง (jl ว่าง) — แต้มจากค่าขนส่ง ใน transaction เดียวกับการเปลี่ยนสถานะ (แถวบิลล็อกอยู่)
            PointCalc::calPoint($data->id, '', 'คำนวณจากรายการชำระเงินค่าสินค้า');
            // SR: พัสดุในบิลออกจากชั้นวางในโกดัง (warehouse_section_detail.tracking_id = bill_payment_detail.id = tracking.id)
            $trackingIds = DB::table('bill_payment_detail')->where('payment_id', (string) $data->id)->pluck('id')->all();
            if ($trackingIds !== []) {
                DB::table('warehouse_section_detail')->whereIn('tracking_id', $trackingIds)->update(['is_delete' => 1]);
            }

            return (string) $data->id;
        });
        if ($approved !== null && ! config('legacy.debug_mail')) {
            PaymentMailer::paymentApproved($approved);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/payment').'";', Lang::get('success_process_no').P::prefix('payment').Alerts::js(e($id))));
    }

    /**
     * POST /_admin/_payment/save_delete — ยกเลิก คืนพัสดุ (สถานะ 1: + คืนเงิน + อีเมล)
     * SR: สถานะ 0 (แอดมินสร้างบิลแบบยังไม่จ่าย) ยกเลิก + คืนพัสดุได้ด้วย (ไม่มีเงินคืน ไม่มีอีเมล)
     */
    public function saveDelete(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $done = DB::transaction(function () use ($id, $remark) {
            $data = DB::table('bill_payment')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                // เดิม: ไม่พบบิล → null == 0 → เข้ากิ่งสถานะ 0 (UPDATE ที่ไม่โดนแถวใด) + ข้อความสำเร็จ
                return ['nopay', null];
            }
            $billId = (string) $data->id;
            if (Php74::eq((string) $data->status, 0)) {
                DB::table('tracking')->where('payment_id', $billId)->update(['payment_id' => '0']);
                $this->cancelBill($billId, $remark);

                return ['nopay', $billId];
            }
            if (! Php74::eq((string) $data->status, 1)) {
                return null;
            }
            DB::table('tracking')->where('payment_id', $billId)->update(['payment_id' => '0']);
            $this->cancelBill($billId, $remark);
            $this->refund($billId);

            return ['refund', $billId];
        });
        if ($done === null) {
            return response(Alerts::success('window.location.reload();', 'รายการถูกยืนยันไปแล้ว'));
        }
        [$kind, $done] = $done;
        if ($kind === 'refund' && ! config('legacy.debug_mail')) {
            PaymentMailer::paymentCancelled($done);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/payment').'";', Lang::get('success_process_no').P::prefix('payment').Alerts::js(e($id))));
    }

    /** POST /_admin/_payment/save_deletenopush — ยกเลิกโดยไม่คืนพัสดุ (สถานะ 0: ไม่คืนเงิน, 1/2: คืนเงิน + อีเมล) */
    public function saveDeleteNoPush(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $billId = null;
        $result = DB::transaction(function () use ($id, $remark, &$billId) {
            $data = DB::table('bill_payment')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return 'nocash'; // เดิม: ไม่พบบิล → null == 0 → UPDATE ที่ไม่โดนแถวใด + ข้อความสำเร็จ
            }
            $billId = (string) $data->id;
            $st = (string) $data->status;
            if (Php74::eq($st, 0)) {
                $this->cancelBill((string) $data->id, $remark);

                return 'nocash';
            }
            if (Php74::eq($st, 1) || Php74::eq($st, 2)) {
                $this->cancelBill((string) $data->id, $remark);
                $this->refund((string) $data->id);

                return 'refund';
            }

            return 'none';
        });
        if ($result === 'none') {
            return response(Alerts::success('window.location.reload();', 'รายการถูกยืนยันไปแล้ว'));
        }
        if ($result === 'refund' && ! config('legacy.debug_mail')) {
            PaymentMailer::paymentCancelled($billId);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/payment').'";', Lang::get('success_process_no').P::prefix('payment').Alerts::js(e($id))));
    }

    /** POST /_admin/_payment/load_shipping_price — getShippingTypePrice_EditSender (JSON) */
    public function loadShippingPrice(Request $request): Response
    {
        $data = Pricing::getShippingTypePriceEditSender($this->in($request, 'id'), $this->in($request, 'payment_ref'), $this->in($request, 'sender'));

        return response(json_encode($data === null ? null : PaymentController::jsonValues($data)));
    }

    /** POST /_admin/_payment/save_new_sender — แก้ที่อยู่/ประเภทการส่ง แล้วคำนวณยอดใหม่ (สิทธิ์ admin_change_sender) */
    public function saveNewSender(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $aType = $this->in($request, 'a_type');
        $b = fn (string $k) => $this->in($request, $k) === 'undefined' ? '' : $this->in($request, $k);

        $st = P::str(DB::table('shipping_type')->where('id', $aType)->first());
        if ($st === null) {
            return response(Alerts::error('CloseLoading()', Lang::get('no_shipping_type')));
        }

        $refused = DB::transaction(function () use ($request, $id, $aType, $b, $st) {
            $head = P::str(DB::table('bill_payment')->where('id', $id)->lockForUpdate()->first());
            // เดิมแก้ได้ทุกสถานะ (และไม่มีใครถือสิทธิ์ admin_change_sender จึงไม่เคยถูกใช้): บิลที่ยกเลิกแล้ว (คืนเงินตามยอดเดิมไปแล้ว)
            // ถูกแก้แถวตัดเงินตามยอดใหม่ = ยอดเงินลูกค้าเพิ่ม/ลดโดยไม่มีที่มา (E2E: ยกเลิกคืน 500 แล้วเปลี่ยนเป็น EMS → แถวตัดเงิน -610)
            // → ไม่ทำอะไรกับบิลที่ไม่มีอยู่/ยกเลิกแล้ว (เหมือน save_vat)
            if ($head === null) {
                return Lang::get('no_data');
            }
            if (Php74::eq($head->status, -1)) {
                return self::SENDER_CANCELLED;
            }
            $items = P::strAll(DB::table('bill_payment_detail')->where('payment_id', $id)->where('is_delete', 0)->get());
            // เดิม getShippingTypePrice($shipping_type, $ref_id, ...) ด้วยตัวแปรที่ไม่ได้กำหนด → id/ref ว่าง
            $fees = PaymentTotals::fees($st, $head->sender_type ?? null, Pricing::getShippingTypePrice('', '', $head->sender_type ?? null));
            $priceShipping = 0;
            foreach ($items as $item) {
                $priceShipping = $priceShipping + P::add($item, ['price_kg', 'price_cubic', 'price_amount', 'price_qc', 'price_express', 'price_else', 'price_repack', 'price_craft']);
            }
            $total = PaymentTotals::plain($fees['storage'], $priceShipping, $fees['extened'], $fees['shipping']);
            $uid = Legacy::userId();
            $balanceId = DB::table('balance')->where('type', '3')->where('prefix_ref_id', $id)
                ->where('prefix', (string) Lookup::getPrefixID('payment'))->orderBy('id')->value('id');
            if ($balanceId !== null) {
                // เจ้าของยืนยัน (2026-10-18): เปลี่ยนประเภทการส่งแล้วยอดลูกค้าติดลบได้ (เหมือนเว็บเดิม) — ไม่ตรวจยอดคงเหลือ
                // แต่ยังล็อกแถว user ใน transaction เพื่อไม่ให้ชนกับรายการเงินอื่นที่ทำพร้อมกัน
                $row = DB::table('balance')->where('id', $balanceId)->first(['user']);
                if ($row !== null) {
                    Money::lockUser($row->user);
                }
                DB::table('balance')->where('id', $balanceId)->update(['amount' => '-'.P::s($total), 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
            }
            DB::table('bill_payment')->where('id', $id)->update([
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'send_name' => $this->in($request, 'a_name'), 'send_address' => $this->in($request, 'a_address'),
                'send_phone' => $this->in($request, 'a_phone'), 'shipping_type' => $aType,
                'sender_name' => $b('b_name'), 'sender_address' => $b('b_address'), 'sender_phone' => $b('b_phone'),
                'price_shipping' => P::s($priceShipping), 'price_thai_storage' => P::s($fees['storage']),
                'price_thai_shipping' => P::s($fees['shipping']), 'total' => P::s($total),
            ]);

            return null;
        });
        if ($refused !== null) {
            return response(Alerts::error('CloseLoading()', $refused));
        }

        return response(Alerts::success('window.location.reload();', Lang::get('success_process_no').P::prefix('payment').Alerts::js(e($id))));
    }

    /** email_approved.php / email_cancel.php เปิดตรง — เดิมใช้ $email_id ที่ไม่ได้กำหนด (ไม่ส่งอะไรได้จริง) */
    public function emailDirect(): Response
    {
        return response('');
    }

    private function cancelBill(string $id, string $remark): void
    {
        DB::table('bill_payment')->where('id', $id)->update([
            'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.(Legacy::user()->code ?? ''),
            'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);
    }

    /** คืนเงิน total ของบิลให้เจ้าของบิล (เดิม SELECT user, total หลัง UPDATE) */
    private function refund(string $id): void
    {
        $row = DB::table('bill_payment')->where('id', $id)->first(['user', 'total']);
        $uid = Legacy::userId();
        DB::table('balance')->insert([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
            'user' => (string) ($row->user ?? ''), 'amount' => P::s($row->total ?? null), 'detail' => 'คืนเงิน '.P::prefix('payment').$id,
            'type' => 3, 'prefix' => (string) Lookup::getPrefixID('payment'), 'prefix_ref_id' => $id,
        ]);
    }
}
