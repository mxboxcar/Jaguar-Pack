<?php

namespace App\Domain\Tracking;

use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * _admin/_lost_package/email_approved.php / email_cancel.php เดิม (include จาก save_approved.php / cancel.php)
 *
 * - อีเมล: เดิม sendmail() ผ่าน Mailgun → ที่นี่ใช้ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เหมือนเดิม
 * - PACK: ไม่มีส่วน LINE / push_notification (SR มี) — ส่งอีเมลอย่างเดียว
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → คัดข้อความคงที่มาไว้ที่นี่
 * - PACK: ปุ่ม "ดูรายละเอียด" สี #eb4823, ท้ายอีเมล "PACK-Cargo"; ผู้ส่ง = config('legacy.mail') (sendmail() ของ PACK — ฐานร่วม)
 */
final class LostPackageMailer
{
    // email_header/center/footer.php ของ PACK (หลัง hotfix 2026-10-18 ไฟล์มีบรรทัดโค้ด PHP กันเปิดตรง
    // ซึ่ง file_get_contents เดิมจะติดไปในอีเมลด้วย: ไม่เลียนแบบ)
    private const HEADER = '<div style="font-family: arial,sans-serif;font-size:13px;max-width: 560px">';

    private const CENTER = '<div style="text-indent: 30px;">';

    private const FOOTER = "</div>\r\nขอแสดงความนับถือ<br />\r\nPACK-Cargo\r\n</div>";

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
            ->first(['lost_package.*', 'user.email']);
        $no = TrackingLookups::prefix('lost_package').$id;
        $link = Legacy::href('lost_package/'.$id);

        $subject = 'แจ้งรายการไม่ได้สินค้า '.$no.($cancel ? ' (Cancel)' : ' (Success)');
        $body = "\n<p>แจ้งรายการไม่ได้สินค้า หมายเลขรายการ <a href='".$link."'>".$no."</a>\n"
            .($cancel ? "มีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ </p>\n" : 'ได้รับการยืนยันแล้ว </p>');
        $body .= "<div style=\"height:20px\"></div>\n";
        $body .= "<p style=\"width:200px;text-align:center;font-size:15px;background-color:#eb4823;padding:10px 25px 10px 0px;\" >\n"
            ."<a style=\"color:#ffffff;text-decoration:none\" href=\"".$link."\" >ดูรายละเอียด</a></p>\n";
        $html = self::HEADER.'<strong>'.$subject.'</strong>'.self::CENTER.$body.self::FOOTER;

        $to = (string) ($data->email ?? '');
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from(config('legacy.mail.from_address'), config('legacy.mail.from_name'));
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('lost_package mail failed', ['id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
