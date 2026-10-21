<?php

namespace App\Domain\Order;

use App\Domain\Money\Money;
use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * อีเมลแจ้งลูกค้าของกลุ่ม order: _admin/_order/email_{approved,cancel,cashback}.php และ _admin/_taobao/email_{approved,cancel}.php
 * (เดิม include จาก save_approved / delete / save_cashback / save_cancel)
 *
 * - อีเมล: เดิม sendmail() ผ่าน Mailgun → ที่นี่ Mail ของ Laravel; ถ้า config legacy.external.enabled = false (dev)
 *   บังคับใช้ mailer "log" เสมอ = ไม่ส่งจริงแน่นอน แม้ MAIL_MAILER จะถูกตั้งผิด
 * - LINE: ไฟล์ email_*.php ของ PACK ไม่มี sendLine()/push_notification() (SR มีแต่เป็นฟังก์ชันว่าง) → ไม่ส่ง
 * - email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → config('legacy.mail') (header/center/footer + ผู้ส่ง — ฐานร่วม)
 */
final class OrderMailer
{
    /** ปุ่ม "ดูรายละเอียด" ของ PACK (สี #eb4823; SR #a3b2f1): order = กว้าง 200px, taobao = 100% (target="_blank" ตัดออกตามที่เจ้าของสั่ง) */
    private const BUTTON = "<div style=\"height:20px\"></div>\n<p style=\"width:200px;text-align:center;font-size:15px;background-color:#eb4823;padding:10px 25px 10px 0px;\" >\n<a style=\"color:#ffffff;text-decoration:none\" href=\"%s\" >ดูรายละเอียด</a></p>\n";

    private const BUTTON_TAOBAO = "<div style=\"height:20px\"></div>\n<p style=\"width:100%%;text-align:center;font-size:15px;background-color:#eb4823;padding:10px 0px;\" >\n<a style=\"color:#ffffff;text-decoration:none\" href=\"%s\" >ดูรายละเอียด</a></p>\n";

    /** email_approved.php (order) */
    public static function orderApproved(int $id): void
    {
        $data = self::orderHead($id);
        $no = OrderLookups::prefix('order').$id;
        $subject = 'แจ้งรายการสั่งซื้อ '.$no.' (Success)';
        $body = "\n<p>แจ้งรายการสั่งซื้อ หมายเลขรายการ <a href='".Legacy::href('order/'.$id)."'>".$no."</a>\n"
            .'ได้รับการยืนยันแล้ว กรุณาชำระภายใน 3 วัน มิเช่นนั้นรายการของคุณอาจถูกยกเลิก ทางบริษัทจะทำการยกเลิกออเดอร์ของท่านทันทีและจะหักค่าบริการยกเลิกออเดอร์ละ 50 บาทค่ะ การไม่ชำระค่าสินค้าหลังจากเราตรวจสอบออเดอร์ให้ภายใน 7 วัน จะส่งผลให้เมื่อท่านส่งออเดอร์มาใหม่ ออเดอร์ของท่านจะได้ตรวจสอบช้าขึ้นค่ะ เพื่อความสะดวกเเละรวดเร็วในการสั่งซื้อสินค้า ไม่ทำให้ลูกค้าเเล้วก็พนักงานสับสนในการสั่งซื้อสินค้า และไม่เสียเวลาลูกค้าท่านอื่นๆ ที่ต้องการสั่งซื้อสินค้าจริงๆรบกวนลูกค้าช่วยตรวจสอบก่อนส่งออเดอร์ด้วยนะคะ </p>'
            ."\n<p>ยอดที่ต้องชำระ : ".Format::numberformat($data->total ?? null)."</p>\n"
            .'<p>เรทการสั่งซื้อ : '.Format::numberformat($data->exchange_rate ?? null).'</p>';
        $body .= sprintf(self::BUTTON, Legacy::href('order/'.$id));
        self::send('order approved', $id, $subject, $body, (string) ($data->email ?? ''));
    }

    /** email_cancel.php (order) */
    public static function orderCancelled(int $id): void
    {
        $data = self::orderHead($id);
        $no = OrderLookups::prefix('order').$id;
        $subject = 'แจ้งรายการสั่งซื้อ '.$no.' (Cancel)';
        $body = "\n<p>แจ้งรายการสั่งซื้อ หมายเลขรายการ <a href='".Legacy::href('order/'.$id)."'>".$no."</a>\n"
            ."มีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ ตามเหตุผลดังข้อต่อไปนี้</p>\n"
            .'<p>- '.Html::h($data->cancel_remark ?? '')."</p>\n";
        $body .= sprintf(self::BUTTON, Legacy::href('order/'.$id));
        self::send('order cancel', $id, $subject, $body, (string) ($data->email ?? ''));
    }

    /**
     * email_cashback.php — เดิมใช้ตัวแปรจาก save_cashback.php: $header_id, $cashback, $item และ $id (= id รายการสินค้า)
     * ลิงก์ปุ่มเดิมใช้ $email_id ที่ไม่ได้ตั้ง → href.'order/' (คงไว้)
     */
    public static function orderCashback(int $headerId, int $detailId, string $cashback, string $item): void
    {
        $data = self::orderHead($headerId);
        $detail = DB::table('bill_product_detail')->where('id', $detailId)->first();
        $oid = (string) ($data->id ?? '');
        $subject = 'แจ้งรายการคืนเงินในรายการสั่งซื้อ '.OrderLookups::prefix('order').$oid;
        $body = "\n<p>แจ้งรายการสั่งซื้อ มีรายการคืนเงิน หมายเลขรายการ <a href='".Legacy::href('order/'.$oid)."'>".Html::h($item).'</a> จำนวน '
            .Format::numberformat($cashback, true, false)." บาท\n ตามเหตุผลดังข้อต่อไปนี้</p>\n"
            .'<p>- '.Html::h($detail->cashback_remark ?? '')."</p>\n";
        $body .= sprintf(self::BUTTON, Legacy::href('order/'));
        self::send('order cashback', $headerId, $subject, $body, (string) ($data->email ?? ''));
    }

    /** _admin/_taobao/email_approved.php */
    public static function taobaoApproved(int $taobaoId, int $detailId): void
    {
        [$data, $detail] = self::taobaoRows($taobaoId, $detailId);
        $no = OrderLookups::prefix('taobao').$taobaoId;
        $subject = 'แจ้งรายการฝากจ่ายเงินเถาเปา '.$no.' (Success)';
        $body = "\n<p>แจ้งรายการฝากจ่ายเงินเถาเปา หมายเลขรายการ <a href='".Legacy::href('taobao/'.$taobaoId)."'>".$no."</a>\n"
            ."ได้รับการยืนยันแล้ว </p>\n<p>รายการสั่งซื้อ : ".Html::h($detail->order ?? '').'</p>';
        $body .= sprintf(self::BUTTON_TAOBAO, Legacy::href('taobao/'.$taobaoId));
        self::send('taobao approved', $taobaoId, $subject, $body, (string) ($data->email ?? ''));
    }

    /** _admin/_taobao/email_cancel.php */
    /** @param  array{cny: float, thb: float, charge: float}|null  $refund  ยอดที่คืนจริง (null = ข้อความแบบเดิม: ค่าบริการเป็นบาท + ราคาเป็นหยวน) */
    public static function taobaoCancelled(int $taobaoId, int $detailId, ?array $refund = null): void
    {
        [$data, $detail] = self::taobaoRows($taobaoId, $detailId);
        $no = OrderLookups::prefix('taobao').$taobaoId;
        $subject = 'แจ้งรายการฝากจ่ายเงินเถาเปา '.$no.' (Cancel)';
        $body = "\n<p>แจ้งรายการฝากจ่ายเงินเถาเปา หมายเลขรายการ <a href='".Legacy::href('taobao/'.$taobaoId)."'>".$no."</a>\n"
            ."มีเหตุขัดข้องบางประการที่ไม่สามารถดำเนินการได้ </p>\n"
            .'<p>- '.Html::h($detail->cancel_remark ?? '')."</p>\n"
            .'<p>รายการสั่งซื้อ : '.Html::h($detail->order ?? '')."</p>\n"
            .'<p>คืนเงินไทย : '.($refund === null ? ($data->charge ?? '') : Money::fmt($refund['charge'] + $refund['thb']))."฿</p>\n"
            .'<p>คืนเงินหยวน : ¥'.($refund === null ? ($detail->price ?? '') : Money::fmt($refund['cny']))."</p>\n";
        $body .= sprintf(self::BUTTON_TAOBAO, Legacy::href('taobao/'.$taobaoId));
        self::send('taobao cancel', $taobaoId, $subject, $body, (string) ($data->email ?? ''));
    }

    private static function orderHead(int $id): ?object
    {
        return DB::table('bill_product')->leftJoin('user', 'bill_product.user', '=', 'user.id')
            ->where('bill_product.id', $id)
            ->first(['bill_product.*', 'user.email', 'user.line_uid', 'user.id as userid']);
    }

    /** @return array{0: ?object, 1: ?object} */
    private static function taobaoRows(int $taobaoId, int $detailId): array
    {
        $data = DB::table('taobao')->leftJoin('user', 'taobao.user', '=', 'user.id')
            ->where('taobao.id', $taobaoId)
            ->first(['taobao.*', 'user.email', 'user.line_uid', 'user.id as userid']);

        return [$data, DB::table('taobao_detail')->where('id', $detailId)->first()];
    }

    private static function send(string $kind, int $ref, string $subject, string $body, string $to): void
    {
        // ไฟล์ email_*.php ของ PACK (เหมือน SR) เป็น CRLF → ขึ้นบรรทัดในเนื้อหาเป็น \r\n แบบเดิม; header/center/footer + ผู้ส่ง = config('legacy.mail')
        $body = (string) preg_replace('/(?<!\r)\n/', "\r\n", $body);
        $html = config('legacy.mail.header').'<strong>'.$subject.'</strong>'.config('legacy.mail.center').$body.config('legacy.mail.footer');
        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            $mailer = config('legacy.external.enabled') ? Mail::mailer() : Mail::mailer('log');
            $mailer->html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from(config('legacy.mail.from_address'), config('legacy.mail.from_name'));
            });
        } catch (\Throwable $e) {
            // เดิม catch (exception $e) {} เงียบ
            Log::warning('order mail failed', ['kind' => $kind, 'ref' => $ref, 'error' => $e->getMessage()]);
        }
    }
}
