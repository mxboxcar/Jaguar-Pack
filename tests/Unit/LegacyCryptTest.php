<?php

namespace Tests\Unit;

use App\Support\Legacy\LegacyCrypt;
use PHPUnit\Framework\TestCase;

/** ผลต้องตรงกับ mcrypt RIJNDAEL_256 ECB ของ PHP 7.4 byte ต่อ byte (fixture สร้างด้วย php7.4 + salt ทดสอบ ไม่ใช่ salt จริง) */
class LegacyCryptTest extends TestCase
{
    public function test_matches_mcrypt_output(): void
    {
        $f = json_decode(file_get_contents(__DIR__.'/../fixtures/legacy-crypt-cases.json'), true);
        foreach ($f['cases'] as [$plain, $enc]) {
            $this->assertSame($enc, LegacyCrypt::encrypt($plain, $f['salt']), "encrypt($plain)");
            $this->assertSame(trim($plain), LegacyCrypt::decrypt($enc, $f['salt']), "decrypt($enc)");
        }
    }
}
