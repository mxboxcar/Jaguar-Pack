<?php

namespace App\Domain\Points;

use App\Support\Legacy\Alerts;

/**
 * ไฟล์แนบใน upload/temp เดิม: _shop_cashback/save.php ถ้าไฟล์มีอยู่ ตอบ JS ให้เบราว์เซอร์ไปเรียก _api/blockblob_ajax (ย้ายขึ้น storage)
 * ที่นี่ไม่เรียกบริการภายนอกเอง — แค่ตอบ JS เดิม (ชื่อไฟล์ผ่าน whitelist + escape; เดิม file_exists('../upload/temp/'.$x) = path traversal)
 * (แบบเดียวกับ App\Domain\MoneyMove\TempUpload / App\Domain\Payment\TempUpload ของกลุ่มอื่น — ควรรวมเป็นของกลางตอน merge)
 */
final class TempUpload
{
    public static function safeName(string $name): bool
    {
        return $name !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $name) === 1 && ! str_contains($name, '..');
    }

    public static function exists(string $name): bool
    {
        return self::safeName($name) && is_file(rtrim((string) config('legacy.upload.temp_dir'), '/').'/'.$name);
    }

    /** JS เดิมของ _shop_cashback/save.php (ไฟล์เดิมเป็น CRLF + tab นำหน้า) */
    public static function blobJs(string $name): string
    {
        $nl = "\r\n\t                ";

        return '$.ajax({ '.$nl."type: 'POST', ".$nl.'async: true, '.$nl."data : 'path=../upload/temp/".Alerts::js($name)."', "
            .$nl."url: rooturl + '_api/blockblob_ajax', ".$nl.'success: function(data) { '.$nl.'  '.$nl.'} });';
    }

    public static function jsIfExists(string $name): string
    {
        return self::exists($name) ? self::blobJs($name) : '';
    }
}
