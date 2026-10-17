<?php

namespace Tests\Feature;

use App\Auth\LegacySessionGuard;
use App\Support\Legacy\LegacyCrypt;
use App\Support\Legacy\LegacySession;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/** guard "legacy": ค่า session เสียต้องไม่ทำให้ 500 และต้องไม่ใช้ session ของ request ก่อนหน้า */
class LegacySessionGuardTest extends TestCase
{
    private const SALT = 'test-salt-not-real-0123456789abc';

    protected function setUp(): void
    {
        parent::setUp();
        config(['legacy.session.salt' => self::SALT]);
    }

    private function freshSession(array $values): LegacySession
    {
        $this->app->forgetScopedInstances();
        $s = $this->app->make(LegacySession::class);
        foreach ($values as $k => $v) {
            $s->put($k, $v);
        }

        return $s;
    }

    public function test_non_string_session_values_mean_logged_out(): void
    {
        foreach ([['x'], new \stdClass, null, false] as $bad) {
            $this->freshSession(['user_id' => $bad, 'user_role' => $bad]);
            /** @var LegacySessionGuard $g */
            $g = Auth::guard('legacy');
            $this->assertNull($g->legacyUserId());
            $this->assertNull($g->legacyIdentity());
        }
    }

    public function test_guard_follows_the_current_request_session(): void
    {
        $this->freshSession(['user_id' => LegacyCrypt::encrypt('someone&42', self::SALT)]);
        /** @var LegacySessionGuard $g */
        $g = Auth::guard('legacy');
        $this->assertSame('42', $g->legacyUserId());

        $this->freshSession([]);
        $this->assertSame($g, Auth::guard('legacy'));
        $this->assertNull($g->legacyUserId());
        $this->assertNull($g->user());
    }

    public function test_username_containing_ampersand_cannot_take_another_users_id(): void
    {
        // username "x&5" (สมัครได้ในระบบเดิม) ล็อกอินเป็น id 900 → เดิม explode()[1] ได้ "5"
        $this->freshSession(['user_id' => LegacyCrypt::encrypt('x&5&900', self::SALT)]);
        $g = Auth::guard('legacy');
        $this->assertSame('5', $g->legacyUserId(), 'raw getUserID() parity (ใช้กับ jail เท่านั้น)');
        $m = new \ReflectionMethod($g, 'sessionUserId');
        $this->assertSame('900', $m->invoke($g));
    }
}
