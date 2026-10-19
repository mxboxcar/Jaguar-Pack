<?php

namespace Tests\Feature\Tracking;

use App\Http\Controllers\Tracking\Admin\AdminLostPackageController;
use App\Http\Controllers\Tracking\Admin\AdminTrackingController;
use App\Http\Controllers\Tracking\Admin\CheckController;
use App\Http\Controllers\Tracking\Admin\CombineTrackController;
use App\Http\Controllers\Tracking\TrackingController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * SR: งานเขียนของกลุ่ม tracking ที่ SR ต่างจาก jl (JL_BENCH_DB_TESTS=1, rollback ทั้งหมด)
 * tracking.is_takeapic / subuser, tracking_check (admin/check), tracking_image, lost_package.admin_quote
 */
class TrackingSrDbTest extends BenchDbTestCase
{
    private const CUST = 11937;

    private const ADMIN = 11938;

    private function as(int $id): User
    {
        $u = User::query()->findOrFail($id);
        $this->actAs($u);

        return $u;
    }

    private function track(array $extra = []): int
    {
        return $this->insertRow('tracking', $extra + ['tracking' => 'ZZ-SR-'.bin2hex(random_bytes(4)), 'user' => self::CUST, 'lot_id' => 0,
            'weight' => 1, 'width' => 1, 'height' => 1, 'long' => 1, 'amount' => 1, 'header' => 0, 'payment_id' => 0]);
    }

    private function checks(int $trackingId): array
    {
        return DB::table('tracking_check')->where('tracking_id', $trackingId)->orderBy('id')->pluck('is_checked')->map(fn ($v) => (string) $v)->all();
    }

    public function test_customer_create_saves_takeapic_and_queues_check_only_for_qc_or_craft(): void
    {
        $this->as(self::CUST);
        foreach ([['1', '0', '0', 1], ['0', '1', '0', 1], ['0', '0', '1', 0], ['0', '0', '0', 0]] as [$qc, $craft, $pic, $queued]) {
            $no = 'ZZSR'.bin2hex(random_bytes(5));
            $out = $this->act(TrackingController::class, 'save', ['id' => '', 'tracking' => $no, 'order' => 'zz_o', 'quote' => 'zz_q',
                'ship' => '0', 'is_craft' => $craft, 'is_qc' => $qc, 'is_takeapic' => $pic]);
            $this->assertStringContainsString('SuccessBox(', $out);
            $row = DB::table('tracking')->where('tracking', $no)->first();
            $this->assertSame($pic, (string) $row->is_takeapic);
            $this->assertCount($queued, $this->checks((int) $row->id), "qc=$qc craft=$craft pic=$pic");
        }
    }

    public function test_customer_update_keeps_tracking_number_and_requeues_check(): void
    {
        $this->as(self::CUST);
        $t = $this->track(['tracking' => 'ZZ-SR-KEEP']);
        $this->insertRow('tracking_check', ['tracking_id' => $t, 'is_checked' => 1]);

        $out = $this->act(TrackingController::class, 'save', ['id' => (string) $t, 'tracking' => 'ZZ-SR-CHANGED', 'order' => 'zz_o2',
            'quote' => 'zz_q2', 'ship' => '1', 'is_craft' => '0', 'is_qc' => '0', 'is_takeapic' => '1']);

        $this->assertStringContainsString('ZZ-SR-KEEP', $out, 'ข้อความแสดงเลขเดิม');
        $row = DB::table('tracking')->where('id', $t)->first();
        $this->assertSame('ZZ-SR-KEEP', $row->tracking, 'SR: ลูกค้าแก้เลขพัสดุไม่ได้');
        $this->assertSame('1', (string) $row->is_takeapic);
        $this->assertSame('zz_o2', $row->order);
        $this->assertSame(['0'], $this->checks($t), 'แถวที่เช็คแล้วถูกตั้งกลับเป็นรอเช็ค');

        // ไม่มีแถวที่เช็คแล้ว → INSERT ใหม่ (แม้มีแถวรอเช็คอยู่แล้ว — เหมือนเดิม)
        $this->act(TrackingController::class, 'save', ['id' => (string) $t, 'order' => 'zz_o2', 'quote' => '', 'ship' => '1',
            'is_craft' => '1', 'is_qc' => '0', 'is_takeapic' => '0']);
        $this->assertSame(['0', '0'], $this->checks($t));

        // ไม่ขออะไร = ไม่เพิ่ม
        $this->act(TrackingController::class, 'save', ['id' => (string) $t, 'order' => 'zz_o2', 'quote' => '', 'ship' => '1',
            'is_craft' => '0', 'is_qc' => '0', 'is_takeapic' => '0']);
        $this->assertCount(2, $this->checks($t));
    }

    public function test_customer_cannot_queue_check_for_someone_elses_tracking(): void
    {
        $this->as(self::CUST);
        $t = $this->track(['user' => self::ADMIN]);
        $this->act(TrackingController::class, 'save', ['id' => (string) $t, 'order' => 'x', 'quote' => 'zz_q', 'ship' => '0',
            'is_craft' => '1', 'is_qc' => '1', 'is_takeapic' => '1']);
        $this->assertSame([], $this->checks($t));
        $this->assertNotSame('zz_q', DB::table('tracking')->where('id', $t)->value('quote'));
    }

    public function test_admin_save_subuser_takeapic_and_replaces_images(): void
    {
        $this->as(self::ADMIN);
        $code = DB::table('user')->where('id', self::CUST)->value('code');
        $t = $this->track();
        $this->insertRow('tracking_image', ['tracking_id' => $t, 'attachment' => 'zz_old1.jpg']);
        $this->insertRow('tracking_image', ['tracking_id' => $t, 'attachment' => 'zz_old2.jpg']);
        $post = ['id' => (string) $t, 'tracking' => 'ZZ-SR-ADM', 'order' => '', 'quote' => '', 'ship' => '0', 'shipping_type' => '',
            'code' => $code, 'subuser' => "zz s'ub", 'is_craft' => '0', 'is_qc' => '0', 'is_takeapic' => '1', 'remark' => '',
            'is_auto_price' => '0', 'date_warehouse' => '', 'date_outofchina' => '', 'date_arrive' => '',
            'upload_upload_track_mul' => ['zz_old2.jpg', '../../etc/passwd', 'zz_new.png', '']];
        foreach (['weight', 'width', 'height', 'long', 'amount', 'price_kg', 'price_cubic', 'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft'] as $k) {
            $post[$k] = '1';
        }

        $out = $this->act(AdminTrackingController::class, 'save', $post);

        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertStringNotContainsString('passwd', $out);
        $row = DB::table('tracking')->where('id', $t)->first();
        $this->assertSame("zzs''ub", $row->subuser, 'ตัดช่องว่าง + nStr แบบเดิม');
        $this->assertSame('1', (string) $row->is_takeapic);
        $live = DB::table('tracking_image')->where('tracking_id', $t)->where('is_delete', 0)->orderBy('id')->pluck('attachment')->all();
        $this->assertSame(['zz_old2.jpg', 'zz_new.png'], $live);
        $this->assertSame(2, DB::table('tracking_image')->where('tracking_id', $t)->where('is_delete', 1)->count());

        // สร้างใหม่: รูปผูกกับ id ใหม่
        $post['id'] = '';
        $post['tracking'] = 'ZZ-SR-ADM-NEW-'.bin2hex(random_bytes(3));
        $post['upload_upload_track_mul'] = ['zz_create.jpg'];
        $this->assertStringContainsString('SuccessBox(', $this->act(AdminTrackingController::class, 'save', $post));
        $newId = (int) DB::table('tracking')->where('tracking', $post['tracking'])->value('id');
        $this->assertSame(['zz_create.jpg'], DB::table('tracking_image')->where('tracking_id', $newId)->where('is_delete', 0)->pluck('attachment')->all());
        $this->assertSame("zzs''ub", DB::table('tracking')->where('id', $newId)->value('subuser'));
    }

    public function test_combine_keeps_subuser_and_takeapic_columns_and_moves_images(): void
    {
        $this->as(self::ADMIN);
        $a = $this->track(['subuser' => '', 'is_takeapic' => 0]);
        $b = $this->track(['subuser' => 'zz_SUB9', 'is_takeapic' => 1]);
        $live = $this->insertRow('tracking_image', ['tracking_id' => $b, 'attachment' => 'zz_live.jpg']);
        $dead = $this->insertRow('tracking_image', ['tracking_id' => $a, 'attachment' => 'zz_dead.jpg', 'is_delete' => 1]);

        $this->assertStringContainsString('SuccessBox(', $this->act(CombineTrackController::class, 'saveCombine', ['tracking' => ','.$a.','.$b]));

        $new = DB::table('tracking')->where('id', '>', $b)->where('user', self::CUST)->orderByDesc('id')->first();
        $this->assertNotNull($new);
        $this->assertSame('zz_SUB9', $new->subuser, 'subuser ลงคอลัมน์ subuser (เดิมสลับกับ is_takeapic)');
        $this->assertSame('0', (string) $new->is_takeapic, 'is_takeapic เทียบกับ "" → 0 ของแถวแรก');
        $this->assertSame((int) $new->id, (int) DB::table('tracking_image')->where('id', $live)->value('tracking_id'));
        $this->assertSame($a, (int) DB::table('tracking_image')->where('id', $dead)->value('tracking_id'), 'รูปที่ลบแล้วไม่ย้าย');
    }

    public function test_check_clear_and_clear_all(): void
    {
        $this->as(self::ADMIN);
        $t = $this->track();
        $c1 = $this->insertRow('tracking_check', ['tracking_id' => $t, 'is_checked' => 0]);
        $c2 = $this->insertRow('tracking_check', ['tracking_id' => $t, 'is_checked' => 0]);
        $c3 = $this->insertRow('tracking_check', ['tracking_id' => $t, 'is_checked' => 0]);
        $other = $this->insertRow('tracking_check', ['tracking_id' => $t, 'is_checked' => 0]);

        $html = app(CheckController::class)->index()->render();
        $this->assertStringContainsString('value="'.$c1.'"', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);

        $this->assertStringContainsString('window.location.reload();', $this->act(CheckController::class, 'saveClear', ['id' => (string) $c1]));
        $this->assertSame('1', (string) DB::table('tracking_check')->where('id', $c1)->value('is_checked'));

        // SQL injection เดิม (IN (...) ต่อ string) → ไม่ใช่ตัวเลขทั้งหมด = ไม่เปลี่ยนอะไร
        $this->act(CheckController::class, 'saveClearAll', ['id' => ', '.$c2.' ) OR (1=1 ']);
        $this->assertSame('0', (string) DB::table('tracking_check')->where('id', $c2)->value('is_checked'));

        $this->act(CheckController::class, 'saveClearAll', ['id' => ', '.$c2.' , '.$c3.' ']);
        $this->assertSame(['1', '1', '0'], DB::table('tracking_check')->whereIn('id', [$c2, $c3, $other])->orderBy('id')->pluck('is_checked')->map(fn ($v) => (string) $v)->all());
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
