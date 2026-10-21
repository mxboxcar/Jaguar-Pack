<?php

namespace App\Domain\Users;

use App\Domain\Settings\UploadBox as SettingsUploadBox;

/**
 * getupload() ของ system_config.php เดิม (กล่องแนบไฟล์ dropper → system_upload) — หน้าสมัครสมาชิก (แนบบัตรประชาชน)
 * ใช้ของกลาง App\Domain\Settings\UploadBox (รุ่น PACK) — คงคลาสนี้ไว้ให้ view เดิมเรียกได้
 * $rand ถูกฝังเป็นชื่อฟังก์ชัน JS → ต้องเป็นค่าคงที่ในโค้ดเท่านั้น
 */
final class UploadBox
{
    public static function single(string $rand = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        return SettingsUploadBox::getupload($rand, $filename, $isDelete, $isValidate, $class);
    }
}
