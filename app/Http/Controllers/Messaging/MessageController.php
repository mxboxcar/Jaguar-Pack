<?php

namespace App\Http\Controllers\Messaging;

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
 * แชทของลูกค้า: _message/*.php เดิม
 * ทุก query ผูกกับผู้ที่ล็อกอิน (Legacy::userId()) — เดิม load.php ไม่ตรวจล็อกอินเลย:
 * ไม่ล็อกอิน = getUserID() '' → `to` = '' (= 0) → อ่านข้อความที่ลูกค้าทุกคนส่งถึงแอดมินได้
 */
class MessageController extends Controller
{
    use ReadsLegacyInput;

    /** GET /message — _message/index.php */
    public function index(): View
    {
        return view('messaging.message.index');
    }

    /** POST /_message/load_dept — รายการแผนก + จำนวนข้อความที่ยังไม่อ่าน */
    public function loadDept(Request $request): Response
    {
        $id = $this->php($request, 'id', null);
        $uid = (string) Legacy::userId();
        $rows = DB::table('chat_dept as t1')
            ->select('t1.*')
            ->selectSub(fn ($q) => $q->from('chat')->selectRaw('COUNT(id)')
                ->where('is_read', '0')->where('to', $uid)->whereColumn('dept', 't1.id'), 'count')
            ->get();

        $html = '<div style="margin-top:10px;font-size:13px;">';
        foreach ($rows as $row) {
            $active = Php74::eq($id, (string) $row->id) ? 'active' : '';
            $html .= '<div class="list-group-item  '.$active.'  cursor" onclick="ChangeDept('.(int) $row->id.', $(this));Load(1, true)">
                <div class="chat-avatar">
                    <i style="float:left" class="'.ChatText::e($row->icon).'"></i>';
            if ((int) $row->count !== 0) {
                $html .= '<i style="float:left;margin: -6px;font-style: normal;" class="badge badge-xs badge-danger">'.(int) $row->count.'</i>';
            }
            $html .= '</div>
                <div class="conversation-text">
                    <div class="ctext-wrap" style="margin-left:25px;"> '.ChatText::e($row->name).'</div>
                </div>
            </div>';
        }
        $html .= '</div>';

        return response(Chat::crlf($html));
    }

    /** POST /_message/load — ข้อความในแผนกที่เลือก (JSON: html ที่ htmlentities แล้ว, title, page, sql) */
    public function load(Request $request): Response
    {
        $page = $this->php($request, 'page', null);
        $dept = $this->php($request, 'id', null);
        if (Php74::eq($dept, 0)) {
            return response('');
        }
        $deptSql = $this->in($request, 'id');
        $uid = (string) Legacy::userId();

        $title = DB::table('chat_dept')->where('id', $deptSql)->value('name');
        [$paging, $offset] = Chat::paging($page);
        $rows = Chat::rows(fn ($q) => $q->where(fn ($w) => $w->where('chat.to', $uid)->orWhere('chat.from', $uid))
            ->where('chat.dept', $deptSql), $offset);

        $html = '<div id="page">';
        if ($rows !== []) {
            $html .= Chat::loadMore($page);
            foreach (array_reverse($rows) as $row) {
                if (Php74::eq((string) $row->from, $uid)) {
                    $html .= '
                        <li class="clearfix odd">
                            <div class="conversation-text">
                                <div style="text-align:right;display: inline-block;padding: 2px 10px;position: relative;font-size: 9px;">'.Chat::stamp($row->create_date).'</div>
                                <div class="ctext-wrap" style="float: right;clear: both;">
                                    <p style="font-size: 13px;">
                                       '.ChatText::render($row->text).'
                                    </p>
                                </div>
                            </div>
                        </li>';
                } else {
                    $html .= '<li class="clearfix ">
                            <div class="conversation-text">
                            <div style="display: inline-block;padding: 2px 10px;position: relative;font-size: 9px;">'.Chat::stamp($row->create_date).'</div>
                                <div class="ctext-wrap" style="float: left;clear: both;">
                                    <i>srshippingsystem Support Team</i>
                                    <p style="font-size: 13px;">
                                       '.ChatText::render($row->text).'
                                    </p>
                                </div>
                            </div>
                        </li>';
                }
            }
        }
        $html .= '</div>';

        return response((string) json_encode([
            'html' => htmlentities(Chat::crlf($html), ENT_COMPAT | ENT_HTML401, 'UTF-8'),
            'title' => $title,
            'page' => null,
            'sql' => $paging,
        ]));
    }

    /** POST /_message/send — ลูกค้าส่งข้อความถึงแอดมิน (to = 0) */
    public function send(Request $request): Response
    {
        if (Php74::eq($this->php($request, 'text', null), '') || Php74::eq($this->php($request, 'id', null), '')) {
            return response('');
        }
        $uid = (string) Legacy::userId();
        DB::table('chat')->insert([
            'text' => $this->in($request, 'text'),
            'date_read' => '',
            'is_read' => '0',
            'dept' => $this->in($request, 'id'),
            'from' => $uid,
            'to' => '0',
            'create_by' => $uid,
            'create_date' => DB::raw('NOW()'),
            'is_read_admin' => '0',
        ]);

        return response('');
    }

    /** POST /_message/send_endconversation — ทำเครื่องหมายอ่านแล้ว (เฉพาะข้อความถึงตัวเอง) */
    public function sendEndconversation(Request $request): Response
    {
        if (Php74::eq($this->php($request, 'id', null), '')) {
            return response('');
        }
        DB::table('chat')->where('to', (string) Legacy::userId())->where('dept', $this->in($request, 'id'))
            ->update(['is_read' => '1']);

        return response('');
    }

    /** POST /_message/save_file — ไฟล์แนบที่อัปโหลดเข้า temp แล้ว → JS ให้หน้าเว็บสั่งย้ายขึ้น blob */
    public function saveFile(Request $request): Response
    {
        return response(UploadedFile::moveScript($this->php($request, 'value')));
    }
}
