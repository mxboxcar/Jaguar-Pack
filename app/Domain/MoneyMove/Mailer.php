<?php

namespace App\Domain\MoneyMove;

use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * _admin/_refill/email_approved.php, email_cancel.php, _admin/_transfer/email_success.php, email_cancel.php เดิม
 * (include จาก save_approved.php / cancel.php / save.php เมื่อ !debug_mail)
 *
 * - อีเมล: เดิม sendmail() ผ่าน Mailgun → ที่นี่ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เงียบเหมือนเดิม
 * - LINE: sendLine()/sendLineImage()/push_notification() เดิมเป็นฟังก์ชันว่าง → ไม่ส่งอะไร (log ไว้ตรวจเท่านั้น)
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → SR: config('legacy.mail') (header/center/footer
 *   + ผู้ส่ง SRShippingSystem <no-reply@srshippingsystem.com> ของ sendmail() เดิม — ใช้ร่วมกับทุกกลุ่ม)
 * - ข้อความที่แอดมินพิมพ์ (approved_remark / cancel_remark) ถูก escape (เดิมใส่ดิบลง HTML)
 * - id ที่ POST มาถูกแทนด้วย id ของแถวที่พบ (เดิมใส่ $_POST['id'] ดิบลงหัวเรื่อง/ลิงก์ HTML เช่น "123<img ...>" ที่ MySQL แปลงเป็น 123 แล้วส่งถึงลูกค้าเจ้าของรายการ 123);
 *   ไม่พบแถว = ไม่ส่ง (เดิมส่งไปที่อยู่ว่าง = ล้มเงียบ)
 * - _admin/_withdrawcash/save_approved.php / cancel.php เดิม include email_success.php / email_cancel.php ที่ไม่มีไฟล์อยู่จริง → ไม่เคยส่ง (ไม่ทำอะไร)
 */
final class Mailer
{
    /** SR: ปุ่ม "ดูรายละเอียด" สี #a3b2f1 padding ขวา 0 (ของ jl เป็นสีแดง); ลิงก์ไม่เปิดแท็บใหม่ตามนโยบาย */
    private const BUTTON = "<div style=\"height:20px\"></div>\n<p style=\"width:200px;text-align:center;font-size:15px;background-color:#a3b2f1;padding:10px 25px 10px 0px;\" >\n<a style=\"color:#ffffff;text-decoration:none\" href=\"%s\" >ดูรายละเอียด</a></p>\n";

    public static function refillApproved(int|string $id): void
    {
        $d = self::row('refill', $id);
        if ($d === null) {
            return;
        }
        $id = $d->id;
        $no = Lookups::prefix('refill').$id;
        $link = Legacy::href('refill/'.$id);
        $subject = 'แจ้งรายการเติมเงิน '.$no.' (Success)';
        $body = "\n<p>แจ้งรายการเติมเงิน หมายเลขรายการ <a href='".$link."'>".$no."</a>\nได้รับการยืนยันแล้ว </p>";
        self::send('refill', $id, $d, $subject, $body.sprintf(self::BUTTON, $link));
    }

    public static function refillCancelled(int|string $id): void
    {
        $d = self::row('refill', $id);
        if ($d === null) {
            return;
        }
        $id = $d->id;
        $no = Lookups::prefix('refill').$id;
        $link = Legacy::href('refill/'.$id);
        $subject = 'แจ้งรายการเติมเงิน '.$no.' (Cancel)';
        $body = "\n<p>แจ้งรายการเติมเงิน หมายเลขรายการ <a href='".$link."'>".$no."</a>\nมีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ </p>\n";
        self::send('refill', $id, $d, $subject, $body.sprintf(self::BUTTON, $link));
    }

    public static function transferSuccess(int|string $id): void
    {
        $d = self::row('bill_transfer', $id);
        if ($d === null) {
            return;
        }
        $id = $d->id;
        $no = Lookups::prefix('transfer').$id;
        $link = Legacy::href('transfer/'.$id);
        $subject = 'แจ้งรายการโอนเงินไปจีน '.$no.' (Success)';
        $body = "\n<p>แจ้งรายการโอนเงินไปจีน หมายเลขรายการ <a href='".$link."'>".$no."</a>\nดำเนินการสำเร็จ หลักฐานการเติมแสดงตามรายการดังต่อไปนี้</p>\n<p>- ".e($d->approved_remark ?? '').'</p>';
        $up = (string) ($d->approved_upload ?? '');
        if ($up !== '') {
            $u = e(Legacy::hrefBlob($up));
            $body .= "<p><a href='".$u."' >\n\t\t<img src='".$u."' style='height:120px' /></a></p>";
        }
        self::send('transfer', $id, $d, $subject, $body.sprintf(self::BUTTON, $link));
    }

    public static function transferCancelled(int|string $id): void
    {
        $d = self::row('bill_transfer', $id);
        if ($d === null) {
            return;
        }
        $id = $d->id;
        $no = Lookups::prefix('transfer').$id;
        $link = Legacy::href('transfer/'.$id);
        $subject = 'แจ้งรายการโอนเงินไปจีน '.$no.' (Cancel)';
        $body = "\n<p>แจ้งรายการโอนเงินไปจีน หมายเลขรายการ <a href='".$link."'>".$no."</a>\nมีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ ตามเหตุผลดังข้อต่อไปนี้</p>\n<p>- ".e($d->cancel_remark ?? '')."</p>\n";
        self::send('transfer', $id, $d, $subject, $body.sprintf(self::BUTTON, $link));
    }

    private static function row(string $table, int|string $id): ?object
    {
        return DB::table($table)->leftJoin('user', $table.'.user', '=', 'user.id')
            ->where($table.'.id', $id)
            ->first([$table.'.*', 'user.email', 'user.line_uid', 'user.id as userid']);
    }

    private static function send(string $module, int|string $id, ?object $d, string $subject, string $body): void
    {
        $mail = config('legacy.mail');
        $html = $mail['header'].'<strong>'.$subject.'</strong>'.$mail['center'].$body.$mail['footer'];
        // sendLine()/sendLineImage()/push_notification() เดิมไม่ทำอะไร — ไม่ยิง LINE จริง
        Log::info('money-move notify (LINE disabled like legacy sendLine no-op)', [
            'module' => $module, 'id' => $id, 'line_uid_set' => (string) ($d->line_uid ?? '') !== '', 'date' => Format::getthaidate(date('Y-m-d'), false, true),
        ]);
        $to = (string) ($d->email ?? '');
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject, $mail) {
                $m->to($to)->subject($subject)->from($mail['from_address'], $mail['from_name']);
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('money-move mail failed', ['module' => $module, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
