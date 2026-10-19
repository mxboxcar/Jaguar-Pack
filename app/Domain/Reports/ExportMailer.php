<?php

namespace App\Domain\Reports;

use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * _admin/_export/email_success.php, email_cancel.php เดิม (include จาก save_approved.php / save_delete.php ด้วย $email_id)
 *
 * - อีเมล: เดิม sendmail() → Mailgun; ที่นี่ใช้ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เหมือนเดิม
 * - LINE / push: sendLine(), sendLineImage(), push_notification() เดิมเป็นฟังก์ชันว่าง (โค้ดถูก comment) → ไม่ส่งอะไร
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → ข้อความคงที่ด้านล่าง (CRLF ตามไฟล์เดิม)
 * - ค่าจาก DB (หมายเหตุ/เหตุผลยกเลิก, ชื่อไฟล์แนบ) escape ก่อนใส่ HTML (เดิมต่อดิบ)
 */
final class ExportMailer
{
    private const HEADER = '<div style="font-family: arial,sans-serif;font-size:13px;">';

    private const CENTER = '<div>';

    private const FOOTER = "<?php require_once('system_config.php'); ?>\r\n</div>\r\n<br />\r\n* หากมีข้อสงสัยกรุณาติดต่อ<br />\r\n"
        ."E-mail : jlcargoth@hotmail.com<br />\r\nPhone : 095-550-4009<br />\r\nLineID : @jlcargo\r\n<br />\r\n<br />\r\nขอแสดงความนับถือ<br />\r\nJLcargo\r\n</div>";

    /** email_success.php */
    public static function success(int|string $id): void
    {
        $data = self::payment($id);
        $no = Rows::prefix('payment').$id;
        $link = Legacy::href('payment/'.$id);
        $subject = 'แจ้งรายการส่งส่งค้า '.$no.' (Success)';
        $body = "\n<p>แจ้งรายการส่งส่งค้า หมายเลขรายการ <a href='".$link."'>".$no."</a>\n"
            .'ดำเนินการสำเร็จ หลักฐานการส่งแสดงตามรายการดังต่อไปนี้</p>'."\n<p>- ".e((string) ($data->approved_remark ?? '')).'</p>';
        $att = (string) ($data->export_attachment ?? '');
        if ($att !== '') {
            $img = e(Legacy::hrefBlob($att));
            $body .= "<p><a href='".$img."' >\n\t\t<img src='".$img."' style='height:120px' /></a></p>";
        }
        self::send('export success', $id, $subject, $body.self::button($link), (string) ($data->email ?? ''));
    }

    /** email_cancel.php */
    public static function cancel(int|string $id): void
    {
        $data = self::payment($id);
        $no = Rows::prefix('payment').$id;
        $link = Legacy::href('payment/'.$id);
        $subject = 'แจ้งรายการส่งส่งค้า '.$no.' (Cancel)';
        $body = "\n<p>แจ้งรายการส่งส่งค้า หมายเลขรายการ <a href='".$link."'>".$no."</a>\n"
            ."มีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ ตามเหตุผลดังข้อต่อไปนี้</p>\n<p>- ".e((string) ($data->cancel_remark ?? ''))."</p>\n";
        self::send('export cancel', $id, $subject, $body.self::button($link), (string) ($data->email ?? ''));
    }

    private static function payment(int|string $id): ?object
    {
        return DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.id', $id)->first(['bill_payment.*', 'user.email', 'user.line_uid', 'user.id as userid']);
    }

    private static function button(string $link): string
    {
        return "<div style=\"height:20px\"></div>\n"
            ."<p style=\"width:200px;text-align:center;font-size:15px;background-color:#991b1e;padding:10px 25px;\" >\n"
            .'<a style="color:#ffffff;text-decoration:none" href="'.$link."\" >ดูรายละเอียด</a></p>\n";
    }

    private static function send(string $kind, int|string $id, string $subject, string $body, string $to): void
    {
        $html = self::HEADER.'<strong>'.$subject.'</strong>'.self::CENTER.$body.self::FOOTER;
        // sendLine()/sendLineImage()/push_notification() เดิมไม่ทำอะไร — ไม่ยิง LINE จริง
        Log::info('export notify (LINE disabled like legacy sendLine no-op)', ['kind' => $kind, 'id' => $id]);
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from('no-reply@jlcargoth.com', 'JLcargoTH');
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('export mail failed', ['kind' => $kind, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
