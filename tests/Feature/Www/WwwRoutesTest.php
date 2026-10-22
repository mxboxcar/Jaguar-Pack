<?php

namespace Tests\Feature\Www;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * เว็บหน้าแรก pack-cargo.net (routes/www.php → WwwController): snapshot ของ WordPress เดิมตรงตัว + redirect แบบ WordPress
 * การค้นหา ?s= ใช้ MySQL (JSON_TABLE) จึงทดสอบใน WwwSearchDbTest (bench DB)
 * ส่ง request ผ่าน www() เพราะ $this->get() ตัด "/" ท้าย URL ทิ้ง (WordPress แยก /faq กับ /faq/)
 */
class WwwRoutesTest extends TestCase
{
    public static function www(TestCase $t, string $url, string $method = 'GET'): TestResponse
    {
        return TestResponse::fromBaseResponse($t->app->make(Kernel::class)->handle(Request::create($url, $method)));
    }

    private function site(): array
    {
        return json_decode((string) file_get_contents(resource_path('www/site.json')), true);
    }

    public function test_every_page_is_served_byte_for_byte_on_both_hosts(): void
    {
        foreach ($this->site()['items'] as $it) {
            $want = file_get_contents(resource_path('www/pages/'.$it['id'].'.html'));
            foreach (['http://pack-cargo.net', 'http://www.pack-cargo.net'] as $base) {
                $res = self::www($this, $base.implode('/', array_map('rawurlencode', explode('/', $it['path']))));
                $res->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
                $this->assertSame($want, $res->getContent(), $it['path']);
            }
        }
        // WordPress ไม่สนตัวพิมพ์ของ slug
        self::www($this, 'http://pack-cargo.net/FAQ/')->assertOk()->assertSee('คำถามที่พบบ่อย', false);
    }

    public function test_wordpress_redirects(): void
    {
        self::www($this, 'http://pack-cargo.net/faq?fbclid=x')->assertStatus(301)->assertHeader('Location', 'https://pack-cargo.net/faq/?fbclid=x');
        self::www($this, 'http://pack-cargo.net/home/')->assertStatus(301)->assertHeader('Location', 'https://pack-cargo.net/');
        self::www($this, 'http://pack-cargo.net/?page_id=55')->assertStatus(301)->assertHeader('Location', 'https://pack-cargo.net/our-services/');
        self::www($this, 'http://pack-cargo.net/?p=701')->assertStatus(301)->assertHeader('Location', 'https://pack-cargo.net/');
        self::www($this, 'http://pack-cargo.net/?p=208')->assertStatus(301)
            ->assertHeader('Location', 'https://pack-cargo.net/2020/05/27/how-to-%e0%b8%aa%e0%b8%b1%e0%b9%88%e0%b8%87%e0%b8%aa%e0%b8%b4%e0%b8%99%e0%b8%84%e0%b9%89%e0%b8%b2%e0%b8%88%e0%b8%b2%e0%b8%81%e0%b8%88%e0%b8%b5%e0%b8%99-%e0%b8%9c%e0%b9%88%e0%b8%b2%e0%b8%99-pack-cargo/');
        // index.php = ชื่อ front controller → ต้องดู REQUEST_URI
        self::www($this, 'http://pack-cargo.net/index.php')->assertStatus(301)->assertHeader('Location', 'https://pack-cargo.net/');
        self::www($this, 'http://pack-cargo.net/?p=99999')->assertStatus(404);
    }

    public function test_unknown_urls_and_portal_paths_get_the_theme_404(): void
    {
        $want = str_replace('%%TERM_HTML%%', '', file_get_contents(resource_path('www/404.html')));
        foreach (['/does-not-exist/', '/login', '/wp-admin/', '/admin/user', '/dashboard'] as $p) {
            $res = self::www($this, 'http://pack-cargo.net'.$p)->assertStatus(404);
            $this->assertSame($want, $res->getContent(), $p);
        }
        // POST ไปโดเมน www ไม่ไปถึง route ของ portal (WordPress ตอบหน้าเดิมเหมือน GET)
        self::www($this, 'http://pack-cargo.net/system_check_login', 'POST')->assertStatus(404);
        self::www($this, 'http://pack-cargo.net/faq/', 'POST')->assertOk();
    }

    public function test_feeds_and_robots(): void
    {
        self::www($this, 'http://pack-cargo.net/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertContent("User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n");
        $res = self::www($this, 'http://pack-cargo.net/feed/?a=1&b=2');
        $res->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $this->assertStringContainsString('<atom:link href="https://pack-cargo.net/feed/?a=1&#038;b=2" rel="self"', $res->getContent());
        $this->assertStringNotContainsString('%%', $res->getContent());
        foreach ($this->site()['extra'] as $path => [$file, $type]) {
            self::www($this, 'http://pack-cargo.net'.implode('/', array_map('rawurlencode', explode('/', $path))))->assertOk()->assertHeader('Content-Type', $type);
        }
    }

    public function test_portal_host_is_untouched(): void
    {
        // โดเมน portal ยังเป็น route ของ portal (หน้าแรก → login)
        self::www($this, 'http://portal.pack-cargo.net/faq/')->assertStatus(302);
    }
}
