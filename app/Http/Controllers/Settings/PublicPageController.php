<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Legacy\Lang;
use Illuminate\Http\Response;

/**
 * หน้าสาธารณะของเว็บเดิมที่ไม่มีข้อมูลผู้ใช้ (เดิมไม่ตรวจล็อกอิน — คงไว้ ดู PUBLIC ใน RouteSecurityTest)
 */
class PublicPageController extends Controller
{
    /** GET /index_close — index_close.php (หน้าปิดปรับปรุงชั่วคราว) */
    public function indexClose(): Response
    {
        return response(view('settings.public.index_close')->render())->header('Cache-Control', 'private');
    }

    /** GET /get_resource — get_resource.php: SR require resource/cn.php ตรงๆ = ข้อความภาษาจีนเสมอ (jl: ภาษาตาม cookie) ต่อด้วย <br /> */
    public function resource(): Response
    {
        return response(self::listing('cn'));
    }

    /** GET /resource — resource.php (SR เท่านั้น): ข้อความภาษาไทยทุกตัว ต่อด้วย <br /> */
    public function resourceTh(): Response
    {
        return response(self::listing('th'));
    }

    /**
     * ไฟล์เดิมของ SR require แค่ resource/<lang>.php (ไม่โหลด system_config.php) → ค่าคงที่ admin_email ใน unapproved_user
     * ไม่ได้ define = PHP 7.4 พิมพ์ชื่อค่าคงที่ "admin_email" แทนอีเมล — เลียนแบบผลเดิม
     */
    private static function listing(string $lang): string
    {
        $out = '';
        foreach (Lang::all($lang) as $key => $item) {
            $item = is_array($item) ? 'Array' : (string) $item;
            if ($key === 'unapproved_user') {
                $item = (string) preg_replace('~<br/>[^<]*$~', '<br/>admin_email', $item);
            }
            $out .= $item.'<br />';
        }

        return $out;
    }
}
