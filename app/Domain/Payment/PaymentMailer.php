<?php

namespace App\Domain\Payment;

use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * อีเมลแจ้งลูกค้าของกลุ่ม payment (include จากไฟล์ save เดิม):
 *   _admin/_payment/email_approved.php, email_cancel.php, _admin/_alipay/email_success.php
 *
 * - อีเมล: เดิม sendmail() → Mailgun; ที่นี่ใช้ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เหมือนเดิม
 * - LINE: sendLine()/sendLineImage()/push_notification() เดิมเป็นฟังก์ชันว่าง (โค้ดถูก comment) → ไม่ส่งอะไร
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → ข้อความคงที่ด้านล่าง
 * - ค่าจาก DB (เหตุผลยกเลิก, หมายเหตุ) escape ก่อนใส่ HTML (เดิมต่อดิบ)
 */
final class PaymentMailer
{
    private const HEADER = '<div style="font-family: arial,sans-serif;font-size:13px;">';

    private const CENTER = '<div>';

    private const FOOTER = "<?php require_once('system_config.php'); ?>\n</div>\n<br />\n* หากมีข้อสงสัยกรุณาติดต่อ<br />\nE-mail : jlcargoth@hotmail.com<br />\nPhone : 095-550-4009<br />\nLineID : @jlcargo\n<br />\n<br />\nขอแสดงความนับถือ<br />\nJLcargo\n</div>";

    /** _admin/_payment/email_approved.php */
    public static function paymentApproved(int|string $id): void
    {
        $data = self::payment($id);
        $no = PaymentLookups::prefix('payment').$id;
        $link = Legacy::href('payment/'.$id);
        $subject = 'แจ้งรายการชำระเงินค่าขนส่งสินค้า '.$no.' (Success)';
        $body = "\n<p>แจ้งรายการชำระเงินค่าขนส่งสินค้า หมายเลขรายการ <a href='".$link."'>".$no."</a>\nได้รับการยืนยันแล้ว </p>";
        self::send('payment approved', $id, $subject, $body.self::button($link), (string) ($data->email ?? ''));
    }

    /** _admin/_payment/email_cancel.php */
    public static function paymentCancelled(int|string $id): void
    {
        $data = self::payment($id);
        $no = PaymentLookups::prefix('payment').$id;
        $link = Legacy::href('payment/'.$id);
        $subject = 'แจ้งรายการชำระเงินค่าขนส่งสินค้า '.$no.' (Cancel)';
        $body = "\n<p>แจ้งรายการชำระเงินค่าขนส่งสินค้า หมายเลขรายการ <a href='".$link."'>".$no."</a>\n"
            ."มีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ ตามเหตุผลดังข้อต่อไปนี้</p>\n<p>- ".e((string) ($data->cancel_remark ?? ''))."</p>\n";
        self::send('payment cancel', $id, $subject, $body.self::button($link), (string) ($data->email ?? ''));
    }

    /** _admin/_alipay/email_success.php */
    public static function alipaySuccess(int|string $id): void
    {
        $data = DB::table('bill_alipay')->leftJoin('user', 'bill_alipay.user', '=', 'user.id')
            ->where('bill_alipay.id', $id)->first(['bill_alipay.*', 'user.email', 'user.line_uid', 'user.id as userid']);
        $no = PaymentLookups::prefix('alipay').$id;
        $link = Legacy::href('alipay/'.$id);
        $subject = 'แจ้งรายการเติมอลีเพล '.$no.' (Success)';
        $body = "\n<p>แจ้งรายการเติมอลีเพล หมายเลขรายการ <a href='".$link."'>".$no."</a>\n"
            ."ดำเนินการสำเร็จ หลักฐานการเติมแสดงตามรายการดังต่อไปนี้</p>\n<p>- ".e((string) ($data->approved_remark ?? '')).'</p>';
        $up = (string) ($data->approved_upload ?? '');
        if ($up !== '') {
            $img = e(Legacy::hrefBlob($up));
            $body .= "<p><a href='".$img."' >\n\t\t<img src='".$img."' style='height:120px' /></a></p>";
        }
        self::send('alipay success', $id, $subject, $body.self::button($link), (string) ($data->email ?? ''));
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
        Log::info('payment notify (LINE disabled like legacy sendLine no-op)', ['kind' => $kind, 'id' => $id]);
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from('no-reply@jlcargoth.com', 'JLcargoTH');
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('payment mail failed', ['kind' => $kind, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
