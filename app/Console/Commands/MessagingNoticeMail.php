<?php

namespace App\Console\Commands;

use App\Domain\Messaging\NoticeMail;
use Illuminate\Console\Command;

/**
 * cron_notice_mail.php เดิม (worker ส่งอีเมลประกาศจากคิว notice_mail_queue) — กลุ่ม messaging
 * ตั้ง cron ทุกนาที: php8.4 artisan messaging:notice-mail   (dev: legacy.external.enabled=false → mailer "log" เท่านั้น)
 */
class MessagingNoticeMail extends Command
{
    protected $signature = 'messaging:notice-mail {--test : ไม่ส่งจริง แค่พิมพ์รายการ (= NOTICEMAIL_TEST=1 เดิม)} {--batch=25} {--gap=2}';

    protected $description = 'Send queued notice e-mails (port of _admin/_notice/cron_notice_mail.php)';

    public function handle(): int
    {
        $test = $this->option('test') || getenv('NOTICEMAIL_TEST') === '1';
        foreach (NoticeMail::work((int) $this->option('batch'), (int) $this->option('gap'), $test) as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }
}
