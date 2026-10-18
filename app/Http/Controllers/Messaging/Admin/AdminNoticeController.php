<?php

namespace App\Http\Controllers\Messaging\Admin;

use App\Domain\Messaging\Chat;
use App\Domain\Messaging\NoticeMail;
use App\Domain\Messaging\NoticeText;
use App\Domain\Messaging\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\ReadsLegacyInput;
use App\Http\Controllers\Messaging\Concerns\RejectsCrossSiteGet;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** ประกาศฝั่งแอดมิน: _admin/_notice/*.php เดิม (สิทธิ์ admin_notice ยกเว้น delete ที่เดิมตรวจ admin_product_type) */
class AdminNoticeController extends Controller
{
    use ReadsLegacyInput;
    use RejectsCrossSiteGet;

    private const AJAX = '_admin/_notice/grid';

    /** GET /admin/notice — _admin/_notice/index.php */
    public function index(): View
    {
        return view('messaging.admin.notice.index');
    }

    /** GET /admin/notice/create, /admin/notice/{id} — _admin/_notice/form.php */
    public function form(Request $request): View
    {
        $id = (int) ($request->route('id') ?? 0);
        $row = DB::table('notice')->where('id', $id)->where('is_delete', 0)->first();

        return view('messaging.admin.notice.form', [
            'id' => $id,
            'd' => (array) ($row ?? []),
            'prefix' => NoticeText::prefix(),
        ]);
    }

    /** POST /_admin/_notice/grid */
    public function grid(Request $request): Response
    {
        if (! perm('admin_notice')) {
            return response(''); // เดิม exit() เงียบ
        }
        $q = DB::table('notice')->where('is_delete', 0);
        $total = (clone $q)->count();
        $page = $this->php($request, 'page', null);
        [, $offset] = Chat::paging($page);
        $rows = $offset === null ? [] : $q->orderByDesc('id')->offset($offset)->limit(10)->get()->all();

        return response(view('messaging.admin.notice.grid', [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'txtsearch' => $this->php($request, 'txtsearch', null),
            'ajaxpage' => self::AJAX,
            'prefix' => NoticeText::prefix(),
        ])->render());
    }

    /** POST /_admin/_notice/save — เพิ่ม (id ว่าง) / แก้ไขประกาศ */
    public function save(Request $request): Response
    {
        $uid = (string) Legacy::userId();
        $text = $this->in($request, 'tabtext');
        $isShow = $this->in($request, 'is_show');
        $done = 'window.location.href = "'.Legacy::href('admin/notice').'";';

        if (Php74::eq($this->php($request, 'id', null), '')) {
            $id = DB::table('notice')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid,
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'text' => $text, 'is_show' => $isShow,
            ]);

            return response(Alerts::success($done, Lang::get('success_process_no').NoticeText::prefix().$id));
        }

        $id = $this->in($request, 'id');
        DB::table('notice')->where('id', $id)->update([
            'text' => $text, 'is_show' => $isShow, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
        ]);

        return response(Alerts::success($done, Lang::get('success_process_no').NoticeText::prefix().Alerts::js($this->php($request, 'id'))));
    }

    /** POST /_admin/_notice/save_isshow — สวิตช์แสดง/ซ่อน */
    public function saveIsshow(Request $request): Response
    {
        DB::table('notice')->where('id', $this->in($request, 'id'))->update([
            'is_show' => $this->in($request, 'value'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => (string) Legacy::userId(),
        ]);

        return response('');
    }

    /**
     * POST /_admin/_notice/delete
     * เดิม (คัดลอกมาจาก _admin/_product_type/delete.php) ลบ product_type ที่ id ตรงกับประกาศ (!) ด้วย `IN (".$id.")`
     * ที่ฉีด SQL ได้ — ที่นี่ลบ (is_delete = 1) ประกาศตามที่ปุ่มในหน้าตั้งใจ, id ต้องเป็นตัวเลขเท่านั้น, สิทธิ์ admin_product_type เหมือนเดิม
     */
    public function delete(Request $request): Response
    {
        $arr = $this->in($request, 'arr_id');
        $ids = Php74::idList($arr !== '' ? substr($arr, 1) : $this->in($request, 'id'));
        if ($ids !== null) {
            DB::table('notice')->whereIn('id', $ids)
                ->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId()]);
        }

        return response(Alerts::success());
    }

    /**
     * GET /_admin/_notice/email?id=&email_to= — ส่งประกาศ 1 ฉบับไปอีเมลที่ระบุ
     * (เดิมได้ $id, $email_to จาก extract($_GET); ไม่มี UI ไหนเรียก)
     */
    public function email(Request $request): Response
    {
        if ($this->isCrossSite($request)) {
            return $this->crossSiteResponse();
        }
        $id = (int) LegacyInput::query($request, 'id', 0);
        $to = (string) (is_scalar($v = LegacyInput::query($request, 'email_to', '')) ? $v : '');
        $row = DB::table('notice')->where('id', $id)->first();
        [$subject, $body] = NoticeMail::template($row ?? (object) []);
        if (config('legacy.debug_mail')) {
            return response($body);
        }
        if ($to !== '') {
            NoticeMail::send($subject, $body, $to);
        }

        return response('');
    }

    /** GET /_admin/_notice/email_notice?id= — เข้าคิวส่งประกาศให้ทุกคนที่มีอีเมล */
    public function emailNotice(Request $request): Response
    {
        if ($this->isCrossSite($request)) {
            return $this->crossSiteResponse();
        }
        $notiId = (int) LegacyInput::query($request, 'id', 0);
        if ($notiId <= 0) {
            return response('ไม่พบประกาศ');
        }
        [$total, $pending] = NoticeMail::enqueue($notiId);

        return response('<meta charset="utf-8"><div style="text-align:center;font-family:arial;color:#555;margin-top:80px;">
  <div style="font-size:70px;color:#81c868;line-height:1;">&#10003;</div>
  <h2 style="margin:10px 0;">เข้าคิวส่งประกาศแล้ว</h2>
  <p>ผู้รับ '.(int) $total.' ราย · รอส่ง '.(int) $pending.' ราย</p>
  <p style="color:#888;">ระบบส่งเบื้องหลังอัตโนมัติ (เว้น 2 วินาที/ฉบับ) — <b>ปิดหน้านี้ได้เลย ไม่ต้องเปิดค้าง</b></p>
  <a href="'.Legacy::href('admin/notice').'" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#f0ad4e;color:#fff;text-decoration:none;border-radius:4px;">กลับหน้าประกาศ</a>
</div>');
    }

    /**
     * GET /_admin/_notice/cron_notice_mail — worker ส่งคิว (เดิมเป็นไฟล์ cron ที่เปิดผ่านเว็บได้โดยไม่ต้องล็อกอิน)
     * ที่นี่ต้องมีสิทธิ์ admin_notice; cron จริงควรใช้ `php8.4 artisan messaging:notice-mail`
     */
    public function cron(Request $request): Response
    {
        if ($this->isCrossSite($request)) {
            return $this->crossSiteResponse();
        }
        $lines = NoticeMail::work(25, 2, getenv('NOTICEMAIL_TEST') === '1');

        return response($lines === [] ? '' : implode("\n", $lines)."\n");
    }
}
