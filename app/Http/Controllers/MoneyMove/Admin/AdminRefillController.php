<?php

namespace App\Http\Controllers\MoneyMove\Admin;

use App\Domain\MoneyMove\Lookups;
use App\Domain\MoneyMove\Mailer;
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
        $q = DB::table('refill')->leftJoin('user', 'refill.user', '=', 'user.id')
            ->where('refill.is_delete', 0)
            ->where(fn ($w) => $w->where('refill.id', 'like', $like)->orWhere('user.code', 'like', $like));
        $st = $s[1] ?? null;
        if (Php74::neq($st, '-2') && Php74::neq($st, '')) {
            $q->where('refill.status', $st);
        }
        $total = (clone $q)->count();
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
            if ($data !== null && Php74::eq($data->status, 1)) {
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
            // เดิม: prefix = '10' (ตายตัว) — แถวแรกคงไว้ ที่เหลือ is_delete = 1
            $dups = DB::table('balance')->where('prefix', '10')->where('prefix_ref_id', $data->id)->where('type', '1')
                ->orderBy('id')->pluck('id')->slice(1)->values();
            if ($dups->isNotEmpty()) {
                DB::table('balance')->whereIn('id', $dups)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => (string) $uid]);
            }

            return $data->id;
        });
        if ($approved) {
            Mailer::refillApproved($refId);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/refill').'";', Lang::get('success_process_no').Lookups::prefix('refill').Alerts::js(e($id))));
    }

    /** POST /_admin/_refill/cancel — เดิมไม่ตรวจ status (ยกเลิกรายการที่อนุมัติแล้วได้ — คงไว้, ไม่คืน/หักเงิน) */
    public function cancel(Request $request): Response
    {
        $u = Legacy::user();
        $id = $this->in($request, 'id');
        DB::table('refill')->where('id', $id)->update([
            'status' => '-1', 'cancel_remark' => $this->in($request, 'cancel_remark').' ,ยกเลิกโดย '.$u->code,
            'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id,
        ]);
        Mailer::refillCancelled($id);

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/refill').'";', ''));
    }

    /** email_approved.php / email_cancel.php เปิดตรง (เดิมไม่ต้องล็อกอิน, $email_id ว่าง) — ต้องมีสิทธิ์ และไม่ส่งอะไร */
    public function emailDirect(): Response
    {
        return response('');
    }
}
