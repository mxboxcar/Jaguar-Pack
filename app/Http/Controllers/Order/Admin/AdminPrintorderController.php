<?php

namespace App\Http\Controllers\Order\Admin;

use App\Domain\Order\OrderDetailRenderer;
use App\Domain\Order\OrderLookups;
use App\Domain\Order\OrderQueries;
use App\Domain\Order\Php74;
use App\Domain\Order\PrintOrder;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Order\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * พิมพ์รายการสั่งซื้อ (SR เท่านั้น): _admin/_printorder/{index,grid,form,load_detail,print,save_print}.php
 * สิทธิ์ตามไฟล์เดิม: index/grid = admin_printorder, form/load_detail/print = admin_order, save_print = admin_receipt
 * ข้อมูล = ใบสั่งซื้อสถานะ 3 (สั่งซื้อสำเร็จ) + สถานะการพิมพ์ bill_product.is_print, เลขใบ bill_product.run_id
 */
class AdminPrintorderController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_printorder/grid';

    /** GET /admin/printorder, /admin/printorder/status/{status} — _admin/_printorder/index.php */
    public function index(Request $request): View
    {
        return view('order.printorder.index', ['status' => strip_tags(trim((string) ($request->route('status') ?? '')))]);
    }

    /** POST /_admin/_printorder/grid — ไม่มีสิทธิ์ admin_printorder = ตอบว่าง (exit เงียบแบบเดิม) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_printorder')) {
            return response('');
        }
        $p = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $term = $p[0];
        $q = OrderQueries::base()
            ->where(fn ($w) => $w->where('user.code', 'like', OrderQueries::like($term))
                ->orWhere('bill_product.id', 'like', OrderQueries::like(str_replace(OrderLookups::prefix('order'), '', $term))))
            ->where('bill_product.status', 3);
        // เดิม $txtSearch[1] != "-2" (ไม่มีค่า = null != "-2" → is_print = '' = 0)
        if (Php74::neq($p[1] ?? null, '-2')) {
            $q->where('bill_product.is_print', $p[1] ?? '');
        }
        $rows = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = OrderLookups::offset($page);
        $items = $offset === null ? [] : (clone $q)->select('bill_product.*', 'user.code')
            ->orderBy('bill_product.who_did_this_3_date')->orderBy('bill_product.id')
            ->offset($offset)->limit(10)->get()->all();
        $shops = OrderQueries::shopCounts(array_map(fn ($r) => (int) $r->id, $items));

        return response(view('order.printorder.grid', [
            'ajaxpage' => self::AJAX,
            'rows' => $rows,
            'items' => $items,
            'shops' => $shops,
            'prefix' => OrderLookups::prefix('order'),
            'search' => $php[0],
            'status' => $php[2] ?? null,
            'page' => $page,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** GET /admin/printorder/{id}[/{div}] — _admin/_printorder/form.php (สิทธิ์ admin_order; ไม่พบใบ = ค่าว่างแบบเดิม) */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $data = Php74::row(OrderQueries::base()->where('bill_product.id', $id)->where('bill_product.is_delete', 0)
            ->first(['bill_product.*', 'user.code']));
        $editLast = DB::table('user')->where('id', $data['mod_by'] ?? '')->first(['id', 'code']);

        return view('order.printorder.form', [
            'id' => $id,
            'div' => strip_tags(trim((string) ($request->route('div') ?? ''))),
            'data' => $data,
            'prefix' => OrderLookups::prefix('order'),
            'editLast' => $editLast,
        ]);
    }

    /** POST /_admin/_printorder/load_detail — ตารางสินค้าแบบอ่านอย่างเดียว (สิทธิ์ admin_order แบบเดิม) */
    public function loadDetail(Request $request): Response
    {
        $id = (int) Php74::num($this->in($request, 'id'));

        return response((new OrderDetailRenderer)->admin($id, true));
    }

    /** GET /_admin/_printorder/print?id= — หน้าพิมพ์ (print_header ของ SR + ใบ + window.print()) */
    public function print(Request $request): Response
    {
        $header = view('order.print_header')->render();
        if (! perm('admin_order')) {
            return response($header.Alerts::permission('admin_order'));
        }
        $id = (int) LegacyInput::query($request, 'id', 0);
        $data = Php74::row(OrderQueries::base()->where('bill_product.id', $id)->where('bill_product.is_delete', 0)
            ->first(['bill_product.*', 'user.code']));
        $editLast = DB::table('user')->where('id', $data['mod_by'] ?? '')->value('code');
        $created = (string) ($data['create_date'] ?? '');
        $exp = explode(' ', $created);
        $workDate = ($created === '0000-00-00 00:00:00' || $created === '')
            ? '-<br />-'
            : Format::getthaidate($exp[0], true).' '.Format::gettime($exp[1] ?? '');

        return response($header.view('order.printorder.print', [
            'data' => $data,
            'runNo' => PrintOrder::runNo($data['run_id'] ?? null, $data['id'] ?? ''),
            'workDate' => $workDate,
            'prefix' => OrderLookups::prefix('order'),
            'editLast' => $editLast,
            // เดิม $id = $data['id'] — ไม่พบใบ = id ว่าง → ไม่มีรายการ
            'detailHtml' => (new OrderDetailRenderer)->print((int) ($data['id'] ?? 0)),
        ])->render());
    }

    /**
     * POST /_admin/_printorder/save_print — บันทึกสถานะการพิมพ์ (สิทธิ์ admin_receipt ตามไฟล์เดิม)
     * เดิมเขียน is_print = ค่าอะไรก็ได้ → รับเฉพาะ 0/1 (ปุ่มส่งแค่ 2 ค่านี้)
     */
    public function savePrint(Request $request): Response
    {
        $status = $this->in($request, 'status');
        if ($status === '0' || $status === '1') {
            DB::table('bill_product')->where('id', $this->in($request, 'id'))->update(['is_print' => $status]);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/printorder').'";'));
    }
}
