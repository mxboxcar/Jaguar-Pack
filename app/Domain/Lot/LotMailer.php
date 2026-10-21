<?php

namespace App\Domain\Lot;

use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * แจ้งความเคลื่อนไหวของล็อตทางอีเมล: _admin/_lot/email_lot.php (เข้าคิว), cron_lot_mail.php (worker), email.php (เนื้อหาเมล) เดิม
 *
 * - อีเมล: เดิม sendmail() ผ่าน Mailgun → ที่นี่ Mail ของ Laravel (dev: MAIL_MAILER=log = ไม่ส่งจริง), ห่อ try/catch เงียบแบบเดิม
 * - LINE: เดิม sendLine() เป็นฟังก์ชันว่าง → ไม่ส่ง (log จำนวนตัวอักษรไว้ตรวจเท่านั้น)
 * - email_header/center/footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → config('legacy.mail') (header/center/footer + ผู้ส่ง
 *   ของ sendmail() เดิม ตามแบรนด์ของเว็บ — ฐานร่วม ใช้ร่วมกับทุกกลุ่ม)
 * - send_a_lot_mail() (SR/PACK system_config.php, SendGrid) ไม่มีใครเรียก — email.php ใช้ sendmail() → ไม่พอร์ต
 */
final class LotMailer
{
    /** email_lot.php: ตั้ง is_mail แล้วเข้าคิวทุก user ในล็อต (INSERT IGNORE — UNIQUE(lot_id,user_id)) → [ลูกค้าทั้งหมด, รอส่ง] */
    public static function enqueue(int $lotId): array
    {
        DB::table('lot')->where('id', $lotId)->update(['is_mail' => '1']);
        DB::statement('INSERT IGNORE INTO `lot_mail_queue` (`lot_id`, `user_id`, `status`, `attempts`, `create_date`)
    SELECT ?, tracking.`user`, 0, 0, NOW() FROM `tracking`
    WHERE tracking.`lot_id` = ? AND tracking.`user` > 0 GROUP BY tracking.`user`', [$lotId, $lotId]);
        $pending = DB::table('lot_mail_queue')->where('lot_id', $lotId)->where('status', 0)->count();
        $total = (int) DB::table('tracking')->where('lot_id', $lotId)->where('user', '>', 0)->distinct()->count('user');

        return [$total, $pending];
    }

    /**
     * cron_lot_mail.php: claim ทีละรายการแบบ atomic (0 → 1) แล้วส่งเมล (+LINE ที่เดิมไม่ทำอะไร)
     *
     * @param  callable(string): void  $out
     * @return array{0:int,1:int} [processed, skipped]
     */
    public static function work(bool $test, callable $out, int $batch = 50): array
    {
        $done = 0;
        $skip = 0;
        $rows = DB::table('lot_mail_queue')->where('status', 0)->orderBy('id')->limit($batch)->get(['id', 'lot_id', 'user_id']);
        foreach ($rows as $row) {
            $claimed = DB::table('lot_mail_queue')->where('id', $row->id)->where('status', 0)
                ->update(['status' => 1, 'attempts' => DB::raw('`attempts` + 1'), 'sent_date' => DB::raw('NOW()')]);
            if ($claimed !== 1) {
                $skip++;

                continue;
            }
            $u = DB::table('user')->where('id', $row->user_id)->first(['email', 'line_uid']);
            $items = self::items((int) $row->lot_id, (int) $row->user_id);

            $line = 'แจ้งเตือนความเคลื่อนไหวสินค้า'."\nวันที่ ".Format::getthaidate(date('Y-m-d'), false, true)."\n\n";
            $i = 1;
            foreach ($items as $it) {
                $line .= $i.'. '.$it->tracking.'-'.self::stage($it, '')."\n";
                $i++;
            }
            $line .= "\nดูเพิ่มเติมได้ที่ ".Legacy::href('payment/create');

            if ($test) {
                $out('[TEST] lot='.$row->lot_id.' user='.$row->user_id.' email='.($u->email ?? '').' line='.((string) ($u->line_uid ?? '') !== '' ? 'yes' : '-').' items='.($i - 1));
            } else {
                self::sendMail((int) $row->lot_id, (int) $row->user_id, (string) ($u->email ?? ''), $items);
                if ((string) ($u->line_uid ?? '') !== '') {
                    Log::info('lot mail: LINE skipped (legacy sendLine is a no-op)', ['lot' => $row->lot_id, 'user' => $row->user_id, 'chars' => mb_strlen($line)]);
                }
            }
            $done++;
        }
        $out(($test ? '[TEST] ' : '').'processed='.$done.' skipped='.$skip);
        // เก็บประวัติ 7 วัน (เหมือนเดิม)
        DB::table('lot_mail_queue')->where('status', '<>', 0)->where('sent_date', '<', DB::raw('(NOW() - INTERVAL 7 DAY)'))->delete();

        return [$done, $skip];
    }

    /** tracking ของ user ในล็อต (email.php / cron เดิม) */
    private static function items(int $lotId, int $userId): \Illuminate\Support\Collection
    {
        return DB::table('tracking')->leftJoin('user', 'tracking.user', '=', 'user.id')->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->where('tracking.lot_id', $lotId)->where('tracking.user', $userId)
            ->orderBy('user.code')->orderBy('tracking.no')
            ->get(['tracking.*', 'lot.status as status', 'user.code', 'lot.name as lot_name']);
    }

    private static function stage(object $it, string $prefix): string
    {
        foreach (['date_finish' => 'ส่งออกถึงลูกค้า', 'date_thai' => 'ถึงไทย', 'date_outofchina' => 'ส่งออกจากจีน', 'date_warehouse' => 'เข้าโกดัง'] as $col => $text) {
            if ((string) $it->$col !== '0000-00-00 00:00:00') {
                return $prefix.$text;
            }
        }

        return '';
    }

    /**
     * email.php เดิม (PACK): คอลัมน์ china_order_number / order_id ไม่มีในตาราง tracking → ช่อง "เลขที่ใบสั่งซื้อ" และ "วลีช่วยจำ" ได้ "-" เสมอ
     * (แบบ jl — SR แสดง tracking.quote), ปุ่มดูรายละเอียดสี #eb4823 padding ขวา 0
     */
    private static function sendMail(int $lotId, int $userId, string $to, $items): void
    {
        $subject = 'แจ้งรายการเคลื่อนไหวรหัสพัสดุ '.Format::getthaidate(date('Y-m-d'), true, true);
        $th = 'text-align:center;font-weight:bold;border:1px #ccc solid;background-color:#e6e6e6';
        // email.php เดิมเป็นไฟล์ CRLF → ขึ้นบรรทัดในข้อความคงที่ของเมลเป็น \r\n (ค่าจาก DB ไม่แปลง)
        $t = fn (string $s): string => str_replace("\n", "\r\n", $s);
        $body = $t("\n\n<table style=\"text-indent:0px!important;font-size:12px;color:#3b3e42;margin-top:20px;border:1px #ccc solid;width:100%;border-collapse: collapse;\">\n<thead>\n\t<tr>\n"
            ."\t\t<th style=\"$th\">ล๊อต/ลำดับ</th>\n\t\t<th style=\"$th\">เลขพัสดุ</th>\n\t\t<th style=\"$th\">เลขที่ใบสั่งซื้อ</th>\n\t\t<th style=\"$th\">วลีช่วยจำ</th>\n"
            ."\t\t<th style=\"$th;width:80px;\">นน.</th>\n\t\t<th style=\"$th;width:80px;\">คิว</th>\n\t\t<th style=\"$th;width:80px;\">สถานะ</th>\n\t</tr>\n</thead>\n<tbody>\n");
        $n = fn ($v) => Php74::num($v);
        foreach ($items as $it) {
            // ค่าจาก DB ถูก escape (เดิมต่อดิบ = HTML injection ในเมล)
            $body .= $t("\n\t<tr style=\"line-height:15px;\">\n\t\t<td style=\"border:1px #ccc solid;text-align:center;padding:3px\">")
                .e((string) $it->lot_name).'/'.e((string) $it->no)
                .$t("</td>\n\t\t<td style=\"border:1px #ccc solid;padding:3px\">").e((string) $it->tracking).$t("</td>\n\t\t")
                .'<td style="border:1px #ccc solid;padding:3px">-</td>'
                .'<td style="border:1px #ccc solid;padding:3px">-</td>'
                .$t("\n\t\t<td style=\"border:1px #ccc solid;padding:3px;text-align:right\">").Format::numberformatone($it->weight)
                .$t("</td>\n\t\t<td style=\"border:1px #ccc solid;padding:3px;text-align:right\">").Format::cubicformat(($n($it->long) * $n($it->width) * $n($it->height)) / 1000000)
                .$t("</td>\n\t\t<td style=\"border:1px #ccc solid;padding:3px;text-align:center\">").Status::getlotstatus($it->status).$t("</td>\n\t</tr>\n\t");
        }
        $link = Legacy::href('payment/create');
        $body .= $t("\n</tbody>\n</table>\n\n\n<div style=\"height:20px\"></div>\n"
            ."<p style=\"width:200px;text-align:center;font-size:15px;background-color:#eb4823;padding:10px 25px 10px 0px;\" >\n"
            .'<a style="color:#ffffff;text-decoration:none" href="').$link.$t("\" >ดูรายละเอียด</a></p>\n");
        $html = config('legacy.mail.header').'<strong>'.$subject.'</strong>'.config('legacy.mail.center').$body.config('legacy.mail.footer');

        try {
            if ($to === '') {
                throw new \RuntimeException('no recipient');
            }
            // dev / legacy.external.enabled = false → บังคับ log mailer (ไม่ส่งออกนอกเครื่อง)
            $mailer = config('legacy.external.enabled') ? Mail::mailer() : Mail::mailer('log');
            $mailer->html($html, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject)->from(config('legacy.mail.from_address'), config('legacy.mail.from_name'));
            });
        } catch (\Throwable $e) {
            Log::warning('lot mail failed', ['lot' => $lotId, 'user' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
