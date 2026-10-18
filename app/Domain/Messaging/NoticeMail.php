<?php

namespace App\Domain\Messaging;

use App\Support\Legacy\LegacyLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * อีเมลประกาศ: _admin/_notice/email.php, email_notice.php (เข้าคิว), cron_notice_mail.php (worker) และ sendmail() เดิม
 *
 * ส่งผ่าน Mail ของ Laravel เท่านั้น — ถ้า legacy.external.enabled = false (dev) บังคับ mailer "log" เสมอ
 * (ไม่ว่า MAIL_MAILER จะตั้งเป็นอะไร) = ไม่มีอีเมลจริงออกจาก dev
 * เดิม sendmail() ยิง Mailgun API ด้วย key ที่ฝังในโค้ด — ที่นี่ไม่มี credential ในโค้ด
 */
final class NoticeMail
{
    /** email_header.php / email_center.php / email_footer.php เดิม (file_get_contents = ได้ข้อความดิบรวม <?php ... ?>) */
    public const HEADER = '<div style="font-family: arial,sans-serif;font-size:13px;">';

    public const CENTER = '<div>';

    public const FOOTER_PHP = "<?php require_once('system_config.php'); ?>\r\n";

    public const FOOTER_BODY = "</div>\r\n<br />\r\n* หากมีข้อสงสัยกรุณาติดต่อ<br />\r\nE-mail : jlcargoth@hotmail.com<br />\r\nPhone : 095-550-4009<br />\r\nLineID : @jlcargo\r\n<br />\r\n<br />\r\nขอแสดงความนับถือ<br />\r\nJLcargo\r\n</div>";

    /** เนื้ออีเมลประกาศ (เหมือน email.php / cron_notice_mail.php) */
    public static function template(object $notice): array
    {
        $subject = NoticeText::subject($notice->create_date ?? '');
        $body = self::HEADER.'<strong>'.$subject.'</strong>'.self::CENTER
            ."\n".NoticeText::decode($notice->text ?? '')
            .self::FOOTER_PHP.self::FOOTER_BODY;

        return [$subject, $body];
    }

    /** sendmail($subject, $body, $to) — เดิม try { mailgun } catch {} เงียบ */
    public static function send(string $subject, string $html, string $to): void
    {
        $mailer = config('legacy.external.enabled') ? null : 'log';
        try {
            Mail::mailer($mailer)->html($html, function ($m) use ($subject, $to) {
                $m->from('no-reply@jlcargoth.com', 'JLcargoTH')->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            LegacyLog::goLogMessage('sendmail', 'failed: '.$e->getMessage());
        }
    }

    /** email_notice.php: เข้าคิวทุก user ที่มีอีเมล (INSERT IGNORE — กดซ้ำไม่ทับตัวที่ส่งแล้ว) */
    public static function enqueue(int $notiId): array
    {
        DB::insert(
            'INSERT IGNORE INTO `notice_mail_queue` (`noti_id`, `user_id`, `status`, `attempts`, `create_date`)'
            ." SELECT ?, `id`, 0, 0, NOW() FROM `user` WHERE `email` != ''",
            [$notiId]
        );
        $pending = DB::table('notice_mail_queue')->where('noti_id', $notiId)->where('status', 0)->count();
        $total = DB::table('user')->where('email', '!=', '')->count();

        return [$total, $pending];
    }

    /**
     * cron_notice_mail.php: ส่งคิวทีละ $batch ฉบับ เว้น $gap วินาที, claim แบบ atomic (กัน worker ซ้อนส่งซ้ำ)
     * เดิม query notice + user ทีละแถว (N+1) → ที่นี่ join มาในคำสั่งเดียว
     * $test = NOTICEMAIL_TEST=1 เดิม (ไม่ส่ง แค่พิมพ์)
     *
     * @return list<string> บรรทัดที่เดิม echo ออก (โหมดทดสอบ)
     */
    public static function work(int $batch = 25, int $gap = 2, bool $test = false): array
    {
        $out = [];
        $rows = DB::table('notice_mail_queue as q')
            ->leftJoin('notice as n', 'n.id', '=', 'q.noti_id')
            ->leftJoin('user as u', 'u.id', '=', 'q.user_id')
            ->where('q.status', 0)->orderBy('q.id')->limit($batch)
            ->get(['q.id', 'q.noti_id', 'q.user_id', 'n.id as n_id', 'n.text', 'n.create_date', 'u.id as u_id', 'u.email'])
            ->all();
        $n = count($rows);
        foreach ($rows as $i => $row) {
            $claimed = DB::table('notice_mail_queue')->where('id', (int) $row->id)->where('status', 0)
                ->update(['status' => 1, 'attempts' => DB::raw('`attempts` + 1'), 'sent_date' => DB::raw('NOW()')]);
            if ($claimed !== 1 || $row->n_id === null || $row->u_id === null) {
                continue;
            }
            [$subject, $body] = self::template($row);
            if ($test) {
                $out[] = "[TEST] noti={$row->noti_id} user={$row->user_id} email={$row->email} line=off";
            } elseif ((string) $row->email !== '') {
                self::send($subject, $body, (string) $row->email);
            }
            if ($i < $n - 1 && $gap > 0) {
                sleep($gap);
            }
        }
        // ล้างคิวที่ส่งแล้วเกิน 7 วัน (เฉพาะตารางคิว)
        DB::table('notice_mail_queue')->where('status', 1)
            ->where('sent_date', '<', DB::raw('DATE_SUB(NOW(), INTERVAL 7 DAY)'))->delete();

        return $out;
    }
}
