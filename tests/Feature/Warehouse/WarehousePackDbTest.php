<?php

namespace Tests\Feature\Warehouse;

use App\Http\Controllers\Warehouse\Admin\AdminGoodsconfirmController;
use App\Http\Controllers\Warehouse\GoodsconfirmController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * PACK: goodsconfirm (เหมือน SR) — DB bench ใน transaction ที่ rollback (opt-in JL_BENCH_DB_TESTS=1)
 * การจัดชั้นวาง (save_lot_detail / ShelfAllocator ของ SR) ไม่มีใน PACK — ตาราง warehouse* ไม่มี
 */
class WarehousePackDbTest extends BenchDbTestCase
{
    private function track(int $lot, ?int $user, float $w, float $weight, string $no = ''): int
    {
        return $this->insertRow('tracking', ['tracking' => 'ZZ-PK-'.$lot.'-'.$no.bin2hex(random_bytes(2)), 'lot_id' => $lot, 'user' => $user,
            'width' => $w, 'height' => $w, 'long' => $w, 'weight' => $weight, 'amount' => 1, 'header' => 0, 'date_thai' => '0000-00-00 00:00:00']);
    }

    public function test_admin_goodsconfirm_save_takes_tracking_of_nobody_user(): void
    {
        $this->actAs($this->zzUser(1));
        $nobody = $this->zzUser();
        DB::table('user')->where('id', $nobody->id)->update(['code' => config('legacy.nobody')]); // PACK: PACKnocode
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
