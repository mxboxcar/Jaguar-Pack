<?php

namespace Tests\Feature\Settings;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** system_upload / system_upload_2: ชนิดไฟล์ที่ยอม/ปฏิเสธ และไฟล์ลงโฟลเดอร์ temp ด้วยชื่อสุ่มเท่านั้น (middleware ปิด) */
class SettingsUploadTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->dir = sys_get_temp_dir().'/jl-upload-test-'.bin2hex(random_bytes(4));
        config(['legacy.upload.temp_dir' => $this->dir]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $f) {
            unlink($f);
        }
        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
        parent::tearDown();
    }

    private function png(string $name): UploadedFile
    {
        $im = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($im);

        return UploadedFile::fake()->createWithContent($name, (string) ob_get_clean());
    }

    private function send(string $url, UploadedFile $file): string
    {
        $res = $this->post('/'.$url, ['file' => $file]);
        $res->assertOk();
        $this->assertStringStartsWith('text/plain', (string) $res->headers->get('Content-Type'));

        return (string) $res->getContent();
    }

    public function test_real_images_are_stored_under_a_random_name(): void
    {
        foreach (['system_upload' => '/^[A-Z]{10}\.png$/', 'system_upload_2' => '/^[A-Z]{10}\.png\[NIDA\]\.\.\/\.\.\/x\.png$/'] as $url => $re) {
            $out = $this->send($url, $this->png('../../x.png'));
            // ชื่อเดิมของผู้ใช้ (มี ../) ไม่ถูกใช้เป็น path — ไฟล์ลง temp_dir เท่านั้น
            $this->assertMatchesRegularExpression(str_replace('\.\.\/\.\.\/', '(\.\.\/\.\.\/)?', $re), $out, $url);
            $this->assertFileExists($this->dir.'/'.explode('[NIDA]', $out)[0]);
        }
        $this->assertCount(2, glob($this->dir.'/*'));
    }

    public function test_php_and_fake_images_are_rejected(): void
    {
        $php = '<?php echo 1; ?>';
        foreach (['system_upload', 'system_upload_2'] as $url) {
            foreach (['x.php', 'x.phtml', 'x.php.jpg', 'fake.jpg', 'fake.png', 'x.pdf'] as $name) {
                $out = $this->send($url, UploadedFile::fake()->createWithContent($name, $php));
                $this->assertStringStartsWith('error', $out, "$url $name");
            }
        }
        $this->assertSame([], glob($this->dir.'/*') ?: []);
    }

    /** PACK system_upload: รายการนามสกุลเดิม (ตรงตัวพิมพ์) รวม zip/rar/7z/doc(x)/xls(x), นามสกุลตามที่ส่งมา, ข้อความ error เดิม */
    public function test_upload_accepts_pack_extension_list(): void
    {
        $this->assertMatchesRegularExpression('/^[A-Z]{10}\.zip$/', $this->send('system_upload', UploadedFile::fake()->createWithContent('a.zip', "PK\x03\x04zz")));
        $this->assertMatchesRegularExpression('/^[A-Z]{10}\.DOCX$/', $this->send('system_upload', UploadedFile::fake()->createWithContent('a.DOCX', 'zz')));
        $this->assertMatchesRegularExpression('/^[A-Z]{10}\.7z$/', $this->send('system_upload', UploadedFile::fake()->createWithContent('a.7z', 'zz')));
        // รูปจริงคงนามสกุลเดิม (ตัวพิมพ์ใหญ่ด้วย) — ชนิดจริงไม่ตรงใช้นามสกุลตามชนิดจริง
        $this->assertMatchesRegularExpression('/^[A-Z]{10}\.PNG$/', $this->send('system_upload', $this->png('x.PNG')));
        $this->assertMatchesRegularExpression('/^[A-Z]{10}\.png$/', $this->send('system_upload', $this->png('x.jpg')));
        foreach (['x.Jpg', 'x.gif', 'x.exe', 'x', 'x.html'] as $name) {
            $this->assertSame('error : รองรับ .png, .jpg, .jpeg, .pdf, .zip, .rar, .7z เท่านั้น', $this->send('system_upload', UploadedFile::fake()->createWithContent($name, 'zz')), $name);
        }
        $this->assertCount(5, glob($this->dir.'/*'));
    }

    public function test_upload_2_uses_the_real_type_extension(): void
    {
        $im = imagecreatetruecolor(4, 4);
        ob_start();
        imagejpeg($im);
        $out = $this->send('system_upload_2', UploadedFile::fake()->createWithContent('photo.png', (string) ob_get_clean()));
        $this->assertMatchesRegularExpression('/^[A-Z]{10}\.jpg\[NIDA\]photo\.png$/', $out);
    }

    /** สิทธิ์สมมติของผู้เรียก (ไม่มีผู้ใช้ใน test — Permissions อ่าน map ที่เติมไว้) */
    private function grant(array $modules): void
    {
        $map = \App\Support\Legacy\Permissions::build(array_map(fn ($n) => (object) ['name' => $n, 'permission' => '1'], $modules));
        $p = $this->app->make(\App\Support\Legacy\Permissions::class);
        (function () use ($map) {
            $this->forUser = null;
            $this->map = $map;
        })->call($p);
    }

    /** PACK system_ckupload: ต้องมี admin_notice หรือ admin_mall — ไม่มี = "No permission." (ไม่เก็บไฟล์) */
    public function test_ckupload_requires_notice_or_mall_permission(): void
    {
        $perm = $this->dir.'-perm';
        config(['legacy.upload.driver' => 'local', 'legacy.upload.local_dir' => $perm]);
        foreach ([[[], 'No permission.'], [['profile', 'admin_user'], 'No permission.'], [['admin_mall'], ''], [['admin_notice'], '']] as [$mods, $want]) {
            $this->grant($mods);
            $res = $this->post('/system_ckupload?CKEditorFuncNum=3', ['upload' => $this->png('x.png')]);
            preg_match("~callFunction\\((-?\\d+), '([^']*)', '([^']*)'\\)~", (string) $res->getContent(), $m);
            $this->assertSame($want, $m[3] ?? null, implode(',', $mods));
            $this->assertSame($want === '', ($m[2] ?? '') !== '', implode(',', $mods));
        }
        $this->assertCount(2, glob($perm.'/*') ?: []);
        foreach (glob($perm.'/*') ?: [] as $f) {
            unlink($f);
        }
        is_dir($perm) && rmdir($perm);
    }

    /** system_ckupload: รูปจริง → URL ของที่เก็บถาวร (BlobStore local ใน test), ไม่ใช่รูป → ข้อความเดิม */
    public function test_ckupload_stores_real_images_only(): void
    {
        $this->grant(['admin_notice']);
        $perm = $this->dir.'-perm';
        config(['legacy.upload.driver' => 'local', 'legacy.upload.local_dir' => $perm]);
        $ck = function (?UploadedFile $f, string $fn = '7') {
            $res = $this->post('/system_ckupload?CKEditorFuncNum='.$fn, $f === null ? [] : ['upload' => $f]);
            $res->assertOk();
            $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
            $this->assertMatchesRegularExpression("~^<script>window\\.parent\\.CKEDITOR\\.tools\\.callFunction\\((-?\\d+), '([^']*)', '([^']*)'\\);</script>$~", (string) $res->getContent());
            preg_match("~callFunction\\((-?\\d+), '([^']*)', '([^']*)'\\)~", (string) $res->getContent(), $m);

            return [(int) $m[1], $m[2], $m[3]];
        };
        try {
            $gif = UploadedFile::fake()->createWithContent('Photo.JPG', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
            [$fn, $url, $msg] = $ck($gif);
            $this->assertSame([7, ''], [$fn, $msg]);
            // ชื่อใหม่ = <time>_<สุ่ม 16>.<นามสกุลตามชนิดจริง> (GIF แม้ชื่อเป็น .JPG) ที่ href_blob
            $this->assertMatchesRegularExpression('~^'.preg_quote(\App\Support\Legacy\Legacy::hrefBlob(), '~').'\d+_[A-Z]{16}\.gif$~', $url);
            $this->assertFileExists($perm.'/'.basename($url));
            $this->assertSame([], glob($this->dir.'/*') ?: [], 'temp copy removed');

            $this->assertSame([0, '', 'Not an image.'], $ck(UploadedFile::fake()->createWithContent('x.png', '<?php echo 1; ?>'), 'abc'));
            $this->assertSame('Only JPG/PNG/GIF.', $ck($this->png('x.bmp'))[2]);
            $this->assertSame('Only JPG/PNG/GIF.', $ck($this->png('x.php'))[2]);
            $this->assertSame('No file.', $ck(null)[2]);
            $this->assertSame('Bad size.', $ck(UploadedFile::fake()->create('big.png', 10241))[2]);
            $this->assertCount(1, glob($perm.'/*'));
        } finally {
            foreach (glob($perm.'/*') ?: [] as $f) {
                unlink($f);
            }
            is_dir($perm) && rmdir($perm);
        }
    }
}
