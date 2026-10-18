<?php

namespace App\Http\Controllers\MoneyMove;

use App\Domain\MoneyMove\Lookups;
use App\Domain\Money\Money;
use App\Domain\MoneyMove\Php74;
use App\Domain\MoneyMove\TempUpload;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MoneyMove\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แจ้งเติมเงิน (ลูกค้า): _refill/index.php, form.php, grid.php, save.php เดิม — สิทธิ์ refill, เห็นเฉพาะของตัวเอง
 */
class RefillController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_refill/grid';

    /** GET /refill, /refill/status/{status} */
    public function index(Request $request): View
    {
        return view('money-move.refill.index', [
            'status' => $request->route('status') ?? (string) LegacyInput::query($request, 'status'),
        ]);
    }

    /** GET /refill/create (id=0), /refill/{id} */
    public function form(Request $request): Response|View
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = DB::table('refill')->where('id', $id)->where('is_delete', 0)->where('user', Legacy::userId())->first();
        // เดิม: ไม่พบ (หรือเป็นของคนอื่น) และ id != "0" → getPermissionAlert_Page('refill')
        if ($data === null && $id !== 0) {
            return response()->view('legacy.no-permission', ['module' => 'refill']);
        }
        $balances = collect();
        if ($id !== 0) {
            // เดิมไม่กรอง is_delete และไม่เปิด <tr> ต่อแถว
            $balances = Lookups::balances([Lookups::prefixId('refill')], $id, false, false);
        }

        return view('money-move.refill.form', [
            'id' => $id,
            'd' => (array) ($data ?? []),
            'prefix' => Lookups::prefix('refill'),
            'balanceTable' => $id !== 0 ? Lookups::balanceTable($balances, ['col' => 'col-xs-12', 'w1' => '190px', 'w2' => '140px', 'br' => '<br />', 'tr' => false]) : '',
        ]);
    }

    /** POST /_refill/grid — เดิมไม่มีสิทธิ์ = exit() เงียบ */
    public function grid(Request $request): Response
    {
        if (! perm('refill')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $q = DB::table('refill')->where('refill.is_delete', 0)
            ->where('refill.id', 'like', '%'.addcslashes($s[0], '%_\\').'%')
            ->where('user', Legacy::userId());
        $st = $s[1] ?? null;
        if (Php74::neq($st, '-2') && Php74::neq($st, '')) {
            $q->where('refill.status', $st);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Lookups::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('id')->offset($offset)->limit(10)->get(['refill.*']);

        return response(view('money-move.refill.grid', [
            'admin' => false,
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $sp[0],
            'status' => $sp[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => Lookups::prefix('refill'),
        ])->render());
    }

    /** POST /_refill/save — สร้างรายการใหม่ (status 1 = รออนุมัติ) เท่านั้น; id ไม่ว่าง = เดิมไม่ทำอะไร */
    public function save(Request $request): Response
    {
        if (! Php74::eq($this->has($request, 'id') ? $this->in($request, 'id') : null, '')) {
            return response('');
        }
        // เดิมไม่ตรวจยอด (ติดลบ / 0 / ไม่ใช่ตัวเลข ได้หมด) — ใหม่ (เจ้าของสั่งแก้): ต้องเป็นตัวเลขบวก
        if (! Money::validRefillAmount($this->in($request, 'amount'))) {
            return response(Money::refuse('กรุณาระบุจำนวนเงินให้ถูกต้อง (ต้องมากกว่า 0)'));
        }
        $uid = Legacy::userId();
        $exp = explode('|', $this->in($request, 'date'));
        $date = Format::getdatesubmit($exp[0]).' '.($exp[1] ?? '');
        $file = $this->in($request, 'upload_file');
        // เดิมบันทึกชื่อไฟล์อะไรก็ได้ (stored XSS ใน grid แอดมิน + path traversal ใน file_exists) — รับเฉพาะชื่อแบบที่ system_upload ออกให้
        if ($file !== '' && ! TempUpload::safeName($file)) {
            $file = '';
        }
        $id = DB::table('refill')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            'is_delete' => 0, 'status' => '1', 'user' => $uid, 'amount' => Format::numformat($this->in($request, 'amount')),
            'date' => $date, 'attachment' => $file, 'remark' => $this->in($request, 'remark'), 'approved_remark' => '',
        ]);

        return response(TempUpload::jsIfExists($file, '         		')
            .Alerts::success('window.location.href = "'.Legacy::href('refill').'";', Lang::get('success_process_no').Lookups::prefix('refill').$id));
    }
}
