<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
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

    /** GET /get_resource — get_resource.php ของ PACK: require system_config.php → ข้อความทุกตัวของภาษาตาม cookie ต่อด้วย <br /> (แบบ jl; SR = จีนเสมอ) */
    public function resource(): Response
    {
        $out = '';
        foreach (Lang::all(Legacy::lang()) as $item) {
            $out .= (is_array($item) ? 'Array' : (string) $item).'<br />';
        }

        return response($out);
    }

    /** GET /resource — resource.php (PACK = SR): require resource/th.php ตรงๆ → ข้อความภาษาไทยทุกตัว ต่อด้วย <br /> */
    public function resourceTh(): Response
    {
        return response(self::listing('th'));
    }

    /**
     * resource.php เดิม require แค่ resource/th.php (ไม่โหลด system_config.php) → ค่าคงที่ admin_email ใน unapproved_user
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
