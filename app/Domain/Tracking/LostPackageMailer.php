<?php

namespace App\Domain\Tracking;

use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * _admin/_lost_package/email_approved.php / email_cancel.php เดิม (include จาก save_approved.php / cancel.php)
 *
 * - อีเมล: เดิม sendmail() ผ่าน Mailgun → ที่นี่ใช้ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เหมือนเดิม
 * - LINE: เดิม sendLine() เป็นฟังก์ชันว่าง (โค้ดถูก comment ทั้งหมด) และ push_notification() ว่าง → ไม่ส่งอะไร (log ข้อความไว้ตรวจเท่านั้น)
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → คัดข้อความคงที่มาไว้ที่นี่
 */
final class LostPackageMailer
{
    private const HEADER = "<div style=\"font-family: arial,sans-serif;font-size:13px;\"><div>\n<?php require_once('system_config.php'); ?>\n";

    private const CENTER = "</div>\n<br />";

    private const FOOTER = "\n* หากมีข้อสงสัยกรุณาติดต่อ<br />\nE-mail : jlcargoth@hotmail.com<br />\nPhone : 095-550-4009<br />\nLineID : @jlcargo\n<br />\n<br />\nขอแสดงความนับถือ<br />\nJLcargo\n</div>";

    public static function approved(int|string $id): void
    {
        self::send($id, false);
    }

    public static function cancelled(int|string $id): void
    {
        self::send($id, true);
    }

    private static function send(int|string $id, bool $cancel): void
    {
        $data = DB::table('lost_package')->leftJoin('user', 'lost_package.user', '=', 'user.id')
            ->where('lost_package.id', $id)
            ->first(['lost_package.*', 'user.email', 'user.line_uid', 'user.id as userid']);
        $no = TrackingLookups::prefix('lost_package').$id;
        $link = Legacy::href('lost_package/'.$id);

        $subject = 'แจ้งรายการไม่ได้สินค้า '.$no.($cancel ? ' (Cancel)' : ' (Success)');
        $body = "\n<p>แจ้งรายการไม่ได้สินค้า หมายเลขรายการ <a href='".$link."'>".$no."</a>\n"
            .($cancel ? "มีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ </p>\n" : 'ได้รับการยืนยันแล้ว </p>');
        $body .= "<div style=\"height:20px\"></div>\n";
        $body .= "<p style=\"width:200px;text-align:center;font-size:15px;background-color:#991b1e;padding:10px 25px;\" >\n"
            ."<a style=\"color:#ffffff;text-decoration:none\" href=\"".$link."\" >ดูรายละเอียด</a></p>\n";
        $html = self::HEADER.'<strong>'.$subject.'</strong>'.self::CENTER.$body.self::FOOTER;

        $line = "แจ้งรายการไม่ได้สินค้า\nวันที่ ".Format::getthaidate(date('Y-m-d'), false, true)."\n\nหมายเลขรายการ : ".$no."\n"
            .($cancel ? "สถานะ : ยกเลิก\nเหตุผล : ".($data->cancel_remark ?? '')."\n" : "สถานะ : ดำเนินการสำเร็จ \n")
            ."\n\tดูเพิ่มเติมได้ที่ ".$link;
        // sendLine()/push_notification() เดิมไม่ทำอะไร — ไม่ยิง LINE จริง
        Log::info('lost_package notify (LINE disabled like legacy sendLine no-op)', ['id' => $id, 'line_uid_set' => (string) ($data->line_uid ?? '') !== '', 'chars' => mb_strlen($line)]);

        $to = (string) ($data->email ?? '');
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from('no-reply@jlcargoth.com', 'JLcargoTH');
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('lost_package mail failed', ['id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
