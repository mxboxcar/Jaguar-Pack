<?php

namespace Tests\Feature\Warehouse;

use App\Domain\Warehouse\WarehouseLookups;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม warehouse: ทุก URL เดิมมี route + สิทธิ์ตรงกับ getPermission() ของไฟล์เดิม และ helper ที่เลียนแบบเว็บเดิม (ไม่แตะ DB)
 */
class WarehouseRoutesTest extends TestCase
{
    /** method uri => middleware สิทธิ์ที่ต้องมี */
    private const EXPECTED = [
        // ลูกค้า
        'GET goodsconfirm' => 'legacy.perm:goodsconfirm',
        'GET goodsconfirm/{id}' => 'legacy.perm:goodsconfirm',
        'POST _goodsconfirm/grid' => 'legacy.auth',
        'POST _goodsconfirm/load_tracking_by_id' => 'legacy.auth',
        'POST _goodsconfirm/save_approved' => 'legacy.perm:goodsconfirm',
        // แอดมิน goodsconfirm
        'GET admin/goodsconfirm' => 'legacy.perm:admin_goodsconfirm',
        'GET admin/goodsconfirm/create' => 'legacy.perm:admin_goodsconfirm',
        'GET admin/goodsconfirm/status/{status}' => 'legacy.perm:admin_goodsconfirm',
        'GET admin/goodsconfirm/{id}' => 'legacy.perm:admin_goodsconfirm',
        'POST _admin/_goodsconfirm/grid' => 'legacy.auth',
        'POST _admin/_goodsconfirm/load_tracking_by_id' => 'legacy.auth',
        'POST _admin/_goodsconfirm/cancel' => 'legacy.perm:admin_goodsconfirm',
        'POST _admin/_goodsconfirm/load_tracking' => 'legacy.perm:admin_goodsconfirm',
        'POST _admin/_goodsconfirm/load_upload' => 'legacy.perm:admin_goodsconfirm',
        'POST _admin/_goodsconfirm/save' => 'legacy.perm:admin_goodsconfirm',
        'POST _admin/_goodsconfirm/save_approved' => 'legacy.perm:admin_goodsconfirm',
        'POST _admin/_goodsconfirm/save_image' => 'legacy.perm:admin_goodsconfirm',
        'POST _admin/_goodsconfirm/save_unapproved' => 'legacy.perm:admin_goodsconfirm',
    ];

    /** PACK: หน้า/ajax รับสินค้าเข้าโกดังและชั้นวาง = dead (content.php ไม่มี page, ตาราง warehouse* ไม่มี, admin_warehouse ไม่มีใครถือ) */
    private const DEAD = [
        'GET admin/warehouse', 'GET admin/warehouse/create', 'GET admin/warehouse/{id}', 'GET admin/warehouse_section',
        'GET admin/warehouse_section/create', 'GET admin/warehouse_section/{id}', 'GET admin/warehouse_section_move',
        'POST _admin/_warehouse/grid', 'POST _admin/_warehouse/load_editshelf', 'POST _admin/_warehouse/load_lot_detail',
        'POST _admin/_warehouse/load_lot_detail_thai', 'POST _admin/_warehouse/load_tracking', 'POST _admin/_warehouse/load_tracking_edit',
        'POST _admin/_warehouse/save', 'POST _admin/_warehouse/save_cancel_warehouse_calculate', 'POST _admin/_warehouse/save_editshelf',
        'GET _admin/_warehouse/save_lot_detail', 'POST _admin/_warehouse/save_new_warehouse', 'POST _admin/_warehouse/save_push_track',
        'POST _admin/_warehouse/save_track_weight', 'POST _admin/_warehouse_section/grid', 'POST _admin/_warehouse_section/save',
        'POST _admin/_warehouse_section/delete', 'POST _admin/_warehouse_section_move/grid',
    ];

    public function test_pack_dead_warehouse_urls_have_no_route(): void
    {
        $found = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $found[$m.' '.$r->uri()] = true;
            }
        }
        foreach (self::DEAD as $key) {
            $this->assertArrayNotHasKey($key, $found, "$key = dead ใน PACK");
        }
        $this->assertFalse(class_exists(\App\Models\Warehouse::class));
    }

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
            $gathered = $found[$key]->gatherMiddleware();
            $this->assertContains($mw, $gathered, "$key ต้องมี $mw");
            if (str_starts_with($key, 'POST ') || str_contains($key, '/_')) {
                // เดิมผ่าน system_check_ajax.php ก่อนตรวจสิทธิ์ (GET save_lot_detail: X-Requested-With = กัน CSRF)
                $this->assertContains('legacy.ajax', $gathered, "$key เดิมผ่าน system_check_ajax");
                $this->assertLessThan(array_search($mw, $gathered, true), array_search('legacy.ajax', $gathered, true), "$key: legacy.ajax ต้องมาก่อน");
            }
        }
    }

    public function test_page_routes_set_legacy_page(): void
    {
        $pages = [
            'goodsconfirm' => 'goodsconfirm', 'goodsconfirm/{id}' => 'goodsconfirm_detail',
            'admin/goodsconfirm' => 'admin_goodsconfirm', 'admin/goodsconfirm/create' => 'admin_goodsconfirm_detail',
            'admin/goodsconfirm/status/{status}' => 'admin_goodsconfirm', 'admin/goodsconfirm/{id}' => 'admin_goodsconfirm_detail',
        ];
        $seen = 0;
        foreach (Route::getRoutes() as $r) {
            if (isset($pages[$r->uri()]) && in_array('GET', $r->methods(), true)) {
                $this->assertSame($pages[$r->uri()], $r->defaults['legacy_page'] ?? null, $r->uri());
                $seen++;
            }
        }
        $this->assertSame(count($pages), $seen);
    }

    public function test_paging_offset_and_upload_names(): void
    {
        $this->assertSame(0, WarehouseLookups::offset('1'));
        $this->assertSame(20, WarehouseLookups::offset('3'));
        $this->assertNull(WarehouseLookups::offset(''));
        $this->assertNull(WarehouseLookups::offset('0'));
        $this->assertTrue(WarehouseLookups::safeName('ABCDEF.jpg'));
        $this->assertFalse(WarehouseLookups::safeName('../../.env'));
        $this->assertFalse(WarehouseLookups::safeName(''));
        $this->assertFalse(WarehouseLookups::tempFileExists('../system_config.php'));
    }

    public function test_blob_js_matches_legacy_bytes(): void
    {
        $js = WarehouseLookups::blobJs("a'b.jpg", "\t", "\t\t");
        $this->assertSame("$.ajax({ \r\n\ttype: 'POST', \r\n\tasync: true, \r\n\tdata : 'path=../upload/temp/a\\'b.jpg', \r\n"
            ."\turl: rooturl + '_api/blockblob_ajax', \r\n\tsuccess: function(data) { \r\n\t\t\r\n\t} });", $js);
    }
}
