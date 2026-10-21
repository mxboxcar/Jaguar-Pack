<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Domain\Money\Money;
use App\Domain\Payment\PaymentLookups as P;
use App\Domain\Payment\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * คูปองส่วนลด (แอดมิน) — PACK เท่านั้น: _admin/_coupon/{index,grid,form,save,delete}.php เดิม (สิทธิ์ admin_coupon)
 * คูปอง = ชื่อ (รหัส) + ราคาส่วนลด; ลูกค้าใส่ชื่อใน payment/create/{random} → ผูก coupon.payment_id กับบิลตอนจ่าย (ใช้ได้ครั้งเดียว)
 * แก้ได้เฉพาะคูปองที่ยังไม่ถูกใช้ (payment_id = 0)
 */
class AdminCouponController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_coupon/grid';

    /** GET /admin/coupon (+ /admin/coupon/status/{n} — RewriteRule เดิมส่ง status แต่ไฟล์ไม่ได้ใช้) — index.php */
    public function index(): View
    {
        return view('payment.admin_coupon.index');
    }

    /** POST /_admin/_coupon/grid — grid.php (ไม่มีสิทธิ์ = ตอบว่างแบบเดิม exit()) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_coupon')) {
            return response('');
        }
        $term = explode('|', $this->in($request, 'txtsearch'))[0];
        $like = '%'.addcslashes($term, '%_\\').'%';
        // เดิม: is_delete = 0 AND (id LIKE '%x%' OR name LIKE '%x%' OR payment_id LIKE '%x%') ORDER BY id DESC LIMIT (MakePaging)
        $q = DB::table('coupon')->where('is_delete', 0)
            ->where(fn ($w) => $w->where('id', 'like', $like)->orWhere('name', 'like', $like)->orWhere('payment_id', 'like', $like));
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = P::offset($page);
        // offset ติดลบ/ไม่ใช่จำนวนเต็ม = SQL เดิมผิด (LIMIT -10,10) → ไม่มีแถว
        $items = $offset === null ? collect() : P::strAll($q->orderByDesc('id')->offset($offset)->limit(10)->get());

        return response(view('payment.admin_coupon.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'search' => explode('|', $this->php($request, 'txtsearch'))[0],
            'txtsearch' => $this->php($request, 'txtsearch'),
            'page' => $page,
            'total' => $total,
            'prefix' => P::prefix('coupon'),
            'payPrefix' => P::prefix('payment'),
        ])->render());
    }

    /** GET /admin/coupon/create (id = 0), /admin/coupon/{id} — form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0); // เดิม $_GET['id'] ถูก (int) ใน system_config.php

        return view('payment.admin_coupon.form', [
            'id' => $id,
            'data' => P::str(DB::table('coupon')->where('id', $id)->where('is_delete', 0)->first()),
            'prefix' => P::prefix('coupon'),
            'payPrefix' => P::prefix('payment'),
            // เดิม value = randd(6) ในฟอร์มเพิ่มใหม่
            'newName' => Format::randd(6),
        ]);
    }

    /**
     * POST /_admin/_coupon/save — save.php: id ว่าง = เพิ่ม; มี id = แก้ชื่อ/ราคา (เฉพาะคูปองที่ยังไม่ถูกใช้, ชื่อซ้ำคูปองอื่น = ErrorBox)
     * ต่างจากเดิม: ราคาส่วนลดต้องเป็นตัวเลขไม่ติดลบ (Money::adminAmount — เดิมลง DB ดิบ: "1,00"/"abc"/ติดลบ)
     */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        // เดิม nStr($_POST['name']) หลัง POST ถูกแปลง ' → '' แล้ว = ' ในชื่อถูกเก็บเป็น '' (คงไว้ให้ค่าใน DB เท่าเดิม)
        $name = str_replace("'", "''", $this->in($request, 'name'));
        $price = Money::adminAmount($this->in($request, 'price'));
        if ($price === null || str_starts_with($price, '-')) {
            return response(Money::refuse('กรุณาระบุส่วนลดให้ถูกต้อง (ตัวเลข ไม่ติดลบ)'));
        }
        $uid = Legacy::userId();
        $done = fn (int|string $no) => response(Alerts::success(
            'window.location.href = "'.Legacy::href('admin/coupon').'";',
            Lang::get('success_process_no').P::prefix('coupon').Alerts::js(e((string) $no)),
        ));

        if ($id === '') {
            // เดิมไม่ตรวจชื่อซ้ำตอนเพิ่ม (คงไว้ — ดูรายงาน)
            $newId = DB::table('coupon')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'name' => $name, 'price' => $price,
            ]);

            return $done($newId);
        }

        $error = DB::transaction(function () use ($id, $name, $price, $uid) {
            $data = DB::table('coupon')->where('id', $id)->lockForUpdate()->first();
            // เดิม: $data['payment_id'] == '0' — ไม่พบแถว (null == '0') ก็เข้ากิ่งนี้ แต่ UPDATE ไม่โดนแถวใด
            if ($data !== null && (string) $data->payment_id !== '0') {
                return null; // คูปองที่ใช้แล้ว: ไม่แก้ แต่ตอบสำเร็จแบบเดิม
            }
            if (Php74::neq($data->name ?? null, $name)) {
                // เดิม SELECT * FROM coupon WHERE name = '<ชื่อใหม่>' AND id != '<id>' (รวมคูปองที่ลบ/ใช้แล้ว)
                if (DB::table('coupon')->where('name', $name)->where('id', '!=', $id)->exists()) {
                    return 'คูปองส่วนลดซ้ำ';
                }
            }
            if ($data !== null) {
                DB::table('coupon')->where('id', $data->id)->update([
                    'name' => $name, 'price' => $price, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                ]);
            }

            return null;
        });
        if ($error !== null) {
            return response(Alerts::error('CloseLoading()', $error));
        }

        return $done($id);
    }

    /**
     * POST /_admin/_coupon/delete — delete.php เดิมเป็นสำเนาของ _admin/_product_type/delete.php ทั้งไฟล์:
     * ตรวจสิทธิ์ admin_product_type แล้ว UPDATE product_type SET is_delete = 1 WHERE id = <id ของคูปอง>
     * = ปุ่มลบคูปองไปลบ "ประเภทสินค้า" ที่ id ตรงกัน ส่วนคูปองไม่ถูกลบ
     * ที่นี่: ตอบเหมือนเดิม (SuccessBox แล้ว JS โหลด grid ใหม่ — คูปองยังอยู่) แต่ไม่แตะ product_type (บั๊กทำลายข้อมูล) — ดูคำถามในรายงาน
     */
    public function delete(): Response
    {
        return response(Alerts::success());
    }
}
