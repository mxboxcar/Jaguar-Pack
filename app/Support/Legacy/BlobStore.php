<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\Log;

/**
 * ที่เดียวที่ส่งไฟล์จาก upload/temp ขึ้นที่เก็บถาวร — ใช้ร่วมทุกกลุ่ม
 *   _api/blockblob_ajax.php (หน้าเว็บยิงมาหลัง save*.php ตอบ JS) และ UploadAzure() ของ _api/blockblob.php (_register/save.php)
 *
 * driver (config legacy.upload.driver):
 *   local (dev บังคับ) = คัดลอกไปที่ legacy.upload.local_dir — ไม่ออกนอกเครื่อง
 *   azure             = Put Blob ขึ้น account legacy.upload.azure.account / container "upload" ชื่อ blob = ชื่อไฟล์เดิม
 *                       (เสิร์ฟต่อที่ legacy.href_blob = https://image.pack-cargo.net/upload/<ชื่อไฟล์>) — App\Support\Legacy\AzureBlob
 *
 * ผลแบบเดิม (หน้าเว็บไม่ได้ใช้ค่า แต่คงไว้): "temp empty" / "exists" / "" (อัปสำเร็จ) / "<code>: <message><br />"
 */
final class BlobStore
{
    /**
     * @param  string  $path  ค่าจาก POST path (เดิม '../upload/temp/<ชื่อ>') — ใช้แค่ basename (เดิมกันไว้แล้วด้วย blob-guard)
     * @param  bool  $skipExisting  true = แบบ blockblob_ajax (มีไฟล์ขนาด > 0 อยู่แล้ว/เช็คไม่ได้ = "exists" ไม่อัปทับ)
     *                              false = แบบ UploadAzure() (อัปทับเลย)
     */
    public static function uploadFromTemp(string $path, bool $skipExisting = true): string
    {
        $file = basename(str_replace('\\', '/', $path));
        if ($file === '' || $file === '.' || $file === '..' || str_contains($file, "\0")) {
            return 'temp empty';
        }
        $src = rtrim((string) config('legacy.upload.temp_dir'), '/').'/'.$file;
        if (! is_file($src) || filesize($src) == 0) {
            return 'temp empty';
        }

        return config('legacy.upload.driver') === 'azure'
            ? self::azure($src, $file, $skipExisting)
            : self::local($src, $file, $skipExisting);
    }

    private static function local(string $src, string $file, bool $skipExisting): string
    {
        $dir = rtrim((string) config('legacy.upload.local_dir'), '/');
        $dst = $dir.'/'.$file;
        if ($skipExisting && is_file($dst) && filesize($dst) > 0) {
            return 'exists';
        }
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return @copy($src, $dst) ? '' : '500: copy failed<br />';
    }

    private static function azure(string $src, string $file, bool $skipExisting): string
    {
        try {
            $blob = AzureBlob::fromConfig();
            // blob-guard-size เดิม: HEAD → 0 (ไม่มี/ว่าง) เท่านั้นที่อัป; -1 (เช็คไม่ได้) = รอรอบหน้า
            if ($skipExisting && $blob->size($file) !== 0) {
                return 'exists';
            }
            $body = (string) file_get_contents($src);
            $r = $blob->put($file, $body, self::mime($src));
            if ($r->successful()) {
                return '';
            }
            Log::warning('upload: azure put blob failed', ['file' => $file, 'status' => $r->status()]);

            return $r->status().': '.self::azureError($r->body()).'<br />';
        } catch (\Throwable $e) {
            Log::warning('upload: azure put blob error', ['file' => $file, 'error' => $e->getMessage()]);

            return '500: '.$e->getMessage().'<br />';
        }
    }

    /** <Error><Code>..</Code><Message>..</Message></Error> → "Code" (ข้อความเต็มไม่ส่งให้ browser) */
    private static function azureError(string $xml): string
    {
        return preg_match('~<Code>([^<]+)</Code>~', $xml, $m) ? $m[1] : 'error';
    }

    /**
     * Content-Type จากเนื้อไฟล์ — เฉพาะชนิดที่แสดงผลได้ปลอดภัย (รูป/PDF/ข้อความล้วน)
     * อื่นๆ (html, svg, js ...) = application/octet-stream (กัน stored XSS บนโดเมนรูป; เดิม SDK ใส่ octet-stream ทุกไฟล์)
     */
    public static function mime(string $path): string
    {
        $t = function_exists('mime_content_type') ? @mime_content_type($path) : false;
        $safe = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'application/pdf', 'text/plain'];

        return is_string($t) && in_array($t, $safe, true) ? $t : 'application/octet-stream';
    }
}
