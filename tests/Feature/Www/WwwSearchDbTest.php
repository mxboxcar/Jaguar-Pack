<?php

namespace Tests\Feature\Www;

use Tests\Feature\Money\BenchDbTestCase;

/**
 * ค้นหา ?s= ของ pack-cargo.net แบบ WordPress 5.2 (LIKE ใน MySQL ด้วย utf8mb4_unicode_520_ci, หน้าละ 5)
 * ผลลัพธ์อ้างอิงจาก WordPress เดิม (เทียบทั้งหน้าแล้วด้วย /root/pack-tools/compare_www.py) — bench DB, opt-in JL_BENCH_DB_TESTS=1
 * JSON_TABLE ไม่อ่าน/เขียนตารางใด
 */
class WwwSearchDbTest extends BenchDbTestCase
{
    private function ids(string $html): array
    {
        preg_match_all('/<article id="post-(\d+)"/', $html, $m);

        return array_map('intval', $m[1]);
    }

    public function test_results_order_and_paging_match_wordpress(): void
    {
        $p1 = WwwRoutesTest::www($this, 'http://pack-cargo.net/?s=%E0%B8%88%E0%B8%B5%E0%B8%99')->assertOk()->getContent();   // จีน
        $this->assertSame([208, 195, 187, 80, 78], $this->ids($p1));
        $this->assertStringContainsString('<div class="alignleft"><a href="https://pack-cargo.net/page/2/?s=%E0%B8%88%E0%B8%B5%E0%B8%99" >&laquo; โพสต์ก่อนหน้า</a></div>', $p1);
        $this->assertStringContainsString("'/?s=%E0%B8%88%E0%B8%B5%E0%B8%99&cat=plus-5-results'", $p1);
        $this->assertStringContainsString('<body class="search search-results et_pb_button_helper_class', $p1);

        // "e": ชื่อเรื่องที่มี e ก่อน แล้ววันที่ใหม่ก่อน — 14 รายการ 3 หน้า, หน้า 4 = 404
        $this->assertSame([701, 215, 208, 195, 187], $this->ids(WwwRoutesTest::www($this, 'http://pack-cargo.net/?s=e')->getContent()));
        $p3 = WwwRoutesTest::www($this, 'http://pack-cargo.net/page/3/?s=e')->assertOk()->getContent();
        $this->assertSame([66, 62, 58, 55], $this->ids($p3));
        $this->assertStringContainsString('search-results paged paged-3 search-paged-3 ', $p3);
        WwwRoutesTest::www($this, 'http://pack-cargo.net/page/4/?s=e')->assertStatus(404)->assertSee('value="e"', false);
    }

    public function test_thai_tone_marks_are_ignored_like_mysql(): void
    {
        // utf8mb4_unicode_520_ci: "ที้" ตรงกับ "ที่"
        $a = $this->ids(WwwRoutesTest::www($this, 'http://pack-cargo.net/?s='.rawurlencode('ที่'))->getContent());
        $b = $this->ids(WwwRoutesTest::www($this, 'http://pack-cargo.net/?s='.rawurlencode('ที้'))->getContent());
        $this->assertNotEmpty($a);
        $this->assertSame($a, $b);
    }

    public function test_no_results_and_escaping(): void
    {
        $html = WwwRoutesTest::www($this, 'http://pack-cargo.net/?s='.rawurlencode('<script>"x'))->assertOk()->getContent();
        $this->assertSame([], $this->ids($html));
        $this->assertStringContainsString('<h1 class="not-found-title">ไม่พบผลลัพธ์</h1>', $html);
        $this->assertStringContainsString('value="&lt;script&gt;&quot;x"', $html);
        $this->assertStringNotContainsString('<script>"x', $html);
        $this->assertStringNotContainsString('%%', $html);
    }
}
