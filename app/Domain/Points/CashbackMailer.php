<?php

namespace App\Domain\Points;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * _admin/_shop_cashback/email_approved.php / email_cancel.php เดิม (include จาก save_approved / save_cancel)
 *
 * - อีเมล: เดิม sendmail() → Mailgun; ที่นี่ใช้ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เหมือนเดิม
 * - PACK: ไม่มี sendLine() / push_notification() ในไฟล์ (SR/jl มี แต่เป็นฟังก์ชันว่าง) → ไม่ส่งอะไรนอกจากอีเมล
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → config('legacy.mail') (header/center/footer + ผู้ส่ง)
 * - PACK: ปุ่ม "ดูรายละเอียด" width:100% สี #eb4823 padding 10px 0px (SR #a3b2f1, jl #991b1e); ลิงก์ไม่เปิดแท็บใหม่ (ไม่มี target="_blank" ตามนโยบาย)
 * - ค่าจาก DB (เลขคำสั่งซื้อ, เหตุผลยกเลิก) escape ก่อนใส่ HTML (เดิมต่อดิบ)
 */
final class CashbackMailer
{
    /** email_approved.php: $email_id = shop_cashback.id, $ids = shop_cashback_detail.id */
    public static function approved(mixed $cashbackId, mixed $detailId): void
    {
        [$head, $detail] = self::load($cashbackId, $detailId);
        $no = Grid::prefixText('shop_cashback').$cashbackId;
        $link = legacy_href('shop_cashback/'.$cashbackId);
        $subject = 'แจ้งร้านค้าคืนเงิน '.$no.' (Success)';
        $body = "\r\n<p>แจ้งร้านค้าคืนเงิน หมายเลขรายการ <a href='".$link."'>".$no."</a>\r\nได้รับการยืนยันแล้ว </p>\r\n"
            .'<p>รายการสั่งซื้อ : '.e((string) ($detail->order ?? ''))."</p>\r\n"
            .'<p>คืนเงิน : ¥'.e((string) ($detail->price ?? '')).'</p>';
        self::send('shop_cashback approved', $cashbackId, $subject, $body.self::button($link), (string) ($head->email ?? ''));
    }

    /** email_cancel.php */
    public static function cancelled(mixed $cashbackId, mixed $detailId): void
    {
        [$head, $detail] = self::load($cashbackId, $detailId);
        $no = Grid::prefixText('shop_cashback').$cashbackId;
        $link = legacy_href('shop_cashback/'.$cashbackId);
        $subject = 'แจ้งร้านค้าคืนเงิน '.$no.' (Cancel)';
        $body = "\r\n<p>แจ้งร้านค้าคืนเงิน หมายเลขรายการ <a href='".$link."'>".$no."</a>\r\nมีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ </p>\r\n"
            .'<p>- '.e((string) ($detail->cancel_remark ?? ''))."</p>\r\n"
            .'<p>รายการสั่งซื้อ : '.e((string) ($detail->order ?? ''))."</p>\r\n";
        self::send('shop_cashback cancel', $cashbackId, $subject, $body.self::button($link), (string) ($head->email ?? ''));
    }

    /** @return array{0: ?object, 1: ?object} */
    private static function load(mixed $cashbackId, mixed $detailId): array
    {
        $head = DB::table('shop_cashback')->leftJoin('user', 'shop_cashback.user', '=', 'user.id')
            ->where('shop_cashback.id', (string) $cashbackId)
            ->first(['shop_cashback.*', 'user.email']);
        $detail = DB::table('shop_cashback_detail')->where('id', (string) $detailId)->first();

        return [$head, $detail];
    }

    private static function button(string $link): string
    {
        return "<div style=\"height:20px\"></div>\r\n"
            ."<p style=\"width:100%;text-align:center;font-size:15px;background-color:#eb4823;padding:10px 0px;\" >\r\n"
            .'<a style="color:#ffffff;text-decoration:none" href="'.$link."\" >ดูรายละเอียด</a></p>\r\n";
    }

    private static function send(string $kind, mixed $id, string $subject, string $body, string $to): void
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
            Log::warning('points mail failed', ['kind' => $kind, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
