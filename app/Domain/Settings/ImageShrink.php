<?php

namespace App\Domain\Settings;

/**
 * _api/image_shrink.php เดิม — ย่อรูปที่อัปโหลดก่อนส่งขึ้นที่เก็บถาวร (ไลบรารี: เปิดไฟล์ตรงๆ ไม่ตอบอะไร)
 *  - ด้านยาวเกิน MAX px ย่อให้พอดี, JPEG/WEBP บันทึกคุณภาพ QUALITY, หมุนตาม EXIF ก่อน
 *  - PNG ย่อเฉพาะตอนใหญ่เกิน, ผลต้องเล็กกว่าเดิมเท่านั้นถึงแทนที่, ผิดพลาด = ใช้ไฟล์เดิม
 * ผู้เรียก: system_upload (UploadController) — log ขนาดก่อน/หลังไปที่ legacy.log_dir/image_shrink.log
 * (เดิมเขียน /home/admin/tmp/image_shrink.log ของเครื่อง)
 */
final class ImageShrink
{
    public static function max(): int
    {
        return (int) config('legacy.upload.shrink_max', 1920);
    }

    public static function quality(): int
    {
        return (int) config('legacy.upload.shrink_quality', 82);
    }

    public static function shrink(string $path): bool
    {
        $before = @filesize($path);
        $done = self::inner($path);
        clearstatcache();
        if ($before) {
            $line = (new \DateTime('now', new \DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s')."\tpack-cargo\t"
                .(int) $before."\t".(int) @filesize($path)."\t".($done ? 1 : 0)."\n";
            @file_put_contents(rtrim((string) config('legacy.log_dir'), '/').'/image_shrink.log', $line, FILE_APPEND | LOCK_EX);
        }

        return $done;
    }

    private static function inner(string $path): bool
    {
        try {
            if (! function_exists('imagecreatefromjpeg') || ! is_file($path)) {
                return false;
            }
            $orig = filesize($path);
            $info = @getimagesize($path);
            if (! $info || $orig === false) {
                return false;
            }
            [$w, $h, $type] = $info;
            if ($w < 1 || $h < 1 || $w * $h > 60000000) {
                return false;
            }
            $long = max($w, $h);
            if ($type != IMAGETYPE_PNG && $long <= self::max() && $orig < 400 * 1024) {
                return false;
            }
            if ($type == IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($path);
            } elseif ($type == IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($path);
            } elseif ($type == IMAGETYPE_PNG) {
                if ($long <= self::max()) {
                    return false;
                }
                $src = @imagecreatefrompng($path);
            } else {
                return false;
            }
            if (! $src) {
                return false;
            }
            if ($type == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($path);
                $o = isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
                if ($o == 3) {
                    $src = imagerotate($src, 180, 0);
                } elseif ($o == 6) {
                    $src = imagerotate($src, -90, 0);
                } elseif ($o == 8) {
                    $src = imagerotate($src, 90, 0);
                }
                if (in_array($o, [3, 6, 8], true)) {
                    $w = imagesx($src);
                    $h = imagesy($src);
                }
            }
            $scale = min(1, self::max() / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            } else {
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $tmp = $path.'.shrink';
            if ($type == IMAGETYPE_PNG) {
                $ok = @imagepng($dst, $tmp, 9);
            } elseif ($type == IMAGETYPE_WEBP) {
                $ok = @imagewebp($dst, $tmp, self::quality());
            } else {
                $ok = @imagejpeg($dst, $tmp, self::quality());
            }
            if ($ok && is_file($tmp) && filesize($tmp) > 0 && filesize($tmp) < $orig) {
                @rename($tmp, $path);

                return true;
            }
            @unlink($tmp);
        } catch (\Throwable) {
            @unlink($path.'.shrink');
        }

        return false;
    }
}
