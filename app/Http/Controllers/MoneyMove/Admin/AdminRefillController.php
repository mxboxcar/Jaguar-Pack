<?php

namespace App\Http\Controllers\MoneyMove\Admin;

use App\Domain\Money\Money;
use App\Domain\MoneyMove\Lookups;
use App\Domain\MoneyMove\Mailer;
use App\Domain\MoneyMove\Php74;
use App\Domain\MoneyMove\TempUpload;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MoneyMove\Concerns\ReadsLegacyInput;
use App\Support\GridQuery;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * เติมเงิน (แอดมิน): _admin/_refill/*.php เดิม — สิทธิ์ admin_refill
 */
class AdminRefillController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_refill/grid';

    /** GET /admin/refill, /admin/refill/status/{status} */
    public function index(Request $request): View
    {
        return view('money-move.refill.index', [
            'admin' => true,
            'status' => $request->route('status') ?? (string) LegacyInput::query($request, 'status'),
        ]);
    }

    /** GET /admin/refill/create (id=0), /admin/refill/{id} — เดิมไม่พบ = หน้าว่าง */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = DB::table('refill')->leftJoin('user', 'refill.user', '=', 'user.id')
            ->where('refill.id', $id)->where('refill.is_delete', 0)
            ->first(['refill.*', 'user.code', 'user.id as user_id']);
        $balances = Lookups::balances([Lookups::prefixId('refill')], $id, true);

        return view('money-move.admin.refill.form', [
            'id' => $id,
            'd' => (array) ($data ?? []),
            'prefix' => Lookups::prefix('refill'),
            'balanceTable' => Lookups::balanceTable($balances, ['col' => 'col-xs-12 col-md-6', 'w1' => '130px', 'w2' => '80px', 'br' => '<br/>', 'tr' => true]),
        ]);
    }

    /** POST /_admin/_refill/grid — เดิมไม่มีสิทธิ์ = exit() เงียบ */
    public function grid(Request $request): Response
    {
        if (! perm('admin_refill')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $like = '%'.addcslashes($s[0], '%_\\').'%';
        $st = $s[1] ?? null;
        // ช่องค้นว่าง: `refill.id LIKE '%%'` จริงทุกแถว (id int NOT NULL) → ทั้งเงื่อนไข OR จริง = ไม่ต้องใส่
        // ค้นด้วยคำ: user.code LIKE ผ่าน LEFT JOIN (user.id = PK) ≡ refill.user IN (SELECT id FROM user WHERE code LIKE …)
        //   → นับได้โดยไม่ join ทุกแถว (เดิม COUNT ผ่าน join ≈ 330 ms)
        $filter = function (Builder $q) use ($s, $st, $like) {
            if ($s[0] !== '') {
                $q->where(fn ($w) => $w->where('refill.id', 'like', $like)
                    ->orWhereIn('refill.user', DB::table('user')->select('id')->where('code', 'like', $like)));
            }
            if (Php74::neq($st, '-2') && Php74::neq($st, '')) {
                $q->where('refill.status', $st);
            }
        };
        $q = DB::table('refill')->leftJoin('user', 'refill.user', '=', 'user.id')
            ->where('refill.is_delete', 0);
        $filter($q);
        // ไม่มีสถานะ: นับจาก idx_refill_user (user, id) อย่างเดียว · มีสถานะ: idx_notif (is_delete, status) อยู่แล้ว
        $total = Php74::neq($st, '-2') && Php74::neq($st, '')
            ? DB::table('refill')->where('refill.is_delete', 0)->where($filter)->count()
            : GridQuery::liveCount('refill', $filter);
        $page = $this->php($request, 'page');
        $offset = Lookups::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('refill.id')->offset($offset)->limit(10)->get(['refill.*', 'user.code']);

        return response(view('money-move.refill.grid', [
            'admin' => true,
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

    /** POST /_admin/_refill/save — แอดมินเติมเงินให้ลูกค้าตามรหัส (status 2 + balance ทันที) */
    public function save(Request $request): Response
    {
        if (! Php74::eq($this->has($request, 'id') ? $this->in($request, 'id') : null, '')) {
            return response('');
        }
        $uid = Legacy::userId();
        $member = DB::table('user')->where('code', trim($this->in($request, 'code')))->first(['id']);
        if ($member === null) {
            return response("ErrorBox('".Lang::get('no_user')."', 'CloseLoading()'); ");
        }
        // แก้จากเดิม (เจ้าของอนุมัติ): เดิมรับยอดติดลบ/0/ไม่ใช่ตัวเลข (= หักเงินลูกค้า หรือรายการ 0 บาท) → ตัวเลขบวก ทศนิยมไม่เกิน 2 ตำแหน่ง
        if (! Money::validRefillAmount($this->in($request, 'amount'))) {
            return response(Money::refuse('กรุณาระบุจำนวนเงินให้ถูกต้อง (ต้องมากกว่า 0)'));
        }
        $amount = Format::numformat($this->in($request, 'amount'));
        $exp = explode('|', $this->in($request, 'date'));
        $date = Format::getdatesubmit($exp[0]).' '.($exp[1] ?? '');
        $file = $this->in($request, 'upload_file');
        if ($file !== '' && ! TempUpload::safeName($file)) {
            $file = '';
        }
        $id = DB::transaction(function () use ($uid, $member, $amount, $date, $file, $request) {
            $id = DB::table('refill')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'status' => '2', 'user' => $member->id, 'amount' => $amount, 'date' => $date,
                'attachment' => $file, 'remark' => $this->in($request, 'remark'), 'approved_remark' => '',
            ]);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => $member->id, 'amount' => $amount, 'detail' => Lookups::prefix('refill').$id, 'type' => 1,
                'prefix' => (string) (Lookups::prefixId('refill') ?? ''), 'prefix_ref_id' => $id,
            ]);

            return $id;
        });

        return response(TempUpload::jsIfExists($file, '	              ')
            .Alerts::success('window.location.href = "'.Legacy::href('admin/refill').'";', Lang::get('success_process_no').Lookups::prefix('refill').$id));
    }

    /**
     * POST /_admin/_refill/save_approved — อนุมัติ (status 1 → 2) + เพิ่มเงิน + อีเมล
     * เดิม: sleep(3) แล้วลบ balance ซ้ำ (กันกดอนุมัติซ้ำ) — ที่นี่ lock แถว refill ใน transaction จึงไม่ซ้ำ; คงขั้นลบซ้ำไว้ (ไม่ sleep)
     */
    public function saveApproved(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $approved = false;
        $refId = DB::transaction(function () use ($uid, $id, &$approved) {
            $data = DB::table('refill')->where('id', $id)->lockForUpdate()->first();
            // รายการแจ้งยอดติดลบ/ศูนย์ที่ค้างจากเว็บเดิม: อนุมัติ = หักเงินลูกค้า → ไม่ยอม
            if ($data !== null && Php74::eq($data->status, 1) && (float) $data->amount <= 0) {
                return 'invalid';
            }
            if ($data !== null && Php74::eq($data->status, 1)) {
                Money::lockUser($data->user);
                DB::table('refill')->where('id', $data->id)->update(['status' => '2', 'approved_date' => DB::raw('NOW()')]);
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => $data->user, 'amount' => $data->amount, 'detail' => 'เติมเงิน '.Lookups::prefix('refill').$data->id, 'type' => 1,
                    'prefix' => (string) (Lookups::prefixId('refill') ?? ''), 'prefix_ref_id' => $data->id,
                ]);
                $approved = true;
            }
            if ($data === null) {
                return null;
            }
            // เดิมตอบ "สำเร็จ" แม้รายการถูกยกเลิกไปแล้ว (ไม่ได้เติมเงิน) → แจ้ง error ให้แอดมินรู้
            if (Money::statusIn($data->status, [Money::CANCELLED])) {
                return 'cancelled';
            }
            // เดิม: prefix = '10' (ตายตัว = id ของ prefix "refill") → ใช้ id จากตาราง prefix — แถวแรกคงไว้ ที่เหลือ is_delete = 1
            $dups = DB::table('balance')->where('prefix', (string) (Lookups::prefixId('refill') ?? ''))->where('prefix_ref_id', $data->id)->where('type', '1')
                ->orderBy('id')->pluck('id')->slice(1)->values();
            if ($dups->isNotEmpty()) {
                DB::table('balance')->whereIn('id', $dups)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => (string) $uid]);
            }

            return $data->id;
        });
        if ($refId === 'cancelled') {
            return response(Money::refuse('รายการนี้ถูกยกเลิกไปแล้ว ไม่สามารถอนุมัติได้'));
        }
        if ($refId === 'invalid') {
            return response(Money::refuse('ยอดเงินไม่ถูกต้อง (ต้องมากกว่า 0)'));
        }
        if ($approved) {
            Mailer::refillApproved($refId);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/refill').'";', Lang::get('success_process_no').Lookups::prefix('refill').Alerts::js(e($id))));
    }

    /**
     * POST /_admin/_refill/cancel — ยกเลิกรายการแจ้งเติมเงิน (ไม่มีการเคลื่อนเงิน)
     * เดิมไม่ตรวจ status: ยกเลิกรายการที่อนุมัติแล้วได้ (เงินที่เติมยังอยู่ในบัญชี แต่รายการแสดงว่ายกเลิก)
     * ใหม่ (เจ้าของสั่งแก้): ล็อกแถว, เฉพาะ status 1 (= ปุ่มยกเลิกในหน้าแสดงเฉพาะสถานะนี้) — เลือก "ปฏิเสธ" แทน "หักเงินคืน"
     * เพราะหน้าเดิมไม่มีทางยกเลิกรายการที่อนุมัติแล้วอยู่แล้ว ถ้าต้องดึงเงินคืนให้ทำรายการปรับยอดแยก
     */
    public function cancel(Request $request): Response
    {
        $u = Legacy::user();
        $id = $this->in($request, 'id');
        $result = Money::cancelPendingRefill($id, [
            'cancel_remark' => $this->in($request, 'cancel_remark').' ,ยกเลิกโดย '.$u->code,
            'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id,
        ]);
        if (is_string($result)) {
            return response(Money::refuse($result));
        }
        Mailer::refillCancelled($result);

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/refill').'";', ''));
    }

    /** email_approved.php / email_cancel.php เปิดตรง (เดิมไม่ต้องล็อกอิน, $email_id ว่าง) — ต้องมีสิทธิ์ และไม่ส่งอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
