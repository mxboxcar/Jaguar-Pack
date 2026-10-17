<?php

namespace Tests\Feature\Reports;

use App\Domain\Reports\Php74;
use App\Domain\Reports\Rows;
use App\Domain\Reports\TempUpload;
use App\Domain\Reports\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use ZipArchive;

/**
 * กลุ่ม reports: ทุก URL เดิมมี route + สิทธิ์ตรงกับ getPermission() ของไฟล์เดิม และ helper ที่เลียนแบบ PHP 7.4 / PHPExcel (ไม่แตะ DB)
 */
class ReportsRoutesTest extends TestCase
{
    /** method uri => middleware สิทธิ์ที่ต้องมี */
    private const EXPECTED = [
        'GET dashboard' => 'legacy.auth',
        'GET export' => 'legacy.perm:export',
        'GET export/{id}' => 'legacy.perm:export',
        'POST _export/grid' => 'legacy.auth',
        'POST _export/save_approved' => 'legacy.perm:payment',
        'POST _export/save_delete' => 'legacy.perm:payment',
        'GET admin/export' => 'legacy.perm:admin_export',
        'GET admin/export/status/{status}' => 'legacy.perm:admin_export',
        'GET admin/export/{id}' => 'legacy.perm:admin_payment',
        'POST _admin/_export/grid' => 'legacy.auth',
        'POST _admin/_export/save_approved' => 'legacy.perm:admin_export',
        'POST _admin/_export/save_delete' => 'legacy.perm:admin_payment',
        'GET _admin/_export/print' => 'legacy.auth',
        'GET _admin/_export/email_success' => 'legacy.perm:admin_export',
        'POST _admin/_export/email_cancel' => 'legacy.perm:admin_export',
        'GET admin/report' => 'legacy.perm:admin_report',
        'GET admin/report/{id}' => 'legacy.perm:admin_report',
        'POST _admin/_report/grid' => 'legacy.auth',
        'GET _admin/_report/file' => 'legacy.auth',
        'GET _admin/_report/else' => 'legacy.auth',
        'GET _admin/_report/else_preorder_working' => 'legacy.auth',
        'GET _admin/_report/refill_5' => 'legacy.auth',
        'GET _admin/_report/refill_6' => 'legacy.auth',
        'GET _admin/_report/refill_7' => 'legacy.auth',
        'GET _admin/_report/shipping' => 'legacy.auth',
        'GET _admin/_report/cashmove' => 'legacy.auth',
        'GET _admin/_report/current_cash' => 'legacy.auth',
        'GET _admin/_report/current_cash_cn' => 'legacy.auth',
        'GET admin/log' => 'legacy.perm:admin_log',
        'POST _admin/_log/grid' => 'legacy.auth',
        'GET admin/nobilling' => 'legacy.perm:admin_nobilling',
        'GET download_file' => 'legacy.perm:admin_lot,admin_lot_view',
    ];

    /** @return array<string, list<string>> */
    private function routes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $m) {
                if ($m !== 'HEAD') {
                    $out[$m.' '.$route->uri()] = $route->gatherMiddleware();
                }
            }
        }

        return $out;
    }

    public function test_every_legacy_url_has_a_route_with_the_legacy_permission(): void
    {
        $routes = $this->routes();
        foreach (self::EXPECTED as $key => $perm) {
            $this->assertArrayHasKey($key, $routes, "ไม่มี route $key");
            $this->assertContains($perm, $routes[$key], "$key ต้องมี $perm");
            $this->assertContains('legacy', $routes[$key], "$key ต้องอยู่ใต้ group legacy");
        }
    }

    public function test_ajax_endpoints_require_xhr_like_system_check_ajax(): void
    {
        $routes = $this->routes();
        foreach (array_keys(self::EXPECTED) as $key) {
            if (str_starts_with($key, 'POST _') && ! str_contains($key, 'email_')) {
                $this->assertContains('legacy.ajax', $routes[$key], "$key ต้องมี legacy.ajax");
            }
        }
    }

    public function test_pages_are_named_for_the_page_query_hook(): void
    {
        foreach (['dashboard', 'export', 'export_detail', 'admin_export', 'admin_export_detail', 'admin_report',
            'admin_report_detail', 'admin_log', 'admin_nobilling'] as $page) {
            $this->assertTrue(Route::has('page.'.$page), "page.$page");
            $this->assertSame($page, Route::getRoutes()->getByName('page.'.$page)->defaults['legacy_page']);
        }
    }

    public function test_legacy_route_patterns(): void
    {
        $this->assertSame('form', $this->match('GET', '/admin/export/12'));
        $this->assertSame('index', $this->match('GET', '/admin/export/status/2'));
        $this->assertSame('print', $this->match('GET', '/_admin/_export/print/'));
        $this->assertSame('form', $this->match('GET', '/admin/report/3'));
    }

    public function test_php74_loose_compare_used_by_report_types(): void
    {
        $this->assertTrue(Php74::eq('99', 99));
        $this->assertTrue(Php74::eq('99abc', 99));
        $this->assertFalse(Php74::eq('9', 99));
        $this->assertTrue(Php74::eq(null, 0));
        $this->assertFalse(Php74::eq(null, '-2'));
    }

    public function test_paging_offset_like_make_paging(): void
    {
        $this->assertSame(0, Rows::offset('1'));
        $this->assertSame(10, Rows::offset('2'));
        $this->assertNull(Rows::offset('0'));
        $this->assertNull(Rows::offset('abc'));
        $this->assertSame(0, Rows::offset('1; DROP TABLE x'));
    }

    public function test_temp_upload_names_are_whitelisted(): void
    {
        $this->assertFalse(TempUpload::safeName('../../x.php'));
        $this->assertFalse(TempUpload::safeName("a'b.jpg"));
        $this->assertTrue(TempUpload::safeName('abc_123.jpg'));
    }

    public function test_xlsx_writer_binds_values_like_phpexcel(): void
    {
        $this->assertTrue(XlsxWriter::isNumeric('12.50'));
        $this->assertTrue(XlsxWriter::isNumeric('-3'));
        $this->assertTrue(XlsxWriter::isNumeric('0123'));
        $this->assertFalse(XlsxWriter::isNumeric('BLA-1'));
        $this->assertFalse(XlsxWriter::isNumeric('1e5'));

        $x = (new XlsxWriter('Report'))->width('A', 15)
            ->set('A', 1, 'หมายเลขรายการ', XlsxWriter::HEAD)
            ->set('A', 2, 'BLA-1', XlsxWriter::CENTER)->set('B', 2, '1250.50', XlsxWriter::RIGHT_NUM)
            ->set('C', 2, null)->set('D', 2, '=HYPERLINK("http://x")');
        $tmp = tempnam(sys_get_temp_dir(), 'xt');
        file_put_contents($tmp, $x->toString());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($tmp);
        $this->assertStringContainsString('<c r="B2" s="4"><v>1250.50</v></c>', $sheet);
        $this->assertStringContainsString('<t xml:space="preserve">BLA-1</t>', $sheet);
        $this->assertStringContainsString('<c r="C2"/>', $sheet);
        // สูตรไม่ถูกเขียนเป็น formula (กัน formula injection)
        $this->assertStringNotContainsString('<f>', $sheet);
        $this->assertStringContainsString('=HYPERLINK(&quot;http://x&quot;)', $sheet);
        $this->assertNotFalse(simplexml_load_string($sheet));
    }

    private function match(string $method, string $uri): string
    {
        $route = Route::getRoutes()->match(Request::create($uri, $method));

        return $route->getActionMethod();
    }
}
