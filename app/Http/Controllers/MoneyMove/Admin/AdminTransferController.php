<?php

namespace App\Http\Controllers\MoneyMove\Admin;

use App\Domain\MoneyMove\Lookups;
use App\Domain\MoneyMove\Mailer;
use App\Domain\MoneyMove\Php74;
use App\Domain\MoneyMove\TempUpload;
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
 * form.php เดิมตรวจ getPermission('transfer') (สิทธิ์ลูกค้า) → ลูกค้าทุกคนเปิด /admin/transfer/<id> ดูรายการของคนอื่นได้; ที่นี่ต้องมีทั้ง transfer และ admin_transfer
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
        $balances = Lookups::balances([Lookups::prefixId('transfer')], $id, false);

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

    /** POST /_admin/_transfer/save — บันทึกหลักฐาน + status 2 + อีเมล (เดิมไม่ตรวจ status — คงไว้) */
    public function save(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $file = $this->in($request, 'upload_approved_file');
        if ($file !== '' && ! TempUpload::safeName($file)) {
            $file = '';
        }
        DB::table('bill_transfer')->where('id', $id)->update([
            'approved_remark' => $this->in($request, 'approved_remark'), 'approved_upload' => $file, 'status' => '2',
            'approved_date' => DB::raw('NOW()'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
        ]);
        $out = TempUpload::jsIfExists($file);
        Mailer::transferSuccess($id);

        return response($out.Alerts::success('window.location.href = "'.Legacy::href('admin/transfer').'";', Lang::get('success_process_no').Lookups::prefix('transfer').Alerts::js(e($id))));
    }

    /** POST /_admin/_transfer/cancel — status 1 เท่านั้น: ยกเลิก + คืนเงิน usecash + อีเมล */
    public function cancel(Request $request): Response
    {
        $u = Legacy::user();
        $id = $this->in($request, 'id');
        $done = DB::transaction(function () use ($u, $id, $request) {
            $data = DB::table('bill_transfer')->where('id', $id)->lockForUpdate()->first();
            if ($data === null || ! Php74::eq($data->status, 1)) {
                return null;
            }
            DB::table('bill_transfer')->where('id', $data->id)->update([
                'status' => '-1', 'cancel_remark' => $this->in($request, 'cancel_remark').' ,ยกเลิกโดย '.$u->code,
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id,
            ]);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $u->id, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id, 'is_delete' => 0,
                'user' => $data->user, 'amount' => $data->usecash, 'detail' => 'ยกเลิกรายการ '.Lookups::prefix('transfer').$data->id, 'type' => 5,
                'prefix' => (string) (Lookups::prefixId('transfer') ?? ''), 'prefix_ref_id' => $data->id,
            ]);

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
     *  status -1: หักเงินคืน (ถ้ามีแถวตัดเงินเดิม detail = TRF-<id>) ทั้ง balance และ balance_cn แล้วล้างหมายเหตุ
     *  status 2 : ล้างหลักฐาน/หมายเหตุ (ไม่แตะเงิน)
     */
    public function rollback(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($uid, $id) {
            $data = DB::table('bill_transfer')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return;
            }
            $no = Lookups::prefix('transfer');
            $reset = ['status' => '1', 'cancel_remark' => '', 'approved_remark' => '', 'approved_upload' => '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid];
            if (Php74::eq($data->status, '-1')) {
                foreach (['balance', 'balance_cn'] as $table) {
                    // เดิม: prefix = 20 (ตายตัว), detail = getPrefix('transfer').$id
                    $b = DB::table($table)->where('type', 5)->where('prefix', 20)->where('prefix_ref_id', $data->id)
                        ->where('is_delete', '0')->where('detail', $no.$id)->orderBy('id')->first();
                    if ($b !== null) {
                        DB::table($table)->insert([
                            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                            'user' => $b->user, 'amount' => '-'.str_replace('-', '', (string) $b->amount), 'detail' => 'Rollback '.$no.$data->id, 'type' => 5,
                            'prefix' => (string) (Lookups::prefixId('transfer') ?? ''), 'prefix_ref_id' => $data->id,
                        ]);
                    }
                }
                DB::table('bill_transfer')->where('id', $data->id)->update($reset);
            }
            if (Php74::eq($data->status, 2)) {
                DB::table('bill_transfer')->where('id', $data->id)->update($reset);
            }
        });

        return response(Alerts::success('window.location.reload(); '));
    }

    /** email_success.php / email_cancel.php เปิดตรง (เดิมไม่ต้องล็อกอิน, $email_id ว่าง) — ต้องมีสิทธิ์ และไม่ส่งอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
