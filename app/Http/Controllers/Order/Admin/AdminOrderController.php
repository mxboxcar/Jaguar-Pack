<?php

namespace App\Http\Controllers\Order\Admin;

use App\Domain\Money\Money;
use App\Domain\Order\OrderLookups;
use App\Domain\Order\OrderMailer;
use App\Domain\Order\OrderQueries;
use App\Domain\Order\OrderTotals;
use App\Domain\Order\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Order\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use App\Support\Legacy\Paging;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * รายการสั่งซื้อฝั่งแอดมิน: _admin/_order/{index,form,grid,load_paging,load_total_order,delete,save_*}.php (สิทธิ์ admin_order)
 * ส่วนรายการสินค้า/tracking ในใบ (load_detail และ save_* ของรายการ) อยู่ที่ AdminOrderDetailController
 */
class AdminOrderController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_order/grid';

    /** GET /admin/order, /admin/dashboard, /admin/order/status/{status} — _admin/_order/index.php */
    public function index(Request $request): View
    {
        return view('order.admin.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** GET /admin/order/{id}[/{div}] — _admin/_order/form.php (ไม่พบใบ = หน้าเปล่าแบบเดิม) */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $row = OrderQueries::base()->where('bill_product.id', $id)->where('bill_product.is_delete', 0)
            ->first(['bill_product.*', 'user.code']);
        $data = Php74::row($row);
        $st = $data['status'] ?? null;
        $balances = [];
        if (Php74::num($st) >= 2 || Php74::eq($st, -1)) {
            $balances = DB::table('balance')->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
                ->where('balance.prefix', OrderLookups::prefixId('order'))->where('balance.prefix_ref_id', $id)
                ->where('balance.is_delete', 0)->orderBy('balance.id')
                ->get(['balance.*', 'prefix.link'])->all();
        }
        $editLast = DB::table('user')->where('id', $data['mod_by'] ?? '')->first(['id', 'code']);

        return view('order.admin.form', [
            'id' => $id,
            'div' => strip_tags(trim((string) ($request->route('div') ?? ''))),
            'data' => $data,
            'st' => $st,
            'prefix' => OrderLookups::prefix('order'),
            'balances' => $balances,
            'editLast' => $editLast,
            'vat' => Lookup::getSetting('vat'),
        ]);
    }

    /** POST /_admin/_order/grid — ไม่มีสิทธิ์ = ตอบว่างแบบเดิม */
    public function grid(Request $request): Response
    {
        if (! perm('admin_order')) {
            return response('');
        }
        $p = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $term = $p[0];
        $q = OrderQueries::base()->where(fn ($w) => $w->where('user.code', 'like', OrderQueries::like($term))
            ->orWhere('bill_product.id', 'like', OrderQueries::like(str_replace(OrderLookups::prefix('order'), '', $term))));
        $having = OrderQueries::applyShipStatus($q, $p[1] ?? null, $p[2] ?? null);
        $page = $this->php($request, 'page');
        $offset = OrderLookups::offset($page);
        $items = $offset === null ? [] : OrderQueries::page($q, $having, $offset);

        // เดิม query รหัสผู้ล็อกต่อแถว (status 0 + is_lock 1) → อ่านครั้งเดียว
        $lockIds = [];
        foreach ($items as $it) {
            if (Php74::eq($it->status, 0) && Php74::eq($it->is_lock, 1)) {
                $lockIds[] = $it->mod_by;
            }
        }
        $lockers = $lockIds === [] ? [] : DB::table('user')->whereIn('id', array_unique($lockIds))->pluck('code', 'id')->all();

        return response(view('order.admin.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'lockers' => $lockers,
            'prefix' => OrderLookups::prefix('order'),
            'search' => $php[0],
            'ship' => isset($p[1]) ? $php[1] : null,
            'status' => isset($p[2]) ? $php[2] : null,
            'page' => $page,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** POST /_admin/_order/load_paging — ค้นหาเพิ่ม admin_quote (ต่างจาก grid เหมือนเดิม) */
    public function loadPaging(Request $request): Response
    {
        $p = explode('|', $this->in($request, 'txtsearch'));
        $term = $p[0];
        $like = OrderQueries::like($term);
        $q = OrderQueries::base()->where(fn ($w) => $w->where('user.code', 'like', $like)
            ->orWhere('bill_product.id', 'like', OrderQueries::like(str_replace(OrderLookups::prefix('order'), '', $term)))
            ->orWhere('bill_product.admin_quote', 'like', $like));
        $having = OrderQueries::applyShipStatus($q, $p[1] ?? null, $p[2] ?? null);
        $rows = OrderQueries::cachedCount('a-paging', $q, $having);

        return response(Paging::getpaging($this->php($request, 'page'), $rows, 10, self::AJAX));
    }

    /** POST /_admin/_order/load_total_order — จำนวนใบทั้งระบบตามปุ่มสถานะ */
    public function loadTotalOrder(Request $request): Response
    {
        $q = OrderQueries::base();
        $having = OrderQueries::applyRef($q, $this->has($request, 'ref') ? $this->in($request, 'ref') : null);

        return response((string) OrderQueries::cachedCount('a-total', $q, $having));
    }

    /**
     * POST /_admin/_order/delete — ยกเลิกใบ (+ คืนเงินถ้าจ่ายแล้ว) แล้วแจ้งลูกค้าทางอีเมล
     * เดิมอ่านสถานะแล้วค่อย UPDATE → กดซ้ำพร้อมกันคืนเงินซ้ำได้ → ทำใน transaction + ล็อกแถว
     */
    public function delete(Request $request): Response
    {
        $idRaw = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $uid = Legacy::userId();
        $code = (string) (Legacy::user()->code ?? '');
        $cancelled = DB::transaction(function () use ($idRaw, $remark, $uid, $code) {
            $data = DB::table('bill_product')->where('id', $idRaw)->lockForUpdate()->first();
            if ($data !== null && Php74::eq($data->status, -1)) {
                return null;
            }
            DB::table('bill_product')->where('id', $idRaw)->update([
                'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.$code, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            if ($data !== null && Php74::num($data->status) >= 2) {
                // เดิม: คืนเท่ากับ balance แถวแรกของใบ (แถวชำระเงิน) โดยไม่หักรายการที่คืนไปแล้ว (save_cashback)
                //       = ลูกค้าได้คืนเกินที่จ่าย — ใหม่ (เจ้าของสั่งแก้): คืนเท่ายอดสุทธิที่ใบนี้ยังถูกตัดอยู่ (ล็อกแถว user)
                Money::lockUser($data->user);
                $refund = Money::refundable(PHP_FLOAT_MAX, Money::net('balance', OrderLookups::prefixId('order'), $data->id));
                if ($refund > 0) {
                    DB::table('balance')->insert([
                        'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                        'is_delete' => 0, 'user' => $data->user, 'amount' => Money::fmt($refund),
                        'detail' => 'คืนเงิน '.OrderLookups::prefix('order').$data->id, 'type' => 2,
                        'prefix' => OrderLookups::prefixId('order'), 'prefix_ref_id' => $data->id,
                    ]);
                }
            }

            return $data;
        });
        if ($cancelled !== null && ! config('legacy.debug_mail')) {
            OrderMailer::orderCancelled((int) $cancelled->id);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/order').'";',
            Lang::get('success_process_no').OrderLookups::prefix('order').Alerts::js(e($idRaw))));
    }

    /**
     * POST /_admin/_order/save_approved — ยืนยันใบ (status 0 → 1) แล้วแจ้งลูกค้า
     * เดิมไม่ตรวจสถานะ: ใบที่จ่ายแล้ว (2/3) ถูกพลิกกลับเป็น 1 = ลูกค้าจ่ายซ้ำได้ ใบที่ยกเลิกแล้วกลับมาเปิดได้
     * ใหม่ (เจ้าของสั่งแก้): เฉพาะ status 0 (= ปุ่มอนุมัติในหน้า) ล็อกแถวใน transaction
     */
    public function saveApproved(Request $request): Response
    {
        $idRaw = $this->in($request, 'id');
        $uid = Legacy::userId();
        $id = DB::transaction(function () use ($idRaw, $uid) {
            $data = DB::table('bill_product')->where('id', $idRaw)->lockForUpdate()->first(['id', 'status']);
            if ($data === null || ! Money::statusIn($data->status, [0])) {
                return null;
            }
            DB::table('bill_product')->where('id', $data->id)->update([
                'status' => '1', 'who_did_this_1' => $uid, 'who_did_this_1_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);

            return (int) $data->id;
        });
        if ($id === null) {
            return response(Money::refuse('ใบสั่งซื้อนี้ไม่อยู่ในสถานะรอการตรวจสอบ (อาจถูกยืนยัน/ชำระ/ยกเลิกไปแล้ว)'));
        }
        if (! config('legacy.debug_mail')) {
            OrderMailer::orderApproved($id);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/order').'";',
            Lang::get('success_process_no').OrderLookups::prefix('order').Alerts::js(e($idRaw))));
    }

    /** POST /_admin/_order/save_finish — ปิดใบ (2 → 3) เมื่อมีรายการชำระเงินแล้ว */
    public function saveFinish(Request $request): Response
    {
        $idRaw = $this->in($request, 'id');
        $uid = Legacy::userId();
        $data = DB::table('bill_product')->where('id', $idRaw)->first();
        if ($data !== null && Php74::eq($data->status, 2)) {
            // เดิมเขียน prefix = 11 ตายตัว — เจตนา = id ของ prefix "order" (แถวชำระเงินของ _order/save_payment ใช้ getPrefixID('order'))
            // ใหม่: อ่านจากตาราง prefix (ค่าเท่ากันใน DB ตอนนี้ แต่ไม่พังถ้า id เปลี่ยน)
            $c = DB::table('balance')->where('type', 2)->where('prefix', OrderLookups::prefixId('order'))->where('prefix_ref_id', $data->id)
                ->whereNull('ref_id_2')->where('is_delete', 0)->count();
            if ($c === 0) {
                return response(Alerts::error('CloseLoading()', 'ยังไม่มีการชำระเงิน กรุณาแจ้งผู้ดูแล'));
            }
            DB::table('bill_product')->where('id', $idRaw)->update([
                'status' => '3', 'who_did_this_3' => $uid, 'who_did_this_3_date' => DB::raw('NOW()'),
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/order').'";',
            Lang::get('success_process_no').OrderLookups::prefix('order').Alerts::js(e($idRaw))));
    }

    /** POST /_admin/_order/save_lock */
    public function saveLock(Request $request): Response
    {
        DB::table('bill_product')->where('id', $this->in($request, 'id'))->update([
            'is_lock' => $this->in($request, 'value'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);

        return response(Alerts::success(''));
    }

    /** POST /_admin/_order/save_remark — หมายเหตุแอดมิน (ช่องใน grid) */
    public function saveRemark(Request $request): Response
    {
        DB::table('bill_product')->where('id', $this->in($request, 'id'))->update(['admin_quote' => $this->in($request, 'remark')]);

        return response(Alerts::success());
    }

    /** POST /_admin/_order/save_quote_admin — หมายเหตุแอดมิน (หน้า form) */
    public function saveQuoteAdmin(Request $request): Response
    {
        DB::table('bill_product')->where('id', $this->in($request, 'id'))->update([
            'admin_quote' => $this->in($request, 'value'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);

        return response('');
    }

    /** POST /_admin/_order/save_vat — เปิด/ปิด VAT แล้วคำนวณยอดใหม่ */
    public function saveVat(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $value = $this->in($request, 'value');
        DB::transaction(fn () => OrderTotals::applyVat($id, Php74::eq($value, '1')));

        return response('');
    }

    /** GET|POST /_admin/_order/email_* — เรียกตรงแบบเดิมไม่มี id (ไม่ได้ส่งอะไรที่ใช้ได้) → ไม่ทำอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
