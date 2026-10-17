<?php

namespace Tests\Feature;

use App\Support\Legacy\Blocklist;
use App\Support\Legacy\Format;
use App\Support\Legacy\Status;
use Tests\TestCase;

/** helper ที่พอร์ตมาต้องให้ผลเหมือนโค้ดเดิม (fixture สร้างจากฟังก์ชันเดิมด้วย php7.4: tools/gen-format-cases.php) */
class LegacyFormatTest extends TestCase
{
    public function test_outputs_match_legacy_php74(): void
    {
        $map = [
            'numberformat' => [Format::class, 'numberformat'], 'numberformatpoint' => [Format::class, 'numberformatpoint'],
            'numberformatzero' => [Format::class, 'numberformatzero'], 'numberformatone' => [Format::class, 'numberformatone'],
            'cubicformat' => [Format::class, 'cubicformat'], 'getthaidate' => [Format::class, 'getthaidate'],
            'getshortdate' => [Format::class, 'getshortdate'], 'getendate' => [Format::class, 'getendate'],
            'bankformat' => [Format::class, 'bankformat'], 'hex2rgb' => [Format::class, 'hex2rgb'], 'getZero' => [Format::class, 'getZero'],
            'getstatus' => [Status::class, 'getstatus'], 'getstatusorder' => [Status::class, 'getstatusorder'],
            'getlotstatus' => [Status::class, 'getlotstatus'], 'getShipType' => [Status::class, 'getShipType'],
            'isBlockedName' => [Blocklist::class, 'isBlockedName'], 'normalizeText' => [Blocklist::class, 'normalizeText'],
            'normalizePersonName' => [Blocklist::class, 'normalizePersonName'],
            'getpaging' => [\App\Support\Legacy\Paging::class, 'getpaging'],
        ];
        $cases = json_decode(file_get_contents(__DIR__.'/../fixtures/legacy-format-cases.json'), true);
        $this->assertNotEmpty($cases);
        foreach ($cases as [$fn, $args, $expected]) {
            $this->assertSame($expected, call_user_func_array($map[$fn], $args), $fn.'('.json_encode($args, JSON_UNESCAPED_UNICODE).')');
        }
    }

    public function test_res_returns_empty_for_missing_key_and_follows_cookie(): void
    {
        $this->assertSame('', res('no_such_key_xyz'));
        $this->assertSame('ชื่อเข้าใช้งาน', res('username', 'th'));
        $this->assertSame('用户名', res('username', 'cn'));
    }
}
