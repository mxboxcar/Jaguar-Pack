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
 * - PACK: ไฟล์เดิมไม่มี LINE / push_notification (SR มี) → ส่งอีเมลอย่างเดียว
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → config('legacy.mail') (header/center/footer + ผู้ส่ง ร่วมกับกลุ่มอื่น)
 * - PACK: ปุ่ม "ดูรายละเอียด" สี #eb4823, padding 10px 25px 10px 0px
 * - ค่าจาก DB (หมายเหตุ/เหตุผลยกเลิก, ชื่อไฟล์แนบ) escape ก่อนใส่ HTML (เดิมต่อดิบ)
 */
final class ExportMailer
{
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
            ->where('bill_payment.id', $id)->first(['bill_payment.*', 'user.email']);
    }

    private static function button(string $link): string
    {
        return "<div style=\"height:20px\"></div>\n"
            ."<p style=\"width:200px;text-align:center;font-size:15px;background-color:#eb4823;padding:10px 25px 10px 0px;\" >\n"
            .'<a style="color:#ffffff;text-decoration:none" href="'.$link."\" >ดูรายละเอียด</a></p>\n";
    }

    private static function send(string $kind, int|string $id, string $subject, string $body, string $to): void
    {
        $html = config('legacy.mail.header').'<strong>'.$subject.'</strong>'.config('legacy.mail.center').$body.config('legacy.mail.footer');
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from(config('legacy.mail.from_address'), config('legacy.mail.from_name'));
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('export mail failed', ['kind' => $kind, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
