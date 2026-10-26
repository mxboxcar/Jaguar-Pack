<?php

namespace Tests\Feature\Lot;

use App\Domain\Lot\LotMailer;
use App\Http\Controllers\Lot\Admin\AdminLotController;
use App\Http\Controllers\Lot\Admin\AdminLotSaveController;
use App\Models\User;
use App\Support\Legacy\Format;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * PACK: งานของกลุ่ม lot (JL_BENCH_DB_TESTS=1 — แถว zz_ ใน transaction ที่ rollback ทั้งหมด)
 * รูปพัสดุใน save (ตอบ SuccessBox + LoadDetail แบบ jl), save_quickfile, load_lot_detail ทั้งตารางแบบ jl (ช่องแก้เฉพาะ user 5,
 * ซ่อนราคาเมื่อมี admin_lot_view), load_unpaid, คอลัมน์ค้างจ่ายใน grid, load_tracking ของผู้ถือ admin_lot_view ไม่เขียน product_type_id,
 * อีเมลล็อตแบบ PACK
 */
class LotPackDbTest extends BenchDbTestCase
{
    private const ADMIN = 11938;

    /** PACK: ผู้ใช้ที่หน้าล็อตให้แก้ได้ (checkUser()['id'] == 5 เดิม) — role 6 ถือทั้ง admin_lot และ admin_lot_view */
    private const EDITOR = 5;

    private string $tmp = '';

    protected function tearDown(): void
    {
        if ($this->tmp !== '') {
            File::deleteDirectory($this->tmp);
        }
        parent::tearDown();
    }

    private function as(int $id): User
    {
        $u = User::query()->findOrFail($id);
        $this->actAs($u);

        return $u;
    }

    private function lot(int $type = 0): int
    {
        return $this->insertRow('lot', ['name' => 'zz_lot_'.bin2hex(random_bytes(3)), 'type' => $type, 'status' => 0]);
    }

    private function track(int $lot, int $user, array $extra = []): int
    {
        return $this->insertRow('tracking', $extra + ['tracking' => 'ZZLOT'.bin2hex(random_bytes(5)), 'user' => $user, 'lot_id' => $lot,
            'weight' => 1, 'width' => 10, 'height' => 10, 'long' => 10, 'amount' => 1, 'header' => 0, 'payment_id' => 0, 'no' => 1,
            'product_type_id' => 1, 'date_warehouse' => '2026-01-01 00:00:00']);
    }

    /** @return list<string> */
    private function images(int $trackingId): array
    {
        return DB::table('tracking_image')->where('tracking_id', $trackingId)->where('is_delete', 0)->orderBy('id')->pluck('attachment')->all();
    }

    private function tempDir(): string
    {
        $this->tmp = sys_get_temp_dir().'/pack-lot-test-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->tmp);
        config(['legacy.upload.temp_dir' => $this->tmp]);

        return $this->tmp;
    }

    private function savePost(int $lot, User $cust, array $extra): array
    {
        return $extra + ['id' => (string) $lot, 'no' => '7', 'tracking_id' => '', 'tracking' => 'ZZLOTSAVE'.bin2hex(random_bytes(4)),
            'choose_price' => '1', 'code' => $cust->code, 'product_type_id' => '1', 'remark' => "zz_r'1",
            'order' => 'zz_o', 'is_craft' => '0', 'is_qc' => '0', 'weight' => '2', 'width' => '10', 'height' => '10', 'long' => '10',
            'qc' => '0', 'repack' => '0', 'express' => '0', 'else' => '0', 'price_craft' => '0', 'amount' => '1'];
    }

    /** role ชั่วคราว (rollback) ที่ถือ admin_lot_view อย่างเดียว — PACK ไม่มี role แบบนี้ใน DB (role 6/9 ถือทั้งสองสิทธิ์) */
    private function viewOnlyUser(): User
    {
        $role = $this->insertRow('role', ['role_name' => 'zz_lot_view']);
        $detail = (int) DB::table('role_detail')->where('role_detail_name', 'admin_lot_view')->value('id');
        $this->insertRow('role_on_detail', ['role_id' => $role, 'role_detail_id' => $detail, 'permission' => 1]);

        return $this->zzUser($role);
    }

    public function test_save_insert_attaches_photos_to_the_new_tracking(): void
    {
        $this->as(self::ADMIN);
        file_put_contents($this->tempDir().'/ZZLOTIMG1.jpg', 'x');
        $cust = $this->zzUser(8);
        $lot = $this->lot();
        $orphans = DB::table('tracking_image')->where('tracking_id', 0)->where('is_delete', 0)->count();
        $post = $this->savePost($lot, $cust, ['upload_upload_track_mul' => ['ZZLOTIMG1.jpg', 'ZZLOTIMG2.jpg', '../../system_db.php', '']]);

        $out = $this->act(AdminLotSaveController::class, 'save', $post);

        $this->assertStringStartsWith('$.ajax(', $out, 'JS ย้ายรูปมาก่อน getSuccess แบบเดิม');
        $this->assertStringEndsWith("SuccessBox('ทำรายการสำเร็จ!<br />หมายเลขรายการ : ".$post['tracking']."','ทำรายการสำเร็จ!', 'CloseLoading();LoadDetail();');", $out, 'PACK = jl: SuccessBox + LoadDetail()');
        $this->assertStringContainsString('path=../upload/temp/ZZLOTIMG1.jpg', $out, 'ไฟล์ที่อยู่ใน temp ได้ JS ย้ายขึ้น storage');
        $this->assertStringNotContainsString('ZZLOTIMG2', $out, 'ไฟล์ที่ไม่อยู่ใน temp ไม่ต้องย้าย (แต่ยังบันทึก)');
        $row = DB::table('tracking')->where('tracking', $post['tracking'])->first();
        $this->assertObjectNotHasProperty('subuser', $row, 'PACK ไม่มีคอลัมน์ subuser');
        $this->assertSame("zz_r'1", $row->remark);
        $this->assertSame((string) $lot, (string) $row->lot_id);
        $this->assertSame('0', (string) $row->ship, 'ship = type ของล็อต');
        $this->assertSame(['ZZLOTIMG1.jpg', 'ZZLOTIMG2.jpg'], $this->images((int) $row->id), 'รูปผูกกับ tracking ใหม่ ชื่อไฟล์ที่มี / ถูกตัดทิ้ง');
        $this->assertSame($orphans, DB::table('tracking_image')->where('tracking_id', 0)->where('is_delete', 0)->count(), 'ไม่แตะรูปกำพร้า (tracking_id 0)');
    }

    public function test_save_update_replaces_photos_only_when_the_list_is_posted(): void
    {
        $this->as(self::ADMIN);
        $this->tempDir();
        $cust = $this->zzUser(8);
        $lot = $this->lot();
        $t = $this->track(0, $cust->id, ['date_warehouse' => '0000-00-00 00:00:00']);
        $this->insertRow('tracking_image', ['tracking_id' => $t, 'attachment' => 'ZZOLD1.jpg']);
        $this->insertRow('tracking_image', ['tracking_id' => $t, 'attachment' => 'ZZOLD2.jpg']);

        $out = $this->act(AdminLotSaveController::class, 'save', $this->savePost($lot, $cust, ['tracking_id' => (string) $t]));
        $this->assertStringStartsWith('SuccessBox(', $out, 'ไม่มีรูปใน temp = ไม่มี JS ย้ายรูป');
        $this->assertSame(['ZZOLD1.jpg', 'ZZOLD2.jpg'], $this->images($t), 'ไม่ส่งรายการรูป = ไม่แตะรูปเดิม');
        $row = DB::table('tracking')->where('id', $t)->first();
        $this->assertSame((string) $lot, (string) $row->lot_id);
        $this->assertNotSame('0000-00-00 00:00:00', (string) $row->date_warehouse, 'date_warehouse = NOW() เมื่อเดิมเป็นศูนย์');

        $this->act(AdminLotSaveController::class, 'save', $this->savePost($lot, $cust, ['tracking_id' => (string) $t,
            'upload_upload_track_mul' => ['ZZOLD2.jpg', 'ZZNEW.jpg']]));
        $this->assertSame(['ZZOLD2.jpg', 'ZZNEW.jpg'], $this->images($t), 'รายการที่ส่งมาแทนของเดิมทั้งหมด');
        $this->assertSame(2, DB::table('tracking_image')->where('tracking_id', $t)->where('is_delete', 1)->count());
    }

    public function test_save_quickfile_returns_js_and_thumbnails_and_ignores_bad_ids(): void
    {
        $this->as(self::ADMIN);
        file_put_contents($this->tempDir().'/ZZQUICK1.jpg', 'x');
        $cust = $this->zzUser(8);
        $t = $this->track($this->lot(), $cust->id);
        $this->insertRow('tracking_image', ['tracking_id' => $t, 'attachment' => 'ZZQOLD.jpg']);

        $res = json_decode($this->act(AdminLotSaveController::class, 'saveQuickfile', ['id' => (string) $t, 'image' => ',ZZQOLD.jpg,ZZQUICK1.jpg,../x']), true);
        $this->assertStringContainsString('_api/blockblob_ajax', (string) $res[0]);
        $this->assertStringContainsString('ZZQUICK1.jpg', (string) $res[1]);
        $this->assertStringNotContainsString('target=', (string) $res[1]);
        $this->assertSame(['ZZQOLD.jpg', 'ZZQUICK1.jpg'], $this->images($t));

        // ล้างรูปทั้งหมด (ส่งรายการว่าง) → [null, null] แบบเดิม
        $this->assertSame('[null,null]', $this->act(AdminLotSaveController::class, 'saveQuickfile', ['id' => (string) $t, 'image' => '']));
        $this->assertSame([], $this->images($t));

        $before = DB::table('tracking_image')->count();
        $this->assertSame('[null,null]', $this->act(AdminLotSaveController::class, 'saveQuickfile', ['id' => '0', 'image' => ',ZZQUICK1.jpg']));
        $this->assertSame('[null,null]', $this->act(AdminLotSaveController::class, 'saveQuickfile', ['id' => "1' OR '1'='1", 'image' => ',ZZQUICK1.jpg']));
        $this->assertSame($before, DB::table('tracking_image')->count(), 'id ที่ไม่ใช่ tracking จริง ไม่เขียนอะไร');
    }

    public function test_lot_detail_is_one_jl_table_with_editor_and_price_columns_by_user(): void
    {
        $cust = $this->zzUser(8);
        $lot = $this->lot();
        for ($i = 1; $i <= 12; $i++) {
            $this->track($lot, $cust->id, ['no' => $i]);
        }
        $t1 = (int) DB::table('tracking')->where('lot_id', $lot)->where('no', 12)->value('id');
        $this->insertRow('tracking_image', ['tracking_id' => $t1, 'attachment' => 'ZZDETAIL.jpg']);

        // แอดมิน 11938 (role 1: admin_lot ไม่มี admin_lot_view, ไม่ใช่ user 5)
        $this->as(self::ADMIN);
        $html = $this->act(AdminLotController::class, 'loadLotDetail', ['id' => (string) $lot, 'search' => 'undefined']);
        $this->assertStringContainsString('id="searching_lot_detail"', $html, 'แบบ jl: ช่องค้นหามากับตาราง');
        $this->assertSame(12, substr_count($html, 'load_quickfile('), 'ทุกแถวในคำขอเดียว (ไม่แบ่งหน้า)');
        $this->assertSame(0, substr_count($html, 'class="cbxitem"'), 'ไม่ใช่ user 5 = ไม่มีช่องเลือก/แก้/ลบ');
        $this->assertStringNotContainsString('cbxitem_head', $html);
        $this->assertStringContainsString('class="sum_pcubic', $html, 'ไม่มี admin_lot_view = เห็นคอลัมน์ราคา');
        $this->assertStringContainsString('class="hid_sum_amount" value="12"', $html);
        $this->assertStringContainsString('class="hid_sum_weight" value="12.0"', $html);
        $this->assertStringContainsString('class="hid_sum_cubic" value="0.0120"', $html);
        $this->assertStringContainsString('ZZDETAIL.jpg', $html, 'รูปพัสดุมาพร้อมแถว');
        $this->assertStringNotContainsString('LoadDetail(1)', $html);
        $this->assertStringNotContainsString('loadendtable', $html);
        $this->assertStringNotContainsString('#768cf2', $html, 'PACK ไม่มีสีถ่ายรูป');

        // user 5 (role 6: admin_lot + admin_lot_view) = ช่องเลือก/แก้/ลบ แต่ไม่เห็นคอลัมน์ราคา
        $this->as(self::EDITOR);
        $html = $this->act(AdminLotController::class, 'loadLotDetail', ['id' => (string) $lot, 'search' => '']);
        $this->assertSame(12, substr_count($html, 'class="cbxitem"'));
        $this->assertStringContainsString('cbxitem_head', $html);
        $this->assertStringNotContainsString('class="sum_pcubic', $html);
        $this->assertStringNotContainsString('class="sum_kg', $html);

        // ค้นหาด้วยรหัสลูกค้า / เลขพัสดุ
        $no = (string) DB::table('tracking')->where('id', $t1)->value('tracking');
        $html = $this->act(AdminLotController::class, 'loadLotDetail', ['id' => (string) $lot, 'search' => $no]);
        $this->assertSame(1, substr_count($html, 'load_quickfile('));
        $this->assertStringContainsString('value="'.$no.'" id="searching_lot_detail"', $html);
    }

    public function test_form_and_index_show_editing_only_to_user_5(): void
    {
        $lot = $this->lot();
        $this->as(self::ADMIN);
        $form = view('lot.admin.form', $this->formData($lot))->render();
        $this->assertStringNotContainsString('_admin/_lot/save" method="post"', $form);
        $this->assertStringNotContainsString('onblur="onsavelotname()"', $form);
        $this->assertStringNotContainsString('onclick="onstatussave()"', $form);
        $this->assertStringContainsString('<div class="col-xs-6 form-control-static">', $form, 'หัวล็อตแบบอ่านอย่างเดียว');
        $this->assertStringContainsString('id="lot_unpayment"', $form);
        $this->assertStringContainsString("url: rooturl + '_admin/_lot/load_unpaid'", $form);
        $index = (string) $this->app->call([app(AdminLotController::class), 'index'])->render();
        $this->assertStringNotContainsString("$('#lot_create .modal').modal('show')", $index);

        $this->as(self::EDITOR);
        $form = view('lot.admin.form', $this->formData($lot))->render();
        $this->assertStringContainsString('_admin/_lot/save" method="post"', $form);
        $this->assertStringContainsString('onblur="onsavelotname()"', $form);
        $this->assertStringContainsString('onclick="onstatussave()"', $form);
        $this->assertStringContainsString('id="attachment_tracking"', $form);
        $index = (string) $this->app->call([app(AdminLotController::class), 'index'])->render();
        $this->assertStringContainsString("$('#lot_create .modal').modal('show')", $index);
    }

    private function formData(int $lot): array
    {
        $req = \Illuminate\Http\Request::create('/admin/lot/'.$lot, 'GET');
        $req->setRouteResolver(fn () => (new \Illuminate\Routing\Route('GET', 'admin/lot/{id}', []))->bind($req));
        app()->instance('request', $req);

        return app(AdminLotController::class)->form($req)->getData();
    }

    public function test_unpaid_list_and_grid_column_count_customers_with_unpaid_tracking(): void
    {
        $this->as(self::ADMIN);
        [$a, $b, $c] = [$this->zzUser(8), $this->zzUser(8), $this->zzUser(8)];
        $lot = $this->lot();
        $this->track($lot, $a->id, ['date_warehouse' => '2026-03-05 00:00:00', 'date_thai' => '2026-03-09 00:00:00']);
        $this->track($lot, $a->id, ['date_warehouse' => '2026-03-02 00:00:00', 'date_thai' => '2026-03-08 00:00:00']);
        $this->track($lot, $b->id, ['date_warehouse' => '2026-03-01 00:00:00', 'is_delete' => 1]);
        $this->track($lot, $c->id, ['payment_id' => 99, 'date_warehouse' => '2026-03-07 00:00:00']);

        $html = $this->act(AdminLotController::class, 'loadUnpaid', ['id' => (string) $lot]);
        $this->assertStringContainsString('รายการลูกค้าค้างจ่าย', $html);
        $this->assertSame(1, substr_count($html, '<tr>'), 'ลูกค้า a เท่านั้น (b ถูกลบ, c จ่ายแล้ว)');
        $this->assertStringContainsString('>'.$a->code.'</td><td class="text-center">2</td>', $html);

        $grid = $this->act(AdminLotController::class, 'grid', ['page' => '1', 'txtsearch' => (string) $lot.'|']);
        $this->assertStringContainsString('ค้างจ่าย', $grid);
        $this->assertStringNotContainsString('print_all', $grid);
        // วันที่ = tracking ที่ยังไม่ลบ วันเข้าโกดังน้อยสุด (2026-03-02 → ถึงไทย 2026-03-08)
        $this->assertStringContainsString(Format::getthaidate('2026-03-08', true, true), $grid);
    }

    public function test_load_tracking_of_view_only_user_does_not_resync_product_type(): void
    {
        $cust = $this->zzUser(8);
        $lot = $this->lot(1); // ล็อตเรือ: product_type รถ (A type 0) ถูกแก้เป็นของเรือ (A type 1) โดย GetCalPrice ของ SR
        $carA = (int) DB::table('product_type')->where('type', 0)->orderBy('id')->value('id');
        $shipA = (int) DB::table('product_type')->where('type', 1)->where('name', DB::table('product_type')->where('id', $carA)->value('name'))->value('id');
        $t = $this->track($lot, $cust->id, ['product_type_id' => $carA, 'tracking' => 'ZZLOTVIEW'.bin2hex(random_bytes(3))]);
        $no = (string) DB::table('tracking')->where('id', $t)->value('tracking');

        $this->as($this->viewOnlyUser()->id); // role ชั่วคราว = admin_lot_view อย่างเดียว
        $view = json_decode($this->act(AdminLotController::class, 'loadTracking', ['tracking' => $no, 'lot_id' => (string) $lot]), true);
        $this->assertSame($carA, (int) DB::table('tracking')->where('id', $t)->value('product_type_id'), 'admin_lot_view ไม่เขียน');
        $this->assertCount(8, $view);
        $this->assertSame('', $view[5], 'PACK = SR: data_craft = "" เมื่อไม่ใช่ craft');
        $this->assertStringContainsString('upload_upload_track', $view[7]);

        $this->as(self::ADMIN);
        $admin = json_decode($this->act(AdminLotController::class, 'loadTracking', ['tracking' => $no, 'lot_id' => (string) $lot]), true);
        $this->assertSame($shipA, (int) DB::table('tracking')->where('id', $t)->value('product_type_id'), 'admin_lot: แก้ประเภทแบบเดิม (GetCalPrice ของ PACK = SR)');
        $this->assertSame($admin[2], $view[2], 'ราคาที่คำนวณเท่ากัน');
    }

    public function test_lot_mail_uses_pack_sender_template_and_dash_columns(): void
    {
        $cust = $this->zzUser(8);
        DB::table('user')->where('id', $cust->id)->update(['email' => 'zz_lot@example.invalid']);
        $lot = $this->lot();
        $this->track($lot, $cust->id, ['quote' => 'zz_quote<b>']);
        config(['mail.default' => 'array', 'legacy.external.enabled' => true]);
        Mail::swap($manager = new MailManager(app()));

        $m = new \ReflectionMethod(LotMailer::class, 'sendMail');
        $items = (new \ReflectionMethod(LotMailer::class, 'items'))->invoke(null, $lot, $cust->id);
        $m->invoke(null, $lot, $cust->id, 'zz_lot@example.invalid', $items);

        $sent = $manager->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);
        $msg = $sent[0]->getOriginalMessage();
        $this->assertSame(config('legacy.mail.from_address'), $msg->getFrom()[0]->getAddress());
        $html = (string) $msg->getHtmlBody();
        $this->assertStringStartsWith((string) config('legacy.mail.header'), $html);
        $this->assertStringEndsWith((string) config('legacy.mail.footer'), $html);
        $this->assertStringNotContainsString('zz_quote', $html, 'PACK: ช่องวลีช่วยจำ = "-" (order_id ไม่มี) แบบ jl');
        $this->assertSame(2, substr_count($html, '<td style="border:1px #ccc solid;padding:3px">-</td>'));
        $this->assertStringContainsString('#eb4823', $html);
        $this->assertStringNotContainsString('#a3b2f1', $html);
        $this->assertStringNotContainsString('JLcargo', $html);
        $this->assertStringNotContainsStringIgnoringCase('srshipping', $html);
    }
}
