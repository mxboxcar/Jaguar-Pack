<?php

namespace App\Domain\Users;

use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyCrypt;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * อีเมลของกลุ่ม users (ไฟล์เดิมที่ถูก require_once จาก save/approve/cancel/reset):
 *   _register/email.php, _register/email_lost_password.php,
 *   _admin/_user/email.php, email_approved.php, email_cancel.php, btn_email_lost_password.php
 *
 * - เดิม sendmail() → Mailgun; ที่นี่ใช้ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เงียบเหมือนเดิม
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → ข้อความคงที่ด้านล่าง (CRLF ตามไฟล์เดิม)
 * - เนื้อหาเหมือนเดิม รวมถึง "รหัสผ่าน : <plaintext>" (เดิมเก็บรหัสแบบถอดรหัสได้แล้วส่งทางอีเมล — บันทึกเป็นช่องโหว่เดิมในรายงาน)
 *   ยกเว้นเมื่อ mailer = log (dev): แทนรหัสผ่านด้วย *** ไม่ให้รหัสจริงไปค้างใน storage/logs
 * - ค่าจาก DB (ชื่อสินค้า, เหตุผลยกเลิก, username) escape ก่อนใส่ HTML (เดิมต่อดิบ)
 */
final class UsersMailer
{
    private const HEADER = '<div style="font-family: arial,sans-serif;font-size:13px;">';

    private const CENTER = '<div>';

    private const FOOTER = "<?php require_once('system_config.php'); ?>\r\n</div>\r\n<br />\r\n* หากมีข้อสงสัยกรุณาติดต่อ<br />\r\nE-mail : jlcargoth@hotmail.com<br />\r\nPhone : 095-550-4009<br />\r\nLineID : @jlcargo\r\n<br />\r\n<br />\r\nขอแสดงความนับถือ<br />\r\nJLcargo\r\n</div>";

    /** _register/email.php — หลังสมัครสมาชิก (เดิม $data['emil'] พิมพ์ผิด = ว่างเสมอ) */
    public static function registered(int $id): void
    {
        $u = self::user($id);
        $subject = 'แจ้งการสมัครสมาชิก '.($u->username ?? '');
        $body = "\n<p>ท่านได้ยื่ยการสมัครสมาชิกกับทาง JLcargo ไว้เป็นที่เรียบร้อยแล้ว การตรวจสอบข้อมูลจะใช้เวลา 1-2 วันทำการ รหัสผ่านเข้าสู่ระบบจะถูกส่งไปยัง  หากต้องการเปลี่ยนแปลงข้อมูลใดๆ กรุณาแจ้งให้เราทราบ</p>";
        self::send('register', $id, $subject, $body, $u);
    }

    /** _register/email_lost_password.php — ลืมรหัสผ่าน (ปุ่มสีแดง) */
    public static function lostPassword(int $id): void
    {
        $u = self::user($id);
        $subject = 'แจ้งรหัสผ่านใหม่ '.($u->code ?? '').' (Success)';
        $body = self::credentials($u, "\n<p>ระบบได้ทำการรีเซตรหัสผ่านของท่านแล้ว</p>\n<p>ท่านสามารถเข้าระบบโดย ผู้ใช้งานและรหัสผ่านดังต่อไปนี้</p>")
            .self::button('background-color:#991b1e;padding: 10px 25px;');
        self::send('lost password', $id, $subject, $body, $u);
    }

    /** _admin/_user/btn_email_lost_password.php — แอดมินกด reset pass (ปุ่มสีฟ้า) */
    public static function adminResetPassword(int $id): void
    {
        $u = self::user($id);
        $subject = 'แจ้งรหัสผ่านใหม่ '.($u->code ?? '').' (Success)';
        $body = self::credentials($u, "\n<p>ระบบได้ทำการรีเซตรหัสผ่านของท่านแล้ว</p>\n<p>ท่านสามารถเข้าระบบโดย ผู้ใช้งานและรหัสผ่านดังต่อไปนี้</p>")
            .self::button('background-color:#7fbfff;padding:10px 25px 10px 0px;');
        self::send('admin reset password', $id, $subject, $body, $u);
    }

    /** _admin/_user/email_approved.php — อนุมัติ / แอดมินสร้างผู้ใช้ใหม่ */
    public static function approved(int $id): void
    {
        $u = self::user($id);
        $code = (string) ($u->code ?? '');
        $subject = 'แจ้งการอนุมัติสมัครสมาชิก '.$code.' (Success)';
        $body = self::credentials($u, "\n<p>ผลการสมัครสมาชิกของท่านได้อนุมัติเป็นที่เรียบร้อยแล้ว โดยรหัสลูกค้าที่ใช้ในการสั่งซื้อและจัดส่งสินค้าของท่านคือ ".e($code)."\nท่านสามารถเข้าระบบโดย ผู้ใช้งานและรหัสผ่านดังต่อไปนี้</p>")
            .self::button('background-color:#991b1e;padding:10px 25px;');
        self::send('approved', $id, $subject, $body, $u);
    }

    /** _admin/_user/email_cancel.php — ไม่อนุมัติ (ลิงก์ alipay/<id> ผิดตั้งแต่เดิม คงไว้) */
    public static function cancelled(int $id): void
    {
        $u = self::user($id);
        $subject = 'แจ้งการอนุมัติบัญชี '.($u->code ?? '').' (Cancel)';
        $body = "\n<p>แจ้งการอนุมัติบัญชีของคุณไม่ผ่านการอนุมัติ หมายเลขรายการ <a href='".Legacy::href('alipay/'.$id)."'>".Lookup::getPrefix('user').$id."</a>\n"
            ."มีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ ตามเหตุผลดังข้อต่อไปนี้</p>\n<p>- ".e((string) ($u->cancel_remark ?? ''))."</p>\n";
        self::send('cancel', $id, $subject, $body, $u);
    }

    /** _admin/_user/email.php — แอดมินแก้ข้อมูลลูกค้า (ตารางเรตราคา) */
    public static function updated(int $id): void
    {
        $u = self::user($id);
        $rows = DB::table('user_type_detail')
            ->leftJoin('product_type', 'product_type.id', '=', 'user_type_detail.product_type_id')
            ->where('user_type_detail.user_type_id', $u->type ?? '')
            ->where('user_type_detail.is_delete', '0')
            ->whereIn('user_type_detail.type', [0, 1])
            ->orderBy('user_type_detail.id')
            ->get(['user_type_detail.kg', 'user_type_detail.cubic', 'product_type.name', 'user_type_detail.type']);
        $car = $rows->filter(fn ($r) => (int) $r->type === 0)->values();
        $ship = $rows->filter(fn ($r) => (int) $r->type === 1)->values();

        $subject = 'แจ้งเตือนการแก้ไขข้อมูล '.($u->code ?? '').' (Update)';
        $body = self::credentials($u, "\n<p>ทางทีมงานได้มีการแก้ไขข้อมูลของลูกค้า รายละเอียดดังต่อไปนี้</p>");
        $body .= '<div style="text-indent:0px!important;"><p style="margin-top: 40px;">ค่าขนส่งทางรถ (4-6 วัน)</p>'."\n".self::rateTable($car)
            ."\n".'<div style="height:20px"></div>'."\n".'<p>ค่าขนส่งทางเรือ</p>'."\n".self::rateTable($ship).'</div>';
        $body .= self::button('background-color:#991b1e;padding:10px 25px;');
        self::send('update', $id, $subject, $body, $u);
    }

    /** @param \Illuminate\Support\Collection<int, object> $rows */
    private static function rateTable($rows): string
    {
        $th = 'border: 1px #000 solid;background-color: #ccc;';
        $td = 'border: 1px #000 solid;text-align: right;';
        $h = '<table style="width:100%;border-spacing: 0;border-collapse: collapse;">'."\n<thead>\n\t<tr>\n\t\t".'<th style="'.$th.'"></th>';
        foreach ($rows as $r) {
            $h .= '<th style="'.$th.'">'.e((string) $r->name).'</th>';
        }
        $h .= "</tr>\n</thead>\n<tbody>\n\t<tr>\n\t\t".'<td style="'.$th.'text-align:left">คิดตามกิโล</td>';
        foreach ($rows as $r) {
            $h .= '<td style="'.$td.'">'.$r->kg.'&nbsp;฿</td>';
        }
        $h .= "</tr>\n\t<tr>\n\t\t".'<td style="'.$th.'text-align:left">คิดตามขนาด</td>';
        foreach ($rows as $r) {
            $h .= '<td style="'.$td.'">'.$r->cubic.'&nbsp;฿</td>';
        }

        return $h."</tr>\n</tbody>\n</table>";
    }

    private static function credentials(?object $u, string $intro): string
    {
        $pass = LegacyCrypt::decrypt($u->password ?? null);
        if (config('mail.default') === 'log') {
            $pass = '***';
        }

        return $intro."\n<p>ชื่อผู้ใช้งาน : ".e((string) ($u->username ?? ''))."</p>\n<p>รหัสผ่าน : ".e($pass).'</p>';
    }

    private static function button(string $style): string
    {
        return "<div style=\"height:20px\"></div>\n"
            .'<p style="width:200px;text-align:center;font-size:15px;'.$style."\" >\n"
            .'<a style="color:#ffffff;text-decoration:none" href="'.Legacy::href('profile/')."\" >ดูรายละเอียด</a></p>\n";
    }

    private static function user(int $id): ?object
    {
        return DB::table('user')->where('id', $id)->first();
    }

    private static function send(string $kind, int $id, string $subject, string $body, ?object $u): void
    {
        $to = (string) ($u->email ?? '');
        $html = self::HEADER.'<strong>'.$subject.'</strong>'.self::CENTER.$body.self::FOOTER;
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from('no-reply@jlcargoth.com', 'JLcargoTH');
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('users mail failed', ['kind' => $kind, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
