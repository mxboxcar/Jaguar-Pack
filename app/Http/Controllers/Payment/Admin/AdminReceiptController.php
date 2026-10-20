<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Domain\Payment\PaymentLookups as P;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\Concerns\ReadsLegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * รายการใบเสร็จ (แอดมิน) — SR เท่านั้น: _admin/_receipt/{index,grid}.php เดิม (สิทธิ์ admin_receipt)
 * ใบเสร็จ = bill_payment ที่ receipt_id != '' (ออก/พิมพ์ใบเสร็จอยู่ที่ _admin/_export/receipt*.php ของกลุ่ม reports)
 * admin/receipt/{id} และ admin/receipt/create ชี้ไป _admin/_receipt/form.php ที่ไม่มีในเว็บเดิม → ไม่พอร์ต (404 → หน้าแรก)
 */
class AdminReceiptController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_receipt/grid';

    /** GET /admin/receipt, /admin/receipt/status/{status} — _admin/_receipt/index.php */
    public function index(Request $request): View
    {
        return view('payment.admin_receipt.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** POST /_admin/_receipt/grid — ไม่มีสิทธิ์ = ตอบว่างเหมือนเดิม (exit) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_receipt')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        // เดิม: id LIKE / id LIKE / receipt_id LIKE / user.code LIKE '%x%' (LEFT JOIN user) — สถานะ (ส่วนหลัง |) ถูก comment ไว้
        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        $q = DB::table('bill_payment')->leftJoin('user', 'user.id', '=', 'bill_payment.user')
            ->where('bill_payment.is_delete', 0)->where('bill_payment.receipt_id', '!=', '')
            ->where(fn ($w) => $w->where('bill_payment.id', 'like', $like)->orWhere('bill_payment.receipt_id', 'like', $like)
                ->orWhere('user.code', 'like', $like));
        $page = $this->php($request, 'page');
        $total = (clone $q)->count();
        $offset = P::offset($page);
        // เดิม ORDER BY receipt_id DESC LIMIT (MakePaging)
        $items = $offset === null ? collect() : P::strAll($q->orderByDesc('bill_payment.receipt_id')->offset($offset)->limit(10)
            ->get(['bill_payment.*', 'user.code']));

        return response(view('payment.admin_receipt.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $php[0],
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => P::prefix('payment'),
        ])->render());
    }
}
