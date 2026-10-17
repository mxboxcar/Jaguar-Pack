<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Domain\Payment\PaymentLookups as P;
use App\Domain\Payment\PaymentMailer;
use App\Domain\Payment\Php74;
use App\Domain\Payment\TempUpload;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\AlipayController;
use App\Http\Controllers\Payment\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * เติมอลีเพล (แอดมิน): _admin/_alipay/*.php เดิม — งานที่ย้ายเงินห่อ transaction + lock แถวบิล
 */
class AdminAlipayController extends Controller
{
    use ReadsLegacyInput;

    /** GET /admin/alipay, /admin/alipay/status/{status} */
    public function index(Request $request): View
    {
        return view('payment.admin_alipay.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** POST /_admin/_alipay/grid (ไม่มีสิทธิ์ = ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_alipay')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;
        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        $withStatus = Php74::neq($status, '-2') && Php74::neq($status, '');
        // เดิม: WHERE is_delete = 0 AND (id LIKE ..) OR user.code LIKE .. [AND status = ..] — ลำดับ AND/OR ตามเดิม
        $q = DB::table('bill_alipay')->leftJoin('user', 'bill_alipay.user', '=', 'user.id')
            ->select(['bill_alipay.*', 'user.code'])
            ->where(fn ($w) => $w->where(fn ($a) => $a->where('bill_alipay.is_delete', 0)->where('bill_alipay.id', 'like', $like))
                ->orWhere(function ($o) use ($like, $withStatus, $status) {
                    $o->where('user.code', 'like', $like);
                    if ($withStatus) {
                        $o->where('bill_alipay.status', $status);
                    }
                }));

        return response(AlipayController::gridView($request, $q, $parts, $php, true));
    }

    /** GET /admin/alipay/create (id 0), /admin/alipay/{id} — เดิมไม่ตรวจว่ามีบิลจริง (แสดงฟอร์มว่าง) */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = P::str(DB::table('bill_alipay')->leftJoin('user', 'bill_alipay.user', '=', 'user.id')
            ->where('bill_alipay.id', $id)->where('bill_alipay.is_delete', 0)->first(['bill_alipay.*', 'user.code']));

        return view('payment.admin_alipay.form', AlipayController::formData($id, $data, true));
    }

    /** POST /_admin/_alipay/save — ยืนยันการเติม (status 2) + หลักฐาน + อีเมล */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $upload = $this->in($request, 'upload_file');
        // เดิม UPDATE ทุกสถานะ (ปุ่มบันทึกแสดงทุกสถานะ): ยืนยันบิลที่ยกเลิก/คืนเงินแล้วเป็นสถานะ 2 ได้ แล้ว rollback (2 → 1 ไม่ตัดเงิน)
        // + ลูกค้ายกเลิก = คืนเงินซ้ำ → ไม่ยอมบิลที่ยกเลิกแล้ว (-1) / ไม่มีอยู่, และล็อกแถวกันชนกับ cancel/rollback
        $billId = DB::transaction(function () use ($request, $id, $upload) {
            $data = DB::table('bill_alipay')->where('id', $id)->lockForUpdate()->first();
            if ($data === null || Php74::eq((string) $data->status, -1)) {
                return null;
            }
            DB::table('bill_alipay')->where('id', $data->id)->update([
                'approved_remark' => $this->in($request, 'approved_remark'), 'approved_upload' => $upload, 'status' => '2',
                'approved_date' => DB::raw('NOW()'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
            ]);

            return (string) $data->id;
        });
        if ($billId === null) {
            return response(Alerts::error('CloseLoading()', 'รายการนี้ถูกยกเลิกไปแล้ว'));
        }
        $js = $upload !== '' ? TempUpload::jsIfExists($upload, "         \t\t") : '';
        if (! config('legacy.debug_mail')) {
            PaymentMailer::alipaySuccess($billId);
        }

        return response($js.Alerts::success('window.location.href = "'.Legacy::href('admin/alipay').'";', Lang::get('success_process_no').P::prefix('alipay').Alerts::js(e($id))));
    }

    /** POST /_admin/_alipay/cancel — ยกเลิก (เฉพาะสถานะ 1) + คืนเงินให้เจ้าของบิล */
    public function cancel(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $done = DB::transaction(function () use ($id, $remark) {
            $data = DB::table('bill_alipay')->where('id', $id)->lockForUpdate()->first();
            if ($data === null || ! Php74::eq((string) $data->status, 1)) {
                return false;
            }
            $uid = Legacy::userId();
            DB::table('bill_alipay')->where('id', $data->id)->update([
                'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.(Legacy::user()->code ?? ''),
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => (string) $data->user, 'amount' => (string) $data->usecash, 'detail' => 'ยกเลิกรายการ '.P::prefix('alipay').$data->id,
                'type' => 6, 'prefix' => (string) Lookup::getPrefixID('alipay'), 'prefix_ref_id' => (string) $data->id,
            ]);

            return true;
        });
        if (! $done) {
            return response(Alerts::success('window.location.reload();', 'รายการถูกยืนยันไปแล้ว'));
        }
        $js = config('legacy.debug_mail') ? ''
            : "$.ajax({ type: 'POST', async: true, data : 'id=".Alerts::js($id)."', url: rooturl + '_admin/_alipay/email_cancel', success: function(data) { } });";

        return response($js.Alerts::success('window.location.href = "'.Legacy::href('admin/alipay').'";', Lang::get('success_process_no').P::prefix('alipay').Alerts::js(e($id))));
    }

    /** POST /_admin/_alipay/rollback — ย้อนสถานะ -1/2 → 1 (กรณี -1: ตัดเงินคืนตามแถว balance/balance_cn เดิม) */
    public function rollback(Request $request): Response
    {
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($id) {
            $data = DB::table('bill_alipay')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return;
            }
            $uid = Legacy::userId();
            $reset = ['status' => '1', 'cancel_remark' => '', 'approved_remark' => '', 'approved_upload' => '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid];
            if ((string) $data->status === '-1') {
                $prefixId = (string) Lookup::getPrefixID('alipay');
                foreach (['balance', 'balance_cn'] as $table) {
                    $row = DB::table($table)->where('type', 6)->where('prefix', $prefixId)->where('prefix_ref_id', (string) $data->id)
                        ->where('is_delete', '0')->where('detail', P::prefix('alipay').$data->id)->orderBy('id')->first();
                    if ($row !== null) {
                        DB::table($table)->insert([
                            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                            'user' => (string) $row->user, 'amount' => '-'.str_replace('-', '', (string) $row->amount),
                            'detail' => 'Rollback '.P::prefix('alipay').$data->id, 'type' => 6, 'prefix' => $prefixId, 'prefix_ref_id' => (string) $data->id,
                        ]);
                    }
                }
                DB::table('bill_alipay')->where('id', $data->id)->update($reset);
            }
            if (Php74::eq((string) $data->status, 2)) {
                DB::table('bill_alipay')->where('id', $data->id)->update($reset);
            }
        });

        return response(Alerts::success('window.location.reload(); '));
    }

    /** email_cancel.php / email_success.php เปิดตรง — เดิมใช้ $email_id ที่ไม่ได้กำหนด → ไม่ส่งอะไรได้จริง */
    public function emailDirect(): Response
    {
        return response('');
    }
}
