<?php

namespace Tests\Unit;

use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Tests\TestCase;

/** X-Real-IP เชื่อได้เฉพาะเมื่อมาจาก proxy ในเครื่อง — กันปลอม IP หลบ jail */
class LegacyClientIpTest extends TestCase
{
    private function ip(array $server): string
    {
        $this->app->instance('request', Request::create('/', 'GET', [], [], [], $server));

        return Legacy::clientIp();
    }

    public function test_spoofed_real_ip_from_public_client_is_ignored(): void
    {
        $this->assertSame('8.8.4.4', $this->ip(['REMOTE_ADDR' => '8.8.4.4', 'HTTP_X_REAL_IP' => '1.2.3.4']));
    }

    public function test_real_ip_from_local_proxy_is_used(): void
    {
        $this->assertSame('1.2.3.4', $this->ip(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_REAL_IP' => '1.2.3.4']));
    }

    public function test_configured_trusted_proxy(): void
    {
        config(['legacy.trusted_proxies' => '9.9.9.9']);
        $this->assertSame('1.2.3.4', $this->ip(['REMOTE_ADDR' => '9.9.9.9', 'HTTP_X_REAL_IP' => '1.2.3.4']));
    }
}
