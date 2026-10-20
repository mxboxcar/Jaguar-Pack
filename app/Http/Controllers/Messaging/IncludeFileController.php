<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * ไฟล์เดิมที่เป็น "ชิ้นส่วน" ให้ไฟล์อื่น include / file_get_contents แต่ก็เปิดผ่าน URL ได้ (rewrite x → x.php)
 *  email_header.php / email_center.php / email_footer.php = ชิ้น HTML ของอีเมล (แม่แบบอยู่ใน config('legacy.mail'))
 *    SR หลัง hotfix 2026-10-18: เปิดตรงจาก URL = 403 ว่าง → ที่นี่ตอบแบบเดียวกัน
 *  notify_token.php / notify_counts.php = ประกาศฟังก์ชันอย่างเดียว เปิดตรงๆ ได้หน้าว่าง
 */
class IncludeFileController extends Controller
{
    /** GET /email_header, /email_center, /email_footer */
    public function emailPart(): Response
    {
        return response('', 403);
    }

    public function blank(): Response
    {
        return response('');
    }
}
