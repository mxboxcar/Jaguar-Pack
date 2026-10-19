<?php

namespace Tests\Feature\Integrations;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;
use Tests\TestCase;

/**
 * Mailgun (เดิม sendmail() → Mailgun API mg.srshippingsystem.com) — ไม่ยิงเครือข่ายจริง (MockHttpClient)
 * secret ใน test = ค่าปลอม ไม่ใช่ key จริง
 */
class MailgunMailerTest extends TestCase
{
    public function test_default_mailer_is_log_when_env_missing(): void
    {
        $src = file_get_contents(config_path('mail.php'));
        $this->assertStringContainsString("'default' => env('MAIL_MAILER', 'log')", $src);
        $this->assertStringContainsString("env('MAIL_FROM_ADDRESS', 'no-reply@srshippingsystem.com')", $src);
        $this->assertSame('mailgun', config('mail.mailers.mailgun.transport'));
        $this->assertSame('api.mailgun.net', config('services.mailgun.endpoint'));
    }

    public function test_secret_is_read_from_secret_file_when_mailer_is_built(): void
    {
        $dir = storage_path('framework/testing/mailgun-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($dir);
        file_put_contents($dir.'/mg.key', "fixture-not-a-real-key\n");
        try {
            config(['services.mailgun.secret' => null, 'services.mailgun.secret_file' => $dir.'/mg.key', 'services.mailgun.domain' => 'mg.srshippingsystem.com']);
            $this->app->forgetInstance('mail.manager');
            Mail::clearResolvedInstances();

            $transport = Mail::mailer('mailgun')->getSymfonyTransport();
            $this->assertSame('fixture-not-a-real-key', config('services.mailgun.secret'));
            $this->assertInstanceOf(MailgunApiTransport::class, $transport);
            $this->assertSame('mailgun+api://api.mailgun.net?domain=mg.srshippingsystem.com', (string) $transport);

            // MAILGUN_SECRET ตรงๆ ชนะไฟล์
            config(['services.mailgun.secret' => 'from-env']);
            AppServiceProvider::loadMailgunSecret();
            $this->assertSame('from-env', config('services.mailgun.secret'));
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_legacy_style_message_payload_and_testmode_header(): void
    {
        $seen = null;
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$seen) {
            $body = $options['body'];
            if ($body instanceof \Closure) { // MockHttpClient: body เป็น closure คืนทีละ chunk จนได้ '' (อ่านก่อน MockResponse อ่าน)
                for ($s = '', $c = $body(8192); $c !== ''; $c = $body(8192)) {
                    $s .= $c;
                }
                $body = $s;
            }
            $seen = compact('method', 'url', 'options', 'body');

            return new MockResponse('{"id":"<20261019.1@mg.srshippingsystem.com>","message":"Queued. Thank you."}', ['http_code' => 200]);
        });
        $transport = new MailgunApiTransport('fixture-key', 'mg.srshippingsystem.com', null, $http);
        $mailer = Mail::mailer('array');
        $mailer->setSymfonyTransport($transport);

        $sent = $mailer->html('<p>สวัสดี</p>', function ($m) {
            $m->to('nobody@example.invalid')->subject('ทดสอบ')->from(config('legacy.mail.from_address'), config('legacy.mail.from_name'));
            $m->getSymfonyMessage()->getHeaders()->addTextHeader('o:testmode', 'yes');
        });

        $this->assertSame('<20261019.1@mg.srshippingsystem.com>', $sent->getMessageId());
        $this->assertSame('POST', $seen['method']);
        $this->assertSame('https://api.mailgun.net/v3/mg.srshippingsystem.com/messages', $seen['url']);
        $this->assertContains('Authorization: Basic '.base64_encode('api:fixture-key'), $seen['options']['headers']);
        $body = (string) $seen['body'];
        foreach (['name="from"' => 'SRShippingSystem <no-reply@srshippingsystem.com>', 'name="to"' => 'nobody@example.invalid', 'name="o:testmode"' => 'yes', 'name="html"' => '<p>สวัสดี</p>'] as $field => $value) {
            $this->assertMatchesRegularExpression('~'.preg_quote($field, '~').'.*?\r\n\r\n'.preg_quote($value, '~').'\r\n~s', $body, $field);
        }
    }
}
