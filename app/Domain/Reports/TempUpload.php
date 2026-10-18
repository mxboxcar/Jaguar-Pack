<?php

namespace App\Domain\Reports;

use App\Support\Legacy\Alerts;

/**
 * ไฟล์แนบที่อัปโหลดไว้ใน upload/temp เดิม: ถ้าไฟล์มีอยู่ เดิมตอบ JS ให้เบราว์เซอร์ไปเรียก _api/blockblob_ajax (ย้ายขึ้น storage)
 * ที่นี่ไม่เรียกบริการภายนอกเอง — แค่ตอบ JS เดิม (ชื่อไฟล์ผ่าน whitelist + escape; เดิม file_exists('../upload/temp/'.$x) = path traversal)
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

    /** JS เดิมของ _alipay/save.php, _admin/_alipay/save.php ฯลฯ (เว้นวรรค/ขึ้นบรรทัดตามไฟล์เดิม) */
    public static function blobJs(string $name, string $indent = '              '): string
    {
        return "$.ajax({ \n".$indent."type: 'POST', \n".$indent."async: true, \n".$indent."data : 'path=../upload/temp/".Alerts::js($name)."', \n"
            .$indent."url: rooturl + '_api/blockblob_ajax', \n".$indent."success: function(data) { \n".$indent."  \n".$indent.'} });';
    }

    /** blobJs() ถ้าไฟล์มีอยู่ ไม่งั้น '' */
    public static function jsIfExists(string $name, string $indent = '              '): string
    {
        return self::exists($name) ? self::blobJs($name, $indent) : '';
    }
}
