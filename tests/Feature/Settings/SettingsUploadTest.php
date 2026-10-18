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

    public function test_upload_2_uses_the_real_type_extension(): void
    {
        $im = imagecreatetruecolor(4, 4);
        ob_start();
        imagejpeg($im);
        $out = $this->send('system_upload_2', UploadedFile::fake()->createWithContent('photo.png', (string) ob_get_clean()));
        $this->assertMatchesRegularExpression('/^[A-Z]{10}\.jpg\[NIDA\]photo\.png$/', $out);
    }
}
