<?php

namespace App\Http\Controllers\MoneyMove;

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
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * โอนเงินไปจีน (ลูกค้า): _transfer/index.php, form.php, grid.php, save.php, cancel.php เดิม — สิทธิ์ transfer, เห็นเฉพาะของตัวเอง
 */
class TransferController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_transfer/grid';

    /** GET /transfer, /transfer/status/{status} */
    public function index(Request $request): View
    {
        return view('money-move.transfer.index', [
            'admin' => false,
            'status' => $request->route('status') ?? (string) LegacyInput::query($request, 'status'),
        ]);
    }

    /** GET /transfer/create (id=0), /transfer/{id} — เดิมไม่พบ/ของคนอื่น = หน้าว่าง (ไม่เด้ง) */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = DB::table('bill_transfer')->where('id', $id)->where('is_delete', 0)->where('user', Legacy::userId())->first();
        $balances = collect();
        // เดิม query balance ด้วย prefix_ref_id อย่างเดียว (ไม่ตรวจเจ้าของ) → /transfer/<id ของคนอื่น> เห็นรายการเงินของคนอื่น — ที่นี่แสดงเฉพาะเมื่อเป็นของตัวเอง
        if ($id !== 0 && $data !== null) {
            $balances = Lookups::balances([Lookups::prefixId('transfer')], $id, false);
        }

        return view('money-move.transfer.form', [
            'id' => $id,
            'd' => (array) ($data ?? []),
            'balanceTable' => $id !== 0 ? Lookups::balanceTable($balances, ['col' => 'col-xs-12 col-md-12', 'w1' => '190px', 'w2' => '140px', 'br' => '<br />', 'tr' => true]) : '',
        ]);
    }

    /** POST /_transfer/grid — เดิมไม่มีสิทธิ์ = exit() เงียบ */
    public function grid(Request $request): Response
    {
        if (! perm('transfer')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $q = DB::table('bill_transfer')->where('bill_transfer.is_delete', 0)
            ->where('bill_transfer.id', 'like', '%'.addcslashes($s[0], '%_\\').'%')
            ->where('user', Legacy::userId());
        $st = $s[1] ?? null;
        if (Php74::neq($st, '-2') && Php74::neq($st, '')) {
            $q->where('bill_transfer.status', $st);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Lookups::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('id')->offset($offset)->limit(10)->get(['bill_transfer.*']);

        return response(view('money-move.transfer.grid', [
            'admin' => false,
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
     * POST /_transfer/save — สร้างรายการ + ตัดเงิน (usecash = total × rate_transfer)
     * เดิม: เช็คยอดเงินแล้ว INSERT แยกกัน ไม่มี transaction (กดซ้ำเร็วๆ ใช้เงินเกินยอดได้) → ที่นี่ lock แถว user ใน transaction
     */
    public function save(Request $request): Response
    {
        $total = str_replace('-', '', $this->in($request, 'total'));
        // เดิม str_replace(',', '') → "1,00" = 100 — รับ , เฉพาะตัวคั่นหลักพัน
        if (Money::ambiguousComma($total)) {
            return response(Alerts::error('CloseLoading()', Money::BAD_AMOUNT));
        }
        $total = str_replace(',', '', $total);
        $rate = Lookup::getSetting('rate_transfer');
        $usecash = Php74::num($total) * Php74::num($rate);
        $cash = Php74::str($usecash);
        if (! Php74::eq($this->has($request, 'id') ? $this->in($request, 'id') : null, '')) {
            return response('');
        }
        $uid = Legacy::userId();
        $file = $this->in($request, 'upload_file');
        if ($file !== '' && ! TempUpload::safeName($file)) {
            $file = '';
        }

        $id = DB::transaction(function () use ($uid, $request, $total, $rate, $cash, $usecash, $file) {
            DB::table('user')->where('id', $uid)->lockForUpdate()->value('id');
            if ((float) Lookup::getCurrentCash() < $usecash) {
                return null;
            }
            $id = DB::table('bill_transfer')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'acc_name' => $this->in($request, 'acc_name'), 'acc_no' => $this->in($request, 'acc_no'),
                'acc_bank' => $this->in($request, 'acc_bank'), 'acc_branch' => $this->in($request, 'acc_branch'),
                'attachment' => $file, 'total' => $total, 'rate' => (string) ($rate ?? ''), 'usecash' => $cash,
                'remark' => $this->in($request, 'remark'), 'status' => 1, 'cancel_remark' => '', 'user' => $uid,
            ]);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => $uid, 'amount' => '-'.$cash, 'detail' => Lookups::prefix('transfer').$id, 'type' => 5,
                'prefix' => (string) (Lookups::prefixId('transfer') ?? ''), 'prefix_ref_id' => $id,
            ]);

            return $id;
        });
        if ($id === null) {
            return response(Alerts::error('CloseLoading();', Lang::get('no_remain_cash')));
        }

        return response(TempUpload::jsIfExists($file, '	                ')
            .Alerts::success('window.location.href = "'.Legacy::href('transfer').'";', Lang::get('success_process_no').Lookups::prefix('transfer').$id));
    }

    /**
     * POST /_transfer/cancel — ยกเลิกรายการของตัวเอง + คืนเงิน usecash
     * เดิม: UPDATE กรอง user แต่ INSERT คืนเงินไม่กรอง และเช็คแค่ status != -1 → ส่ง id ของคนอื่น/รายการที่โอนแล้ว
     * ได้เงิน usecash เข้าบัญชีตัวเองซ้ำได้ไม่จำกัด — ที่นี่คืนเงินเฉพาะรายการของตัวเองที่ status = 1 (ปุ่มยกเลิกเดิมแสดงเฉพาะ status 1)
     */
    public function cancel(Request $request): Response
    {
        $u = Legacy::user();
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($u, $id) {
            $row = DB::table('bill_transfer')->where('id', $id)->where('user', $u->id)->lockForUpdate()->first();
            if ($row === null || ! Php74::eq($row->status, 1)) {
                return;
            }
            Money::lockUser($u->id);
            DB::table('bill_transfer')->where('id', $row->id)->where('user', $u->id)->update([
                'status' => '-1', 'cancel_remark' => 'ยกเลิกโดย '.$u->code, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id,
            ]);
            // คืนเท่าที่รายการถูกตัดไว้จริง (ไม่เกิน usecash)
            $refund = Money::refundable((float) $row->usecash, Money::net('balance', Lookups::prefixId('transfer'), $row->id));
            if ($refund > 0) {
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $u->id, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id, 'is_delete' => 0,
                    'user' => $u->id, 'amount' => Money::fmt($refund), 'detail' => 'ยกเลิกรายการ '.Lookups::prefix('transfer').$row->id, 'type' => 5,
                    'prefix' => (string) (Lookups::prefixId('transfer') ?? ''), 'prefix_ref_id' => $row->id,
                ]);
            }
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('transfer').'";'));
    }
}
