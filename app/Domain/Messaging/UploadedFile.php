<?php

namespace App\Domain\Messaging;

use App\Support\Legacy\Alerts;

/**
 * _message/save_file.php และ _admin/_message/save_file.php เดิม (เหมือนกันทุกบรรทัด ต่างแค่สิทธิ์)
 *
 * ไฟล์ที่ system_upload_2 วางไว้ใน upload/temp → debug_mail = false: ตอบ JS ให้หน้าเว็บเรียก _api/blockblob_ajax
 * (ส่วน debug_mail = true เดิม copy ไป upload/ ตรงๆ)
 *
 * แก้จากเดิม: ชื่อไฟล์ต้องเป็นชื่อไฟล์เดี่ยว (เดิม file_exists('../upload/temp/'.$value) รับ ../ = เช็ค/ส่งต่อ path นอก temp ได้)
 * และ escape ค่าที่ฝังใน JS (เดิมฝังดิบใน '...')
 */
final class UploadedFile
{
    public static function moveScript(?string $value): string
    {
        $value = (string) $value;
        if ($value === '' || ! self::safeName($value)) {
            return '';
        }
        $dir = rtrim((string) config('legacy.upload.temp_dir'), '/');
        if (! is_file($dir.'/'.$value)) {
            return '';
        }
        if (config('legacy.debug_mail')) {
            $dest = rtrim((string) config('legacy.upload.local_dir'), '/');
            @copy($dir.'/'.$value, $dest.'/'.$value);

            return '';
        }

        return "$.ajax({
	         		type: 'POST',
	         		async: true,
	         		data : 'path=".Alerts::js('../upload/temp/'.$value)."',
	         		url: rooturl + '_api/blockblob_ajax',
	         		success: function(data) {

	         		} });";
    }

    private static function safeName(string $v): bool
    {
        return $v !== '.' && $v !== '..' && ! preg_match('~[/\\\\\x00]~', $v);
    }
}
