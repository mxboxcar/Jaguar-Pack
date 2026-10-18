<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Money\Money;
use App\Domain\Payment\PaymentLookups as P;
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
        $balance = $id === 0 && ! $admin ? collect() : P::strAll(DB::table('balance')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->where('balance.prefix', (string) Lookup::getPrefixID('alipay'))
            ->where('balance.prefix_ref_id', (string) $id)->where('balance.is_delete', 0)
            ->orderBy('balance.id')
            ->get(['balance.*', 'prefix.link']));
        $cash = Lookup::getCurrentCash();
        $rate = Lookup::getSetting('rate_alipay');

        return [
            'id' => $id,
            'data' => $data ?? (object) [],
            'prefix' => P::prefix('alipay'),
            'rate' => $rate,
            'cash' => $cash,
            // เดิม getCurrentCash() / getSetting('rate_alipay') — rate ว่าง/0 = DivisionByZero ใน 8.4 (7.4: warning + false) → ''
            'est' => Php74::num($rate) == 0 ? '' : Php74::num($cash) / Php74::num($rate),
            'balance' => $balance,
        ];
    }

    /** POST /_alipay/save — สร้างรายการใหม่ + ตัดเงิน (transaction + lock ต่อผู้ใช้) */
    public function save(Request $request): Response
    {
        if ($this->in($request, 'id') !== '') {
            return response(''); // เดิม: มี id = ไม่ทำอะไร ไม่ตอบอะไร
        }
        $uid = Legacy::userId();
        $total = str_replace('-', '', $this->in($request, 'total'));
        // เดิม str_replace(',', '') → "1,00" = 100 — รับ , เฉพาะตัวคั่นหลักพัน
        if (Money::ambiguousComma($total)) {
            return response(Alerts::error('CloseLoading()', Money::BAD_AMOUNT));
        }
        $total = str_replace(',', '', $total);
        $rate = Lookup::getSetting('rate_alipay');
        $usecash = Php74::num($total) * Php74::num($rate);
        $file = $this->in($request, 'upload_file');

        return DB::transaction(function () use ($request, $uid, $total, $rate, $usecash, $file) {
            DB::table('user')->where('id', $uid)->lockForUpdate()->value('id');
            if (Php74::gt($usecash, Lookup::getCurrentCash())) {
                return response(Alerts::error('CloseLoading();', Lang::get('no_remain_cash')));
            }
            $id = DB::table('bill_alipay')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'taobao_id' => $this->in($request, 'taobao_id'), 'taobao_password' => $this->in($request, 'taobao_password'),
                'alipay_id' => $this->in($request, 'alipay_id'), 'alipay_password' => $this->in($request, 'alipay_password'),
                'password' => $this->in($request, 'password_alipay'), 'total' => $total, 'rate' => (string) $rate,
                'usecash' => P::s($usecash), 'remark' => $this->in($request, 'remark'), 'status' => 1, 'cancel_remark' => '',
                'user' => (string) $uid, 'attachment' => $file,
            ]);
            $js = $file !== '' ? TempUpload::jsIfExists($file, '	              ') : '';
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => (string) $uid, 'amount' => '-'.P::s($usecash), 'detail' => P::prefix('alipay').$id,
                'type' => 6, 'prefix' => (string) Lookup::getPrefixID('alipay'), 'prefix_ref_id' => (string) $id,
            ]);

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
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => (string) $uid, 'amount' => (string) $data->usecash, 'detail' => 'ยกเลิกรายการ '.P::prefix('alipay').$data->id,
                'type' => 6, 'prefix' => (string) Lookup::getPrefixID('alipay'), 'prefix_ref_id' => (string) $data->id,
            ]);
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('alipay').'";'));
    }
}
