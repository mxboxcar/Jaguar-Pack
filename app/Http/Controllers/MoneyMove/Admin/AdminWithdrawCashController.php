<?php

namespace App\Http\Controllers\MoneyMove\Admin;

use App\Domain\MoneyMove\Lookups;
use App\Domain\Money\Money;
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
 * ถอนเงินออกจากระบบ (แอดมิน): _admin/_withdrawcash/*.php เดิม — สิทธิ์ admin_withdrawcash
 * (รายการถูกสร้างโดยลูกค้าที่ _balance/save_withdraw ของกลุ่ม balance)
 * email_success.php / email_cancel.php ที่ไฟล์เดิม include ไม่มีอยู่จริง → ไม่เคยส่งอีเมล (ที่นี่ก็ไม่ส่ง)
 */
class AdminWithdrawCashController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_withdrawcash/grid';

    /** GET /admin/withdrawcash, /admin/withdrawcash/status/{status} */
    public function index(Request $request): View
    {
        return view('money-move.admin.withdrawcash.index', [
            'status' => $request->route('status') ?? (string) LegacyInput::query($request, 'status'),
        ]);
    }

    /** GET /admin/withdrawcash/{id} */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $data = DB::table('balance_withdraw')->leftJoin('user', 'balance_withdraw.user', '=', 'user.id')
            ->where('balance_withdraw.id', $id)->where('balance_withdraw.is_delete', 0)
            ->first(['balance_withdraw.*', 'user.code', 'user.id as user_id']);
        // เดิม: prefix = point หรือ withdrawcash (แถว typess = "balance" เสมอ → numberformat)
        $balances = Lookups::balances([Lookups::prefixId('point'), Lookups::prefixId('withdrawcash')], $id, true);

        return view('money-move.admin.withdrawcash.form', [
            'id' => $id,
            'd' => (array) ($data ?? []),
            'prefix' => Lookups::prefix('withdrawcash'),
            'balanceTable' => Lookups::balanceTable($balances, ['col' => 'col-xs-12 col-md-6', 'w1' => '130px', 'w2' => '80px', 'br' => '<br/>', 'tr' => true]),
        ]);
    }

    /** POST /_admin/_withdrawcash/grid — เดิมไม่มีสิทธิ์ = exit() เงียบ */
    public function grid(Request $request): Response
    {
        if (! perm('admin_withdrawcash')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $like = '%'.addcslashes($s[0], '%_\\').'%';
        $q = DB::table('balance_withdraw')->leftJoin('user', 'balance_withdraw.user', '=', 'user.id')
            ->where('balance_withdraw.is_delete', 0)
            ->where(fn ($w) => $w->where('balance_withdraw.id', 'like', $like)->orWhere('user.code', 'like', $like));
        $st = $s[1] ?? null;
        if (Php74::neq($st, '-2') && Php74::neq($st, '')) {
            $q->where('balance_withdraw.status', $st);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Lookups::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('balance_withdraw.id')->offset($offset)->limit(10)->get(['balance_withdraw.*', 'user.code']);

        return response(view('money-move.admin.withdrawcash.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $sp[0],
            'status' => $sp[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => Lookups::prefix('withdrawcash'),
        ])->render());
    }

    /**
     * POST /_admin/_withdrawcash/save_approved — บันทึกหลักฐาน; status 1 → 2 (2 = บันทึกหลักฐานซ้ำได้ ปุ่มแสดงทุกสถานะ)
     * เดิมไม่ตรวจ status -1: อนุมัติ (จ่ายเงินออก) รายการที่ยกเลิกและคืนเงินแล้วได้
     * ใหม่ (เจ้าของสั่งแก้): ไม่พบรายการ / status -1 / สถานะอื่น = ปฏิเสธ ไม่บันทึกอะไร
     */
    public function saveApproved(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $file = $this->in($request, 'approved_upload');
        if ($file !== '' && ! TempUpload::safeName($file)) {
            $file = '';
        }
        $error = DB::transaction(function () use ($id, $file, $request) {
            $data = DB::table('balance_withdraw')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return 'ไม่พบรายการ';
            }
            if (! Money::statusIn($data->status, [Money::PENDING, Money::DONE])) {
                return 'รายการนี้ถูกยกเลิกและคืนเงินแล้ว ไม่สามารถอนุมัติได้';
            }
            DB::table('balance_withdraw')->where('id', $data->id)->update([
                'approved_remark' => $this->in($request, 'approved_remark'), 'approved_upload' => $file, 'approved_date' => DB::raw('NOW()'),
            ]);
            if (Php74::neq($data->status, 2)) {
                DB::table('balance_withdraw')->where('id', $data->id)->update(['status' => '2', 'approved_date' => DB::raw('NOW()')]);
            }

            return null;
        });
        if ($error !== null) {
            return response(Money::refuse($error));
        }

        return response(TempUpload::jsIfExists($file, '	         		')
            .Alerts::success('window.location.href = "'.Legacy::href('admin/withdrawcash').'";', Lang::get('success_process_no').Lookups::prefix('withdrawpoint').Alerts::js(e($id))));
    }

    /**
     * POST /_admin/_withdrawcash/cancel — ยกเลิก + คืนเงิน
     * เดิมเช็ค $data['status'] != -1 ก่อนอ่าน $data (ตัวแปรว่าง → จริงเสมอ) = กดยกเลิกซ้ำได้เงินคืนซ้ำทุกครั้ง, id ที่ไม่มี = แถว balance ขยะ
     * และยกเลิก+คืนเงินรายการที่จ่ายออกไปแล้ว (status 2) ได้
     * ใหม่ (เจ้าของสั่งแก้): เฉพาะ status 1 (= ปุ่มยกเลิกในหน้า), ล็อกแถวรายการ + user, คืนเท่าที่ถูกตัดไว้จริง (กดซ้ำไม่คืนซ้ำ)
     */
    public function cancel(Request $request): Response
    {
        $u = Legacy::user();
        $id = $this->in($request, 'id');
        $error = DB::transaction(function () use ($u, $id, $request) {
            $data = DB::table('balance_withdraw')->where('id', $id)->lockForUpdate()->first();
            if ($data === null) {
                return 'ไม่พบรายการ';
            }
            if (Money::statusIn($data->status, [Money::DONE])) {
                return 'รายการนี้จ่ายเงินไปแล้ว ไม่สามารถยกเลิกได้';
            }
            if (! Money::statusIn($data->status, [Money::PENDING])) {
                return 'รายการนี้ถูกยกเลิกไปแล้ว';
            }
            Money::lockUser($data->user);
            DB::table('balance_withdraw')->where('id', $data->id)->update([
                'status' => '-1', 'cancel_remark' => $this->in($request, 'cancel_remark').' ,ยกเลิกโดย '.$u->code, 'approved_date' => DB::raw('NOW()'),
            ]);
            $refund = Money::refundable((float) $data->amount, Money::net('balance', Lookups::prefixId('withdrawcash'), $data->id));
            if ($refund > 0) {
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $u->id, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id, 'is_delete' => 0,
                    'user' => $data->user, 'amount' => Money::fmt($refund), 'detail' => 'ยกเลิก รายการถอนเงินออกจากระบบ '.Lookups::prefix('withdrawcash').$data->id,
                    'type' => 1, 'prefix' => (string) (Lookups::prefixId('withdrawcash') ?? ''), 'prefix_ref_id' => $data->id,
                ]);
            }

            return null;
        });
        if ($error !== null) {
            return response(Money::refuse($error));
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/withdrawcash').'";'));
    }
}
