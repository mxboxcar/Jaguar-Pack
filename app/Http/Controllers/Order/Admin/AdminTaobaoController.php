<?php

namespace App\Http\Controllers\Order\Admin;

use App\Domain\Money\Money;
use App\Domain\Order\OrderLookups;
use App\Domain\Order\OrderMailer;
use App\Domain\Order\Php74;
use App\Domain\Order\TaobaoRefund;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Order\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ฝากจ่ายเงินเถาเปา ฝั่งแอดมิน: _admin/_taobao/*.php (สิทธิ์ admin_taobao; rollback = admin_alipay; load_calculator/load_new_line = taobao)
 */
class AdminTaobaoController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_taobao/grid';

    private const WAIT = '(SELECT COUNT(td.id) FROM taobao_detail AS td WHERE td.status = 1 AND td.taobao_id = taobao.id)';

    private const COMPLETE = '(SELECT COUNT(td.id) FROM taobao_detail AS td WHERE td.status = 2 AND td.taobao_id = taobao.id)';

    /** GET /admin/taobao, /admin/taobao/status/{status} */
    public function index(Request $request): View
    {
        return view('order.taobao.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** GET /admin/taobao/{id}, /admin/taobao/create (id = 0) — _admin/_taobao/form.php */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $row = DB::table('taobao')->leftJoin('user', 'taobao.user', '=', 'user.id')
            ->where('taobao.id', $id)->where('taobao.is_delete', 0)->first(['taobao.*', 'user.code']);
        $data = Php74::row($row);
        $tid = $data['id'] ?? '';
        $details = DB::table('taobao_detail')->leftJoin('user', 'user.id', '=', 'taobao_detail.mod_by')
            ->where('taobao_id', $tid)->orderBy('taobao_detail.id')->get(['taobao_detail.*', 'user.code'])->all();
        $agg = DB::table('taobao_detail')->where('taobao_id', $tid)->selectRaw('count(id) as count, SUM(price) as sum')->first();

        $balances = [];
        $pid = OrderLookups::prefixId('taobao');
        foreach (DB::table('balance')->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->where('balance.prefix', $pid)->where('balance.prefix_ref_id', $id)->where('balance.is_delete', 0)
            ->orderBy('balance.id')->get(['balance.*', 'prefix.link as link']) as $b) {
            $b->types = 'balance';
            $balances[] = $b;
        }
        foreach (DB::table('balance_cn')->leftJoin('prefix', 'balance_cn.prefix', '=', 'prefix.id')
            ->where('balance_cn.prefix', $pid)->where('balance_cn.prefix_ref_id', $id)->where('balance_cn.is_delete', 0)
            ->orderBy('balance_cn.id')->get(['balance_cn.*', 'prefix.link as link']) as $b) {
            $b->types = 'balance_cn';
            $balances[] = $b;
        }
        // sortFunction เดิม: strtotime(create_date) น้อยไปมาก
        usort($balances, fn ($a, $b) => (int) strtotime((string) $a->create_date) - (int) strtotime((string) $b->create_date));

        return view('order.taobao.form', [
            'id' => $id,
            'data' => $data,
            'details' => $details,
            'agg' => $agg,
            'balances' => $balances,
            'prefix' => OrderLookups::prefix('taobao'),
        ]);
    }

    /** POST /_admin/_taobao/grid — ไม่มีสิทธิ์ = ตอบว่างแบบเดิม */
    public function grid(Request $request): Response
    {
        if (! perm('admin_taobao')) {
            return response('');
        }
        $p = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $pre = strtolower(OrderLookups::prefix('taobao'));
        $term = str_replace($pre, '', strtolower($p[0]));
        $like = '%'.addcslashes($term, '%_\\').'%';
        $q = DB::table('taobao')->leftJoin('user', 'user.id', '=', 'taobao.user')->where('taobao.is_delete', 0)
            ->where(fn ($w) => $w->where('taobao.id', 'like', $like)->orWhere('user.code', 'like', $like));
        $s = $p[1] ?? null;
        // HAVING เดิม "-1" = wait = 0 AND complete = 0 กับ derived table (= NULL เมื่อไม่มีแถว) → ไม่มีแถวผ่านเลย
        // แก้ (เจ้าของสั่ง 2026-10-18): นับด้วย correlated COUNT (0 แทน NULL) → "ยกเลิก" = ไม่มีรายการรอ/สำเร็จเหลือ
        $having = null;
        if (Php74::eq($s, '1')) {
            $having = 'wait != 0';
        }
        if (Php74::eq($s, '2')) {
            $having = 'complete != 0';
        }
        if (Php74::eq($s, '-1')) {
            $having = 'wait = 0 AND complete = 0';
        }
        if ($having !== null) {
            $q->selectRaw('taobao.id')->selectRaw(self::WAIT.' AS wait')->selectRaw(self::COMPLETE.' AS complete')->havingRaw($having);
            $rows = DB::query()->fromSub($q, 'x')->count();
        } else {
            $rows = (clone $q)->count();
        }
        $page = $this->php($request, 'page');
        $offset = OrderLookups::offset($page);
        $items = [];
        if ($offset !== null) {
            $list = clone $q;
            if ($having === null) {
                $list->select('taobao.*', 'user.code');
            } else {
                $list->select('taobao.*', 'user.code')->selectRaw(self::WAIT.' AS wait')->selectRaw(self::COMPLETE.' AS complete');
            }
            $items = $list->orderByDesc('taobao.id')->offset($offset)->limit(10)->get()->all();
        }
        $ids = array_map(fn ($r) => (int) $r->id, $items);
        $prices = [];
        if ($ids !== []) {
            foreach (DB::table('taobao_detail')->whereIn('taobao_id', $ids)->orderBy('id')->get(['taobao_id', 'price', 'status']) as $d) {
                $prices[(int) $d->taobao_id][] = $d;
            }
        }

        return response(view('order.taobao.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'prices' => $prices,
            'prefix' => OrderLookups::prefix('taobao'),
            'search' => $php[0],
            'status' => isset($p[1]) ? $php[1] : null,
            'page' => $page,
            'rows' => $rows,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** POST /_admin/_taobao/load_calculator (สิทธิ์ taobao) */
    public function loadCalculator(Request $request): Response
    {
        $orderRaw = $this->has($request, 'order') ? $this->php($request, 'order') : null;
        $order = Php74::num($orderRaw);
        $price = Php74::num($this->in($request, 'price'));
        $byOrder = Php74::num(Lookup::getSetting('charge_taobao'));
        $rateRaw = Lookup::getSetting('rate_transfer');
        $rate = Php74::num($rateRaw);
        $data = [
            'order' => $orderRaw,
            'total_order' => Format::numberformat($order * $byOrder),
            'price_th' => Format::numberformat($price * $rate),
            'price_cn' => Format::numberformat($this->has($request, 'price') ? $this->php($request, 'price') : null, true, true, true),
            'rate' => Format::numberformat($rateRaw),
            'total_thai' => Format::numberformat(($price * $rate) + ($order * $byOrder)),
        ];

        return response(json_encode($data));
    }

    /** POST /_admin/_taobao/load_new_line (สิทธิ์ taobao) */
    public function loadNewLine(): Response
    {
        return response(view('order.taobao.new_line')->render());
    }

    /** POST /_admin/_taobao/save — หมายเหตุแอดมิน (ฟอร์มหลัก) */
    public function save(Request $request): Response
    {
        DB::table('taobao')->where('id', $this->in($request, 'id'))->update([
            'remark_admin' => $this->in($request, 'remark_admin'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);

        return response(Alerts::success('window.location.reload();'));
    }

    /** POST /_admin/_taobao/save_remark — หมายเหตุแอดมิน (ช่องใน grid) */
    public function saveRemark(Request $request): Response
    {
        DB::table('taobao')->where('id', $this->in($request, 'id'))->update(['remark_admin' => $this->in($request, 'remark')]);

        return response(Alerts::success());
    }

    /**
     * POST /_admin/_taobao/save_approved — เดิมตรวจ admin_taobao แต่แจ้งเตือนด้วยชื่อ admin_shop_cashback
     * เดิมไม่ตรวจสถานะ: อนุมัติรายการที่ยกเลิก (คืนเงินแล้ว) → rollback 2 → 1 (ไม่หักเงิน) → ยกเลิกซ้ำ = คืนเงินซ้ำ
     * ใหม่ (เจ้าของสั่งแก้): ล็อกแถว, เฉพาะ status 1 (= ปุ่มในหน้า)
     */
    public function saveApproved(Request $request): Response
    {
        if (! perm('admin_taobao')) {
            return response(Alerts::permission('admin_shop_cashback'));
        }
        $id = $this->in($request, 'id');
        $result = DB::transaction(function () use ($id) {
            $data = DB::table('taobao_detail')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return 'ไม่พบรายการ';
            }
            if (! Money::statusIn($data->status, [1])) {
                return self::notPending($data->status);
            }
            DB::table('taobao_detail')->where('id', $data->id)->update(['status' => '2', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);

            return $data;
        });
        if (is_string($result)) {
            return response(Money::refuse($result));
        }
        if (! config('legacy.debug_mail')) {
            OrderMailer::taobaoApproved((int) $result->taobao_id, (int) $result->id);
        }

        return response(Alerts::success('window.location.reload();'));
    }

    /**
     * POST /_admin/_taobao/save_cancel — ยกเลิกรายการ + คืนเงิน + อีเมล
     * เดิม: ยกเลิกได้ทุกสถานะที่ไม่ใช่ -1 (รวมรายการที่จ่ายร้านแล้ว), คืนราคาเป็นหยวน (balance_cn) เสมอไม่ว่าลูกค้าจ่ายด้วยอะไร
     *       + คืนค่าบริการ (charge = ค่าบริการต่อ 1 ออเดอร์) — ไม่ผูกแถวคืนเงินกับรายการ ทำให้ rollback หักคืนไม่ตรง
     * ใหม่ (เจ้าของสั่งแก้):
     *  - เฉพาะ status 1 (= ปุ่มในหน้า), ล็อกแถวรายการ + ใบ + user
     *  - คืนในสกุลที่ลูกค้าจ่ายจริง: ส่วนที่จ่ายด้วยหยวน (usecash_cn ที่ยังไม่ถูกคืน) คืนเป็นหยวน ที่เหลือคืนเป็นบาท × rate ของใบ
     *  - ค่าบริการคืน 1 ออเดอร์ (charge ของใบ) — รวมทุกยอดที่คืนไม่เกินยอดที่ใบนี้ถูกตัดไว้ในแต่ละสกุล
     *  - แถวคืนเงินมี ref_id_2 = id ของรายการ (taobao_detail) เพื่อให้ rollback หักคืนตรงตามที่คืนจริง
     */
    public function saveCancel(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $uid = Legacy::userId();
        $code = (string) (Legacy::user()->code ?? '');
        $done = DB::transaction(function () use ($id, $remark, $uid, $code) {
            $data = DB::table('taobao_detail')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return 'ไม่พบรายการ';
            }
            if (! Money::statusIn($data->status, [1])) {
                return self::notPending($data->status);
            }
            $head = DB::table('taobao')->where('id', $data->taobao_id)->lockForUpdate()->first();
            if ($head === null) {
                return 'ไม่พบรายการ';
            }
            Money::lockUser($head->user);
            DB::table('taobao_detail')->where('id', $data->id)->update([
                'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.$code, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            $pre = OrderLookups::prefix('taobao');
            $pid = OrderLookups::prefixId('taobao');
            $r = TaobaoRefund::forCancel(
                (float) Php74::num($data->price), (float) Php74::num($head->charge), (float) Php74::num($head->rate),
                Money::net('balance_cn', $pid, $head->id), Money::net('balance', $pid, $head->id),
            );
            $row = ['create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => $head->user, 'type' => '7', 'prefix' => $pid, 'prefix_ref_id' => $head->id, 'ref_id_2' => $data->id];
            if ($r['charge'] > 0) {
                DB::table('balance')->insert($row + ['amount' => Money::fmt($r['charge']), 'detail' => $pre.$head->id.', คืนเงินค่าบริการ']);
            }
            if ($r['cny'] > 0) {
                DB::table('balance_cn')->insert($row + ['amount' => Money::fmt($r['cny']), 'detail' => $pre.$head->id.', รายการสั่งซื้อ : '.$data->order]);
            }
            if ($r['thb'] > 0) {
                DB::table('balance')->insert($row + ['amount' => Money::fmt($r['thb']), 'detail' => $pre.$head->id.', รายการสั่งซื้อ : '.$data->order.' (คืนเป็นเงินบาท)']);
            }

            return [$data, $r];
        });
        if (is_string($done)) {
            return response(Money::refuse($done));
        }
        if (! config('legacy.debug_mail')) {
            OrderMailer::taobaoCancelled((int) $done[0]->taobao_id, (int) $done[0]->id, $done[1]);
        }

        return response(Alerts::success('window.location.reload();'));
    }

    /**
     * POST /_admin/_taobao/rollback (สิทธิ์ admin_alipay) — ยกเลิก (-1) หรือ จ่ายแล้ว (2) → กลับเป็นรอดำเนินการ (1)
     * เดิม: ตอนยกเลิกคืนเป็นหยวน แต่ rollback หักจากบาท (rate × price) + หักค่าบริการแม้ไม่ได้คืน, ไม่ตรวจยอดคงเหลือ
     * ใหม่ (เจ้าของสั่งแก้): หักคืนตามแถวคืนเงินของรายการนี้ (ref_id_2) ในสกุลเดียวกับที่คืน; รายการที่ยกเลิกจากเว็บเดิม
     * (ไม่มี ref_id_2) = เว็บเดิมคืนราคาเป็นหยวน + ค่าบริการเป็นบาท → หักคืนแบบนั้น; ยอดคงเหลือไม่พอ = ปฏิเสธ
     */
    public function rollback(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $uid = Legacy::userId();
        $error = DB::transaction(function () use ($id, $uid) {
            $data = DB::table('taobao_detail')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return null;
            }
            $reset = ['status' => '1', 'cancel_remark' => '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid];
            if (Money::statusIn($data->status, [2])) {
                DB::table('taobao_detail')->where('id', $data->id)->update($reset);

                return null;
            }
            if (! Money::statusIn($data->status, [-1])) {
                return null;
            }
            $head = DB::table('taobao')->where('id', $data->taobao_id)->lockForUpdate()->first();
            if ($head === null) {
                return null;
            }
            Money::lockUser($head->user);
            $pre = OrderLookups::prefix('taobao');
            $pid = OrderLookups::prefixId('taobao');
            $item = fn ($q) => $q->where('ref_id_2', $data->id);
            $tracked = DB::table('balance')->where('prefix', $pid)->where('prefix_ref_id', $head->id)->where('ref_id_2', $data->id)->exists()
                || DB::table('balance_cn')->where('prefix', $pid)->where('prefix_ref_id', $head->id)->where('ref_id_2', $data->id)->exists();
            if ($tracked) {
                $thb = max(0.0, Money::net('balance', $pid, $head->id, $item));
                $cny = max(0.0, Money::net('balance_cn', $pid, $head->id, $item));
                $thbText = 'Rollback '.$pre.$head->id;
            } else {
                $thb = max(0.0, round((float) Php74::num($head->charge), 2));
                $cny = max(0.0, round((float) Php74::num($data->price), 2));
                $thbText = 'Rollback คืนเงินค่าบริการ '.$pre.$head->id;
            }
            if (Money::cash($head->user) < $thb || Money::cash($head->user, 'balance_cn') < $cny) {
                return 'ยอดเงินคงเหลือของลูกค้าไม่พอสำหรับ RollBack (ต้องการ '.Money::fmt($thb).' บาท / '.Money::fmt($cny).' หยวน)';
            }
            DB::table('taobao_detail')->where('id', $data->id)->update($reset);
            $row = ['create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => $head->user, 'type' => 7, 'prefix' => $pid, 'prefix_ref_id' => $head->id, 'ref_id_2' => $data->id];
            if ($cny > 0) {
                DB::table('balance_cn')->insert($row + ['amount' => '-'.Money::fmt($cny), 'detail' => 'Rollback '.$pre.$head->id]);
            }
            if ($thb > 0) {
                DB::table('balance')->insert($row + ['amount' => '-'.Money::fmt($thb), 'detail' => $thbText]);
            }

            return null;
        });
        if ($error !== null) {
            return response(Money::refuse($error));
        }

        return response(Alerts::success('window.location.reload(); '));
    }

    private static function notPending(mixed $status): string
    {
        return Money::statusIn($status, [-1]) ? 'รายการนี้ถูกยกเลิกไปแล้ว' : 'รายการนี้ดำเนินการ (จ่ายร้านค้า) แล้ว กรุณา RollBack ก่อน';
    }

    /** GET|POST /_admin/_taobao/email_* — เรียกตรงแบบเดิมไม่มี id → ไม่ทำอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
