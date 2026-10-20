<?php

namespace App\Http\Controllers\MoneyMove\Admin;

use App\Domain\MoneyMove\Lookups;
use App\Domain\MoneyMove\Mailer;
use App\Domain\Money\Money;
use App\Domain\MoneyMove\Php74;
use App\Domain\MoneyMove\TempUpload;
use App\Domain\MoneyMove\TransferRefund;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MoneyMove\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * โอนเงินไปจีน (แอดมิน): _admin/_transfer/*.php เดิม — สิทธิ์ admin_transfer
 * form.php ของ jl ตรวจ getPermission('transfer') (สิทธิ์ลูกค้า = IDOR) — SR แก้ในไฟล์เดิมแล้วเป็น admin_transfer (ใช้ตามนั้น)
 * SR: bill_transfer.usecash_cn (เงินหยวนที่ใช้) — ยกเลิกคืนทั้งบาทและหยวน, RollBack ตัดคืนทั้งสองกระเป๋า
 */
class AdminTransferController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_transfer/grid';

    /** GET /admin/transfer, /admin/transfer/status/{status} */
    public function index(Request $request): View
    {
        return view('money-move.transfer.index', [
            'admin' => true,
            'status' => $request->route('status') ?? (string) LegacyInput::query($request, 'status'),
        ]);
    }

    /** GET /admin/transfer/create (id=0), /admin/transfer/{id} */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = DB::table('bill_transfer')->leftJoin('user', 'bill_transfer.user', '=', 'user.id')
            ->where('bill_transfer.id', $id)->where('bill_transfer.is_delete', 0)
            ->first(['bill_transfer.*', 'user.code']);
        $balances = Lookups::balancesWithCn(Lookups::prefixId('transfer'), $id);

        return view('money-move.admin.transfer.form', [
            'id' => $id,
            'd' => (array) ($data ?? []),
            'balanceTable' => Lookups::balanceTable($balances, ['col' => 'col-xs-12 col-md-12', 'w1' => '190px', 'w2' => '140px', 'br' => '<br />', 'tr' => true]),
        ]);
    }

    /** POST /_admin/_transfer/grid — เดิมไม่มีสิทธิ์ = exit() เงียบ */
    public function grid(Request $request): Response
    {
        if (! perm('admin_transfer')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $like = '%'.addcslashes($s[0], '%_\\').'%';
        $q = DB::table('bill_transfer')->leftJoin('user', 'bill_transfer.user', '=', 'user.id')
            ->where('bill_transfer.is_delete', 0)
            ->where(fn ($w) => $w->where('bill_transfer.id', 'like', $like)->orWhere('user.code', 'like', $like));
        $st = $s[1] ?? null;
        if (Php74::neq($st, '-2') && Php74::neq($st, '')) {
            $q->where('bill_transfer.status', $st);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Lookups::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('bill_transfer.id')->offset($offset)->limit(10)->get(['bill_transfer.*', 'user.code']);

        return response(view('money-move.transfer.grid', [
            'admin' => true,
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $sp[0],
            'status' => $sp[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => Lookups::prefix('transfer'),
        ])->render());
    }

    /**
     * POST /_admin/_transfer/save — บันทึกหลักฐาน + status 2 + อีเมล
     * jl เดิมไม่ตรวจ status: บันทึกรายการที่ยกเลิก (คืนเงินแล้ว) เป็น 2 ได้ → ลูกค้าได้ทั้งเงินคืนและการโอน
     * SR แก้ในไฟล์เดิมแล้ว: เฉพาะ status 1 อื่นๆ (รวมไม่พบ) ErrorBox('รายการไม่พร้อมที่จะดำเนินการนี้', 'window.location.reload();')
     * ที่นี่: แบบ SR + ล็อกแถวใน transaction (กันชนกับ cancel/rollback ที่มาพร้อมกัน)
     */
    public function save(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $file = $this->in($request, 'upload_approved_file');
        if ($file !== '' && ! TempUpload::safeName($file)) {
            $file = '';
        }
        $result = DB::transaction(function () use ($uid, $id, $file, $request) {
            $data = DB::table('bill_transfer')->where('id', $id)->lockForUpdate()->first();
            if ($data === null || ! Php74::eq($data->status, 1)) {
                return null;
            }
            DB::table('bill_transfer')->where('id', $data->id)->update([
                'approved_remark' => $this->in($request, 'approved_remark'), 'approved_upload' => $file, 'status' => '2',
                'approved_date' => DB::raw('NOW()'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);

            return $data->id;
        });
        if ($result === null) {
            return response("ErrorBox('รายการไม่พร้อมที่จะดำเนินการนี้', 'window.location.reload();'); ");
        }
        $out = TempUpload::jsIfExists($file);
        Mailer::transferSuccess($result);

        return response($out.Alerts::success('window.location.href = "'.Legacy::href('admin/transfer').'";', Lang::get('success_process_no').Lookups::prefix('transfer').Alerts::js(e($id))));
    }

    /**
     * POST /_admin/_transfer/cancel — status 1 เท่านั้น: ยกเลิก + คืนเงิน + อีเมล
     * SR: คืนทั้งบาท (usecash → balance) และหยวน (usecash_cn → balance_cn) ให้เจ้าของรายการ — เดิมเขียนทั้งสองแถวเสมอ (ยอด 0 ก็เขียน)
     * jl port: คืนเท่าที่รายการถูกตัดอยู่จริง ไม่เกินยอดของรายการ (ยอด 0/คืนแล้ว = ไม่เขียนแถว)
     */
    public function cancel(Request $request): Response
    {
        $u = Legacy::user();
        $id = $this->in($request, 'id');
        $done = DB::transaction(function () use ($u, $id, $request) {
            $data = DB::table('bill_transfer')->where('id', $id)->lockForUpdate()->first();
            if ($data === null || ! Php74::eq($data->status, 1)) {
                return null;
            }
            Money::lockUser($data->user);
            DB::table('bill_transfer')->where('id', $data->id)->update([
                'status' => '-1', 'cancel_remark' => $this->in($request, 'cancel_remark').' ,ยกเลิกโดย '.$u->code,
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id,
            ]);
            TransferRefund::refund($data, $u->id, $data->user);

            return $data->id;
        });
        if ($done === null) {
            return response(Alerts::success('window.location.reload();', 'รายการถูกยืนยันไปแล้ว'));
        }
        Mailer::transferCancelled($done);

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/transfer').'";', Lang::get('success_process_no').Lookups::prefix('transfer').$done));
    }

    /**
     * POST /_admin/_transfer/rollback — กลับเป็น status 1
     *  status 2 : ล้างหลักฐาน/หมายเหตุ (ไม่แตะเงิน — ลูกค้ายังถูกตัดเงินอยู่)
     *  status -1: ตัดเงินคืนให้ยอดสุทธิของรายการกลับเป็น -usecash (บาท) และ -usecash_cn (หยวน) แล้วล้างหมายเหตุ
     * เดิม: หักเงินจากแถวตัดเงินแรก (detail = TRF-<id>) ทั้ง balance และ balance_cn โดยไม่ตรวจยอดคงเหลือ และไม่ล็อกแถว (กดซ้ำ = หักซ้ำ)
     *       (SR: การยกเลิกคืนทั้งสองกระเป๋า → การหัก balance_cn ถูกต้องตามหลัก; jl คืนแค่ balance)
     * ใหม่ (เจ้าของสั่งแก้): ล็อกแถวรายการ + user; หักเท่าที่ยอดสุทธิขาดต่อกระเป๋า (ไม่หักซ้ำ), ยอดคงเหลือกระเป๋าใดไม่พอ = ปฏิเสธทั้งรายการ
     */
    public function rollback(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $error = DB::transaction(function () use ($uid, $id) {
            $data = DB::table('bill_transfer')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return null;
            }
            $no = Lookups::prefix('transfer');
            $pid = Lookups::prefixId('transfer');
            $reset = ['status' => '1', 'cancel_remark' => '', 'approved_remark' => '', 'approved_upload' => '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid];
            if (Money::statusIn($data->status, [Money::CANCELLED])) {
                Money::lockUser($data->user);
                // ยอดที่ต้องถูกตัดไว้สำหรับรายการรอโอน = usecash / usecash_cn; ตัดเฉพาะส่วนที่ขาด (สุทธิครบแล้ว = ไม่ตัดอีก)
                $due = [];
                foreach (['balance' => $data->usecash, 'balance_cn' => $data->usecash_cn ?? null] as $table => $amount) {
                    $d = round((float) $amount + Money::net($table, $pid, $data->id), 2);
                    if ($d <= 0) {
                        continue;
                    }
                    if (Money::cash($data->user, $table) < $d) {
                        return ($table === 'balance' ? 'ยอดเงินคงเหลือของลูกค้าไม่พอ' : 'ยอดเงินหยวนคงเหลือของลูกค้าไม่พอ').'สำหรับ RollBack (ต้องการ '.Money::fmt($d).')';
                    }
                    $due[$table] = $d;
                }
                foreach ($due as $table => $d) {
                    DB::table($table)->insert([
                        'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                        'user' => $data->user, 'amount' => '-'.Money::fmt($d), 'detail' => 'Rollback '.$no.$data->id, 'type' => 5,
                        'prefix' => (string) ($pid ?? ''), 'prefix_ref_id' => $data->id,
                    ]);
                }
                DB::table('bill_transfer')->where('id', $data->id)->update($reset);
            } elseif (Money::statusIn($data->status, [Money::DONE])) {
                DB::table('bill_transfer')->where('id', $data->id)->update($reset);
            }

            return null;
        });
        if ($error !== null) {
            return response(Money::refuse($error));
        }

        return response(Alerts::success('window.location.reload(); '));
    }

    /** email_success.php / email_cancel.php เปิดตรง (เดิมไม่ต้องล็อกอิน, $email_id ว่าง) — ต้องมีสิทธิ์ และไม่ส่งอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
