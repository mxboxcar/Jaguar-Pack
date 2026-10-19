<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Money\Money;
use App\Domain\Payment\PaymentLookups as P;
use App\Domain\Payment\PinBox;
use App\Domain\Payment\Php74;
use App\Domain\Payment\TempUpload;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Lookup;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * เติมอลีเพล (ลูกค้า): _alipay/*.php เดิม — ทุก query กรอง user = ผู้ที่ล็อกอิน
 */
class AlipayController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_alipay/grid';

    /** GET /alipay, /alipay/status/{status} — _alipay/index.php */
    public function index(Request $request): View
    {
        return view('payment.alipay.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** POST /_alipay/grid — _alipay/grid.php (ไม่มีสิทธิ์ = ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('alipay')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;
        $q = DB::table('bill_alipay')->where('is_delete', 0)
            ->where('id', 'like', '%'.addcslashes($parts[0], '%_\\').'%')
            ->where('user', Legacy::userId());
        if (Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('status', $status);
        }

        return response(self::gridView($request, $q, $parts, $php, false));
    }

    /** grid ของลูกค้า/แอดมิน (HTML เดียวกัน ต่างที่คอลัมน์รหัสลูกค้าและลิงก์) */
    public static function gridView(Request $request, Builder $q, array $parts, array $php, bool $admin): string
    {
        $total = (clone $q)->count();
        $page = LegacyInput::postPhp($request, 'page');
        $page = is_scalar($page) ? (string) $page : '';
        $offset = P::offset($page);
        $items = $offset === null ? collect() : P::strAll($q->orderByDesc('bill_alipay.id')->offset($offset)->limit(10)->get());

        return view('payment.alipay.grid', [
            'admin' => $admin,
            'ajaxpage' => $admin ? '_admin/_alipay/grid' : self::AJAX,
            'items' => $items,
            'search' => $php[0],
            'status' => isset($parts[1]) ? ($php[1] ?? '') : null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => implode('|', $php),
            'prefix' => P::prefix('alipay'),
        ])->render();
    }

    /** GET /alipay/create (id = 0), /alipay/{id} — _alipay/form.php */
    public function form(Request $request): View|Response
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = P::str(DB::table('bill_alipay')->where('id', $id)->where('is_delete', 0)->where('user', Legacy::userId())->first());
        if ($id !== 0 && $data === null) {
            return response()->view('legacy.no-permission', ['module' => 'alipay']);
        }

        return view('payment.alipay.form', self::formData($id, $data, false));
    }

    /** ข้อมูลของ form.php (ลูกค้า/แอดมิน) */
    public static function formData(int $id, ?object $data, bool $admin): array
    {
        // SR: แถว balance (บาท) ตามด้วยแถว balance_cn (หยวน — แสดงด้วยสัญลักษณ์ ¥) ของรายการนี้
        $rows = fn (string $table) => P::strAll(DB::table($table)
            ->leftJoin('prefix', $table.'.prefix', '=', 'prefix.id')
            ->where($table.'.prefix', (string) Lookup::getPrefixID('alipay'))
            ->where($table.'.prefix_ref_id', (string) $id)->where($table.'.is_delete', 0)
            ->orderBy($table.'.id')
            ->get([$table.'.*', 'prefix.link']))->each(fn ($r) => $r->types = $table);
        $balance = $id === 0 && ! $admin ? collect() : $rows('balance')->concat($rows('balance_cn'))->values();
        $cash = Lookup::getCurrentCash();
        $cashCn = Lookup::getCurrentCashCN();
        $rate = Lookup::getSetting('rate_alipay');

        return [
            'admin' => $admin,
            'id' => $id,
            'data' => $data ?? (object) [],
            'prefix' => P::prefix('alipay'),
            'rate' => $rate,
            'cash' => $cash,
            'cashCn' => $cashCn,
            // SR: (getCurrentCash() / getSetting('rate_alipay')) + getCurrentCashCN()
            // rate ว่าง/0 = DivisionByZero ใน 8.4 (7.4: warning + false → false + cn = cn)
            'est' => (Php74::num($rate) == 0 ? 0 : Php74::num($cash) / Php74::num($rate)) + Php74::num($cashCn),
            'balance' => $balance,
        ];
    }

    /**
     * POST /_alipay/save — สร้างรายการใหม่ + ตัดเงิน (transaction + lock ต่อผู้ใช้)
     * SR ต่างจาก jl: ถาม PIN จ่ายเงิน, ใช้เงินหยวนในระบบ (balance_cn) ก่อน ส่วนที่ขาดตัดจากเงินบาท (× เรท) — usecash_cn
     */
    public function save(Request $request): Response
    {
        // เดิม: ตรวจ PIN ก่อนดู id (มี id = ผ่าน PIN แล้วไม่ทำอะไร ไม่ตอบอะไร)
        if (($pin = PinBox::guard($request, Legacy::user())) !== null) {
            return response($pin);
        }
        if ($this->in($request, 'id') !== '') {
            return response('');
        }
        $uid = Legacy::userId();
        $total = str_replace('-', '', $this->in($request, 'total'));
        // เดิม str_replace(',', '') → "1,00" = 100 — รับ , เฉพาะตัวคั่นหลักพัน
        // SR: ยอดที่ไม่ใช่ตัวเลข (เดิม "abc" >= ยอดหยวน เทียบแบบ string แล้วคำนวณผิด) → ปฏิเสธเหมือนยอดกำกวม
        if (Money::ambiguousComma($total) || ! preg_match('/^[0-9]*\.?[0-9]*$/', str_replace(',', '', trim($total)))) {
            return response(Alerts::error('CloseLoading()', Money::BAD_AMOUNT));
        }
        $total = str_replace(',', '', $total);
        $rate = Lookup::getSetting('rate_alipay');
        $file = $this->in($request, 'upload_file');

        return DB::transaction(function () use ($request, $uid, $total, $rate, $file) {
            Money::lockUser($uid);
            $cashCn = Lookup::getCurrentCashCN();
            // เดิม: total >= ยอดหยวน → ใช้หยวนทั้งหมด ที่เหลือตัดบาท; ไม่งั้นใช้หยวนเท่ายอด
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

            if (Php74::gt($useCash, Lookup::getCurrentCash())) {
                return response(Alerts::error('CloseLoading()', Lang::get('no_remain_cash')));
            }
            $id = DB::table('bill_alipay')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'taobao_id' => $this->in($request, 'taobao_id'), 'taobao_password' => $this->in($request, 'taobao_password'),
                'alipay_id' => $this->in($request, 'alipay_id'), 'alipay_password' => $this->in($request, 'alipay_password'),
                'password' => $this->in($request, 'password_alipay'), 'total' => $total, 'rate' => (string) $rate,
                'usecash' => P::s($useCash), 'remark' => $this->in($request, 'remark'), 'status' => 1, 'cancel_remark' => '',
                'user' => (string) $uid, 'attachment' => $file, 'usecash_cn' => P::s($useBalance),
            ]);
            $js = $file !== '' ? TempUpload::jsIfExists($file, '	              ') : '';
            if (Php74::num($useCash) != 0) {
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => (string) $uid, 'amount' => '-'.P::s($useCash), 'detail' => P::prefix('alipay').$id,
                    'type' => 6, 'prefix' => (string) Lookup::getPrefixID('alipay'), 'prefix_ref_id' => (string) $id,
                ]);
            }
            if (Php74::num($useBalance) != 0) {
                DB::table('balance_cn')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => (string) $uid, 'amount' => '-'.P::s($useBalance), 'detail' => P::prefix('alipay').$id,
                    'type' => '6', 'prefix' => (string) Lookup::getPrefixID('alipay'), 'prefix_ref_id' => (string) $id,
                ]);
            }

            return response($js.Alerts::success('window.location.href = "'.Legacy::href('alipay').'";', Lang::get('success_process_no').P::prefix('alipay').$id));
        });
    }

    /** POST /_alipay/cancel — ลูกค้ายกเลิก + คืนเงิน */
    public function cancel(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($uid, $id) {
            // เดิม: อ่านบิลโดยไม่กรองเจ้าของ แล้วคืนเงิน usecash ของบิลนั้นให้ "ผู้ที่กด" (บิลของคนอื่นก็ได้ กดซ้ำได้เรื่อยๆ)
            // และยอมทุกสถานะที่ไม่ใช่ -1 (รวม 2 = เติมให้แล้ว) — ที่นี่เฉพาะบิลของตัวเอง สถานะ 1 (= ปุ่มยกเลิกในหน้าเดิม)
            $data = DB::table('bill_alipay')->where('id', $id)->where('user', $uid)->lockForUpdate()->first();
            if ($data === null || (string) $data->status !== '1') {
                return;
            }
            DB::table('bill_alipay')->where('id', $data->id)->where('user', $uid)->update([
                'status' => '-1', 'cancel_remark' => 'ยกเลิกโดย '.(Legacy::user()->code ?? ''), 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            // SR: คืนเฉพาะส่วนที่ไม่เป็น 0 — บาท (usecash) และหยวน (usecash_cn)
            foreach (['balance' => $data->usecash, 'balance_cn' => $data->usecash_cn ?? null] as $table => $amount) {
                if (Php74::num($amount) == 0) {
                    continue;
                }
                DB::table($table)->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => (string) $uid, 'amount' => (string) $amount, 'detail' => 'ยกเลิกรายการ '.P::prefix('alipay').$data->id,
                    'type' => 6, 'prefix' => (string) Lookup::getPrefixID('alipay'), 'prefix_ref_id' => (string) $data->id,
                ]);
            }
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('alipay').'";'));
    }
}
