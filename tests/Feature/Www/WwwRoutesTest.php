<?php

namespace Tests\Feature\Www;

use App\Http\Controllers\Www\WwwController;
use Tests\TestCase;

/**
 * เว็บหน้าแรก (routes/www.php): URL เดิมแบบ .php และแบบไม่มีนามสกุลใช้ได้, ผูกเฉพาะโดเมน www/apex,
 * apex → 302 ไป www. (เดิมทุกหน้าทำเอง) — หน้า index อ่านตาราง setting จึงทดสอบเทียบ HTML บน dev แทน
 */
class WwwRoutesTest extends TestCase
{
    public function test_every_page_is_served_with_and_without_php_extension(): void
    {
        foreach (WwwController::PAGES as $page) {
            if ($page === 'index') {
                continue;
            }
            $this->get('http://www.jlcargoth.com/'.$page.'.php')->assertOk()->assertSee('<html', false);
            $this->get('http://www.jlcargoth.com/'.$page)->assertOk();
        }
    }

    public function test_apex_host_redirects_to_www_keeping_the_path(): void
    {
        $this->get('https://jlcargoth.com/how_to_order.php?x=1')
            ->assertStatus(302)
            ->assertRedirect('https://www.jlcargoth.com/how_to_order.php?x=1');
    }

    public function test_www_routes_do_not_answer_on_the_portal_host(): void
    {
        $this->get('http://portal.jlcargoth.com/how_to_order.php')->assertRedirect();
        $this->assertStringNotContainsString('how_to', (string) $this->get('http://portal.jlcargoth.com/how_to')->headers->get('Location'));
    }
}
