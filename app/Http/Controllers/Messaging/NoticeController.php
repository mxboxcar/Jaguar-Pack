<?php

namespace App\Http\Controllers\Messaging;

use App\Domain\Messaging\Chat;
use App\Domain\Messaging\NoticeText;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\ReadsLegacyInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** ประกาศฝั่งลูกค้า: _notice/*.php เดิม (ประกาศเป็นข้อมูลกลาง ไม่ผูกกับผู้ใช้) */
class NoticeController extends Controller
{
    use ReadsLegacyInput;

    /** GET /notice — _notice/index.php */
    public function index(): View
    {
        return view('messaging.notice.index');
    }

    /** GET /notice/{id} — _notice/form.php (เดิมไม่เช็ค is_show: ประกาศที่ซ่อนอยู่ก็เปิดด้วย id ได้ — คงไว้) */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $row = DB::table('notice')->where('id', $id)->where('is_delete', 0)->first();

        return view('messaging.notice.form', [
            'd' => (array) ($row ?? []),
            'prefix' => NoticeText::prefix(),
        ]);
    }

    /** POST /_notice/grid — รายการประกาศที่เปิดแสดง */
    public function grid(Request $request): Response
    {
        $q = DB::table('notice')->where('is_delete', 0)->where('is_show', 1);
        $total = (clone $q)->count();
        $page = $this->php($request, 'page', null);
        [, $offset] = Chat::paging($page);
        $rows = $offset === null ? [] : $q->orderByDesc('id')->offset($offset)->limit(10)->get()->all();

        return response(view('messaging.notice.grid', [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'txtsearch' => $this->php($request, 'txtsearch', null),
            'ajaxpage' => '_notice/grid',
        ])->render());
    }
}
