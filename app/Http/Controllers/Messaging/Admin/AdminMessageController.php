<?php

namespace App\Http\Controllers\Messaging\Admin;

use App\Domain\Messaging\Chat;
use App\Domain\Messaging\ChatText;
use App\Domain\Messaging\Php74;
use App\Domain\Messaging\UploadedFile;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แชทฝั่งแอดมิน: _admin/_message/*.php เดิม — ทุก route ต้องมีสิทธิ์ admin_message (load ตรวจใน controller: ตอบว่างแบบ hotfix ของ PACK)
 * (เดิมมีแค่ deletechat / save_file ที่ตรวจ; index, load, load_dept, load_user, send, send_endconversation
 *  ไม่ตรวจสิทธิ์ และ load.php ไม่ตรวจแม้แต่การล็อกอิน = ใครก็อ่านแชทของลูกค้าคนไหนก็ได้)
 *
 * JSON ของ load_dept / load_user เดิมเป็น urldecode(json_encode(...)) — ที่นี่ urldecode ทีละค่าแล้วค่อย escape + json_encode
 * (ผลเท่ากันในกรณีปกติ: "+" → ช่องว่าง, %xx → อักขระ; แต่ไม่สร้าง JSON พัง/HTML แทรกจาก %3C ได้อีก)
 * PACK: load.php ตอบ urldecode(json_encode(['html' => str_replace('"', "'", $html), 'title', 'page'])) ไม่มี 'sql'
 *   (JS ใส่ด้วย .html($.parseHTML(data.html)) / .prepend(data.html)) — urldecode ทีละค่าเหมือน load_dept/load_user
 */
class AdminMessageController extends Controller
{
    use ReadsLegacyInput;

    /** GET /admin/message — _admin/_message/index.php */
    public function index(): View
    {
        return view('messaging.admin.message.index');
    }

    /** POST /_admin/_message/load_dept — แผนก + จำนวนข้อความจากลูกค้าที่แอดมินยังไม่อ่าน */
    public function loadDept(Request $request): Response
    {
        $id = $this->php($request, 'id', null);
        $rows = DB::table('chat_dept as t1')
            ->select('t1.*')
            ->selectSub(fn ($q) => $q->from('chat')->selectRaw('COUNT(id)')
                ->where('is_read_admin', '0')->where('to', '0')->whereColumn('dept', 't1.id'), 'count')
            ->get();

        $total = 0;
        $html = '<div style="margin-top:10px;font-size:13px;">';
        foreach ($rows as $row) {
            $total += (int) $row->count;
            $active = Php74::eq($id, (string) $row->id) ? 'active' : '';
            $html .= '<div class="list-group-item  '.$active.'  cursor" onclick="ChangeDept('.(int) $row->id.', $(this));Load(1, true)">
                <div class="chat-avatar">
                    <i style="float:left;" class="'.$this->u($row->icon).'"></i>';
            if ((int) $row->count !== 0) {
                $html .= '<span style="float:right;" class="badge badge-xs badge-danger">'.(int) $row->count.'</span>';
            }
            $html .= '</div>
                <div class="conversation-text">
                    <div class="ctext-wrap" style="margin-left:25px;"> '.$this->u($row->name).'</div>
                </div>
            </div>';
        }
        $html .= '</div>';

        return response((string) json_encode(['html' => Chat::crlf($html), 'total' => $total]));
    }

    /** POST /_admin/_message/load — ข้อความระหว่างแอดมินกับลูกค้า $user ในแผนก $id */
    public function load(Request $request): Response
    {
        if (! perm('admin_message')) {
            return response(''); // PACK: if (!getPermission('admin_message')) { exit(); }
        }
        $page = $this->php($request, 'page', null);
        $dept = $this->php($request, 'id', null);
        $user = $this->php($request, 'user', null);
        if (Php74::eq($dept, 0) || Php74::eq($user, 0)) {
            return response('');
        }
        $userSql = $this->in($request, 'user');
        $deptSql = $this->in($request, 'id');
        $code = DB::table('user')->where('id', $userSql)->value('code');

        // เดิม str_replace("-10", "0", $sql) ทั้งคำสั่ง (ตั้งใจแก้ LIMIT -10 ตอน page=0) — กระทบค่า user/dept ด้วย
        [$paging, $offset] = Chat::paging($page, true);
        $u = str_replace('-10', '0', $userSql);
        $d = str_replace('-10', '0', $deptSql);
        $rows = Chat::rows(fn ($q) => $q
            ->where(fn ($w) => $w->where('chat.from', $u)->orWhere('chat.from', '0'))
            ->where(fn ($w) => $w->where('chat.to', $u)->orWhere('chat.to', '0'))
            ->where('chat.dept', $d), $offset, true);

        $html = '<div id="page">';
        if ($rows !== []) {
            $html .= Chat::loadMore($page);
            foreach (array_reverse($rows) as $row) {
                if (Php74::eq((string) $row->from, '0')) {
                    $html .= '
                        <li class="clearfix odd">
                            <div class="conversation-text">
                                <div style="text-align:right;display: inline-block;padding: 2px 10px;position: relative;font-size: 9px;">'.Chat::stamp($row->create_date).'</div>
                                <div class="ctext-wrap" style="float: right;clear: both;">
                                    <i>'.$this->u($row->owner).' <span onclick="deletechat('.(int) $row->id.');" class="fa fa-close text-danger"></span></i>

                                    <p style="font-size: 13px;">
                                       '.ChatText::render(urldecode((string) $row->text)).'
                                    </p>
                                </div>
                            </div>
                        </li>';
                } else {
                    $html .= '<li class="clearfix ">
                            <div class="conversation-text">
                                <div style="display: inline-block;padding: 2px 10px;position: relative;font-size: 9px;">'.Chat::stamp($row->create_date).'</div>
                                <div class="ctext-wrap" style="float: left;clear: both;">
                                    <i>'.$this->u($row->from_code).'</i>
                                    <p style="font-size: 13px;">
                                       '.ChatText::render(urldecode((string) $row->text)).'
                                    </p>
                                </div>
                            </div>
                        </li>';
                }
            }
        }
        $html .= '</div>';

        // PACK: เดิม str_replace('"', "'", $html) ทั้งก้อน — " ทุกตัว (attribute และข้อความ) กลายเป็น '
        // ที่นี่ข้อความถูก escape แล้ว (" → &quot;) จึงแทน &quot; เป็น ' ด้วย ให้ข้อความแสดงเหมือนเดิม (ค่าอยู่ในเนื้อหา ไม่ใช่ใน attribute)
        $html = str_replace(['"', '&quot;'], "'", Chat::crlf($html));

        return response((string) json_encode([
            'html' => $html,
            'title' => $code === null ? null : $this->u($code), // JS .html(title) — escape (เดิมดิบ)
            'page' => null,
        ]));
    }

    /** POST /_admin/_message/load_user — ค้นหาลูกค้า (10 คน เรียงตามจำนวนข้อความที่ยังไม่อ่านในแผนก) */
    public function loadUser(Request $request): Response
    {
        $select = $this->php($request, 'select', null);
        $like = '%'.addcslashes($this->in($request, 'keyword'), '%_\\').'%';
        $rows = DB::select(
            'SELECT t1.*, reply.ct as count FROM user as t1'
            .' LEFT JOIN (SELECT chat.from, COUNT(id) as ct FROM `chat` WHERE chat.is_read_admin = 0'
            .' AND chat.`dept` = ? group by chat.from) as reply ON t1.id = reply.from'
            .' WHERE code LIKE ? ORDER BY count DESC LIMIT 10',
            [$this->in($request, 'dept'), $like]
        );

        $total = 0;
        $html = '<div style="margin-top:10px;">';
        foreach ($rows as $row) {
            $total += (int) $row->count;
            $active = Php74::eq($select, (string) $row->id) ? 'active' : '';
            $html .= '<div class="list-group-item '.$active.' cursor" onclick="ChangeUser('.(int) $row->id.', $(this));Load(0, true)">'.$this->u($row->code);
            if ($row->count !== null && (int) $row->count !== 0) {
                $html .= '<span class="badge badge-xs badge-danger">'.(int) $row->count.'</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        return response((string) json_encode(['html' => Chat::crlf($html), 'total' => $total]));
    }

    /** POST /_admin/_message/send — แอดมินส่งข้อความถึงลูกค้า (from = 0) */
    public function send(Request $request): Response
    {
        if (Php74::eq($this->php($request, 'text', null), '') || Php74::eq($this->php($request, 'id', null), '')) {
            return response('');
        }
        DB::table('chat')->insert([
            'text' => $this->in($request, 'text'),
            'date_read' => '',
            'is_read' => '0',
            'dept' => $this->in($request, 'id'),
            'from' => '0',
            'to' => $this->in($request, 'user'),
            'create_by' => (string) Legacy::userId(),
            'create_date' => DB::raw('NOW()'),
        ]);

        return response('');
    }

    /** POST /_admin/_message/send_endconversation — ข้อความของลูกค้า $user ในแผนก $id = แอดมินอ่านแล้ว */
    public function sendEndconversation(Request $request): Response
    {
        $id = $this->php($request, 'id', null);
        $user = $this->php($request, 'user', null);
        if (Php74::eq($id, 0) || Php74::eq($user, 0) || Php74::eq($id, '') || Php74::eq($user, '')) {
            return response('');
        }
        DB::table('chat')->where('from', $this->in($request, 'user'))->where('to', '0')
            ->where('dept', $this->in($request, 'id'))->update(['is_read_admin' => '1']);

        return response('');
    }

    /** POST /_admin/_message/deletechat — ลบข้อความที่ตัวเองส่ง */
    public function deletechat(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $uid = (string) Legacy::userId();
        $own = DB::table('chat')->where('id', $id)->where('create_by', $uid)->exists();
        if ($own) {
            DB::table('chat')->where('id', $id)->where('create_by', $uid)->delete();
        }

        return response('');
    }

    /** POST /_admin/_message/save_file */
    public function saveFile(Request $request): Response
    {
        return response(UploadedFile::moveScript($this->php($request, 'value')));
    }

    /** ค่าจาก DB ใน HTML: urldecode แบบเดิม แล้ว escape (เดิม echo ดิบ) */
    private function u(mixed $v): string
    {
        return ChatText::e(urldecode((string) $v));
    }
}
