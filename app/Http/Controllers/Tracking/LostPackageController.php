<?php

namespace App\Http\Controllers\Tracking;

use App\Domain\Money\Money;
use App\Domain\Tracking\Php74;
use App\Domain\Tracking\TempUpload;
use App\Domain\Tracking\TrackingLookups;
use App\Domain\Tracking\UploadBox;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tracking\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แจ้งไม่ได้รับสินค้า (ลูกค้า): _lost_package/*.php เดิม (สิทธิ์ lost_package, เห็นเฉพาะของตัวเอง)
 */
class LostPackageController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_lost_package/grid';

    /** GET /lost_package (?status=) */
    public function index(Request $request): View
    {
        $status = $request->route('status');
        return view('tracking.lost.index', [
            'admin' => $this->admin,
            // /admin/lost_package/status/N = ?status=N เดิม (strip_tags(trim()))
            'status' => $status ?? (string) LegacyInput::query($request, 'status'),
        ]);
    }

    protected bool $admin = false;

    /** GET /lost_package/create (id=0) */
    public function create(): Response|View
    {
        return $this->form(0);
    }

    /** GET /lost_package/{id} */
    public function show(Request $request): Response|View
    {
        return $this->form((int) $request->route('id'));
    }

    /** GET /lost_package/status/{n} — RewriteRule เดิมส่งไป page=lost_package_detail โดยไม่มี id (= ฟอร์มใหม่ แต่หัวเรื่องเป็น "แก้ไข") */
    public function statusForm(): Response|View
    {
        return $this->form(null);
    }

    /** _lost_package/form.php / _admin/_lost_package/form.php — id เดิม: (int) เมื่อมีค่า, ไม่มี = null */
    protected function form(?int $id): Response|View
    {
        $admin = $this->admin;
        $q = DB::table('lost_package')->where('lost_package.id', $id ?? '')->where('lost_package.is_delete', 0);
        if ($admin) {
            // PACK: + bill_product.exchange_rate (ช่องยอดคืนเป็นบาท cashback_th = cashback × อัตราของออเดอร์)
            $row = $q->leftJoin('bill_product', 'lost_package.order_id', '=', 'bill_product.id')
                ->leftJoin('user', 'lost_package.user', '=', 'user.id')
                ->first(['lost_package.*', 'user.code', 'user.id as user_id', 'bill_product.exchange_rate']);
        } else {
            $row = $q->where('user', Legacy::userId())->first(['lost_package.*']);
            // เดิม: ไม่พบ (หรือของคนอื่น) และ id != "" → getPermissionAlert_Page
            if ($row === null && Php74::neq($id, '')) {
                return response()->view('legacy.no-permission', ['module' => 'lost_package']);
            }
        }
        $d = (array) ($row ?? []);
        $balances = collect();
        if (! Php74::eq($id, 0)) {
            $pid = TrackingLookups::prefixId('lost_package');
            $balances = DB::table('balance')->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
                ->where('balance.prefix', (string) ($pid ?? ''))->where('balance.prefix_ref_id', $id ?? '')
                ->where('balance.is_delete', 0)->orderBy('balance.id')
                ->get(['balance.*', 'prefix.link']);
        }

        return view('tracking.lost.form_body', [
            'admin' => $admin,
            'id' => $id,
            'd' => $d,
            'images' => DB::table('lost_package_detail')->where('lost_package_id', $d['id'] ?? '')->where('is_delete', 0)->orderBy('id')->pluck('image'),
            'balances' => $balances,
            'prefix' => TrackingLookups::prefix('lost_package'),
            'orderPrefix' => TrackingLookups::prefix('order'),
        ]);
    }

    /** POST /_lost_package/grid (ลูกค้า) และ /_admin/_lost_package/grid (แอดมิน) */
    public function grid(Request $request): Response
    {
        $admin = $this->admin;
        if (! perm($admin ? 'admin_lost_package' : 'lost_package')) {
            return response('');
        }
        $s = explode('|', $this->in($request, 'txtsearch'));
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $like = '%'.addcslashes($s[0], '%_\\').'%';
        $q = DB::table('lost_package')->where('lost_package.is_delete', 0);
        if ($admin) {
            // PACK: แอดมินค้นได้ทั้งเลขรายการ และรหัสลูกค้า (ไม่มีเลขออเดอร์แบบ SR — ต้อง join user ตั้งแต่ตอนนับ)
            $q->leftJoin('user', 'user.id', '=', 'lost_package.user')
                ->where(fn ($w) => $w->where('lost_package.id', 'like', $like)
                    ->orWhere('user.code', 'like', $like));
        } else {
            $q->where('lost_package.id', 'like', $like)->where('lost_package.user', Legacy::userId());
        }
        $st = $s[1] ?? null;
        if ($st !== null && Php74::neq($st, '-2') && Php74::neq($st, '')) {
            $q->where('lost_package.status', $st);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = TrackingLookups::offset($page);
        $items = collect();
        if ($offset !== null) {
            $items = $q->orderByDesc('lost_package.id')->offset($offset)->limit(10)
                ->get($admin ? ['lost_package.*', 'user.code'] : ['lost_package.*']);
        }
        // รูปแรกของแต่ละรายการ (เดิม query ทีละแถว, ไม่กรอง is_delete)
        $images = [];
        if ($items->isNotEmpty()) {
            foreach (DB::table('lost_package_detail')->whereIn('lost_package_id', $items->pluck('id'))->orderBy('id')->get(['lost_package_id', 'image']) as $r) {
                $images[(int) $r->lost_package_id] ??= $r->image;
            }
        }

        return response(view('tracking.lost.grid', [
            'admin' => $admin,
            'ajaxpage' => $admin ? '_admin/_lost_package/grid' : self::AJAX,
            'items' => $items,
            'images' => $images,
            'search' => $sp[0],
            'status' => $sp[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => TrackingLookups::prefix('lost_package'),
            'orderPrefix' => TrackingLookups::prefix('order'),
        ])->render());
    }

    /** POST /_lost_package/cancel — ยกเลิกรายการของตัวเอง */
    public function cancel(Request $request): Response
    {
        $u = Legacy::user();
        // เดิมยกเลิกได้ทุกสถานะ (ปุ่มแสดงเฉพาะสถานะ 1): ลูกค้ายกเลิกรายการที่อนุมัติ+คืนเงินแล้ว → แอดมิน RollBack (-1 → 1 ไม่หักเงิน)
        // → อนุมัติอีกครั้ง = ได้เงินคืนซ้ำ — ยกเลิกได้เฉพาะรายการของตัวเองที่รอ (1); ไม่พบ = ไม่ทำอะไรแบบเดิม
        $refused = DB::transaction(function () use ($request, $u) {
            $cur = DB::table('lost_package')->where('id', $this->in($request, 'id'))->where('user', $u->id)->lockForUpdate()->first(['id', 'status']);
            if ($cur === null) {
                return null;
            }
            if (! Money::statusIn($cur->status, [Money::PENDING])) {
                return Php74::eq($cur->status, 2) ? 'รายการนี้อนุมัติแล้ว ไม่สามารถยกเลิกได้' : 'รายการนี้ถูกยกเลิกไปแล้ว';
            }
            DB::table('lost_package')->where('id', $cur->id)->where('user', $u->id)->update([
                'status' => '-1', 'cancel_remark' => 'ยกเลิกโดย '.$u->code, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id,
            ]);

            return null;
        });
        if ($refused !== null) {
            return response(Money::refuse($refused));
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('lost_package').'";'));
    }

    /** POST /_lost_package/load_order_number — typeahead เลขออเดอร์ของตัวเอง */
    public function loadOrderNumber(Request $request): Response
    {
        $prefix = TrackingLookups::prefix('order');
        $term = str_replace($prefix, '', $this->in($request, 'query'));
        $codes = DB::table('bill_product')->where('id', 'like', '%'.addcslashes($term, '%_\\').'%')
            ->where('is_delete', 0)->where('user', Legacy::userId())->orderBy('id')->pluck('id')
            ->map(fn ($id) => $prefix.$id)->all();

        // เดิม $arr_user ไม่มีค่า = null
        return response(json_encode(['code' => $codes === [] ? null : $codes]));
    }

    /** POST /_lost_package/load_upload — กล่องแนบรูป (getupload) */
    public function loadUpload(): Response
    {
        return response("<div class=\"row\">\n    ".UploadBox::single('uploadfile', '', true).'</div>');
    }

    /** POST /_lost_package/save — สร้างรายการใหม่เท่านั้น (id ไม่ว่าง = เดิมไม่ทำอะไรและไม่ตอบอะไร) */
    public function save(Request $request): Response
    {
        $uid = Legacy::userId();
        $order = str_replace(TrackingLookups::prefix('order'), '', $this->in($request, 'order'));
        $billId = DB::table('bill_product')->where('id', $order)->where('is_delete', 0)->where('user', $uid)->value('id');
        if ($billId === null) {
            return response("ErrorBox('".Lang::get('no_order')."', 'CloseLoading()'); ");
        }
        if ($this->in($request, 'id') !== '') {
            return response('');
        }
        // เดิมบันทึกทุกค่าที่ส่งมา (รวม ../../x) — รับเฉพาะชื่อไฟล์ธรรมดาแบบที่ system_upload ออกให้
        $imgs = TempUpload::names(LegacyInput::post($request, 'upload_file_mul', []));

        $out = '';
        $id = DB::transaction(function () use ($uid, $billId, $request, $imgs, &$out) {
            $id = DB::table('lost_package')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'order_id' => $billId, 'attachment' => '', 'submit_remark' => $this->in($request, 'submit_remark'),
                'cashback' => $this->in($request, 'cashback'), 'user' => $uid, 'status' => '1', 'approved_date' => null, 'cancel_remark' => '',
            ]);
            foreach ($imgs as $img) {
                if (TempUpload::exists($img)) {
                    $out .= TempUpload::blobJs($img);
                }
                DB::table('lost_package_detail')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'lost_package_id' => $id, 'image' => $img,
                ]);
            }

            return $id;
        });

        return response($out.Alerts::success('window.location.href = "'.Legacy::href('lost_package').'";',
            Lang::get('success_process_no').TrackingLookups::prefix('lost_package').$id));
    }

    /** POST /_lost_package/save_image — เดิมไม่ตรวจสิทธิ์ (ใส่ lost_package); ตอบ HTML รูปตัวอย่าง */
    public function saveImage(Request $request): Response
    {
        $image = $this->in($request, 'image');
        $out = ($image !== '' && TempUpload::exists($image)) ? TempUpload::blobJs($image) : '';
        $u = e(Legacy::hrefBlob($image));
        $out .= '<div class="img_list_detail col-xs-12" style="margin-left: 10px;width:85px"><a style="float:left" class="fancybox" href="'.$u.'">'
            ."\n\t\t<img src=\"".$u."\" style=\"max-width: 50px;max-height: 50px;\">\n\t\t<input type=\"hidden\" name=\"img[]\" value=\"".e($image)."\" />\n\t\t</a>\n"
            ."\t\t<div style=\"float:left;position:absolute;margin-left:53px;\">\n\t\t\t<div onclick=\"$(this).parent().parent().parent().remove();\" class=\"btn btn-danger btn-xs\">ลบ</div>\n\t\t</div>\n\t\t</div>";

        return response($out);
    }

    /**
     * POST /_lost_package/upload — รับไฟล์แนบ (ต้องล็อกอิน) เก็บใน temp ของ legacy.upload.temp_dir (dev = storage/app/legacy-upload/temp)
     * ตรวจเหมือนเดิม: นามสกุล jpg/jpeg/png/gif/pdf, ≤ 10MB, รูปต้องเป็นรูปจริง
     */
    public function upload(Request $request): Response
    {
        $f = $request->file('file');
        if ($f === null || ! $f->isValid() || $f->getClientOriginalName() === '') {
            return response('error: no file');
        }
        $size = (int) $f->getSize();
        if ($size <= 0 || $size > 10485760) {
            return response('error: bad size');
        }
        $ext = strtolower(pathinfo($f->getClientOriginalName(), PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf'], true)) {
            return response('error: ext');
        }
        if ($ext !== 'pdf') {
            $i = @getimagesize($f->getPathname());
            if ($i === false || ! in_array($i[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
                return response('error: not image');
            }
        }
        $name = time().'_'.\App\Support\Legacy\Format::randd(16).'.'.$ext;
        $dir = rtrim((string) config('legacy.upload.temp_dir'), '/');
        try {
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $f->move($dir, $name);
            @chmod($dir.'/'.$name, 0644);
        } catch (\Throwable) {
            return response('error: failed');
        }

        return response($name);
    }
}
