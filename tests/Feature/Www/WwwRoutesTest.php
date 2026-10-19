<?php

namespace Tests\Feature\Www;

use App\Http\Controllers\Www\WwwController;
use Tests\TestCase;

/**
 * เว็บหน้าแรก (routes/www.php): URL ใหม่ /how-to/..., /contact, /terms · URL เดิม (.php และไม่มีนามสกุล) → 301
 * apex → 302 ไป www. · ผูกเฉพาะโดเมน www/apex · หน้า index อ่านตาราง setting จึงไม่ทดสอบเนื้อหาที่นี่
 */
class WwwRoutesTest extends TestCase
{
    public function test_every_page_is_served_on_its_new_url(): void
    {
        foreach (WwwController::URLS as $page => $url) {
            if ($page === 'index') {
                continue;
            }
            $html = $this->get('http://www.jlcargoth.com'.$url)->assertOk()->getContent();
            $this->assertStringContainsString('<html', $html);
            // asset ต้องเป็น path จาก root (หน้า /how-to/order โหลด css/ แบบ relative ไม่เจอ)
            $this->assertDoesNotMatchRegularExpression('~(src|href)=["\'](css|js|img|image|XDX|master)/~', $html);
            $this->assertStringNotContainsString('.php"', $html);
            $this->assertStringNotContainsString('_blank', $html, 'ลิงก์ต้องเปิดในแท็บเดิม');
        }
    }

    public function test_old_urls_redirect_permanently_to_the_new_ones(): void
    {
        $this->get('http://www.jlcargoth.com/how_to_order.php')->assertStatus(301)->assertRedirect('http://www.jlcargoth.com/how-to/order');
        $this->get('http://www.jlcargoth.com/how_to_order')->assertStatus(301)->assertRedirect('http://www.jlcargoth.com/how-to/order');
        $this->get('http://www.jlcargoth.com/page_02.php')->assertStatus(301)->assertRedirect('http://www.jlcargoth.com/contact');
        $this->get('http://www.jlcargoth.com/page_03.php?a=1')->assertStatus(301)->assertRedirect('http://www.jlcargoth.com/terms?a=1');
        $this->get('http://www.jlcargoth.com/index.php')->assertStatus(301)->assertRedirect('http://www.jlcargoth.com/');
        $this->get('https://jlcargoth.com/how_to.php')->assertStatus(301)->assertRedirect('https://www.jlcargoth.com/how-to');
    }

    public function test_apex_host_redirects_to_www_keeping_the_path(): void
    {
        $this->get('https://jlcargoth.com/how-to/order?x=1')->assertStatus(302)->assertRedirect('https://www.jlcargoth.com/how-to/order?x=1');
    }

    public function test_www_routes_do_not_answer_on_the_portal_host(): void
    {
        $this->get('http://portal.jlcargoth.com/how-to')->assertRedirect();
        $this->assertStringNotContainsString('how-to', (string) $this->get('http://portal.jlcargoth.com/how-to')->headers->get('Location'));
    }
}
