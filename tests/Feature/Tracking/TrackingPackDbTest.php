<?php

namespace Tests\Feature\Tracking;

use App\Http\Controllers\Tracking\Admin\AdminLostPackageController;
use App\Http\Controllers\Tracking\Admin\AdminTrackingController;
use App\Http\Controllers\Tracking\Admin\CombineTrackController;
use App\Http\Controllers\Tracking\TrackingController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * PACK: งานเขียนของกลุ่ม tracking ที่ PACK ต่างจาก SR (JL_BENCH_DB_TESTS=1, rollback ทั้งหมด)
 * - _tracking/save ตรวจประเภทผู้ใช้ (2 / ลูกของ 2) แทนสิทธิ์ tracking, ลูกค้าเปลี่ยนเลขพัสดุได้, ไม่มี is_takeapic / tracking_check
 * - _admin/_tracking/save ไม่มี subuser / is_takeapic, รูป tracking_image เหมือน SR
 * - combine: 40 คอลัมน์ + ย้ายรูป
 */
class TrackingPackDbTest extends BenchDbTestCase
{
    private const CUST = 11937;   // zz_fdn_cust: type 3 (นำเข้าและสั่งซื้อ) = เพิ่ม tracking เองไม่ได้

    private const ADMIN = 11938;  // zz_fdn_admin: type 2

    private function as(int $id): User
    {
        $u = User::query()->findOrFail($id);
        $this->actAs($u);

        return $u;
    }

    private function track(array $extra = []): int
    {
        return $this->insertRow('tracking', $extra + ['tracking' => 'ZZ-PK-'.bin2hex(random_bytes(4)), 'user' => self::CUST, 'lot_id' => 0,
            'weight' => 1, 'width' => 1, 'height' => 1, 'long' => 1, 'amount' => 1, 'header' => 0, 'payment_id' => 0]);
    }

    /** ผู้ใช้ทดสอบประเภท 2 (หรือประเภทที่ child = 2) — สร้างใน transaction */
    private function importer(bool $viaChild = false): User
    {
        $u = $this->zzUser(2);
        $type = 2;
        if ($viaChild) {
            $type = $this->insertRow('user_type', ['name' => 'zz_child', 'is_default' => 0, 'is_show' => 0, 'child' => 2]);
        }
        DB::table('user')->where('id', $u->id)->update(['type' => $type]);
        $u = User::query()->findOrFail($u->id);
        $this->actAs($u);

        return $u;
    }

    private function save(array $post): string
    {
        return $this->act(TrackingController::class, 'save', $post + ['order' => 'zz_o', 'quote' => 'zz_q', 'ship' => '0', 'is_craft' => '0', 'is_qc' => '1']);
    }

    public function test_customer_save_needs_user_type_2_or_child_of_2(): void
    {
        $this->as(self::CUST);
        $no = 'ZZPK'.bin2hex(random_bytes(5));
        $out = $this->save(['id' => '', 'tracking' => $no]);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertFalse(DB::table('tracking')->where('tracking', $no)->exists());
        $this->assertFalse(TrackingController::canAdd());

        foreach ([false, true] as $viaChild) {
            $u = $this->importer($viaChild);
            $this->assertTrue(TrackingController::canAdd());
            $no = 'ZZPK'.bin2hex(random_bytes(5));
            $out = $this->save(['id' => '', 'tracking' => ' '.$no.' ', 'is_takeapic' => '1']);
            $this->assertStringContainsString('SuccessBox(', $out);
            $this->assertStringContainsString($no, $out);
            $row = DB::table('tracking')->where('tracking', $no)->first();
            $this->assertSame($u->id, (int) $row->user);
            $this->assertSame('1', (string) $row->is_qc);
            $this->assertObjectNotHasProperty('is_takeapic', $row);
        }
        $this->assertStringContainsString('เลขรหัสพัสดุซ้ำ', $this->save(['id' => '', 'tracking' => $no]));
    }

    public function test_customer_update_changes_tracking_number_and_shows_empty_number(): void
    {
        $u = $this->importer();
        $t = $this->track(['tracking' => 'ZZ-PK-OLD', 'user' => $u->id]);

        $out = $this->save(['id' => (string) $t, 'tracking' => 'ZZ-PK-NEW', 'order' => 'zz_o2', 'ship' => '1', 'is_craft' => '1', 'is_qc' => '0']);

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertStringNotContainsString('ZZ-PK', $out, 'เดิม $data เป็นแถว bill_product_on_tracking → เลขในข้อความว่าง');
        $row = DB::table('tracking')->where('id', $t)->first();
        $this->assertSame('ZZ-PK-NEW', $row->tracking, 'PACK: ลูกค้าแก้เลขพัสดุได้ (เหมือน jl)');
        $this->assertSame(['zz_o2', '1', '1', '0'], [$row->order, (string) $row->ship, (string) $row->is_craft, (string) $row->is_qc]);

        // ในล็อตแล้ว: แก้ได้แค่ quote และข้อความแสดงเลขเดิม
        DB::table('tracking')->where('id', $t)->update(['lot_id' => 1]);
        $out = $this->save(['id' => (string) $t, 'tracking' => 'ZZ-PK-X', 'quote' => 'zz_q3']);
        $this->assertStringContainsString('ZZ-PK-NEW', $out);
        $this->assertSame(['ZZ-PK-NEW', 'zz_q3'], [DB::table('tracking')->where('id', $t)->value('tracking'), DB::table('tracking')->where('id', $t)->value('quote')]);
    }

    public function test_customer_cannot_change_someone_elses_tracking(): void
    {
        $this->importer();
        $t = $this->track(['user' => self::ADMIN, 'quote' => 'zz_orig']);
        $this->save(['id' => (string) $t, 'tracking' => 'ZZ-PK-HIJACK', 'quote' => 'zz_q']);
        $row = DB::table('tracking')->where('id', $t)->first();
        $this->assertSame('zz_orig', $row->quote);
        $this->assertNotSame('ZZ-PK-HIJACK', $row->tracking);
    }

    public function test_admin_save_without_sr_columns_and_replaces_images(): void
    {
        $this->as(self::ADMIN);
        $code = DB::table('user')->where('id', self::CUST)->value('code');
        $t = $this->track();
        $this->insertRow('tracking_image', ['tracking_id' => $t, 'attachment' => 'zz_old1.jpg']);
        $this->insertRow('tracking_image', ['tracking_id' => $t, 'attachment' => 'zz_old2.jpg']);
        $post = ['id' => (string) $t, 'tracking' => 'ZZ-PK-ADM', 'order' => '', 'quote' => '', 'ship' => '0', 'shipping_type' => '',
            'code' => $code, 'subuser' => 'zz_sub', 'is_craft' => '0', 'is_qc' => '0', 'is_takeapic' => '1', 'remark' => '',
            'is_auto_price' => '0', 'date_warehouse' => '', 'date_outofchina' => '', 'date_arrive' => '',
            'upload_upload_track_mul' => ['zz_old2.jpg', '../../etc/passwd', 'zz_new.png', '']];
        foreach (['weight', 'width', 'height', 'long', 'amount', 'price_kg', 'price_cubic', 'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft'] as $k) {
            $post[$k] = '1';
        }

        $out = $this->act(AdminTrackingController::class, 'save', $post);

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertStringNotContainsString('passwd', $out);
        $this->assertSame('ZZ-PK-ADM', DB::table('tracking')->where('id', $t)->value('tracking'));
        $live = DB::table('tracking_image')->where('tracking_id', $t)->where('is_delete', 0)->orderBy('id')->pluck('attachment')->all();
        $this->assertSame(['zz_old2.jpg', 'zz_new.png'], $live);
        $this->assertSame(2, DB::table('tracking_image')->where('tracking_id', $t)->where('is_delete', 1)->count());

        $post['id'] = '';
        $post['tracking'] = 'ZZ-PK-ADM-NEW-'.bin2hex(random_bytes(3));
        $post['upload_upload_track_mul'] = ['zz_create.jpg'];
        $this->assertStringContainsString('SuccessBox(', $this->act(AdminTrackingController::class, 'save', $post));
        $newId = (int) DB::table('tracking')->where('tracking', $post['tracking'])->value('id');
        $this->assertSame(['zz_create.jpg'], DB::table('tracking_image')->where('tracking_id', $newId)->where('is_delete', 0)->pluck('attachment')->all());
    }

    public function test_admin_grid_counts_inline_and_searches_by_prefix(): void
    {
        $this->as(self::ADMIN);
        $tag = 'ZZPKG'.strtoupper(bin2hex(random_bytes(3)));
        foreach (range(1, 12) as $i) {
            $this->track(['tracking' => $tag.sprintf('%02d', $i)]);
        }
        $this->track(['tracking' => 'X'.$tag.'99']);

        $html = $this->act(AdminTrackingController::class, 'grid', ['page' => '1', 'txtsearch' => $tag.'||||']);
        $this->assertStringNotContainsString('load_paging', $html);
        $this->assertStringContainsString("loadAjaxGrid('_admin/_tracking/grid', '2',", $html, 'หน้า 2 ของ 12 แถว (LIKE ขึ้นต้น ไม่รวม X...)');
        $this->assertStringNotContainsString('X'.$tag, $html);
        $page2 = $this->act(AdminTrackingController::class, 'grid', ['page' => '2', 'txtsearch' => $tag.'||||']);
        $this->assertSame(2, preg_match_all('/onclick="Delete\(\d+\)/', $page2));
    }

    public function test_combine_has_pack_columns_and_moves_images(): void
    {
        $this->as(self::ADMIN);
        $a = $this->track(['remark' => '']);
        $b = $this->track(['remark' => 'zz_R']);
        $live = $this->insertRow('tracking_image', ['tracking_id' => $b, 'attachment' => 'zz_live.jpg']);
        $dead = $this->insertRow('tracking_image', ['tracking_id' => $a, 'attachment' => 'zz_dead.jpg', 'is_delete' => 1]);

        $this->assertStringContainsString('SuccessBox(', $this->act(CombineTrackController::class, 'saveCombine', ['tracking' => ','.$a.','.$b]));

        $new = DB::table('tracking')->where('id', '>', $b)->where('user', self::CUST)->orderByDesc('id')->first();
        $this->assertNotNull($new);
        $this->assertSame('zz_R', $new->remark);
        $this->assertFalse(DB::table('tracking')->whereIn('id', [$a, $b])->exists());
        $this->assertSame((int) $new->id, (int) DB::table('tracking_image')->where('id', $live)->value('tracking_id'));
        $this->assertSame($a, (int) DB::table('tracking_image')->where('id', $dead)->value('tracking_id'), 'รูปที่ลบแล้วไม่ย้าย');
    }

    public function test_lost_package_admin_remark(): void
    {
        $this->as(self::ADMIN);
        $id = $this->insertRow('lost_package', ['order_id' => 0, 'attachment' => '', 'submit_remark' => 'zz_lp', 'cashback' => 1, 'user' => self::CUST,
            'status' => 1, 'cancel_remark' => '']);
        $out = $this->act(AdminLostPackageController::class, 'saveRemark', ['id' => (string) $id, 'remark' => "zz_note <b>'x'"]);
        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertSame("zz_note <b>'x'", DB::table('lost_package')->where('id', $id)->value('admin_quote'));
    }
}
