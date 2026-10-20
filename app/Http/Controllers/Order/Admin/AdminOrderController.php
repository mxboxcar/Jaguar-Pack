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
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * รายการสั่งซื้อฝั่งแอดมิน: _admin/_order/{index,form,grid,load_total_order,delete,save_*}.php (สิทธิ์ admin_order)
 * SR เพิ่ม: delete_withcashback, load_changeuser, save_changeuser, save_change_send_type, save_newrate
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
        // SR: นับจำนวนแถวใน grid เอง (MakePaging + getpaging ท้ายตาราง) แทน load_paging แยกของ jl
        $rows = OrderQueries::count($q, $having);

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
            'rows' => $rows,
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

    /** POST /_admin/_order/load_total_order — จำนวนใบทั้งระบบตามปุ่มสถานะ */
    public function loadTotalOrder(Request $request): Response
    {
        $q = OrderQueries::base();
        $having = OrderQueries::applyRef($q, $this->has($request, 'ref') ? $this->in($request, 'ref') : null);

        // SR: นับสดทุกครั้ง (เดิมของ SR ไม่มี readRedis/writeRedis — jl cache 10 นาที)
        return response((string) OrderQueries::count($q, $having));
    }

    /**
     * POST /_admin/_order/delete — ยกเลิกใบ (+ คืนเงินถ้าจ่ายแล้ว) แล้วแจ้งลูกค้าทางอีเมล
     * เดิมอ่านสถานะแล้วค่อย UPDATE → กดซ้ำพร้อมกันคืนเงินซ้ำได้ → ทำใน transaction + ล็อกแถว
     */
    public function delete(Request $request): Response
    {
        return $this->cancelOrder($request, false);
    }

    /**
     * POST /_admin/_order/delete_withcashback — SR: ยกเลิกใบ + คืนเงิน (เหมือน delete) + "คืนธรรมเนียมการเปิดใบสั่งซื้อสินค้า" 50 บาท
     * (ฟังก์ชัน JS cancelingcashback() มีในหน้า แต่ไม่มีปุ่มเรียก — เรียกได้ด้วย POST ตรง)
     * เดิม: เครดิต 50 บาทให้ **แอดมินที่กด** (user = getUserID()) ทุกครั้ง ทั้งที่ค่าธรรมเนียม -50 ตอนเปิดใบถูก comment ไว้ใน _cart/save_checkout
     *       = สร้างเงินให้แอดมินจากอากาศ, ไม่มี transaction/ล็อก
     * ใหม่: คืนค่าธรรมเนียมให้ **เจ้าของใบ** เฉพาะเมื่อมีแถวหักค่าธรรมเนียมของใบนี้จริงและยังไม่เคยคืน (ตอนนี้ไม่มีแถวแบบนี้ = ไม่คืน)
     *       ส่วนคืนค่าสินค้า = ยอดสุทธิที่ใบนี้ยังถูกตัดอยู่ แบบเดียวกับ delete (เจ้าของอนุมัติ 2026-10-18)
     */
    public function deleteWithCashback(Request $request): Response
    {
        return $this->cancelOrder($request, true);
    }

    /** ยกเลิกใบ (+ คืนเงินถ้าจ่ายแล้ว) — ใช้ร่วม delete / delete_withcashback; สถานะตรวจภายใต้ล็อกแถว */
    private function cancelOrder(Request $request, bool $withFee): Response
    {
        $idRaw = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $uid = Legacy::userId();
        $code = (string) (Legacy::user()->code ?? '');
        $cancelled = DB::transaction(function () use ($idRaw, $remark, $uid, $code, $withFee) {
            $data = DB::table('bill_product')->where('id', $idRaw)->lockForUpdate()->first();
            if ($data !== null && Php74::eq($data->status, -1)) {
                return null;
            }
            if ($withFee && $data === null) {
                // delete_withcashback เดิม: ไม่พบใบ = status null != -1 → UPDATE 0 แถว + เครดิต 50 ให้แอดมิน → ใหม่ไม่ทำอะไร
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
            if ($withFee) {
                $this->refundOpeningFee($data, $uid);
            }

            return $data;
        });
        if ($cancelled !== null && ! config('legacy.debug_mail')) {
            OrderMailer::orderCancelled((int) $cancelled->id);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/order').'";',
            Lang::get('success_process_no').OrderLookups::prefix('order').Alerts::js(e($idRaw))));
    }

    /** คืนค่าธรรมเนียมเปิดใบ 50 บาท ให้เจ้าของใบ เฉพาะเมื่อเคยถูกหักจริง (แถว -50 ของ _cart/save_checkout เดิม) และยังไม่เคยคืน */
    private function refundOpeningFee(object $data, ?int $uid): void
    {
        $no = OrderLookups::prefix('order').$data->id;
        Money::lockUser($data->user);
        $base = fn () => DB::table('balance')->where('user', $data->user)->where('is_delete', 0)->where('type', 2);
        $charged = (float) $base()->where('detail', 'ธรรมเนียมการเปิดใบสั่งซื้อสินค้า '.$no)->sum('amount');
        $refunded = (float) $base()->where('detail', 'คืนธรรมเนียมการเปิดใบสั่งซื้อสินค้า '.$no)->sum('amount');
        $fee = Money::refundable(50.0, round($charged + $refunded, 2));
        if ($fee <= 0) {
            return;
        }
        DB::table('balance')->insert([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            'is_delete' => 0, 'user' => $data->user, 'amount' => Money::fmt($fee),
            'detail' => 'คืนธรรมเนียมการเปิดใบสั่งซื้อสินค้า '.$no, 'type' => 2, 'prefix' => '', 'prefix_ref_id' => '',
        ]);
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

    /**
     * POST /_admin/_order/load_changeuser — SR: typeahead รหัสลูกค้า (user ที่เปิดใช้งานและไม่ถูกลบ) → {"code":[...]} (ไม่พบ = {"code":null})
     */
    public function loadChangeuser(Request $request): Response
    {
        $codes = DB::table('user')->where('code', 'like', OrderQueries::like($this->in($request, 'query')))
            ->where('is_enable', '1')->where('is_delete', 0)->orderBy('id')->pluck('code')->all();

        return response(json_encode(['code' => $codes === [] ? null : array_map(fn ($c) => $c === null ? null : (string) $c, $codes)]));
    }

    /**
     * POST /_admin/_order/save_changeuser — SR: ย้ายใบสั่งซื้อ (และรายการ/tracking ของใบ) ไปเป็นของลูกค้ารหัสอื่น
     * เดิม: ทำได้ทุกสถานะ ไม่มี transaction — ใบที่ชำระแล้ว (status >= 2) แถวเงินยังเป็นของคนเดิม แต่ยกเลิกใบภายหลังจะคืนเงินให้ "เจ้าของใหม่"
     *       (= ย้ายเงินข้ามบัญชีได้) → ใหม่: เปลี่ยนได้เฉพาะใบที่ยังไม่ชำระ (status < 2) ทำใน transaction + ล็อกแถวใบ
     */
    public function saveChangeuser(Request $request): Response
    {
        $idRaw = $this->in($request, 'id');
        $code = trim($this->in($request, 'code'));
        $target = DB::table('user')->where('code', $code)->orderBy('id')->value('id');
        if ($target === null) {
            return response(Alerts::error('CloseLoading()', Lang::get('no_user')).' ');
        }
        $result = DB::transaction(function () use ($idRaw, $target) {
            $bill = DB::table('bill_product')->where('id', $idRaw)->lockForUpdate()->first(['id', 'status']);
            if ($bill === null) {
                // เดิม: ไม่พบใบ = UPDATE ... WHERE id = '' (0 แถว) แล้ว reload
                return 'ok';
            }
            if (Php74::num($bill->status) >= 2) {
                return 'paid';
            }
            $detailIds = DB::table('bill_product_detail')->where('bill_product_id', $bill->id)->pluck('id')->all();
            $onRows = $detailIds === [] ? collect() : DB::table('bill_product_on_tracking')->whereIn('bill_product_detail_id', $detailIds)->get(['id', 'tracking_id']);
            $trackIds = $onRows->pluck('tracking_id')->filter(fn ($v) => $v !== null)->unique()->values()->all();
            DB::table('bill_product')->where('id', $bill->id)->update(['user' => $target]);
            if ($detailIds !== []) {
                DB::table('bill_product_detail')->whereIn('id', $detailIds)->update(['user' => $target]);
            }
            if ($onRows->isNotEmpty()) {
                DB::table('bill_product_on_tracking')->whereIn('id', $onRows->pluck('id')->all())->update(['user' => $target]);
            }
            if ($trackIds !== []) {
                DB::table('tracking')->whereIn('id', $trackIds)->update(['user' => $target]);
            }

            return 'ok';
        });
        if ($result === 'paid') {
            return response(Money::refuse('ใบสั่งซื้อนี้ชำระเงินแล้ว ไม่สามารถเปลี่ยนลูกค้าได้'));
        }

        return response('window.location.reload();');
    }

    /**
     * POST /_admin/_order/save_change_send_type — SR: เปลี่ยนประเภทการส่ง (รถ/เรือ) ของใบ + tracking ของใบ แล้วคำนวณราคา tracking ใหม่ (CalPriceAutoDiff)
     * (ปุ่มในหน้าแสดงเฉพาะผู้ใช้ id 84 / 3 แต่ไฟล์เดิมตรวจแค่ admin_order — คงไว้)
     * เดิม: เขียนค่า type อะไรก็ได้, echo SQL กลับ (JS ไม่ใช้ → reload), ไม่มี transaction
     * ใหม่: รับเฉพาะ 0/1, ใบที่ยกเลิกแล้ว (-1) ไม่แก้, ทำใน transaction + ล็อกแถวใบ, ตอบว่าง
     */
    public function saveChangeSendType(Request $request): Response
    {
        $idRaw = $this->in($request, 'id');
        $type = $this->in($request, 'type');
        if ($type !== '0' && $type !== '1') {
            return response('');
        }
        $uid = Legacy::userId();
        DB::transaction(function () use ($idRaw, $type, $uid) {
            $bill = DB::table('bill_product')->where('id', $idRaw)->lockForUpdate()->first(['id', 'status']);
            if ($bill === null || Php74::eq($bill->status, -1)) {
                return;
            }
            DB::table('bill_product')->where('id', $bill->id)->update(['ship' => $type, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
            $detailIds = DB::table('bill_product_detail')->where('is_delete', 0)->where('bill_product_id', $bill->id)->pluck('id')->all();
            if ($detailIds === []) {
                return; // เดิม IN () = SQL error → ไม่แก้ tracking
            }
            $trackIds = DB::table('bill_product_on_tracking')->where('is_delete', 0)->whereIn('bill_product_detail_id', $detailIds)
                ->pluck('tracking_id')->filter(fn ($v) => $v !== null)->unique()->values()->all();
            if ($trackIds === []) {
                return;
            }
            DB::table('tracking')->whereIn('id', $trackIds)->where('is_delete', 0)
                ->update(['ship' => $type, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
            foreach (DB::table('tracking')->whereIn('id', $trackIds)->where('is_delete', 0)->orderBy('id')->pluck('id') as $tid) {
                Pricing::calPriceAutoDiff($tid);
            }
        });

        return response('');
    }

    /**
     * POST /_admin/_order/save_newrate — SR: เปลี่ยนเรทของใบ + ทุกรายการ แล้วคำนวณยอดรวมใหม่ (สูตรเดียวกับ save_price)
     * เดิม: ทำได้ทุกสถานะ — ใบที่ลูกค้าจ่ายแล้ว (usecash ถูกหักไปแล้ว) ยอดรวมจะไม่ตรงกับที่จ่าย, ไม่มี transaction
     * ใหม่: เฉพาะใบที่ยังไม่ชำระ (status 0/1 — เหมือนช่องแก้จำนวน/ราคาที่แสดงเฉพาะ status < 2) ทำใน transaction + ล็อกแถวใบ
     *       (JS เดิม reload หน้าเสมอ ไม่ได้อ่านคำตอบ)
     */
    public function saveNewrate(Request $request): Response
    {
        $idRaw = $this->in($request, 'id');
        $rate = $this->in($request, 'rate');
        if ($idRaw === '') {
            return response('');
        }
        $ok = DB::transaction(function () use ($idRaw, $rate) {
            $bill = DB::table('bill_product')->where('id', $idRaw)->lockForUpdate()->first(['id', 'status']);
            if ($bill === null || ! Money::statusIn($bill->status, [0, 1])) {
                return false;
            }
            DB::table('bill_product')->where('id', $bill->id)->update(['exchange_rate' => $rate]);
            DB::table('bill_product_detail')->where('bill_product_id', $bill->id)->update(['exchange_rate' => $rate]);
            OrderTotals::saveTotal($bill->id);

            return true;
        });

        return response($ok ? '' : Money::refuse('เปลี่ยนเรทได้เฉพาะใบสั่งซื้อที่ยังไม่ชำระเงิน'));
    }

    /** GET|POST /_admin/_order/email_* — เรียกตรงแบบเดิมไม่มี id (ไม่ได้ส่งอะไรที่ใช้ได้) → ไม่ทำอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
