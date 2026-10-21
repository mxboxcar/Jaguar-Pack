<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\LegacyAuthOrSignupUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** legacy.auth_or_signup: หน้าสมัคร (ยังไม่ล็อกอิน) แนบสำเนาบัตรได้ · ที่อื่นยังต้องล็อกอิน · จำกัดต่อ IP */
class SignupUploadTest extends TestCase
{
    private function run_(string $referer, string $ip = '203.0.113.50'): string
    {
        $req = Request::create('https://portal.pack-cargo.net/system_upload', 'POST', [], [], [], [
            'HTTP_REFERER' => $referer,
            'REMOTE_ADDR' => $ip,
        ]);
        $res = (new LegacyAuthOrSignupUpload)->handle($req, fn () => response('PASSED'));

        return $res->isRedirect() ? 'REDIRECT' : (string) $res->getContent();
    }

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('signup-upload:203.0.113.50');
    }

    public function test_route_uses_signup_aware_auth(): void
    {
        $mw = app('router')->getRoutes()->match(Request::create('/system_upload', 'POST'))->gatherMiddleware();
        $this->assertContains('legacy.auth_or_signup', $mw);
        $this->assertNotContains('legacy.auth', $mw);
    }

    public function test_guest_from_register_page_passes(): void
    {
        $this->assertSame('PASSED', $this->run_('https://portal.pack-cargo.net/register'));
        $this->assertSame('PASSED', $this->run_('https://portal.pack-cargo.net/register.php'));
    }

    public function test_guest_from_elsewhere_redirects_to_login(): void
    {
        $this->assertSame('REDIRECT', $this->run_('https://portal.pack-cargo.net/order'));
        $this->assertSame('REDIRECT', $this->run_('https://evil.example/register'));
        $this->assertSame('REDIRECT', $this->run_(''));
    }

    public function test_guest_upload_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < LegacyAuthOrSignupUpload::MAX_PER_HOUR; $i++) {
            $this->assertSame('PASSED', $this->run_('https://portal.pack-cargo.net/register'));
        }
        $this->assertStringStartsWith('error:', $this->run_('https://portal.pack-cargo.net/register'));
        RateLimiter::clear('signup-upload:203.0.113.50');
    }
}
