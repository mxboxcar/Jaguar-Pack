<?php

namespace App\Domain\Tracking;

use App\Support\Legacy\Alerts;

/**
 * ไฟล์ที่อัปโหลดไว้ใน upload/temp (system_upload / _lost_package/upload) แล้วหน้าเดิมสั่งให้เบราว์เซอร์ยิง _api/blockblob_ajax ย้ายขึ้น storage
 * ใช้โดย _lost_package/save, save_image และ (SR) _admin/_tracking/save (รูปพัสดุ tracking_image)
 */
final class TempUpload
{
    /** ชื่อไฟล์ใน temp ที่ปลอดภัย (เดิม file_exists('../upload/temp/'.$img) ตรงๆ = path traversal) */
    public static function safeName(string $name): bool
    {
        return $name !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $name) === 1 && ! str_contains($name, '..');
    }

    public static function exists(string $name): bool
    {
        if (! self::safeName($name)) {
            return false;
        }

        return is_file(rtrim((string) config('legacy.upload.temp_dir'), '/').'/'.$name);
    }

    /** JS ที่เดิมตอบให้เบราว์เซอร์ไปเรียก _api/blockblob_ajax (ย้ายไฟล์ขึ้น storage) — ไม่มีการเรียก API ภายนอกจากฝั่งนี้ */
    public static function blobJs(string $name): string
    {
        return "$.ajax({ \n                type: 'POST', \n                async: true, \n                data : 'path=../upload/temp/".Alerts::js($name)."', \n"
            ."                url: rooturl + '_api/blockblob_ajax', \n                success: function(data) { \n                  \n                } });";
    }

    /**
     * ค่า xxx_mul[] จากฟอร์ม → เฉพาะชื่อไฟล์ธรรมดาแบบที่ system_upload ออกให้ (เดิมบันทึกทุกค่าที่ส่งมา รวม ../../x)
     *
     * @return list<string>
     */
    public static function names(mixed $values): array
    {
        return is_array($values) ? array_values(array_filter($values, fn ($v) => is_string($v) && $v !== '' && self::safeName($v))) : [];
    }
}
