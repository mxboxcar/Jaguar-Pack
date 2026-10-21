<?php

namespace Tests\Feature\Points;

use App\Domain\Points\Cashback;
use App\Domain\Points\Rules;
use App\Domain\Points\TempUpload;
use App\Domain\Points\UploadInline;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม points: เงื่อนไขที่พึ่ง PHP 7.4 (fixture สร้างด้วย php7.4 จากนิพจน์เดิม), JS/HTML ที่ต้องตรงกับเดิม, และ route ครบตาม url-map — ไม่แตะ DB
 */
class PointsLogicTest extends TestCase
{
    private function cases(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../fixtures/points-php74-cases.json'), true);
    }

    public function test_withdraw_check_matches_php74(): void
    {
        foreach ($this->cases()['withdraw'] as [$want, $current, $invalid]) {
            $this->assertSame($invalid, Rules::withdrawInvalid($want, $current), json_encode([$want, $current]));
        }
    }

    public function test_cashback_credit_check_matches_php74(): void
    {
        foreach ($this->cases()['credit'] as [$price, $credit]) {
            $this->assertSame($credit, Rules::cashbackCreditable($price), json_encode($price));
        }
    }

    public function test_sum_starts_from_null_like_legacy(): void
    {
        $this->assertNull(Cashback::sum([]));
        $rows = [(object) ['price' => '10.50', 'status' => 1], (object) ['price' => '5.00', 'status' => -1]];
        $this->assertSame(15.5, Cashback::sum($rows));
        $this->assertSame(10.5, Cashback::sum($rows, fn ($r) => $r->status !== -1));
    }

    public function test_blob_js_matches_legacy_bytes(): void
    {
        $this->assertSame(
            "$.ajax({ \r\n\t                type: 'POST', \r\n\t                async: true, \r\n\t                data : 'path=../upload/temp/a.jpg', \r\n"
            ."\t                url: rooturl + '_api/blockblob_ajax', \r\n\t                success: function(data) { \r\n\t                  \r\n\t                } });",
            TempUpload::blobJs('a.jpg')
        );
        $this->assertFalse(TempUpload::safeName('../x'));
        $this->assertFalse(TempUpload::safeName(''));
        $this->assertFalse(TempUpload::safeName("a'b.jpg"));
        $this->assertTrue(TempUpload::safeName('20260101_abc-1.jpg'));
    }

    public function test_upload_inline_escapes_existing_filename(): void
    {
        $html = UploadInline::render('file_0', '"><script>x</script>.jpg', false);
        $this->assertStringNotContainsString('<script>x', $html);
        $this->assertStringContainsString('name="upload_file_0"', $html);
    }

    /** ทุก RewriteRule / ajax ของกลุ่มใน docs/url-map.md มี route (method เดียวกับ JS เดิม) */
    public function test_every_legacy_url_has_a_route(): void
    {
        $expect = [
            'GET point', 'GET shop_cashback', 'GET shop_cashback/create', 'GET shop_cashback/{id}',
            'GET admin/shop_cashback', 'GET admin/shop_cashback/create', 'GET admin/shop_cashback/{id}', 'GET admin/shop_cashback/status/{status}',
            'GET admin/point', 'GET admin/withdrawpoint', 'GET admin/withdrawpoint/{id}', 'GET admin/withdrawpoint/status/{status}',
            'POST _point/grid', 'POST _point/load_withdraw', 'POST _point/save_withdraw',
            'POST _admin/_point/grid',
            'POST _admin/_withdrawpoint/grid', 'POST _admin/_withdrawpoint/cancel', 'POST _admin/_withdrawpoint/save_approved',
            'POST _shop_cashback/grid', 'POST _shop_cashback/load_calculator', 'POST _shop_cashback/load_new_line',
            'POST _shop_cashback/save', 'POST _shop_cashback/save_remark',
            'POST _admin/_shop_cashback/grid', 'POST _admin/_shop_cashback/save', 'POST _admin/_shop_cashback/save_approved',
            'POST _admin/_shop_cashback/save_cancel', 'POST _admin/_shop_cashback/save_remark',
            'POST _admin/_shop_cashback/email_approved', 'POST _admin/_shop_cashback/email_cancel',
        ];
        $have = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $have[$m.' '.$r->uri()] = $r->gatherMiddleware();
            }
        }
        foreach ($expect as $k) {
            $this->assertArrayHasKey($k, $have, $k);
        }
    }

    /** หน้า/ajax แอดมินต้องมีสิทธิ์โมดูลเดิม (grid ตรวจใน controller เพราะเดิม exit เงียบ) */
    public function test_admin_routes_require_legacy_permission(): void
    {
        $perm = [
            'admin/point' => 'legacy.perm:admin_point',
            'admin/withdrawpoint/{id}' => 'legacy.perm:admin_withdrawpoint',
            '_admin/_withdrawpoint/cancel' => 'legacy.perm:admin_withdrawpoint',
            '_admin/_withdrawpoint/save_approved' => 'legacy.perm:admin_withdrawpoint',
            'admin/shop_cashback/{id}' => 'legacy.perm:admin_shop_cashback',
            '_admin/_shop_cashback/save_approved' => 'legacy.perm:admin_shop_cashback',
            '_admin/_shop_cashback/save_cancel' => 'legacy.perm:admin_shop_cashback',
            '_admin/_shop_cashback/email_approved' => 'legacy.perm:admin_shop_cashback',
            'point' => 'legacy.perm:point',
            'shop_cashback/{id}' => 'legacy.perm:shop_cashback',
            '_shop_cashback/save' => 'legacy.perm:shop_cashback',
        ];
        foreach (Route::getRoutes() as $r) {
            if (isset($perm[$r->uri()])) {
                $this->assertContains($perm[$r->uri()], $r->gatherMiddleware(), $r->uri());
            }
        }
    }
}
