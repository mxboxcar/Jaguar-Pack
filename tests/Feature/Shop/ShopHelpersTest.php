<?php

namespace Tests\Feature\Shop;

use App\Domain\Shop\CartRepo;
use App\Domain\Shop\CrawlGuard;
use App\Domain\Shop\Php74;
use App\Domain\Shop\ProductCrawler;
use App\Domain\Shop\Upload;
use App\Domain\Shop\UserAgent;
use App\Http\Controllers\Shop\ApiController;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** helper ของกลุ่ม shop (ไม่แตะ DB) */
class ShopHelpersTest extends TestCase
{
    public function test_php74_arithmetic_semantics(): void
    {
        $this->assertSame(0, Php74::num(''));
        $this->assertSame(0, Php74::num('abc'));
        $this->assertSame(12, Php74::num('12abc'));
        $this->assertSame(10.5, Php74::num(' 10.5'));
        $this->assertSame(6, Php74::num('2') * Php74::num('3'));
        $this->assertSame('0.3', Php74::str(0.1 + 0.2));          // precision 14 แบบ PHP 7.4
        $this->assertSame('212.625', Php74::str(13.5 * 3 * 5.25));
        $this->assertTrue(Php74::looseEq(0, ''));                 // PHP 7.4: 0 == "" เป็นจริง
        $this->assertFalse(Php74::looseEq(1, ''));
    }

    public function test_id_lists_only_accept_integers(): void
    {
        $this->assertSame([1, 2, 3], CartRepo::parseIdList('1,2, 3'));
        $this->assertSame([5], CartRepo::parseIdList("'5'"));
        $this->assertNull(CartRepo::parseIdList(''));
        $this->assertNull(CartRepo::parseIdList('1) OR (1=1'));
        $this->assertNull(CartRepo::parseIdList('1,2 UNION SELECT 1'));
        $this->assertSame([7, 8], CartRepo::decodeIds(base64_encode('7,8')));
        $this->assertNull(CartRepo::decodeIds(base64_encode('7) OR 1=1 --')));
    }

    public function test_crawl_guard_network_and_quota(): void
    {
        $this->assertSame('203.0.113.0/24', CrawlGuard::net('203.0.113.77'));
        $this->assertSame('2001:db8:1:2::/64', CrawlGuard::net('2001:db8:1:2:3:4:5:6'));
        $this->assertSame(50, CrawlGuard::userDayMax(0));
        $this->assertSame(150, CrawlGuard::userDayMax(1));
        $this->assertSame(300, CrawlGuard::userDayMax(5));
        $this->assertSame(150, CrawlGuard::userDayMax(0, 1));      // บิลแจ้งชำระ/อนุมัติแล้ว
        $this->assertSame(150, CrawlGuard::userDayMax(0, 0, 1));   // ของเข้าโกดังแล้ว
        $this->assertSame(300, CrawlGuard::userDayMax(0, 3, 10));
        $this->assertSame(150, CrawlGuard::userDayMax(4, 9, 9));
        $this->assertSame(45, CrawlGuard::userHourMax(50));
        $this->assertSame(90, CrawlGuard::userHourMax(150));
        $this->assertSame(90, CrawlGuard::userHourMax(300));
        $this->assertSame('โควตาดึงสินค้าของวันนี้หมดแล้ว (150 ครั้ง/วัน) ใช้ได้อีกครั้งหลังเที่ยงคืน', CrawlGuard::message('user_day_150'));
        $this->assertStringContainsString('เครือข่ายนี้', CrawlGuard::message('net_day'));
        $this->assertSame(CrawlGuard::MSG, CrawlGuard::message('other'));
    }

    public function test_product_crawler_never_calls_out_when_external_disabled(): void
    {
        config(['legacy.external.enabled' => false]);
        Http::fake();
        $json = json_decode(ProductCrawler::stub('https://item.taobao.com/item.htm?id=1'), true);
        $this->assertStringContainsString('DEV STUB', $json['product_name']);
        $this->assertSame('https://item.taobao.com/item.htm?id=1', $json['url']);
        $this->assertSame('', json_decode(ProductCrawler::stub(''), true)['product_name']);
        Http::assertNothingSent();
    }

    public function test_load_product_v2_parser_on_empty_output(): void
    {
        $out = json_decode(ApiController::parseV2('', 'http://www.vcanbuy.com/x?u=https://item.taobao.com/item.htm?id=9', 'https://item.taobao.com/item.htm?id=9'), true);
        $this->assertSame('', $out['product_name']);
        $this->assertSame('https://item.taobao.com/item.htm?id=', $out['url']);
        $this->assertSame([], $out['color']);
    }

    public function test_upload_temp_path_cannot_escape_temp_dir(): void
    {
        config(['legacy.upload.temp_dir' => '/tmp/zz-shop-temp']);
        $this->assertSame('/tmp/zz-shop-temp/x.png', Upload::tempPath('../../x.png'));
        $this->assertSame('/tmp/zz-shop-temp/passwd', Upload::tempPath('/etc/passwd'));
        $this->assertNull(Upload::tempPath('..'));
        $this->assertSame('', Upload::blobJs('does-not-exist.png'));
    }

    public function test_getupload_escapes_filename(): void
    {
        $html = Upload::getupload('file', 'a"b.pdf', true, false);
        $this->assertStringContainsString('value="a&quot;b.pdf"', $html);
        $this->assertStringContainsString('$(".upload_file").dropper({', $html);
        $this->assertStringNotContainsString('validate[requiredattachment]', $html);
    }

    public function test_random_user_agent_comes_from_legacy_list(): void
    {
        $this->assertMatchesRegularExpression('/^(Mozilla|Opera)/', UserAgent::random());
        $this->assertNotSame('', UserAgent::random());
    }
}
