<?php

namespace Tests\Feature\Users;

use App\Domain\Users\PinThrottle;
use App\Http\Controllers\Users\ProfileController;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): _profile/pin (method=verified) เดิมเดา PIN ได้ไม่จำกัด
 * → ผิด 5 ครั้ง / 15 นาที ต่อ (ผู้ใช้ + IP) = ล็อก, ต่อผู้ใช้ทุก IP รวม 10 ครั้ง = ล็อก
 */
class ProfilePinThrottleTest extends TestCase
{
    use FakeLegacyUser;

    private const WRONG = "ErrorBox('PIN จ่ายเงินไม่ถูกต้อง', 'CloseLoading()');";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLegacyWorld();
        // Jail::recordFailure() บันทึกแถว ips ทุกครั้งที่ PIN ผิด (เหมือนเดิม) — DB ปลอม
        $ips = Mockery::mock();
        $ips->shouldReceive('insert')->andReturn(true);
        DB::shouldReceive('table')->with('ips')->andReturn($ips);
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new \Illuminate\Database\Query\Expression($v));
    }

    private function verify(string $pin, int $uid = 137, string $ip = '10.1.2.3'): string
    {
        $req = $this->actAs($uid, ['profile'], [], ['pin' => $pin, 'method' => 'verified', 'eq' => '0', 'callback' => ''],
            '/_profile/pin', $ip, ['pin' => '123456']);

        return (string) app(ProfileController::class)->pin($req)->getContent();
    }

    public function test_correct_pin_passes(): void
    {
        $this->assertStringContainsString("name='paymentpin' value='123456'", $this->verify('123456'));
    }

    public function test_five_wrong_attempts_lock_even_the_correct_pin(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(self::WRONG, $this->verify('000000'), "ครั้งที่ ".($i + 1));
        }
        $out = $this->verify('123456');
        $this->assertStringStartsWith("ErrorBox('ใส่ PIN ผิดเกินจำนวนครั้งที่กำหนด", $out);
        $this->assertStringContainsString('15 นาที', $out);
        $this->assertStringNotContainsString('paymentpin', $out);
    }

    public function test_lock_is_per_user_and_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->verify('000000', 137, '10.1.2.3');
        }
        $this->assertStringContainsString('paymentpin', $this->verify('123456', 137, '10.9.9.9'), 'IP อื่นของผู้ใช้เดิมยังใช้ได้');
        $this->assertStringContainsString('paymentpin', $this->verify('123456', 138, '10.1.2.3'), 'ผู้ใช้อื่นจาก IP เดิมยังใช้ได้');
    }

    public function test_rotating_ips_hits_the_per_user_cap(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->verify('000000', 137, '10.0.0.'.$i);
        }
        $this->assertStringStartsWith("ErrorBox('ใส่ PIN ผิด", $this->verify('123456', 137, '10.0.0.200'));
    }

    public function test_success_resets_the_counter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->verify('000000');
        }
        $this->assertStringContainsString('paymentpin', $this->verify('123456'));
        for ($i = 0; $i < 4; $i++) {
            $this->assertSame(self::WRONG, $this->verify('000000'));
        }
    }

    public function test_lock_expires(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->verify('000000');
        }
        $this->travel(16)->minutes();
        $this->assertSame(0, PinThrottle::lockedFor(137, '10.1.2.3'));
        $this->assertStringContainsString('paymentpin', $this->verify('123456'));
    }
}
