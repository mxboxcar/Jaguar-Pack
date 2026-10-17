<?php

namespace App\Domain\Shop;

use Illuminate\Support\Facades\Log;

/**
 * _api/blockblob_ajax.php / _api/blockblob.php (UploadAzure) เดิม — ส่งไฟล์จาก upload/temp ขึ้นที่เก็บถาวร
 *
 * driver (config legacy.upload.driver):
 *   local (dev บังคับ) = คัดลอกไปที่ legacy.upload.local_dir — ไม่ออกนอกเครื่อง
 *   azure             = ยังไม่พอร์ต (ไม่มี SDK ใน vendor และห้ามยิง Azure จริงจาก dev) → ตอบ error และ log
 *                       ผู้รับผิดชอบการอัปโหลดร่วมคือกลุ่ม settings (CONVENTIONS ข้อ 8) — ดู docs/modules/shop.md
 */
final class BlobStore
{
    /** ผลแบบเดิม: "temp empty" / "exists" / "" (อัปสำเร็จ) / "code: message<br />" */
    public static function uploadFromTemp(string $path): string
    {
        $src = Upload::tempPath($path);
        if ($src === null || ! is_file($src) || filesize($src) == 0) {
            return 'temp empty';
        }
        $file = basename($src);

        if (config('legacy.upload.driver') !== 'local') {
            Log::warning('shop: blockblob_ajax azure driver not ported', ['file' => $file]);

            return '501: azure upload driver is not available in this build<br />';
        }

        $dir = rtrim((string) config('legacy.upload.local_dir'), '/');
        $dst = $dir.'/'.$file;
        // เดิม: HEAD บน Azure → มีไฟล์ขนาด > 0 อยู่แล้ว = "exists" (ไม่อัปทับ)
        if (is_file($dst) && filesize($dst) > 0) {
            return 'exists';
        }
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (! @copy($src, $dst)) {
            return '500: copy failed<br />';
        }

        return '';
    }
}
