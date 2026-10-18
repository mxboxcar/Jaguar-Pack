<?php

namespace Tests\Feature\Lot;

use App\Http\Controllers\Lot\Admin\AdminLotPrintController;
use Illuminate\Http\Request;
use Tests\TestCase;

/** print / print_all / email_lot เป็น GET ที่เขียนข้อมูล → ไม่ยอมคำขอที่มาจากเว็บอื่น (LegacyCsrf ตรวจแค่ POST) */
class LotCrossSiteGetTest extends TestCase
{
    private function req(array $headers): Request
    {
        $server = ['HTTP_HOST' => 'portal.example'];
        foreach ($headers as $k => $v) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $k))] = $v;
        }

        return Request::create('http://portal.example/_admin/_lot/email_lot?id=1', 'GET', [], [], [], $server);
    }

    public function test_cross_site_requests_are_refused(): void
    {
        $this->assertTrue(AdminLotPrintController::crossSite($this->req(['Sec-Fetch-Site' => 'cross-site'])));
        $this->assertTrue(AdminLotPrintController::crossSite($this->req(['Sec-Fetch-Site' => 'same-site'])));
        $this->assertTrue(AdminLotPrintController::crossSite($this->req(['Referer' => 'https://evil.example/x'])));
        $this->assertTrue(AdminLotPrintController::crossSite($this->req(['Referer' => 'not a url'])));
    }

    public function test_same_origin_and_direct_requests_are_allowed(): void
    {
        $this->assertFalse(AdminLotPrintController::crossSite($this->req(['Sec-Fetch-Site' => 'same-origin'])));
        $this->assertFalse(AdminLotPrintController::crossSite($this->req(['Sec-Fetch-Site' => 'none'])));
        $this->assertFalse(AdminLotPrintController::crossSite($this->req(['Referer' => 'http://portal.example/admin/lot'])));
        $this->assertFalse(AdminLotPrintController::crossSite($this->req([])));
    }
}
