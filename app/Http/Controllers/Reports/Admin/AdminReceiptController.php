<?php

namespace App\Http\Controllers\Reports\Admin;

use App\Domain\Reports\Php74;
use App\Domain\Reports\Receipt;
use App\Domain\Reports\Rows;
use App\Domain\Reports\Running;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Format;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * SR: ใบเสร็จรับเงิน/ใบกำกับภาษีของบิลส่งสินค้า (มีเฉพาะ SR — คอลัมน์ bill_payment.receipt_*)
 *  receipt.php (หน้า admin/export/receipt/{id}, admin_payment) · receipt_1.php / receipt_2.php (ตาราง, admin_payment)
 *  calvat.php (คำนวณภาษี, admin_export) · save_receipt.php (บันทึก + ออกเลขใบเสร็จ, admin_export)
 *  print_receipt.php (พิมพ์ใบเสร็จ, admin_export) · _admin/_report/printtax.php (รายงานภาษีขายรายเดือน, admin_export)
 */
class AdminReceiptController extends Controller
{
    use ReadsLegacyInput;

    /** GET /admin/export/receipt/{id} */
    public function form(Request $request): View
    {
        $data = self::bill((int) $request->route('id'));
        if ($data === null) {
            // เดิม getPermissionAlert_Page('payment') แล้ว exit
            return view('legacy.no-permission', ['module' => 'payment']);
        }

        return view('reports.receipt.form', ['data' => $data, 'prefix' => Rows::prefix('payment')]);
    }

    /** POST /_admin/_export/receipt_1 (บวก VAT 7%) */
    public function table1(Request $request): Response
    {
        return $this->table($request, true);
    }

    /** POST /_admin/_export/receipt_2 (รวม VAT แล้ว) */
    public function table2(Request $request): Response
    {
        return $this->table($request, false);
    }

    private function table(Request $request, bool $addVat): Response
    {
        // เดิมไม่ตรวจว่าเจอบิล: ไม่เจอ = ตารางยอดศูนย์
        $data = self::bill($this->in($request, 'id')) ?? new \stdClass;

        return response(view('reports.receipt.table', [
            'v' => Receipt::form($data, $addVat),
            'type' => $addVat ? 1 : 2,
        ])->render());
    }

    /** POST /_admin/_export/calvat — JSON แบบเดิม (urldecode(json_encode())) */
    public function calvat(Request $request): Response
    {
        return response(Receipt::calvat($this->php($request, 'type'), $this->php($request, 'total')));
    }

    /**
     * POST /_admin/_export/save_receipt — บันทึกข้อมูลใบเสร็จ; ครั้งแรก (receipt_id ว่าง) ออกเลข getRunning(ปี พ.ศ. 2 หลัก, false, 5)
     * ต่างจากเดิม: transaction + lock แถวบิลและแถว running (กดซ้ำ/พร้อมกันไม่ได้เลขซ้ำหรือข้ามเลข), id ต้องเป็นตัวเลขล้วน
     */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $name = str_replace("'", '', $this->in($request, 'receipt_name'));
        $address = str_replace("'", '', $this->in($request, 'receipt_address'));
        $tax = str_replace("'", '', $this->in($request, 'receipt_tax'));
        $date = $this->in($request, 'date');
        $isVat = Php74::neq($this->in($request, 'is_vat'), '1') ? 0 : 1;
        $total = $this->in($request, 'receipt_total');
        $exp = explode('|', $date);
        $newDateTime = Format::getdatesubmit($exp[0]).' '.($exp[1] ?? '');

        if (preg_match('/^[0-9]+$/', $id) === 1) {
            DB::transaction(function () use ($id, $name, $address, $tax, $isVat, $total, $newDateTime) {
                $data = DB::table('bill_payment')->where('id', $id)->where('is_delete', 0)->lockForUpdate()->first();
                if ($data === null) {
                    return;
                }
                $set = [
                    'receipt_total' => $total, 'receipt_is_vat' => $isVat, 'receipt_name' => $name,
                    'receipt_address' => $address, 'receipt_tax' => $tax, 'receipt_date' => $newDateTime,
                ];
                if (Php74::eq($data->receipt_id, '')) {
                    $set['receipt_id'] = Running::next(substr((string) ((int) date('Y') + 543), -2), false, 5);
                }
                DB::table('bill_payment')->where('id', $data->id)->update($set);
            });
        }

        return response('window.location.reload();');
    }

    /** GET /_admin/_export/print_receipt?id= */
    public function print(Request $request): Response
    {
        $id = $this->get($request, 'id');
        if (! perm('admin_export') || Php74::eq($id, '') || Php74::eq($id, 0)) {
            return response('ERROR : access denied');
        }

        return response(view('reports.receipt.print', ['data' => self::bill($id) ?? new \stdClass])->render());
    }

    /** GET /_admin/_report/printtax?m=&y= — ใบเสร็จที่ receipt_date อยู่ในเดือนนั้น (ช่วง 'Y-m-01' ถึง 'Y-m-31' แบบเดิม) */
    public function printtax(Request $request): Response
    {
        if (! perm('admin_export')) {
            return response('ERROR : access denied');
        }
        $m = $this->get($request, 'm');
        $y = $this->get($request, 'y');
        $mm = strlen($m) === 1 ? '0'.$m : $m;
        $rows = Rows::strAll(DB::table('bill_payment')->where('receipt_id', '!=', '')
            ->whereBetween('receipt_date', [$y.'-'.$mm.'-01', $y.'-'.$mm.'-31'])
            ->orderBy('receipt_id')
            ->get(['id', 'receipt_id', 'receipt_date', 'receipt_name', 'receipt_total', 'receipt_is_vat']));

        return response(view('reports.receipt.printtax', [
            'rows' => $rows,
            'monthName' => self::month($m),
            'year' => $y,
        ])->render());
    }

    /** getm() ของ printtax.php: switch แบบหลวม ('01' == '1') */
    private static function month(string $m): string
    {
        $names = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        foreach ($names as $i => $name) {
            if (Php74::eq($m, (string) ($i + 1))) {
                return $name;
            }
        }

        return '';
    }

    /** SELECT bill_payment.*, user.code ... WHERE id = ? AND is_delete = 0 (id ไม่ใช่ตัวเลขล้วน = ไม่เจอ) */
    private static function bill(mixed $id): ?object
    {
        if (! is_int($id) && preg_match('/^[0-9]+$/', (string) $id) !== 1) {
            return null;
        }

        return Rows::str(DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.id', $id)->where('bill_payment.is_delete', 0)
            ->first(['bill_payment.*', 'user.code']));
    }
}
