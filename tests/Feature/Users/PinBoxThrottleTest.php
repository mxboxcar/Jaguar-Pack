<?php

namespace Tests\Feature\Users;

use App\Domain\Payment\PinBox;
use App\Http\Controllers\Points\PointController;
use App\Http\Controllers\Users\ProfileController;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): endpoint ที่ตรวจ paymentpin เอง (PinBox::guard → _point/save_withdraw, _admin/_user/btn_*)
 * ใช้ PinThrottle ตัวนับเดียวกับ _profile/pin: ผิด 5 ครั้ง / 15 นาที ต่อ (ผู้ใช้ + IP), 10 ครั้งต่อผู้ใช้ → ล็อก; PIN ถูก = ล้างตัวนับ
 */
class PinBoxThrottleTest extends TestCase
{
    use FakeLegacyUser;

    private const WRONG = "ErrorBox('PIN จ่ายเงินไม่ถูกต้อง', 'CloseLoading()');";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLegacyWorld();
        // Jail::recordFailure() บันทึกแถว ips ทุกครั้งที่ PIN ผิด (เหมือนเดิม) — DB ปลอม (ห้ามมีคำสั่งอื่นถึง DB)
        $ips = Mockery::mock();
        $ips->shouldReceive('insert')->andReturn(true);
        DB::shouldReceive('table')->with('ips')->andReturn($ips);
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new \Illuminate\Database\Query\Expression($v));
    }

    /** null = ผ่าน (PIN ถูกและไม่ถูกล็อก) */
    private function guard(string $pin, int $uid = 237, string $ip = '10.1.2.3'): ?string
    {
        $req = $this->actAs($uid, ['point'], [], ['paymentpin' => $pin], '/_point/save_withdraw', $ip, ['pin' => '123456']);

        return PinBox::guard($req, \App\Support\Legacy\Legacy::user(), '.cashback_form', 0, 'withdrawpointing()');
    }

    public function test_correct_pin_passes_and_empty_pin_asks_for_box(): void
    {
        $this->assertNull($this->guard('123456'));
        $this->assertStringContainsString('htmlpin', (string) $this->guard(''));
    }

    public function test_five_wrong_attempts_lock_even_the_correct_pin(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(self::WRONG, $this->guard('000000'), 'ครั้งที่ '.($i + 1));
        }
        $out = (string) $this->guard('123456');
        $this->assertStringStartsWith("ErrorBox('ใส่ PIN ผิดเกินจำนวนครั้งที่กำหนด", $out);
        $this->assertStringContainsString('15 นาที', $out);
        $this->assertStringNotContainsString('123456', $out);
    }

    public function test_lock_expires(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->guard('000000');
        }
        $this->travel(16)->minutes();
        $this->assertNull($this->guard('123456'));
    }

    public function test_lock_is_per_user_and_ip_with_per_user_cap(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->guard('000000', 237, '10.1.2.3');
        }
        $this->assertNull($this->guard('123456', 237, '10.9.9.9'), 'IP อื่นของผู้ใช้เดิมยังใช้ได้ (และล้างตัวนับ)');
        $this->assertNull($this->guard('123456', 238, '10.1.2.3'), 'ผู้ใช้อื่นจาก IP เดิมยังใช้ได้');

        for ($i = 0; $i < 10; $i++) {
            $this->guard('000000', 239, '10.0.0.'.$i);
        }
        $this->assertStringStartsWith("ErrorBox('ใส่ PIN ผิด", (string) $this->guard('123456', 239, '10.0.0.200'));
    }

    public function test_success_resets_the_counter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->guard('000000');
        }
        $this->assertNull($this->guard('123456'));
        for ($i = 0; $i < 4; $i++) {
            $this->assertSame(self::WRONG, $this->guard('000000'));
        }
    }

    public function test_counter_is_shared_with_profile_pin(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $req = $this->actAs(240, ['profile'], [], ['pin' => '000000', 'method' => 'verified', 'eq' => '0', 'callback' => ''],
                '/_profile/pin', '10.1.2.3', ['pin' => '123456']);
            $this->assertSame(self::WRONG, (string) app(ProfileController::class)->pin($req)->getContent());
        }
        $this->guard('000000', 240);
        $this->guard('000000', 240);
        $this->assertStringStartsWith("ErrorBox('ใส่ PIN ผิด", (string) $this->guard('123456', 240), 'สลับ endpoint ไม่ได้เดาเพิ่ม');
    }

    public function test_point_save_withdraw_is_throttled(): void
    {
        $call = function (string $pin) {
            $req = $this->actAs(241, ['point'], [], ['paymentpin' => $pin, 'wantcash' => '10'], '/_point/save_withdraw', '10.1.2.3', ['pin' => '123456']);

            return (string) app(PointController::class)->saveWithdraw($req)->getContent();
        };
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(self::WRONG, $call('000000'));
        }
        $this->assertStringStartsWith("ErrorBox('ใส่ PIN ผิดเกินจำนวนครั้งที่กำหนด", $call('123456'), 'ล็อกแล้วไม่ถึงขั้นเขียน DB');
    }
}
