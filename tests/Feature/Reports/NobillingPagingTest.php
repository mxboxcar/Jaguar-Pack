<?php

namespace Tests\Feature\Reports;

use App\Http\Controllers\Reports\Admin\AdminNobillingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * แก้จากเดิม (เจ้าของสั่ง 2026-10-18): หน้า admin/nobilling แบ่งหน้าตามจำนวนพัสดุ PER_PAGE แถวต่อหน้า
 * เรียงตามรหัสลูกค้า (เดิมแสดงทุกกลุ่มในหน้าเดียว) — ไม่แตะ DB จริง: DB::select ถูกแทนด้วยแถวจำลอง
 */
class NobillingPagingTest extends TestCase
{
    private function fakeRows(int $customers, int $perCustomer): void
    {
        $rows = [];
        // ใส่รหัสสลับลำดับ เพื่อเช็คว่าเรียงตามรหัสจริง
        for ($c = $customers; $c >= 1; $c--) {
            for ($i = 1; $i <= $perCustomer; $i++) {
                $rows[] = (object) ['code' => sprintf('JL%04d', $c), 'lot_no' => 'L'.$c, 'no' => $i, 'tracking' => "T{$c}-{$i}",
                    'date_thai' => '2026-09-01 00:00:00', 'weight' => '1', 'width' => '1', 'height' => '1', 'long' => '1'];
            }
        }
        DB::shouldReceive('select')->once()->andReturn($rows);
    }

    private function data(?string $page): array
    {
        $q = $page === null ? [] : ['page' => $page];

        return (new AdminNobillingController)->index(Request::create('/admin/nobilling', 'GET', $q))->getData();
    }

    public function test_pages_by_rows_sorted_by_code_and_split_groups(): void
    {
        $per = AdminNobillingController::PER_PAGE;
        $this->fakeRows(3, $per);               // 3 ลูกค้า × PER_PAGE แถว = 3 หน้าพอดี
        $d = $this->data(null);
        $this->assertSame(1, $d['page']);
        $this->assertSame(3, $d['pages']);
        $this->assertSame(3, $d['totalGroups']);
        $this->assertSame(3 * $per, $d['totalRows']);
        $this->assertSame(['JL0001'], array_keys($d['groups']));
        $this->assertCount($per, $d['groups']['JL0001']);

        $this->fakeRows(3, (int) ($per * 0.75)); // กลุ่มที่ 2 ข้ามหน้า → หัวรหัสซ้ำหน้าถัดไป
        $d = $this->data('2');
        $this->assertSame(['JL0002', 'JL0003'], array_keys($d['groups']));
        $this->assertSame($per, count($d['groups']['JL0002']) + count($d['groups']['JL0003']));
    }

    public function test_out_of_range_and_junk_page(): void
    {
        $per = AdminNobillingController::PER_PAGE;
        $this->fakeRows(5, $per);
        $this->assertSame(5, $this->data('999')['page']);

        $this->fakeRows(5, $per);
        $this->assertSame(1, $this->data('1) OR (1=1')['page']);
    }

    public function test_no_rows(): void
    {
        $this->fakeRows(0, 0);
        $d = $this->data(null);
        $this->assertSame([], $d['groups']);
        $this->assertSame(1, $d['pages']);
        $this->assertSame(0, $d['totalRows']);
    }
}
