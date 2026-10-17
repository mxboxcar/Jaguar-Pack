<?php

namespace App\Http\Controllers\Messaging;

use App\Domain\Messaging\NoticeMail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * ไฟล์เดิมที่เป็น "ชิ้นส่วน" ให้ไฟล์อื่น include / file_get_contents แต่ก็เปิดผ่าน URL ได้ (rewrite x → x.php)
 *  email_header.php / email_center.php / email_footer.php = ชิ้น HTML ของอีเมล
 *  notify_token.php / notify_counts.php = ประกาศฟังก์ชันอย่างเดียว เปิดตรงๆ ได้หน้าว่าง
 */
class IncludeFileController extends Controller
{
    public function emailHeader(): Response
    {
        return response(NoticeMail::HEADER);
    }

    public function emailCenter(): Response
    {
        return response(NoticeMail::CENTER);
    }

    /** เปิดผ่าน URL = PHP รัน require_once แล้วพิมพ์ส่วนที่เหลือ */
    public function emailFooter(): Response
    {
        return response(NoticeMail::FOOTER_BODY);
    }

    public function blank(): Response
    {
        return response('');
    }
}
