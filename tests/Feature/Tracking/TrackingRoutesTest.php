<?php

namespace Tests\Feature\Tracking;

use App\Domain\Tracking\CombineTrack;
use App\Domain\Tracking\Php74;
use App\Domain\Tracking\TrackingLookups;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม tracking: ทุก URL เดิมมี route + สิทธิ์ตรงกับ getPermission() ของไฟล์เดิม, และ helper ที่เลียนแบบ PHP 7.4
 * (ไม่แตะ DB)
 */
class TrackingRoutesTest extends TestCase
{
    /** method uri => middleware สิทธิ์ที่ต้องมี */
    private const EXPECTED = [
        'GET tracking' => 'legacy.perm:tracking',
        'GET tracking/create' => 'legacy.perm:tracking',
        'GET tracking/{id}' => 'legacy.perm:tracking',
        'POST _tracking/grid' => 'legacy.auth',
        'POST _tracking/delete' => 'legacy.perm:tracking',
        'POST _tracking/save' => 'legacy.perm:tracking',
        'POST _tracking/load_pricing' => 'legacy.auth',
        'POST _tracking/load_product_type' => 'legacy.auth',
        'POST _tracking/save_quick_quote' => 'legacy.auth',
        'GET admin/tracking' => 'legacy.perm:admin_tracking',
        'GET admin/tracking/create' => 'legacy.perm:admin_tracking',
        'GET admin/tracking/status/{status}' => 'legacy.perm:admin_tracking',
        'GET admin/tracking/{id}' => 'legacy.perm:admin_tracking',
        'POST _admin/_tracking/grid' => 'legacy.auth',
        'POST _admin/_tracking/load_paging' => 'legacy.perm:admin_tracking',
        'POST _admin/_tracking/delete' => 'legacy.perm:admin_tracking',
        'POST _admin/_tracking/save' => 'legacy.perm:admin_tracking',
        'POST _admin/_tracking/save_lock' => 'legacy.perm:admin_tracking',
        'POST _admin/_tracking/load_pricing' => 'legacy.perm:admin_tracking',
        'POST _admin/_tracking/load_product_type' => 'legacy.perm:admin_tracking',
        'GET admin/combine_track' => 'legacy.perm:admin_combine_track',
        'POST _admin/_combine_track/load_tracking' => 'legacy.perm:admin_combine_track',
        'POST _admin/_combine_track/save_combine' => 'legacy.perm:admin_combine_track',
        'GET lost_package' => 'legacy.perm:lost_package',
        'GET lost_package/create' => 'legacy.perm:lost_package',
        'GET lost_package/status/{status}' => 'legacy.perm:lost_package',
        'GET lost_package/{id}' => 'legacy.perm:lost_package',
        'POST _lost_package/grid' => 'legacy.auth',
        'POST _lost_package/cancel' => 'legacy.perm:lost_package',
        'POST _lost_package/load_order_number' => 'legacy.perm:lost_package',
        'POST _lost_package/load_upload' => 'legacy.perm:lost_package',
        'POST _lost_package/save' => 'legacy.perm:lost_package',
        'POST _lost_package/save_image' => 'legacy.perm:lost_package',
        'POST _lost_package/upload' => 'legacy.auth',
        'GET admin/lost_package' => 'legacy.perm:admin_lost_package',
        'GET admin/lost_package/create' => 'legacy.perm:admin_lost_package',
        'GET admin/lost_package/status/{status}' => 'legacy.perm:admin_lost_package',
        'GET admin/lost_package/{id}' => 'legacy.perm:admin_lost_package',
        'POST _admin/_lost_package/grid' => 'legacy.auth',
        'POST _admin/_lost_package/cancel' => 'legacy.perm:admin_lost_package',
        'POST _admin/_lost_package/rollback' => 'legacy.perm:admin_lost_package',
        'POST _admin/_lost_package/save_approved' => 'legacy.perm:admin_lost_package',
        'GET _admin/_lost_package/email_approved' => 'legacy.perm:admin_lost_package',
        'GET _admin/_lost_package/email_cancel' => 'legacy.perm:admin_lost_package',
    ];

    public function test_every_legacy_url_has_a_route_with_the_legacy_permission(): void
    {
        $found = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $found[$m.' '.$r->uri()] = $r;
            }
        }
        foreach (self::EXPECTED as $key => $mw) {
            $this->assertArrayHasKey($key, $found, "ไม่มี route $key");
            $this->assertContains($mw, $found[$key]->gatherMiddleware(), "$key ต้องมี $mw");
            if (str_starts_with($key, 'POST ')) {
                $this->assertContains('legacy.ajax', $found[$key]->gatherMiddleware(), "$key เดิมผ่าน system_check_ajax");
            }
        }
    }

    public function test_page_routes_set_legacy_page(): void
    {
        $pages = [
            'tracking' => 'tracking', 'tracking/{id}' => 'tracking_detail', 'admin/tracking/{id}' => 'admin_tracking_detail',
            'admin/combine_track' => 'admin_combine_track', 'lost_package/status/{status}' => 'lost_package_detail',
            'admin/lost_package/status/{status}' => 'admin_lost_package',
        ];
        foreach (Route::getRoutes() as $r) {
            if (isset($pages[$r->uri()]) && in_array('GET', $r->methods(), true)) {
                $this->assertSame($pages[$r->uri()], $r->defaults['legacy_page'] ?? null, $r->uri());
            }
        }
    }

    public function test_php74_loose_comparison(): void
    {
        $this->assertTrue(Php74::eq(0, ''));
        $this->assertTrue(Php74::eq('abc', 0));
        $this->assertTrue(Php74::eq(null, 0));
        $this->assertFalse(Php74::eq(null, '0'));
        $this->assertTrue(Php74::eq('1.0', '1'));
        $this->assertFalse(Php74::eq('1 ', '1'));
        $this->assertTrue(Php74::eq('1abc', 1));
        $this->assertTrue(Php74::neq('-1abc', '-1'));
        $this->assertTrue(Php74::gt('1', ''));
        $this->assertFalse(Php74::gt('1', '2'));
    }

    public function test_php74_numbers(): void
    {
        $this->assertSame(0, Php74::num(''));
        $this->assertSame(12, Php74::num('12abc'));
        $this->assertSame(1.5, Php74::num('1.5'));
        $this->assertSame('2', Php74::str(Php74::num('2.0') * 1));
        $this->assertSame([1, 2], Php74::idList('1,2'));
        $this->assertSame([1, 2], Php74::idList("'1','2'"));
        $this->assertNull(Php74::idList('1) OR (1=1'));
        $this->assertNull(Php74::idList(''));
    }

    public function test_paging_offset_matches_make_paging(): void
    {
        $this->assertSame(0, TrackingLookups::offset('1'));
        $this->assertSame(20, TrackingLookups::offset('3'));
        $this->assertSame(5, TrackingLookups::offset('1.5'));
        $this->assertNull(TrackingLookups::offset(''));   // LIMIT -10 = SQL error เดิม
        $this->assertNull(TrackingLookups::offset('0'));
    }

    public function test_combine_merge_rules(): void
    {
        $a = (object) ['create_date' => '2026-01-01 00:00:00', 'mod_date' => '0000-00-00 00:00:00', 'no' => 0, 'tracking' => '',
            'order' => 'O1', 'price_kg' => '0.00', 'amount' => 2, 'ship' => 0, 'is_craft' => 1, 'quote' => '', 'rate_kg' => null,
            'is_checked' => 0, 'date_section' => '0000-00-00 00:00:00', 'remark' => null, 'id' => 5, 'create_by' => 9];
        $b = (object) ['create_date' => '2026-02-01 00:00:00', 'mod_date' => '2026-02-02 00:00:00', 'no' => 7, 'tracking' => 'T2',
            'order' => 'O2', 'price_kg' => '12.50', 'amount' => 5, 'ship' => 1, 'is_craft' => 0, 'quote' => 'Q', 'rate_kg' => null,
            'is_checked' => 1, 'date_section' => '2026-03-01 00:00:00', 'remark' => 'R', 'id' => 6, 'create_by' => 9];
        $m = CombineTrack::merge([$a, $b], '');
        $this->assertSame('2026-01-01 00:00:00', $m['create_date']);
        $this->assertSame('2026-02-02 00:00:00', $m['mod_date']);
        $this->assertSame('7', $m['no']);
        $this->assertSame('T2', $m['tracking']);
        $this->assertSame('O1', $m['order']);
        $this->assertSame('12.50', $m['price_kg']);
        $this->assertSame('5', $m['amount']);
        $this->assertSame('1', $m['ship']);
        $this->assertSame('1', $m['is_craft']);
        // quote '' != '0' → เดิมเอาค่าว่างของแถวแรก แล้วแถวถัดไปเติมได้เพราะยัง == ''
        $this->assertSame('Q', $m['quote']);
        $this->assertSame('', $m['rate_kg']);
        // save_combine: is_checked เทียบกับ '' → 0 ของแถวแรกถูกเลือก
        $this->assertSame('0', $m['is_checked']);
        $this->assertSame('1', CombineTrack::merge([$a, $b], '0')['is_checked']);
        $this->assertSame('', $m['id']);
        $this->assertSame('', $m['create_by']);
    }
}
