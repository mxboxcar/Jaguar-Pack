<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Reports\ExportBill;
use App\Domain\Reports\Php74;
use App\Domain\Reports\Rows;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * ลูกค้า: รายการส่งสินค้า (_export/index.php, grid.php, form.php, save_approved.php, save_delete.php)
 * ทุก query กรอง bill_payment.user = Legacy::userId()
 */
class ExportController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_export/grid';

    /** GET /export — สิทธิ์ export (route) */
    public function index(): View
    {
        return view('reports.export.index');
    }

    /** POST /_export/grid — เดิมตรวจ getPermission('payment') แล้ว exit() (ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('payment')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;

        $q = DB::table('bill_payment')
            ->leftJoin('shipping_type', 'bill_payment.shipping_type', '=', 'shipping_type.id')
            ->where('bill_payment.is_delete', 0)
            ->where('bill_payment.id', 'like', '%'.addcslashes($parts[0], '%_\\').'%')
            ->where('bill_payment.user', Legacy::userId());
        if (Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('bill_payment.status', $status);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Rows::offset($page);
        $items = $offset === null ? collect() : Rows::strAll($q->orderByDesc('bill_payment.id')->offset($offset)->limit(10)
            ->get(['bill_payment.*', 'shipping_type.name as ship_name']));

        return response(view('reports.export.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $php[0],
            'status' => isset($php[1]) ? $php[1] : null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => Rows::prefix('payment'),
        ])->render());
    }

    /** GET /export/{id} — _export/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $data = Rows::str(DB::table('bill_payment')->where('id', $id)->where('user', Legacy::userId())->where('is_delete', 0)->first());
        if ($id !== 0 && $data === null) {
            // เดิม getPermissionAlert_Page('payment') แล้ว exit
            return view('legacy.no-permission', ['module' => 'payment']);
        }
        $data ??= new \stdClass;
        $shipName = isset($data->shipping_type) ? DB::table('shipping_type')->where('id', $data->shipping_type)->value('name') : null;
        // เดิม query รายการด้วย payment_id = '$id' แม้ไม่มีบิล (id 0) — ผลเท่ากัน
        $details = ExportBill::details($id);

        return view('reports.export.form', [
            'id' => $id,
            'data' => $data,
            'shipName' => $shipName,
            'details' => $details,
            'totals' => ExportBill::totals($details),
            'rows' => ExportBill::trackingRows($details, false),
            'balance' => ExportBill::balanceBox($id, false),
            'prefix' => Rows::prefix('payment'),
        ]);
    }

    /**
     * POST /_export/save_approved — ยืนยันชำระด้วยเงินในระบบ (สถานะ 0 → 1 + ตัดเงิน)
     * ต่างจากเดิม: ทำใน transaction + lock แถวบิล (กดซ้ำ/พร้อมกันไม่ตัดเงินซ้ำ), บิลของคนอื่นไม่ทำอะไร
     * (เดิม: null == 0 เป็นจริงใน PHP 7.4 → INSERT balance 0 บาท "ชำระเงิน PAY-<id ของคนอื่น>" ให้คนที่กด)
     */
    public function saveApproved(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $error = DB::transaction(function () use ($uid, $id) {
            $data = DB::table('bill_payment')->where('id', $id)->where('user', $uid)->lockForUpdate()->first();
            $total = $data->total ?? null;
            if (Php74::num(Format::numberformatforsubmit(Lookup::getCurrentCash())) < Php74::num(Format::numberformatforsubmit($total))) {
                return Alerts::error('CloseLoading()', Lang::get('error_usingcash'));
            }
            if ($data === null || (string) $data->status !== '0') {
                return null;
            }
            DB::table('bill_payment')->where('id', $data->id)->where('user', $uid)
                ->update(['status' => '1', 'approved_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => (string) $uid, 'amount' => '-'.$total, 'detail' => 'ชำระเงิน '.Rows::prefix('payment').$data->id,
                'type' => 3, 'prefix' => (string) Lookup::getPrefixID('payment'), 'prefix_ref_id' => $data->id,
            ]);

            return null;
        });
        if ($error !== null) {
            return response($error);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('payment').'";', Lang::get('success_process_no').Rows::prefix('payment').Alerts::js($id)));
    }

    /**
     * POST /_export/save_delete — ยกเลิกบิล
     * ต่างจากเดิม: เฉพาะบิลของตัวเองที่สถานะ 0 (ยังไม่ตัดเงิน = ปุ่มยกเลิกในหน้าเดิม) หรือ 1 (ตัดเงินแล้ว รอตรวจ → คืนเงิน)
     * เดิม: UPDATE บิลด้วย id อย่างเดียว (ยกเลิกบิลของลูกค้าคนอื่นได้ + ปลด tracking ของบิลนั้น)
     *       และยกเลิก+คืนเงินบิลสถานะ 2/3 (ส่งของแล้ว) ได้
     */
    public function saveDelete(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $code = (string) (Legacy::user()->code ?? '');
        DB::transaction(function () use ($uid, $id, $remark, $code) {
            $data = DB::table('bill_payment')->where('id', $id)->where('user', $uid)->lockForUpdate()->first();
            if ($data === null || ! in_array((string) $data->status, ['0', '1'], true)) {
                return;
            }
            if ((string) $data->status !== '0') {
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => (string) $data->user, 'amount' => (string) $data->total, 'detail' => 'คืนเงิน '.Rows::prefix('payment').$data->id,
                    'type' => 3, 'prefix' => (string) Lookup::getPrefixID('payment'), 'prefix_ref_id' => $data->id,
                ]);
            }
            // calPoint() เดิมเป็นฟังก์ชันว่าง
            DB::table('bill_payment')->where('id', $data->id)->where('user', $uid)->update([
                'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.$code, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            DB::table('tracking')->where('payment_id', $data->id)->update(['payment_id' => '0']);
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('payment').'";', Lang::get('success_process_no').Rows::prefix('payment').Alerts::js($id)));
    }
}
