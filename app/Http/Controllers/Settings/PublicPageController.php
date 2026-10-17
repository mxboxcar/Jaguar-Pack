<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * หน้าสาธารณะของเว็บเดิมที่ไม่มีข้อมูลผู้ใช้ (เดิมไม่ตรวจล็อกอิน — คงไว้ ดู PUBLIC ใน RouteSecurityTest)
 */
class PublicPageController extends Controller
{
    /** GET /gateway — gateway.php (ประกาศย้ายระบบ 2017; login/index เดิม comment ลิงก์มาที่นี่ออกแล้ว) */
    public function gateway(): View
    {
        return view('settings.public.gateway');
    }

    /** GET /index_close — index_close.php (หน้าปิดปรับปรุงชั่วคราว) */
    public function indexClose(): Response
    {
        return response(view('settings.public.index_close')->render())->header('Cache-Control', 'private');
    }

    /** GET /get_resource — get_resource.php: ข้อความทุกตัวของภาษาปัจจุบัน ต่อด้วย <br /> */
    public function resource(): Response
    {
        $out = '';
        foreach (Lang::all(Legacy::lang()) as $item) {
            $out .= (is_array($item) ? 'Array' : (string) $item).'<br />';
        }

        return response($out);
    }
}
