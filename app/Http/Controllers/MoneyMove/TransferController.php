<?php

namespace App\Http\Controllers\MoneyMove;

use App\Domain\MoneyMove\Lookups;
use App\Domain\Money\Money;
use App\Domain\MoneyMove\Php74;
use App\Domain\MoneyMove\TempUpload;
use App\Domain\MoneyMove\TransferRefund;
use App\Domain\Payment\PinBox;
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
            $balances = Lookups::balancesWithCn(Lookups::prefixId('transfer'), $id);
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
     * POST /_transfer/save — สร้างรายการ + ตัดเงิน
     * SR ต่างจาก jl (แบบเดียวกับ _alipay/save ของ SR): ถาม PIN จ่ายเงิน (PinBox::guard), ใช้เงินหยวนในระบบ (balance_cn) ก่อน
     * ส่วนที่ขาด × rate_transfer ตัดจากเงินบาท — เก็บ bill_transfer.usecash (บาท) + usecash_cn (หยวน), เขียน balance/balance_cn เฉพาะยอดที่ไม่เป็น 0
     * เดิม: เช็คยอดเงินแล้ว INSERT แยกกัน ไม่มี transaction (กดซ้ำเร็วๆ ใช้เงินเกินยอดได้) → ที่นี่ lock แถว user ใน transaction แล้วอ่านยอดทั้งสองกระเป๋าภายใน lock
     */
    public function save(Request $request): Response
    {
        // เดิม: ตรวจ PIN ก่อนดู id (มี id = ผ่าน PIN แล้วไม่ทำอะไร ไม่ตอบอะไร)
        if (($pin = PinBox::guard($request, Legacy::user())) !== null) {
            return response($pin);
        }
        if (! Php74::eq($this->has($request, 'id') ? $this->in($request, 'id') : null, '')) {
            return response('');
        }
        $total = str_replace('-', '', $this->in($request, 'total'));
        // เดิม str_replace(',', '') → "1,00" = 100 — รับ , เฉพาะตัวคั่นหลักพัน
        // SR: ยอดที่ไม่ใช่ตัวเลข (เดิม "abc" >= ยอดหยวน เทียบแบบ string → ตัดหยวนทั้งหมดแต่ไม่ได้รายการที่มีค่า) → ปฏิเสธเหมือนยอดกำกวม
        if (Money::ambiguousComma($total) || ! preg_match('/^[0-9]*\.?[0-9]*$/', str_replace(',', '', trim($total)))) {
            return response(Alerts::error('CloseLoading()', Money::BAD_AMOUNT));
        }
        $total = str_replace(',', '', $total);
        $rate = Lookup::getSetting('rate_transfer');
        $uid = Legacy::userId();
        $file = $this->in($request, 'upload_file');
        if ($file !== '' && ! TempUpload::safeName($file)) {
            $file = '';
        }

        $id = DB::transaction(function () use ($uid, $request, $total, $rate, $file) {
            Money::lockUser($uid);
            $cashCn = Lookup::getCurrentCashCN();
            // เดิม: total >= ยอดหยวน → ใช้หยวนทั้งหมด ที่เหลือ × เรท ตัดบาท; ไม่งั้นใช้หยวนเท่ายอด (ไม่ตัดบาท)
            // ยอดหยวนติดลบ: เดิม use_balance ติดลบ → แถว balance_cn '--x' (MySQL เก็บ 0) แต่ส่วนบาทถูกบวกเพิ่ม = ลูกค้าเสียเงิน → ถือว่ามีหยวน 0
            $cn = max(0, Php74::num($cashCn));
            if (Php74::num($total) >= $cn) {
                $useCash = Php74::num($total) - $cn;
                $useBalance = $cn === 0 ? '0' : (string) $cashCn;
            } else {
                $useCash = 0;
                $useBalance = $total;
            }
            $useCash = $useCash * Php74::num($rate);
            // เดิม: (string) use_cash != (string) getCurrentCash() แล้ว use_cash > getCurrentCash() → ไม่พอ (เท่ากันพอดี = ผ่าน)
            if (Php74::gt($useCash, Lookup::getCurrentCash())) {
                return null;
            }
            $cash = Php74::str($useCash);
            $id = DB::table('bill_transfer')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'acc_name' => $this->in($request, 'acc_name'), 'acc_no' => $this->in($request, 'acc_no'),
                'acc_bank' => $this->in($request, 'acc_bank'), 'acc_branch' => $this->in($request, 'acc_branch'),
                'attachment' => $file, 'total' => $total, 'rate' => (string) ($rate ?? ''), 'usecash' => $cash,
                'remark' => $this->in($request, 'remark'), 'status' => 1, 'cancel_remark' => '', 'user' => $uid,
                'usecash_cn' => (string) $useBalance,
            ]);
            $base = [
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => $uid, 'detail' => Lookups::prefix('transfer').$id, 'type' => 5,
                'prefix' => (string) (Lookups::prefixId('transfer') ?? ''), 'prefix_ref_id' => $id,
            ];
            if (Php74::num($useCash) != 0) {
                DB::table('balance')->insert($base + ['amount' => '-'.$cash]);
            }
            if (Php74::num($useBalance) != 0) {
                DB::table('balance_cn')->insert($base + ['amount' => '-'.$useBalance]);
            }

            return $id;
        });
        if ($id === null) {
            return response(Alerts::error('CloseLoading()', Lang::get('no_remain_cash')));
        }

        return response(TempUpload::jsIfExists($file, '	                ')
            .Alerts::success('window.location.href = "'.Legacy::href('transfer').'";', Lang::get('success_process_no').Lookups::prefix('transfer').$id));
    }

    /**
     * POST /_transfer/cancel — ยกเลิกรายการของตัวเอง + คืนเงิน (บาท usecash → balance, หยวน usecash_cn → balance_cn)
     * เดิม (ก่อน hotfix 2026-10-18): UPDATE กรอง user แต่ INSERT คืนเงินไม่กรอง และเช็คแค่ status != -1 → ส่ง id ของคนอื่น/รายการที่โอนแล้ว
     * ได้เงินเข้าบัญชีตัวเองซ้ำได้ไม่จำกัด — hotfix ของ SR: เฉพาะรายการของตัวเองที่ status = 1 คืนครั้งเดียว (= ที่นี่)
     * jl port: คืนเท่าที่รายการถูกตัดอยู่จริง (ไม่เกินยอดของรายการ) แยกต่อกระเป๋า — ยอด 0 ไม่เขียนแถว (เหมือน SR)
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
            TransferRefund::refund($row, $u->id, $u->id);
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('transfer').'";'));
    }
}
