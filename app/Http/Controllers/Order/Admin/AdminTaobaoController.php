<?php

namespace App\Http\Controllers\Order\Admin;

use App\Domain\Order\OrderLookups;
use App\Domain\Order\OrderMailer;
use App\Domain\Order\Php74;
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
        // HAVING เดิม (derived table = NULL เมื่อไม่มีแถว): "-1" = wait = 0 AND complete = 0 → ไม่มีแถวผ่านเลย
        $having = null;
        if (Php74::eq($s, '1')) {
            $having = 'wait != 0';
        }
        if (Php74::eq($s, '2')) {
            $having = 'complete != 0';
        }
        if (Php74::eq($s, '-1')) {
            $having = '1 = 0';
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

    /** POST /_admin/_taobao/save_approved — เดิมตรวจ admin_taobao แต่แจ้งเตือนด้วยชื่อ admin_shop_cashback */
    public function saveApproved(Request $request): Response
    {
        if (! perm('admin_taobao')) {
            return response(Alerts::permission('admin_shop_cashback'));
        }
        $id = $this->in($request, 'id');
        $data = DB::table('taobao_detail')->where('id', $id)->first();
        DB::table('taobao_detail')->where('id', $id)->update(['status' => '2', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);
        if ($data !== null && ! config('legacy.debug_mail')) {
            OrderMailer::taobaoApproved((int) $data->taobao_id, (int) $data->id);
        }

        return response(Alerts::success('window.location.reload();'));
    }

    /**
     * POST /_admin/_taobao/save_cancel — ยกเลิกรายการ + คืนค่าบริการ (balance) และยอดหยวน (balance_cn) + อีเมล
     * เดิมอ่านสถานะแล้วค่อย UPDATE → กดซ้ำพร้อมกันคืนเงินซ้ำได้ → transaction + ล็อกแถว
     */
    public function saveCancel(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $uid = Legacy::userId();
        $code = (string) (Legacy::user()->code ?? '');
        $done = DB::transaction(function () use ($id, $remark, $uid, $code) {
            $data = DB::table('taobao_detail')->where('id', $id)->lockForUpdate()->first();
            $head = DB::table('taobao')->where('id', $data->taobao_id ?? '')->first();
            if ($data !== null && Php74::eq($data->status, -1)) {
                return null;
            }
            DB::table('taobao_detail')->where('id', $id)->update([
                'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.$code, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            $pre = OrderLookups::prefix('taobao');
            $pid = OrderLookups::prefixId('taobao');
            $charge = $head->charge ?? null;
            if (Php74::neq($charge, '') && Php74::neq($charge, '0.00') && Php74::neq($charge, '0')) {
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => $head->user ?? '', 'amount' => $charge, 'detail' => $pre.($head->id ?? '').', คืนเงินค่าบริการ',
                    'type' => '7', 'prefix' => $pid, 'prefix_ref_id' => $head->id ?? '',
                ]);
            }
            $price = $data->price ?? null;
            if (Php74::neq($price, '') && Php74::neq($price, '0.00') && Php74::neq($price, '0')) {
                DB::table('balance_cn')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => $head->user ?? '', 'amount' => $price, 'detail' => $pre.($head->id ?? '').', รายการสั่งซื้อ : '.($data->order ?? ''),
                    'type' => '7', 'prefix' => $pid, 'prefix_ref_id' => $head->id ?? '',
                ]);
            }

            return $data;
        });
        if ($done !== null && ! config('legacy.debug_mail')) {
            OrderMailer::taobaoCancelled((int) $done->taobao_id, (int) $done->id);
        }

        return response(Alerts::success('window.location.reload();'));
    }

    /**
     * POST /_admin/_taobao/rollback (สิทธิ์ admin_alipay) — ยกเลิก (-1) หรือ จ่ายแล้ว (2) → กลับเป็นรอดำเนินการ (1)
     * คงพฤติกรรมเดิม: ตอนยกเลิกคืนเงินเป็น "หยวน" (balance_cn) แต่ rollback หักคืนจาก "บาท" (balance) = rate × price
     */
    public function rollback(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $uid = Legacy::userId();
        DB::transaction(function () use ($id, $uid) {
            $data = DB::table('taobao_detail')->leftJoin('taobao', 'taobao.id', '=', 'taobao_detail.taobao_id')
                ->where('taobao_detail.id', $id)->lockForUpdate()
                ->first(['taobao_detail.*', 'taobao.rate', 'taobao.charge', 'taobao.user', 'taobao.id as taobao_ids']);
            if ($data === null) {
                return;
            }
            $reset = ['status' => '1', 'cancel_remark' => '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid];
            if (Php74::eq($data->status, '-1')) {
                $n = DB::table('taobao_detail')->where('id', $data->id)->where('status', -1)->update($reset);
                if ($n !== 1) {
                    return;
                }
                $pre = OrderLookups::prefix('taobao');
                $pid = OrderLookups::prefixId('taobao');
                $newprice = Php74::num($data->rate) * Php74::num($data->price);
                $row = ['create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'user' => $data->user ?? '', 'type' => 7, 'prefix' => $pid, 'prefix_ref_id' => $data->taobao_ids ?? ''];
                DB::table('balance')->insert($row + ['amount' => '-'.Php74::str($newprice), 'detail' => 'Rollback '.$pre.($data->taobao_ids ?? '')]);
                DB::table('balance')->insert($row + ['amount' => '-'.($data->charge ?? ''), 'detail' => 'Rollback คืนเงินค่าบริการ '.$pre.($data->taobao_ids ?? '')]);

                return;
            }
            if (Php74::eq($data->status, 2)) {
                DB::table('taobao_detail')->where('id', $data->id)->update($reset);
            }
        });

        return response(Alerts::success('window.location.reload(); '));
    }

    /** GET|POST /_admin/_taobao/email_* — เรียกตรงแบบเดิมไม่มี id → ไม่ทำอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
