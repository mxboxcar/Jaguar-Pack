<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Settings\ImageShrink;
use App\Http\Controllers\Controller;
use App\Support\Legacy\BlobStore;
use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

/**
 * อัปโหลดไฟล์ชั่วคราว (dropper ของ getupload*()): system_upload.php / system_upload_2.php เดิม
 *
 * เก็บที่ config('legacy.upload.temp_dir') (= upload/temp เดิม; dev = storage/app/legacy-upload/temp)
 * ไม่ส่งขึ้น Azure ที่นี่ (เดิมก็ไม่ส่ง — ส่งตอนกดบันทึกฟอร์มของแต่ละกลุ่ม)
 *
 * ต่างจากเดิม: ต้องล็อกอิน (เดิมเช็คแค่ X-Requested-With = ใครก็อัปโหลดไฟล์ลง webroot ได้)
 * ผลตอบเป็น text/plain (dropper อ่าน responseText เท่านั้น) — ชื่อไฟล์เดิมใน system_upload_2 จึงไม่ถูกตีความเป็น HTML
 */
class UploadController extends Controller
{
    /** ชนิดจริงของไฟล์ → นามสกุลที่บันทึก (system_upload.php) */
    private const MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    /** system_upload_2.php: นามสกุลที่ยอม (ตรงตัวพิมพ์แบบเดิม — .Jpg ไม่ผ่าน) */
    private const EXT_2 = ['jpg', 'png', 'jpeg', 'JPG', 'pdf', 'PDF', 'JPEG', 'PNG', 'xls', 'xlsx', 'XLS', 'XLSX'];

    /** POST /system_upload — รูปภาพ/PDF เท่านั้น, ตรวจชนิดจากเนื้อไฟล์, ย่อรูป, ตอบชื่อไฟล์ใหม่ */
    public function upload(Request $request): Response
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->text('error: อัพโหลดไฟล์ไม่สำเร็จ ไฟล์อาจใหญ่เกินกำหนด');
        }
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (! in_array($ext, Format::uploadAllowedExtensions(), true)) {
            return $this->text('error : รองรับ .jpg, .jpeg, .png, .pdf เท่านั้น');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
        if (! is_string($mime) || ! isset(self::MIME_TO_EXT[$mime])) {
            return $this->text('error : ไฟล์นี้ไม่ใช่รูปภาพหรือ PDF รองรับ .jpg, .jpeg, .png, .pdf เท่านั้น');
        }
        $name = $this->store($file, self::MIME_TO_EXT[$mime]);
        if ($name === null) {
            return $this->text('error: เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
        }
        ImageShrink::shrink($this->tempDir().'/'.$name);

        return $this->text($name);
    }

    /**
     * POST /system_upload_2 — แชท/ไฟล์ lot: รูป, PDF, Excel ตามนามสกุล ตอบ "<ชื่อใหม่>[NIDA]<ชื่อเดิม>"
     * เดิมไม่ตรวจเนื้อไฟล์ → ที่นี่รูป/PDF ต้องเป็นของจริง (Excel ไม่ตรวจ — finfo ไม่แน่นอน), ต้องล็อกอิน, ชื่อไฟล์ใหม่สุ่มเสมอ
     */
    public function upload2(Request $request): Response
    {
        $file = $request->file('file');
        $orig = $file instanceof UploadedFile ? $file->getClientOriginalName() : '';
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        if (! in_array($ext, self::EXT_2, true)) {
            return $this->text('error: สกุล .png, .jpg, .jpeg, .pdf เท่านั้น');
        }
        // ตั้งใจต่างจากเดิม: นามสกุลรูป/PDF ต้องเป็นรูป/PDF จริง (เดิมไม่ตรวจเนื้อไฟล์ → ไฟล์ PHP/HTML ชื่อ .jpg ผ่าน)
        // ชนิดจริงไม่ตรงนามสกุล (เช่น JPEG ชื่อ .png) ใช้นามสกุลตามชนิดจริงแบบ system_upload; Excel ไม่ตรวจ (finfo ไม่แน่นอน)
        if ($file->getError() === UPLOAD_ERR_OK && ! in_array(strtolower($ext), ['xls', 'xlsx'], true)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
            if (! is_string($mime) || ! isset(self::MIME_TO_EXT[$mime])) {
                return $this->text('error: สกุล .png, .jpg, .jpeg, .pdf เท่านั้น');
            }
            if (self::MIME_TO_EXT[$mime] !== strtolower($ext) && ! ($mime === 'image/jpeg' && strtolower($ext) === 'jpeg')) {
                $ext = self::MIME_TO_EXT[$mime];
            }
        }
        $name = $file->getError() === UPLOAD_ERR_OK ? $this->store($file, $ext) : null;
        if ($name === null) {
            return $this->text('error: ไม่ทราบสาเหตุ');
        }

        return $this->text($name.'[NIDA]'.$orig);
    }

    /** system_ckupload: ชนิดรูปจาก getimagesize() → นามสกุลที่บันทึก */
    private const CK_TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];

    /**
     * POST /system_ckupload — SR: ปุ่ม "อัปโหลด" รูปใน CKEditor (config.filebrowserUploadUrl, ไฟล์ช่อง upload, GET CKEditorFuncNum)
     *
     * ตรวจแบบเดิม: jpg/jpeg/png/gif ตามนามสกุล, 1 byte – 10 MB, เนื้อไฟล์ต้องเป็นรูป JPEG/PNG/GIF (getimagesize),
     * ชื่อใหม่ = <time>_<สุ่ม 16 ตัว>.<นามสกุลตามชนิดจริง> · ตอบ <script> ให้ CKEditor (URL / ข้อความ error เดิม)
     *
     * ต่างจากเดิม (ตั้งใจ): เดิม move ลง webroot upload/ แล้วเสิร์ฟที่ portal/upload/<ชื่อ>
     *   → ใหม่เก็บที่ temp_dir แล้วส่งผ่าน BlobStore (dev = local_dir, production = Azure) เสิร์ฟที่ href_blob/<ชื่อ> แบบไฟล์อัปโหลดอื่น
     *   ไม่ล็อกอิน = legacy.auth (เดิมตอบ "No permission." ใน script)
     */
    public function ckupload(Request $request): Response
    {
        $url = '';
        $message = '';
        $file = $request->file('upload');
        if (! $file instanceof UploadedFile || $file->getClientOriginalName() === '' || $file->getError() !== UPLOAD_ERR_OK) {
            $message = 'No file.';
        } elseif ($file->getSize() <= 0 || $file->getSize() > 10485760) {
            $message = 'Bad size.';
        } elseif (! $file->isValid()) {
            $message = 'Invalid.';
        } elseif (! in_array(strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'], true)) {
            $message = 'Only JPG/PNG/GIF.';
        } else {
            $info = @getimagesize($file->getPathname());
            if ($info === false || ! isset(self::CK_TYPES[$info[2]])) {
                $message = 'Not an image.';
            } else {
                $name = time().'_'.Format::randd(16).'.'.self::CK_TYPES[$info[2]];
                $dir = $this->tempDir();
                $stored = false;
                try {
                    if (is_dir($dir) || @mkdir($dir, 0770, true) || is_dir($dir)) {
                        $file->move($dir, $name);
                        $stored = BlobStore::uploadFromTemp($name, false) === '';
                        @unlink($dir.'/'.$name);
                    }
                } catch (\Throwable) {
                    $stored = false;
                }
                if ($stored) {
                    $url = Legacy::hrefBlob().$name;
                } else {
                    $message = 'Move failed.';
                }
            }
        }
        if ($message !== '') {
            $url = '';
        }
        $fn = (int) $request->query('CKEditorFuncNum', '0');
        $js = fn (string $s) => str_replace(['\\', "'", '<', "\n", "\r"], ['\\\\', "\\'", '\\x3C', '\\n', '\\r'], $s);

        return response("<script>window.parent.CKEDITOR.tools.callFunction($fn, '".$js($url)."', '".$js($message)."');</script>")
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function store(UploadedFile $file, string $ext): ?string
    {
        $dir = $this->tempDir();
        if (! is_dir($dir) && ! @mkdir($dir, 0770, true) && ! is_dir($dir)) {
            return null;
        }
        $name = Format::randd().'.'.$ext;
        try {
            $file->move($dir, $name);
        } catch (\Throwable) {
            return null;
        }

        return $name;
    }

    private function tempDir(): string
    {
        return rtrim((string) config('legacy.upload.temp_dir'), '/');
    }

    private function text(string $s): Response
    {
        return response($s, 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }
}
