<?php

namespace App\Http\Controllers\Points\Admin;

use App\Domain\Points\Cashback;
use App\Domain\Points\CashbackMailer;
use App\Domain\Points\Grid;
use App\Domain\Points\Php74;
use App\Domain\Points\Rules;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แจ้งร้านค้าคืนเงิน (แอดมิน): _admin/_shop_cashback/*.php เดิม (สิทธิ์ admin_shop_cashback)
 */
class AdminShopCashbackController extends Controller
{
    private const AJAX = '_admin/_shop_cashback/grid';

    /** GET /admin/shop_cashback, /admin/shop_cashback/status/{status} — _admin/_shop_cashback/index.php */
    public function index(Request $request): View
    {
        // route defaults (legacy_page) ถูกส่งเป็น argument ตามลำดับ → อ่านพารามิเตอร์จาก route ตรงๆ
        $status = $request->route('status') ?? LegacyInput::query($request, 'status');

        return view('points.admin_shop_cashback.index', ['status' => is_scalar($status) ? (string) $status : '']);
    }

    /** POST /_admin/_shop_cashback/grid — _admin/_shop_cashback/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_shop_cashback')) {
            return response('');
        }
        $s = Grid::search($request);
        $like = Grid::contains(Cashback::searchTerm($s['term']));
        $count = fn (int $status) => DB::table('shop_cashback_detail')->where('status', $status)
            ->groupBy('shop_cashback_id')->select('shop_cashback_id', DB::raw('COUNT(id) as ct'));

        $q = DB::table('shop_cashback')
            ->leftJoinSub($count(1), 'reply', 'shop_cashback.id', '=', 'reply.shop_cashback_id')
            ->leftJoinSub($count(2), 'reply2', 'shop_cashback.id', '=', 'reply2.shop_cashback_id')
            ->leftJoin('user', 'user.id', '=', 'shop_cashback.user')
            ->where('shop_cashback.is_delete', 0)
            ->where(fn ($w) => $w->where('shop_cashback.id', 'like', $like)->orWhere('user.code', 'like', $like));
        // เดิม HAVING wait != 0 / complete != 0 / (wait = 0 and complete = 0) — ไม่มี GROUP BY จึงเท่ากับ WHERE บนคอลัมน์ (NULL ไม่ผ่าน)
        $f = $s['filter'];
        if ($f !== null && Php74::eq($f, '1')) {
            $q->where('reply.ct', '!=', 0);
        }
        if ($f !== null && Php74::eq($f, '2')) {
            $q->where('reply2.ct', '!=', 0);
        }
        if ($f !== null && Php74::eq($f, '-1')) {
            $q->where('reply.ct', '=', 0)->where('reply2.ct', '=', 0);
        }
        $page = Grid::php($request, 'page');
        [$total, $items] = Grid::page($q, 'shop_cashback.id', $page,
            ['shop_cashback.*', 'user.code', 'reply.ct as wait', 'reply2.ct as complete']);

        return response(view('points.shop_cashback.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'details' => Cashback::detailsFor($items),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Grid::prefixText('shop_cashback'),
            'admin' => true,
        ])->render());
    }

    /** GET /admin/shop_cashback/create (id 0), /admin/shop_cashback/{id} — _admin/_shop_cashback/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0);
        $data = DB::table('shop_cashback')->leftJoin('user', 'shop_cashback.user', '=', 'user.id')
            ->where('shop_cashback.id', $id)->where('shop_cashback.is_delete', 0)
            ->first(['shop_cashback.*', 'user.code']);
        $details = $data === null ? collect()
            : DB::table('shop_cashback_detail')->where('shop_cashback_id', $data->id)->orderBy('id')->get();

        return view('points.admin_shop_cashback.form', [
            'id' => $id,
            'data' => $data,
            'details' => $details,
            'prices' => Cashback::sum($details, fn ($r) => ! Php74::eq($r->status, -1)),
            'prefix' => Grid::prefixText('shop_cashback'),
            'ledger' => Cashback::ledger($id),
        ]);
    }

    /** POST /_admin/_shop_cashback/save — _admin/_shop_cashback/save.php */
    public function save(Request $request): Response
    {
        DB::table('shop_cashback')->where('id', Grid::in($request, 'id'))->update([
            'remark_admin' => Grid::in($request, 'remark_admin'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);

        return response(Alerts::success('window.location.reload();'));
    }

    /** POST /_admin/_shop_cashback/save_remark — _admin/_shop_cashback/save_remark.php */
    public function saveRemark(Request $request): Response
    {
        DB::table('shop_cashback')->where('id', Grid::in($request, 'id'))->update(['remark_admin' => Grid::in($request, 'remark')]);

        return response(Alerts::success());
    }

    /** POST /_admin/_shop_cashback/save_approved — _admin/_shop_cashback/save_approved.php */
    public function saveApproved(Request $request): Response
    {
        $id = Grid::in($request, 'id');
        $uid = Legacy::userId();

        // เดิมไม่มี transaction/lock: กดอนุมัติซ้อนกัน = เติมเงินหยวนซ้ำ → ล็อกแถวรายการย่อยแล้วตรวจสถานะภายใน transaction
        $detail = DB::transaction(function () use ($id, $uid) {
            $d = DB::table('shop_cashback_detail')->where('id', $id)->lockForUpdate()->first();
            if ($d === null || Php74::eq($d->status, 2)) {
                return null; // เดิม id ที่ไม่มีจริง: UPDATE ไม่โดนแถวใด ไม่ INSERT (price ว่าง) แต่ยังส่งอีเมลหาใครไม่ได้ → ไม่ทำอะไร
            }
            $head = DB::table('shop_cashback')->where('id', $d->shop_cashback_id)->first();
            DB::table('shop_cashback_detail')->where('id', $d->id)->update([
                'status' => '2', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            $price = $d->price;
            if (Rules::cashbackCreditable($price)) {
                DB::table('balance_cn')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'user' => $head->user ?? '', 'amount' => $price,
                    'detail' => Grid::prefixText('shop_cashback').($head->id ?? '').', รายการสั่งซื้อ : '.$d->order,
                    'type' => '9', 'prefix' => Grid::prefixId('shop_cashback'), 'prefix_ref_id' => $head->id ?? '',
                ]);
            }

            return $d;
        });
        if ($detail !== null && ! config('legacy.debug_mail')) {
            CashbackMailer::approved($detail->shop_cashback_id, $detail->id);
        }

        return response(Alerts::success('window.location.reload();'));
    }

    /** POST /_admin/_shop_cashback/save_cancel — _admin/_shop_cashback/save_cancel.php */
    public function saveCancel(Request $request): Response
    {
        $id = Grid::in($request, 'id');
        $remark = Grid::in($request, 'cancel_remark').' ,ยกเลิกโดย '.(string) (Legacy::user()->code ?? '');

        $detail = DB::transaction(function () use ($id, $remark) {
            $d = DB::table('shop_cashback_detail')->where('id', $id)->lockForUpdate()->first();
            // เดิมยกเลิกได้ทุกสถานะ: ยกเลิกรายการที่อนุมัติแล้ว (เงินหยวนไม่ถูกหักคืน) แล้วอนุมัติซ้ำ = เติมเงินซ้ำ
            // → ไม่ยกเลิกรายการที่อนุมัติแล้ว (ปุ่มยกเลิกเดิมแสดงเฉพาะ status 1)
            if ($d === null || Php74::eq($d->status, 2)) {
                return null;
            }
            DB::table('shop_cashback_detail')->where('id', $d->id)->update([
                'status' => '-1', 'cancel_remark' => $remark, 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
            ]);

            return $d;
        });
        if ($detail !== null && ! config('legacy.debug_mail')) {
            CashbackMailer::cancelled($detail->shop_cashback_id, $detail->id);
        }

        return response(Alerts::success('window.location.reload();'));
    }

    /**
     * POST /_admin/_shop_cashback/email_approved, email_cancel — ไฟล์ที่ save_* include
     * เดิมเรียกตรงได้โดยไม่ล็อกอิน (ไม่มี system_check_ajax / getPermission) และรับ $email_id/$ids ผ่าน extract($_GET)
     * = ใครก็สั่งส่งอีเมลแจ้งลูกค้าได้ → ใหม่ต้องมีสิทธิ์ admin_shop_cashback, รับ id (หัวรายการ) + ids (รายการย่อย) ทาง POST
     * แบบที่ JS ที่ถูก comment ไว้ในไฟล์เดิมเคยเรียก; ตอบว่างเหมือนเดิม
     */
    public function emailApproved(Request $request): Response
    {
        if (self::detailBelongsToHead($request)) {
            CashbackMailer::approved(Grid::in($request, 'id'), Grid::in($request, 'ids'));
        }

        return response('');
    }

    public function emailCancel(Request $request): Response
    {
        if (self::detailBelongsToHead($request)) {
            CashbackMailer::cancelled(Grid::in($request, 'id'), Grid::in($request, 'ids'));
        }

        return response('');
    }

    /**
     * เดิมรับ id (หัวรายการ) กับ ids (รายการย่อย) แยกกันโดยไม่ตรวจว่าเข้าคู่กัน → อีเมลถึงลูกค้า A แต่ใส่เลขคำสั่งซื้อ/ยอดของลูกค้า B ได้
     * → ส่งเฉพาะเมื่อรายการย่อยเป็นของหัวรายการนั้นจริง (save_approved / save_cancel ส่งคู่ที่ถูกต้องเสมอ)
     */
    private static function detailBelongsToHead(Request $request): bool
    {
        $head = DB::table('shop_cashback')->where('id', Grid::in($request, 'id'))->value('id');

        return $head !== null && DB::table('shop_cashback_detail')
            ->where('id', Grid::in($request, 'ids'))->where('shop_cashback_id', $head)->exists();
    }
}
