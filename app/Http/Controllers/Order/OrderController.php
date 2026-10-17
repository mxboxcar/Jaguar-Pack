<?php

namespace App\Http\Controllers\Order;

use App\Domain\Order\OrderDetailRenderer;
use App\Domain\Order\OrderLookups;
use App\Domain\Order\OrderQueries;
use App\Domain\Order\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Order\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Lookup;
use App\Support\Legacy\Paging;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * รายการสั่งซื้อของลูกค้า: _order/*.php เดิม — ทุก query กรอง bill_product.user = ผู้ที่ล็อกอิน
 * (เดิม save_payment.php ไม่กรองเจ้าของ: จ่ายเงิน/พลิกสถานะใบของคนอื่นได้ โดยหักเงินจากเจ้าของใบ)
 */
class OrderController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_order/grid';

    /** GET /order, /order/status/{status} — _order/index.php */
    public function index(Request $request): View
    {
        return view('order.customer.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** GET /order/{id}, /order/{id}/{div} — _order/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $row = OrderQueries::base()->where('bill_product.id', $id)->where('bill_product.user', Legacy::userId())
            ->first(['bill_product.*', 'user.code']);
        if ($row === null) {
            return view('legacy.no-permission', ['module' => 'order']);
        }
        $data = Php74::row($row);
        $balances = [];
        if (Php74::num($data['status']) >= 2) {
            $balances = DB::table('balance')->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
                ->where('balance.prefix', OrderLookups::prefixId('order'))->where('balance.prefix_ref_id', $id)
                ->where('balance.is_delete', 0)->orderBy('balance.id')
                ->get(['balance.*', 'prefix.link'])->all();
        }

        $renderer = new OrderDetailRenderer;
        $detailHtml = $renderer->customer($id, $data);

        return view('order.customer.form', [
            'id' => $id,
            'div' => strip_tags(trim((string) ($request->route('div') ?? ''))),
            'data' => $data,
            'prefix' => OrderLookups::prefix('order'),
            'balances' => $balances,
            'cash' => Php74::eq($data['status'], 1) ? Lookup::getCurrentCash() : null,
            'detailHtml' => $detailHtml,
            'tail' => $renderer->tail,
        ]);
    }

    /** POST /_order/grid — _order/grid.php (ไม่มีสิทธิ์ = ตอบว่างแบบเดิม) */
    public function grid(Request $request): Response
    {
        if (! perm('order')) {
            return response('');
        }
        $txt = $this->in($request, 'txtsearch');
        $p = explode('|', $txt);
        $php = explode('|', $this->php($request, 'txtsearch'));
        $term = str_replace(OrderLookups::prefix('order'), '', $p[0]);
        $q = OrderQueries::base()->where('bill_product.user', Legacy::userId())
            ->where('bill_product.id', 'like', OrderQueries::like($term));
        $having = OrderQueries::applyShipStatus($q, $p[1] ?? null, $p[2] ?? null);
        $page = $this->php($request, 'page');
        $offset = OrderLookups::offset($page);

        return response(view('order.customer.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $offset === null ? [] : OrderQueries::page($q, $having, $offset),
            'prefix' => OrderLookups::prefix('order'),
            'search' => $php[0],
            'ship' => isset($p[1]) ? $php[1] : null,
            'status' => isset($p[2]) ? $php[2] : null,
            'page' => $page,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** POST /_order/load_paging — _order/load_paging.php (นับแถวแบบเดิม: เงื่อนไขค้นหาต่างจาก grid เล็กน้อย) */
    public function loadPaging(Request $request): Response
    {
        if (! perm('order')) {
            return response('');
        }
        $p = explode('|', $this->in($request, 'txtsearch'));
        $uid = Legacy::userId();
        $term = $p[0];
        $q = OrderQueries::base()
            ->where(fn ($w) => $w->where('user.code', 'like', OrderQueries::like($term))
                ->orWhere('bill_product.id', 'like', OrderQueries::like(str_replace(OrderLookups::prefix('order'), '', $term))))
            ->where('user.id', $uid);
        $having = OrderQueries::applyShipStatus($q, $p[1] ?? null, $p[2] ?? null, true);
        $rows = OrderQueries::cachedCount('c-paging', $q, $having);

        return response(Paging::getpaging($this->php($request, 'page'), $rows, 10, self::AJAX));
    }

    /** POST /_order/load_total_order — จำนวนตามปุ่มสถานะ */
    public function loadTotalOrder(Request $request): Response
    {
        if (! perm('order')) {
            return response('');
        }
        $q = OrderQueries::base()->where('bill_product.user', Legacy::userId());
        $ref = $this->has($request, 'ref') ? $this->in($request, 'ref') : null;
        $having = OrderQueries::applyRef($q, $ref);

        return response((string) OrderQueries::cachedCount('c-total', $q, $having));
    }

    /**
     * POST /_order/delete — ยกเลิกใบสั่งซื้อของตัวเอง
     * เดิมยกเลิกได้ทุกสถานะ (แม้จ่ายเงินแล้ว/แอดมินล็อกไว้) ด้วยการยิง POST ตรง → จำกัดให้ตรงกับปุ่มในหน้า (status 0 และไม่ถูกล็อก)
     */
    public function delete(Request $request): Response
    {
        DB::table('bill_product')->where('id', $this->in($request, 'id'))->where('user', Legacy::userId())
            ->where('status', 0)->where('is_lock', 0)
            ->update(['status' => '-1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);

        return response(Alerts::success('window.location.href = "'.Legacy::href('order').'";'));
    }

    /**
     * POST /_order/save_payment — ชำระเงินด้วยยอดในระบบ
     * เดิม: ไม่ตรวจเจ้าของใบ และพลิกสถานะใดก็ได้ที่ไม่ใช่ 2 (รวมใบที่ยกเลิก/ยังไม่อนุมัติ) เป็น "ชำระแล้ว"
     * ใหม่: เฉพาะใบของตัวเองที่ status = 1 (ปุ่มจ่ายเงินแสดงเฉพาะสถานะนี้), ทำใน transaction + ล็อกแถว user กันจ่ายซ้อน
     */
    public function savePayment(Request $request): Response
    {
        if (! perm('order')) {
            return response('');
        }
        $uid = Legacy::userId();
        $idRaw = $this->in($request, 'id');
        $remark = $this->in($request, 'submit_remark');
        $done = function () use ($idRaw) {
            return response(Alerts::success('window.location.href = "'.Legacy::href('order').'";',
                Lang::get('success_process_no').OrderLookups::prefix('order').Alerts::js(e($idRaw))));
        };

        $result = DB::transaction(function () use ($uid, $idRaw, $remark) {
            DB::table('user')->where('id', $uid)->lockForUpdate()->value('id');
            $data = DB::table('bill_product')->where('id', $idRaw)->where('user', $uid)->lockForUpdate()
                ->first(['id', 'user', 'total', 'status']);
            if ($data === null) {
                return 'skip';
            }
            if (Php74::num(Lookup::getCurrentCash($data->user)) < Php74::num($data->total)) {
                return 'no_cash';
            }
            if (! Php74::eq($data->status, 1)) {
                return 'skip';
            }
            $n = DB::table('bill_product')->where('id', $data->id)->where('user', $uid)->where('status', 1)->update([
                'submit_date' => DB::raw('NOW()'), 'usecash' => $data->total, 'submit_remark' => $remark, 'status' => '2',
            ]);
            if ($n === 1) {
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'user' => $data->user, 'amount' => '-'.$data->total,
                    'detail' => 'ชำระเงิน '.OrderLookups::prefix('order').$data->id, 'type' => 2,
                    'prefix' => OrderLookups::prefixId('order'), 'prefix_ref_id' => $data->id,
                ]);
            }

            return 'ok';
        });
        if ($result === 'no_cash') {
            return response(Alerts::error('CloseLoading()', Lang::get('no_remain_cash')));
        }

        return $done();
    }

    /** POST /_order/save_quote — หมายเหตุของใบ (หน้า form) */
    public function saveQuote(Request $request): Response
    {
        if (! perm('order')) {
            return response('');
        }
        DB::table('bill_product')->where('id', $this->in($request, 'id'))->where('user', Legacy::userId())
            ->update(['quote' => $this->in($request, 'quote')]);

        return response('');
    }

    /** POST /_order/save_remark — หมายเหตุของใบ (ช่องใน grid) */
    public function saveRemark(Request $request): Response
    {
        if (! perm('order')) {
            return response('');
        }
        DB::table('bill_product')->where('id', $this->in($request, 'id'))->where('user', Legacy::userId())
            ->update(['quote' => $this->in($request, 'remark')]);

        return response(Alerts::success());
    }

    /** POST /_order/save_quote_tracking — หมายเหตุของ tracking ของตัวเอง */
    public function saveQuoteTracking(Request $request): Response
    {
        if (! perm('order')) {
            return response('');
        }
        DB::table('tracking')->where('id', $this->in($request, 'id'))->where('user', Legacy::userId())
            ->update(['quote' => $this->in($request, 'value')]);

        return response('');
    }

    /** GET /_order/print?id= — _order/print.php (หน้าพิมพ์ ไม่ใช้ layout หลัก) */
    public function print(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $header = view('order.print_header')->render();
        if (! perm('order')) {
            return response($header.Alerts::permission('order'));
        }
        $id = (int) LegacyInput::query($request, 'id', 0);
        $row = OrderQueries::base()->where('bill_product.id', $id)->where('bill_product.is_delete', 0)
            ->where('bill_product.user', Legacy::userId())->first(['bill_product.*', 'user.code']);
        if ($row === null) {
            return redirect()->to(Legacy::href()); // Redirect(href) เดิม
        }
        $data = Php74::row($row);
        $editLast = DB::table('user')->where('id', $data['mod_by'] ?? '')->value('code');

        return response($header.view('order.customer.print', [
            'data' => $data,
            'prefix' => OrderLookups::prefix('order'),
            'editLast' => $editLast,
            'detailHtml' => (new OrderDetailRenderer)->print($id),
        ])->render());
    }
}
