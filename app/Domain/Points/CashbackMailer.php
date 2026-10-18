<?php

namespace App\Domain\Points;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * _admin/_shop_cashback/email_approved.php / email_cancel.php เดิม (include จาก save_approved / save_cancel)
 *
 * - อีเมล: เดิม sendmail() → Mailgun; ที่นี่ใช้ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง) ห่อ try/catch เหมือนเดิม
 * - LINE / push: sendLine() และ push_notification() เดิมเป็นฟังก์ชันว่าง (โค้ดถูก comment) → ไม่ส่งอะไร
 * - แม่แบบ email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → ข้อความคงที่ด้านล่าง (เหมือน PaymentMailer)
 * - ค่าจาก DB (เลขคำสั่งซื้อ, เหตุผลยกเลิก) escape ก่อนใส่ HTML (เดิมต่อดิบ)
 */
final class CashbackMailer
{
    private const HEADER = '<div style="font-family: arial,sans-serif;font-size:13px;">';

    private const CENTER = '<div>';

    private const FOOTER = "<?php require_once('system_config.php'); ?>\r\n</div>\r\n<br />\r\n* หากมีข้อสงสัยกรุณาติดต่อ<br />\r\nE-mail : jlcargoth@hotmail.com<br />\r\nPhone : 095-550-4009<br />\r\nLineID : @jlcargo\r\n<br />\r\n<br />\r\nขอแสดงความนับถือ<br />\r\nJLcargo\r\n</div>";

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
            ->first(['shop_cashback.*', 'user.email', 'user.line_uid', 'user.id as userid']);
        $detail = DB::table('shop_cashback_detail')->where('id', (string) $detailId)->first();

        return [$head, $detail];
    }

    private static function button(string $link): string
    {
        return "<div style=\"height:20px\"></div>\r\n"
            ."<p style=\"width:200px;text-align:center;font-size:15px;background-color:#991b1e;padding:10px 25px;\" >\r\n"
            .'<a target="_blank" style="color:#ffffff;text-decoration:none" href="'.$link."\" >ดูรายละเอียด</a></p>\r\n";
    }

    private static function send(string $kind, mixed $id, string $subject, string $body, string $to): void
    {
        $html = self::HEADER.'<strong>'.$subject.'</strong>'.self::CENTER.$body.self::FOOTER;
        // sendLine()/push_notification() เดิมไม่ทำอะไร — ไม่ยิง LINE จริง
        Log::info('points notify (LINE disabled like legacy sendLine no-op)', ['kind' => $kind, 'id' => $id]);
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            Mail::html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from('no-reply@jlcargoth.com', 'JLcargoTH');
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('points mail failed', ['kind' => $kind, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }
}
