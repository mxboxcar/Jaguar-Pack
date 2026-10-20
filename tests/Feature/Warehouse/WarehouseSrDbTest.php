<?php

namespace Tests\Feature\Warehouse;

use App\Domain\Warehouse\ShelfAllocator;
use App\Http\Controllers\Warehouse\Admin\AdminGoodsconfirmController;
use App\Http\Controllers\Warehouse\GoodsconfirmController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * SR: พฤติกรรมของกลุ่ม warehouse ที่ต่างจาก jl — DB bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 */
class WarehouseSrDbTest extends BenchDbTestCase
{
    private function section(string $name, int $floors, float $w = 100, float $h = 100, float $l = 100, ?int $id = null): int
    {
        return $this->insertRow('warehouse_section', ($id === null ? [] : ['id' => $id])
            + ['name' => $name, 'width' => $w, 'height' => $h, 'long' => $l, 'status' => 0, 'attachment' => '', 'floors' => $floors]);
    }

    private function track(int $lot, ?int $user, float $w, float $weight, string $no = ''): int
    {
        return $this->insertRow('tracking', ['tracking' => 'ZZ-SR-'.$lot.'-'.$no.bin2hex(random_bytes(2)), 'lot_id' => $lot, 'user' => $user,
            'width' => $w, 'height' => $w, 'long' => $w, 'weight' => $weight, 'amount' => 1, 'header' => 0, 'date_thai' => '0000-00-00 00:00:00']);
    }

    public function test_fixed_sections_insert_once_and_skip_auto_allocation(): void
    {
        $this->actAs($this->zzUser(1));
        $this->section('zz_sr_auto1', -100);
        $lot = $this->insertRow('lot', ['name' => 'zz_sr_lot', 'type' => 1, 'status' => 1]);
        $t4 = $this->track($lot, 4, 20, 1, 'a');      // ใน REPEAT_USERS และ FIXED_SECTIONS → 33 (เดิม 2 แถว)
        $t1362 = $this->track($lot, 1362, 20, 1, 'b'); // FIXED_SECTIONS เท่านั้น → 41
        $wh = $this->insertRow('warehouse', ['remark' => '']);

        $html = (new ShelfAllocator($wh, (string) $lot))->run();

        $rows = DB::table('warehouse_section_detail')->where('warehouse_id', $wh)->orderBy('id')->get();
        $this->assertSame([[$t4, 33], [$t1362, 41]], $rows->map(fn ($r) => [(int) $r->tracking_id, (int) $r->section_id])->all());
        $this->assertSame([0, 0], $rows->map(fn ($r) => (int) $r->user_agent)->all());
        // print_r ของรายการ "จัดแล้ว" ตรงกับเดิม: 4- ซ้ำจากรอบ REPEAT_USERS
        $this->assertStringContainsString("[0] => 4-\n    [1] => 1362-\n    [2] => 4-\n", $html);
    }

    public function test_count_code_groups_user_agent_and_no_suffix_4_rule(): void
    {
        $this->actAs($this->zzUser(1));
        // ชั้นแรก: 10 แถวของ user เดียวแต่ user_agent ต่างกัน → SR นับ 10 รหัส (jl นับ 1) และใช้พื้นที่เกินครึ่ง → ข้าม
        $s1 = $this->section('zz_sr_A01', -100);
        $s4 = $this->section('zz_sr_A04', -99); // ลงท้าย 4: jl ไม่รับของ >= 15 kg, SR รับ
        $occLot = $this->insertRow('lot', ['name' => 'zz_sr_occ', 'type' => 1, 'status' => 1]);
        foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, null] as $agent) {
            $t = $this->track($occLot, 20, 40, 1, 'o');
            $this->insertRow('warehouse_section_detail', ['section_id' => $s1, 'width' => 40, 'height' => 40, 'long' => 40,
                'user' => 20, 'tracking_id' => $t, 'warehouse_id' => 0, 'user_agent' => $agent]);
        }
        $lot = $this->insertRow('lot', ['name' => 'zz_sr_lot', 'type' => 1, 'status' => 1]);
        $cust = $this->zzUser();
        $t = $this->track($lot, $cust->id, 20, 20, 'c');
        $wh = $this->insertRow('warehouse', ['remark' => '']);

        $html = (new ShelfAllocator($wh, (string) $lot))->run();

        $row = DB::table('warehouse_section_detail')->where('tracking_id', $t)->first();
        $this->assertSame($s4, (int) $row->section_id);
        $this->assertSame(0, (int) $row->user_agent);
        // SR ไม่ echo ตัวท้ายชื่อชั้นระหว่างวน (jl พิมพ์ "14" ก่อนตาราง)
        $this->assertStringContainsString('</pre><div style="margin-top:10px;">', $html);
    }

    public function test_admin_goodsconfirm_save_takes_tracking_of_nobody_user(): void
    {
        $this->actAs($this->zzUser(1));
        $nobody = $this->zzUser();
        DB::table('user')->where('id', $nobody->id)->update(['code' => 'nobody']);
        $nocode = $this->zzUser();
        DB::table('user')->where('id', $nocode->id)->update(['code' => 'nocode']);
        $lot = $this->insertRow('lot', ['name' => 'zz_sr_lot', 'type' => 1, 'status' => 1]);
        $tn = $this->track($lot, $nobody->id, 10, 1, 'n');
        $tc = $this->track($lot, $nocode->id, 10, 1, 'c');
        $trk = fn (int $id) => (string) DB::table('tracking')->where('id', $id)->value('tracking');

        $out = $this->act(AdminGoodsconfirmController::class, 'save', ['id' => '', 'tracking' => $trk($tc), 'submit_remark' => 'zz_sr']);
        $this->assertStringStartsWith('ErrorBox(', $out);
        $this->assertSame(0, DB::table('goodsconfirm')->where('tracking_id', $tc)->count());

        $out = $this->act(AdminGoodsconfirmController::class, 'save', ['id' => '', 'tracking' => $trk($tn), 'submit_remark' => 'zz_sr']);
        $this->assertStringContainsString('SuccessBox(', $out);
        $row = DB::table('goodsconfirm')->where('tracking_id', $tn)->first();
        $this->assertNotNull($row);
        $this->assertSame(['zz_sr', 0, 0], [$row->submit_remark, (int) $row->user, (int) $row->status]);
    }

    public function test_customer_grid_lists_only_unclaimed_items(): void
    {
        $cust = $this->zzUser(8);
        $this->actAs($cust);
        $lot = $this->insertRow('lot', ['name' => 'zz_sr_lot', 'type' => 1, 'status' => 1]);
        $g = fn (int $status, int $user, ?int $tracking) => $this->insertRow('goodsconfirm', ['tracking_id' => $tracking, 'attachment' => '',
            'submit_remark' => 'zz_sr_grid', 'cashback' => 0, 'user' => $user, 'status' => $status, 'cancel_remark' => '', 'approved_upload' => '']);
        $open = $g(0, 0, $this->track($lot, null, 10, 1));
        $noTracking = $g(0, 0, null);      // SR ไม่ตัดแถวที่ไม่มี tracking
        $mine = $g(1, $cust->id, $this->track($lot, $cust->id, 10, 1)); // SR ไม่แสดงรายการของตัวเอง
        $grid = fn (string $s) => $this->act(GoodsconfirmController::class, 'grid', ['txtsearch' => 'zz_sr_grid|'.$s, 'page' => '1']);

        $all = $grid('-2');
        $this->assertStringContainsString('goodsconfirm/'.$open.'"', $all);
        $this->assertStringContainsString('goodsconfirm/'.$noTracking.'"', $all);
        $this->assertStringNotContainsString('goodsconfirm/'.$mine.'"', $all);
        $this->assertStringNotContainsString('goodsconfirm/'.$mine.'"', $grid('1'));
        $this->assertStringNotContainsString('goodsconfirm/'.$open.'"', $grid('1'));
    }
}
