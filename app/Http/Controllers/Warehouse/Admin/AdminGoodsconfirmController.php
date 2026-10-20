<?php

namespace App\Http\Controllers\Warehouse\Admin;

use App\Domain\Warehouse\Php74;
use App\Domain\Warehouse\UploadBox;
use App\Domain\Warehouse\WarehouseLookups as W;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Warehouse\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ยืนยันเจ้าของพัสดุ (แอดมิน): _admin/_goodsconfirm/*.php เดิม — สิทธิ์ admin_goodsconfirm
 */
class AdminGoodsconfirmController extends Controller
{
    use ReadsLegacyInput;

    /** GET /admin/goodsconfirm, /admin/goodsconfirm/status/{status} */
    public function index(Request $request): View
    {
        return view('warehouse.admin.goodsconfirm.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** POST /_admin/_goodsconfirm/grid (เดิมไม่มีสิทธิ์ = ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_goodsconfirm')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $phpParts = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;
        $page = $this->php($request, 'page');

        // เดิม: select * from (... (SELECT code FROM user WHERE id = goodsconfirm.user) as users ...) as a where ...
        // → LEFT JOIN user แทน derived table (ผลเท่ากัน: user.id เป็น PK) และไม่ต้อง query รหัสลูกค้าทีละแถว
        $like = '%'.addcslashes($parts[0], '%_\\').'%';
        $q = DB::table('goodsconfirm')
            ->leftJoin('tracking', 'tracking.id', '=', 'goodsconfirm.tracking_id')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('user', 'user.id', '=', 'goodsconfirm.user')
            ->where('goodsconfirm.is_delete', 0)
            ->where(fn ($w) => $w->where('user.code', 'like', $like)->orWhere('goodsconfirm.id', 'like', $like));
        if ($status !== null && Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('goodsconfirm.status', $status);
        }
        $total = (clone $q)->count();
        $offset = W::offset($page);
        $items = $offset === null ? collect() : $q->orderByDesc('goodsconfirm.id')->offset($offset)->limit(10)
            ->get(['goodsconfirm.*', 'tracking.tracking', 'tracking.lot_id', 'tracking.no', 'lot.name as lot_name', 'user.code as users']);
        $items = $items->map(fn ($r) => W::strings($r));

        return response(view('warehouse.admin.goodsconfirm.grid', [
            'ajaxpage' => '_admin/_goodsconfirm/grid',
            'items' => $items,
            'images' => self::firstImages($items->pluck('id')->all()),
            'prefix' => W::prefix('goodsconfirm'),
            'search' => $phpParts[0],
            'status' => $phpParts[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /**
     * รูปแรกของแต่ละรายการ (เดิม SELECT image FROM goodsconfirm_detail WHERE goodsconfirm_id = x ทีละแถว, ไม่กรอง is_delete)
     *
     * @param  list<string|int>  $ids
     * @return array<int, string>
     */
    public static function firstImages(array $ids): array
    {
        $ids = array_map('intval', $ids);
        if ($ids === []) {
            return [];
        }
        $first = DB::table('goodsconfirm_detail')->whereIn('goodsconfirm_id', $ids)->groupBy('goodsconfirm_id')
            ->select(DB::raw('MIN(id) as id'));

        return DB::table('goodsconfirm_detail as d')->joinSub($first, 'f', 'f.id', '=', 'd.id')
            ->pluck('d.image', 'd.goodsconfirm_id')->map(fn ($v) => (string) $v)->all();
    }

    /** GET /admin/goodsconfirm/create, /admin/goodsconfirm/{id} */
    public function form(Request $request): Response|View
    {
        $id = (int) ($request->route('id') ?? 0);
        $data = W::strings(DB::table('goodsconfirm')
            ->leftJoin('tracking', 'tracking.id', '=', 'goodsconfirm.tracking_id')
            ->leftJoin('user', 'user.id', '=', 'goodsconfirm.user')
            ->where('goodsconfirm.id', $id)->where('goodsconfirm.is_delete', 0)
            ->first(['goodsconfirm.*', 'tracking.tracking', 'user.code']));
        // เดิม: $data == "" && $id != "" (7.4: 0 != "" เป็น false) → id ที่ไม่มีจริงเด้งออกด้วยข้อความของ "goodsconfirm"
        if ($data === null && $id !== 0) {
            return response()->view('legacy.no-permission', ['module' => 'goodsconfirm']);
        }
        $d = $data ?? [];

        return view('warehouse.admin.goodsconfirm.form', [
            'id' => $id,
            'd' => $d,
            'prefix' => W::prefix('goodsconfirm'),
            // เดิม goodsconfirm_id = '<id>' (สร้างใหม่ = '' → ตรงกับแถวที่ goodsconfirm_id = 0)
            'images' => DB::table('goodsconfirm_detail')->where('goodsconfirm_id', $d['id'] ?? '')
                ->where('is_delete', 0)->orderBy('id')->pluck('image'),
            'approvedUpload' => UploadBox::single('file', $d['approved_upload'] ?? ''),
            'uploadMultiple' => UploadBox::multiple('file', $d['slip'] ?? '', true),
        ]);
    }

    /** POST /_admin/_goodsconfirm/load_tracking — autocomplete (เดิม user.code = nobody: SR define('nobody', 'nobody')) */
    public function loadTracking(Request $request): Response
    {
        $like = '%'.addcslashes($this->in($request, 'query'), '%_\\').'%';
        $list = DB::table('tracking')->leftJoin('user', 'user.id', '=', 'tracking.user')
            ->where('tracking.tracking', 'like', $like)->where('tracking.is_delete', 0)->where('user.code', 'nobody')
            ->pluck('tracking.tracking')->all();

        return response(json_encode(['code' => $list === [] ? null : $list]));
    }

    /** POST /_admin/_goodsconfirm/load_tracking_by_id (เดิมตอบ getPermissionAlert_Page = <script>) */
    public function loadTrackingById(Request $request): Response
    {
        if (! perm('admin_goodsconfirm')) {
            return response(Alerts::permissionPage('admin_goodsconfirm'));
        }

        return response(self::trackingInfo($this->in($request, 'data'), 'col-md-3', 'col-xs-3'));
    }

    /** ส่วนแสดงล็อต/วันที่ของ tracking (ใช้ทั้งแอดมินและลูกค้า ต่างกันที่ class ของ label) */
    public static function trackingInfo(string $tracking, string $lotLabel, string $dateLabel, bool $exists = true): string
    {
        $data = $exists ? W::strings(DB::table('tracking')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->where('tracking.tracking', $tracking)->where('tracking.is_delete', 0)
            ->first(['tracking.*', 'lot.name as lot_name', 'lot.type as lot_type', 'user.code'])) : null;

        return view('warehouse.partials.tracking_info', [
            'd' => $data ?? [],
            'lotLabel' => $lotLabel,
            'dateLabel' => $dateLabel,
        ])->render();
    }

    /** POST /_admin/_goodsconfirm/load_upload */
    public function loadUpload(): Response
    {
        return response("<div class=\"row\">\r\n    ".UploadBox::single('uploadfile', '', true)."</div>");
    }

    /** POST /_admin/_goodsconfirm/save — สร้างรายการใหม่จาก tracking ของผู้ใช้ nobody (SR; jl ใช้ nocode) */
    public function save(Request $request): Response
    {
        $trackingId = DB::table('tracking')->leftJoin('user', 'user.id', '=', 'tracking.user')
            ->where('tracking.tracking', $this->in($request, 'tracking'))
            ->where('tracking.is_delete', 0)->where('user.code', 'nobody') // SR: define('nobody', 'nobody') (jl: ค่าคงที่ nocode ที่ไม่ได้ define)
            ->value('tracking.id');
        if ($trackingId === null) {
            return response("ErrorBox('".Lang::get('no_tracking')."', 'CloseLoading()'); ");
        }
        if (Php74::neq($this->php($request, 'id'), '')) {
            return response(''); // เดิม: แก้ไขไม่ได้ (ไม่ทำอะไร)
        }
        $uid = (int) Legacy::userId();
        $imgs = $this->inArray($request, 'upload_file_mul') ?? [];
        $out = '';
        $id = DB::transaction(function () use ($request, $trackingId, $uid, $imgs, &$out) {
            $id = DB::table('goodsconfirm')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'tracking_id' => $trackingId, 'attachment' => '',
                'submit_remark' => $this->in($request, 'submit_remark'), 'cashback' => '0', 'user' => 0, 'status' => '0',
                'approved_date' => null, 'cancel_remark' => '',
            ]);
            // เดิม for ($i = 0; $i <= count($img); $i++) — index ตามลำดับ 0..n-1
            foreach (array_values($imgs) as $img) {
                $img = is_array($img) ? 'Array' : (string) $img;
                if ($img === '') {
                    continue;
                }
                if (W::tempFileExists($img)) {
                    $out .= W::blobJs($img, "\t\t\t                ", "\t\t\t                  ");
                }
                DB::table('goodsconfirm_detail')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'goodsconfirm_id' => $id, 'image' => $img,
                ]);
            }

            return $id;
        });

        return response($out.Alerts::success(
            'window.location.href = "'.Legacy::href('admin/goodsconfirm').'";',
            Lang::get('success_process_no').W::prefix('goodsconfirm').$id,
        ));
    }

    /** POST /_admin/_goodsconfirm/save_image — แสดงรูปที่เพิ่ง upload (ยังไม่บันทึก) */
    public function saveImage(Request $request): Response
    {
        $image = $this->in($request, 'image');
        $out = '';
        if ($image !== '' && W::tempFileExists($image)) {
            $out .= '<script>'.W::blobJs($image, "\t         \t\t", "\t         \t\t\t").'</script>';
        }
        $u = e(Legacy::hrefBlob($image));
        $v = e($image);
        $out .= '<div class="img_list_detail col-xs-12" style="margin-left: 10px;width:85px"><a style="float:left" class="fancybox" href="'.$u."\">\r\n"
            ."\t\t<img src=\"".$u."\" style=\"max-width: 50px;max-height: 50px;\">\r\n"
            ."\t\t<input type=\"hidden\" name=\"img[]\" value=\"".$v."\" />\r\n"
            ."\t\t</a>\r\n"
            ."\t\t<div style=\"float:left;position:absolute;margin-left:53px;\">\r\n"
            ."\t\t\t<div onclick=\"$(this).parent().parent().parent().remove();\" class=\"btn btn-danger btn-xs\">ลบ</div>\r\n"
            ."\t\t</div>\r\n"
            ."\t\t</div>";

        return response($out);
    }

    /** POST /_admin/_goodsconfirm/cancel — ยกเลิกรายการที่ยังไม่มีเจ้าของ */
    public function cancel(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $row = DB::table('goodsconfirm')->where('id', $id)->first(['id', 'status']);
        // เดิม: $data['status'] == 0 (ไม่พบแถว: null == 0 → UPDATE ที่ไม่ตรงแถวใด)
        if ($row !== null && Php74::eq(W::strings($row)['status'], 0)) {
            DB::table('goodsconfirm')->where('id', $id)->update([
                'user' => 0, 'status' => '-1', 'cancel_remark' => 'ยกเลิกโดย '.(Legacy::user()->code ?? ''),
                'mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId(),
            ]);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/goodsconfirm').'";'));
    }

    /** POST /_admin/_goodsconfirm/save_approved — อนุมัติ: โอน tracking ให้ลูกค้าที่ยืนยัน */
    public function saveApproved(Request $request): Response
    {
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($id) {
            $row = W::strings(DB::table('goodsconfirm')->where('id', $id)->lockForUpdate()->first());
            if ($row !== null && Php74::eq($row['status'], 1)) {
                $uid = (int) Legacy::userId();
                DB::table('goodsconfirm')->where('id', $id)->update([
                    'status' => '2', 'approved_date' => DB::raw('NOW()'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                ]);
                DB::table('tracking')->where('id', $row['tracking_id'] ?? '')->update([
                    'user' => $row['user'] ?? '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                ]);
                // เดิม include_once('email_approved.php') — ไม่มีไฟล์นี้ในเว็บเดิม → ไม่เคยส่งอีเมล
            }
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/goodsconfirm').'";'));
    }

    /** POST /_admin/_goodsconfirm/save_unapproved — ไม่อนุมัติ: กลับเป็น "ไม่มีเจ้าของ" */
    public function saveUnapproved(Request $request): Response
    {
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($id) {
            $row = W::strings(DB::table('goodsconfirm')->where('id', $id)->lockForUpdate()->first(['id', 'status']));
            if ($row !== null && Php74::eq($row['status'], 1)) {
                DB::table('goodsconfirm')->where('id', $id)->update([
                    'status' => '0', 'approved_upload' => '', 'approved_date' => DB::raw('NOW()'),
                    'cancel_remark' => 'ยกเลิกโดย '.(Legacy::user()->code ?? ''),
                    'mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId(),
                ]);
                // เดิม include_once('email_cancel.php') — ไม่มีไฟล์นี้ → ไม่เคยส่งอีเมล
            }
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/goodsconfirm').'";'));
    }
}
