<?php

namespace App\Console\Commands;

use App\Domain\Lot\LotMailer;
use Illuminate\Console\Command;

/**
 * แทน _admin/_lot/cron_lot_mail.php เดิม (worker ส่งเมลแจ้งล็อตจากคิว lot_mail_queue ทุก 1 นาที)
 * --test = แบบ LOTMAIL_TEST=1 เดิม: ไม่ส่งจริง แค่พิมพ์ว่าจะส่งใคร (แต่ยัง claim คิวเหมือนเดิม)
 */
class LotMailWorker extends Command
{
    protected $signature = 'lot:mail-worker {--test : ไม่ส่งจริง แค่พิมพ์รายการ} {--batch=50}';

    protected $description = 'Send queued lot movement e-mails (legacy _admin/_lot/cron_lot_mail.php)';

    public function handle(): int
    {
        $test = (bool) $this->option('test') || getenv('LOTMAIL_TEST') === '1';
        LotMailer::work($test, fn (string $line) => $this->line($line), max(1, (int) $this->option('batch')));

        return self::SUCCESS;
    }
}
