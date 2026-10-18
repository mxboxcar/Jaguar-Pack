<?php

namespace App\Http\Controllers\Reports\Admin;

use App\Domain\Money\Money;
use App\Domain\Reports\ExportBill;
use App\Domain\Reports\ExportMailer;
use App\Domain\Reports\Php74;
use App\Domain\Reports\Rows;
use App\Domain\Reports\TempUpload;
use App\Domain\Reports\UploadBox;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\ReadsLegacyInput;
use App\Http\Controllers\Reports\Concerns\RejectsCrossSiteGet;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * แอดมิน: รายการส่งสินค้า (_admin/_export/*)
 * สิทธิ์ตามไฟล์เดิม: index/grid/save_approved/print = admin_export, form/save_delete = admin_payment
 */
class AdminExportController extends Controller
{
    use ReadsLegacyInput;
    use RejectsCrossSiteGet;

    private const AJAX = '_admin/_export/grid';

    /** GET /admin/export, /admin/export/status/{status} */
    public function index(Request $request): View
    {
        // เดิม echo $_GET['status'] ดิบลงใน JS — route รับเฉพาะตัวเลข
        return view('reports.admin_export.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** POST /_admin/_export/grid — เดิมไม่มีสิทธิ์ = exit() (ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_export')) {
            return response('');
        }
        $parts = explode('|', $this->in($request, 'txtsearch'));
        $php = explode('|', $this->php($request, 'txtsearch'));
        $status = $parts[1] ?? null;
        $s = strtolower($parts[0]);
        $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';

        $q = DB::table('bill_payment')
            ->leftJoin('shipping_type', 'bill_payment.shipping_type', '=', 'shipping_type.id')
            ->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.is_delete', 0)
            ->where(fn ($w) => $w->where('bill_payment.id', 'like', $like(str_replace(strtolower(Rows::prefix('payment')), '', $s)))
                ->orWhere('user.code', 'like', $like($s))
                ->orWhere('bill_payment.send_name', 'like', $like($s)))
            ->where(fn ($w) => $w->where('bill_payment.status', '>=', 2)->orWhere('bill_payment.status', -2));
        if (Php74::neq($status, '-2') && Php74::neq($status, '')) {
            $q->where('bill_payment.status', $status);
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Rows::offset($page);
        $items = $offset === null ? collect() : Rows::strAll($q->orderByDesc('bill_payment.id')->offset($offset)->limit(10)
            ->get(['bill_payment.*', 'user.code', 'shipping_type.name as ship_name']));

        return response(view('reports.admin_export.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => $php[0],
            'status' => $php[1] ?? null,
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => Rows::prefix('payment'),
        ])->render());
    }

    /** GET /admin/export/{id} — _admin/_export/form.php (สิทธิ์ admin_payment) */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        $data = Rows::str(DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.id', $id)->where('bill_payment.is_delete', 0)
            ->first(['bill_payment.*', 'user.code']));
        if ($data === null) {
            return view('legacy.no-permission', ['module' => 'payment']);
        }
        $shipName = DB::table('shipping_type')->where('id', $data->shipping_type)->value('name');
        $details = ExportBill::details($id);
        $exportDate = '';
        if (Php74::neq($data->export_date, '0000-00-00 00:00:00') && Php74::neq($data->export_date, '')) {
            $exp = explode(' ', (string) $data->export_date);
            $exportDate = Format::getthaidate($exp[0], false, true).'|'.($exp[1] ?? '');
        }

        return view('reports.admin_export.form', [
            'id' => $id,
            'data' => $data,
            'shipName' => $shipName,
            'details' => $details,
            'totals' => ExportBill::totals($details),
            'rows' => ExportBill::trackingRows($details, true),
            'balance' => ExportBill::balanceBox($id, true),
            'prefix' => Rows::prefix('payment'),
            'exportDate' => $exportDate,
            'upload' => UploadBox::single('file', (string) $data->export_attachment, true),
        ]);
    }

    /**
     * POST /_admin/_export/save_approved — บันทึกการส่ง (สถานะ 3) + แถว balance ค่าส่งในไทย + อีเมลแจ้งลูกค้า
     * ต่างจากเดิม: id ต้องเป็นบิลที่มีอยู่จริง (เดิม id ว่าง/ผิด = UPDATE tracking ... WHERE payment_id = '' → ทุก tracking ที่ยังไม่ชำระ
     * ได้ date_finish = NOW() และ INSERT balance ไม่มีเจ้าของ), ทำใน transaction + lock แถวบิล
     */
    public function saveApproved(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $tracking = $this->in($request, 'export_tracking');
        $remark = $this->in($request, 'export_remark');
        $exportDate = $this->in($request, 'export_date');
        $price = $this->in($request, 'export_price');
        $file = $this->in($request, 'upload_file');
        $exp = explode('|', $exportDate);
        $newDateTime = Format::getdatesubmit($exp[0]).' '.($exp[1] ?? '');

        $js = '';
        $found = false;
        if (preg_match('/^[1-9][0-9]*$/', $id) === 1) {
            $found = DB::transaction(function () use ($uid, $id, $tracking, $remark, $exportDate, $newDateTime, $price, $file) {
                $data = DB::table('bill_payment')->where('id', $id)->lockForUpdate()->first();
                if ($data === null) {
                    return false;
                }
                // เดิมรับทุกสถานะ: บิลที่ยกเลิก/คืนเงินแล้ว (-1) หรือยังไม่จ่าย (0/1) ถูกพลิกเป็น "ส่งแล้ว" + ตัดค่าส่งในประเทศ
                // ใหม่ (เจ้าของสั่งแก้): เฉพาะ 2 (รอส่ง) และ 3 (ส่งแล้ว = แก้ข้อมูลการส่ง; แถวค่าส่งเดิมถูกแทนที่ ไม่ตัดซ้ำ)
                if (! in_array((string) $data->status, ['2', '3'], true)) {
                    return 'invalid';
                }
                Money::lockUser($data->user);
                DB::table('bill_payment')->where('id', $data->id)->update([
                    'status' => '3', 'export_tracking' => $tracking, 'export_remark' => $remark,
                    'export_date' => $exportDate === '' ? DB::raw('NOW()') : $newDateTime,
                    'export_price' => $price, 'export_attachment' => $file, 'mod_by' => $uid,
                ]);
                DB::table('tracking')->where('payment_id', $data->id)->update(['date_finish' => DB::raw('NOW()')]);
                DB::table('balance')->where('type', 4)->where('prefix', Lookup::getPrefixID('export'))->where('prefix_ref_id', $data->id)
                    ->update(['is_delete' => '1']);
                DB::table('balance')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                    'user' => (string) $data->user, 'amount' => $price, 'detail' => 'หักเงิน-คืนเงิน (ค่าขนส่งในประเทศ) '.Rows::prefix('export').$data->id,
                    'type' => 4, 'prefix' => (string) Lookup::getPrefixID('export'), 'prefix_ref_id' => $data->id,
                ]);

                return true;
            });
        }
        if ($found === 'invalid') {
            return response(Money::refuse('บิลนี้ไม่อยู่ในสถานะรอส่งสินค้า ไม่สามารถบันทึกการส่งได้'));
        }
        if ($found === true) {
            if ($file !== '') {
                // เดิม echo JS ให้เบราว์เซอร์ย้ายไฟล์จาก upload/temp ขึ้น storage (_api/blockblob_ajax)
                $js = self::blobJs($file);
            }
            ExportMailer::success($id);
        }

        return response($js.Alerts::success('window.location.href = "'.Legacy::href('admin/export').'";', Lang::get('success_process_no').Rows::prefix('payment').Alerts::js($id)));
    }

    /**
     * POST /_admin/_export/save_delete (สิทธิ์ admin_payment) — ยกเลิกบิลสถานะ 2 (รอส่ง) + คืนเงิน + อีเมลแจ้ง
     * ต่างจากเดิม: transaction + lock (กดซ้ำไม่คืนเงินซ้ำ)
     */
    public function saveDelete(Request $request): Response
    {
        $uid = Legacy::userId();
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark');
        $code = (string) (Legacy::user()->code ?? '');
        $done = DB::transaction(function () use ($uid, $id, $remark, $code) {
            // id ต้องเป็นตัวเลขล้วน (เดิม '5abc' ถูก MySQL แปลงเป็น 5)
            $data = preg_match('/^[0-9]+$/', $id) === 1 ? DB::table('bill_payment')->where('id', $id)->lockForUpdate()->first() : null;
            if ($data === null || (string) $data->status !== '2') {
                return null;
            }
            DB::table('tracking')->where('payment_id', $data->id)->update(['payment_id' => '0']);
            // calPoint() เดิมเป็นฟังก์ชันว่าง
            DB::table('bill_payment')->where('id', $data->id)->update([
                'status' => '-1', 'cancel_remark' => $remark.' ,ยกเลิกโดย '.$code, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ]);
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'user' => (string) $data->user, 'amount' => (string) $data->total, 'detail' => 'คืนเงิน '.Rows::prefix('payment').$data->id,
                'type' => 3, 'prefix' => (string) Lookup::getPrefixID('payment'), 'prefix_ref_id' => $data->id,
            ]);

            return $data->id;
        });
        if ($done === null) {
            return response(Alerts::success('window.location.reload();', 'รายการถูกยืนยันไปแล้ว'));
        }
        ExportMailer::cancel($done);

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/export').'";', Lang::get('success_process_no').Rows::prefix('payment').Alerts::js($id)));
    }

    /** GET /_admin/_export/print?id= — ใบปะหน้า (ตั้ง is_print) */
    public function print(Request $request): Response
    {
        $id = $this->get($request, 'id');
        if (! perm('admin_export') || $id === '' || $id === '0') {
            return response('ERROR : access denied');
        }
        if ($this->isCrossSite($request)) {
            return $this->crossSiteResponse();
        }
        DB::table('bill_payment')->where('id', $id)->update(['is_print' => '1', 'print_date' => DB::raw('NOW()')]);
        $data = Rows::str(DB::table('bill_payment')->leftJoin('user', 'bill_payment.user', '=', 'user.id')
            ->where('bill_payment.id', $id)->first(['bill_payment.*', 'user.code'])) ?? new \stdClass;
        $shipName = isset($data->shipping_type) ? DB::table('shipping_type')->where('id', $data->shipping_type)->value('name') : null;
        $details = ExportBill::details($id);

        return response(view('reports.admin_export.print', [
            'data' => $data,
            'shipName' => $shipName,
            'details' => $details,
            'locations' => ExportBill::locations($details),
        ])->render());
    }

    /** JS เดิมของ save_approved.php (CRLF + ย่อหน้าตามไฟล์เดิม) ถ้าไฟล์อยู่ใน upload/temp — ชื่อไฟล์ผ่าน whitelist + escape */
    private static function blobJs(string $file): string
    {
        if (! TempUpload::exists($file)) {
            return '';
        }
        $i = "\t         \t\t";

        return "$.ajax({ \r\n".$i."type: 'POST', \r\n".$i."async: true, \r\n".$i."data : 'path=../upload/temp/".Alerts::js($file)."', \r\n"
            .$i."url: rooturl + '_api/blockblob_ajax', \r\n".$i."success: function(data) { \r\n".$i."\t\r\n".$i.'} });';
    }

    /** GET|POST /_admin/_export/email_success, email_cancel — เปิดตรงๆ เดิมไม่มี $email_id = ไม่มีผู้รับ (ส่งไม่ได้, ตอบว่าง) */
    public function emailNoop(): Response
    {
        return response('');
    }
}
