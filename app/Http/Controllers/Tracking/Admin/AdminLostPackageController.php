<?php

namespace App\Http\Controllers\Tracking\Admin;

use App\Domain\Money\Money;
use App\Domain\Tracking\LostPackageMailer;
use App\Domain\Tracking\Php74;
use App\Domain\Tracking\TrackingLookups;
use App\Http\Controllers\Tracking\LostPackageController;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * แจ้งไม่ได้รับสินค้า (แอดมิน): _admin/_lost_package/*.php เดิม (สิทธิ์ admin_lost_package)
 * หน้า list/form/grid ใช้โค้ดร่วมกับฝั่งลูกค้า (ต่างกันที่ไม่กรองเจ้าของ + มีรหัสลูกค้า)
 */
class AdminLostPackageController extends LostPackageController
{
    protected bool $admin = true;

    /** POST /_admin/_lost_package/cancel — ยกเลิก + แจ้งอีเมล (email_cancel.php) */
    public function adminCancel(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $u = Legacy::user();
        // เดิมยกเลิกได้ทุกสถานะ: ยกเลิกรายการที่อนุมัติ (คืนเงิน) แล้วไม่หักเงินคืน → RollBack → อนุมัติซ้ำ = คืนเงินซ้ำ
        // → ยกเลิกได้เฉพาะรายการที่รอ (1) = ปุ่มในหน้า (ไม่พบรายการ = ไม่ทำอะไรแบบเดิม)
        $result = DB::transaction(function () use ($id, $request, $u) {
            $cur = DB::table('lost_package')->where('id', $id)->lockForUpdate()->first(['id', 'status']);
            if ($cur === null) {
                return null;
            }
            if (! Money::statusIn($cur->status, [Money::PENDING])) {
                return Php74::eq($cur->status, 2)
                    ? 'รายการนี้อนุมัติ (คืนเงิน) แล้ว ยกเลิกไม่ได้ (ต้อง RollBack ก่อน)'
                    : 'รายการนี้ถูกยกเลิกไปแล้ว';
            }
            DB::table('lost_package')->where('id', $cur->id)->update([
                'status' => '-1', 'cancel_remark' => $this->in($request, 'cancel_remark').' ,ยกเลิกโดย '.$u->code,
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $u->id,
            ]);

            return true;
        });
        if (is_string($result)) {
            return response(Money::refuse($result));
        }
        if ($result === true) {
            LostPackageMailer::cancelled($id);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/lost_package').'";'));
    }

    /** POST /_admin/_lost_package/rollback — ยกเลิก → รออนุมัติ / อนุมัติแล้ว → ตั้งยอดคืนเงินกลับ + รออนุมัติ */
    public function rollback(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $uid = Legacy::userId();
        DB::transaction(function () use ($id, $uid) {
            $data = DB::table('lost_package')->leftJoin('bill_product', 'lost_package.order_id', '=', 'bill_product.id')
                ->where('lost_package.id', $id)->lockForUpdate()
                ->first(['lost_package.*', 'bill_product.exchange_rate']);
            if ($data === null) {
                return;
            }
            $prefix = TrackingLookups::prefix('lost_package');
            if (Php74::eq($data->status, '-1')) {
                DB::table('lost_package')->where('id', $id)->update([
                    'status' => '1', 'cancel_remark' => '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                ]);
            }
            if (Php74::eq($data->status, 2)) {
                $bal = DB::table('balance')->where('type', 8)->where('prefix', 26)->where('prefix_ref_id', $data->id)
                    ->where('is_delete', '0')
                    ->where('detail', 'คืนเงิน '.$prefix.$id.' ยอดจำนวน ¥'.$data->cashback.' * '.$data->exchange_rate.'฿')
                    ->orderByDesc('create_date')->first();
                if ($bal !== null) {
                    DB::table('balance')->insert([
                        'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                        'is_delete' => 0, 'user' => $bal->user, 'amount' => '-'.str_replace('-', '', (string) $bal->amount),
                        'detail' => 'Rollback '.$prefix.$id, 'type' => 8, 'prefix' => (string) (TrackingLookups::prefixId('lost_package') ?? ''),
                        'prefix_ref_id' => $id,
                    ]);
                }
                DB::table('lost_package')->where('id', $data->id)->update(['status' => '1']);
            }
        });

        return response(Alerts::success('window.location.reload(); '));
    }

    /** POST /_admin/_lost_package/save_approved — อนุมัติ: บันทึกยอดคืน (¥ × อัตราแลกเปลี่ยนของออเดอร์) เข้า balance + แจ้งอีเมล */
    public function saveApproved(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $uid = Legacy::userId();
        $prefix = TrackingLookups::prefix('lost_package');
        // ยอดคืนติดลบ = หักเงินลูกค้า (เดิมรับได้) → ไม่ยอม
        if (Php74::num($this->php($request, 'cashback')) < 0) {
            return response(Money::refuse('ยอดคืนเงินไม่ถูกต้อง (ห้ามติดลบ)'));
        }
        $approved = DB::transaction(function () use ($id, $uid, $request, $prefix) {
            $cur = DB::table('lost_package')->where('id', $id)->lockForUpdate()->first();
            // เดิม: ไม่พบรายการ (null != 2) ก็ยัง INSERT balance ขยะ user = '' → ที่นี่ข้าม
            if ($cur === null || Php74::eq($cur->status, 2)) {
                return false;
            }
            // เดิม status != 2 = อนุมัติได้ รวมรายการที่ยกเลิกแล้ว (-1): ยกเลิกหลังอนุมัติ (ไม่หักคืน) → RollBack (-1 → 1 ไม่หัก)
            // → อนุมัติซ้ำ = คืนเงินซ้ำ (ทดสอบ e2e 2026-10-18) — อนุมัติได้เฉพาะรายการที่รอ (1)
            if (! Money::statusIn($cur->status, [Money::PENDING])) {
                return 'not_pending';
            }
            DB::table('lost_package')->where('id', $id)->update([
                'status' => '2', 'approved_remark' => $this->in($request, 'approved_remark'),
                'cashback' => $this->in($request, 'cashback'), 'approved_date' => DB::raw('NOW()'),
            ]);
            $data = DB::table('lost_package')->leftJoin('bill_product', 'lost_package.order_id', '=', 'bill_product.id')
                ->where('lost_package.id', $id)->first(['lost_package.*', 'bill_product.exchange_rate']);
            // เดิมคูณใน PHP แล้วต่อเป็น string (precision 14)
            $amount = Php74::str(Php74::num($data->cashback) * Php74::num($data->exchange_rate));
            DB::table('balance')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => $data->user, 'amount' => $amount,
                'detail' => 'คืนเงิน '.$prefix.$id.' ยอดจำนวน ¥'.$data->cashback.' * '.$data->exchange_rate.'฿',
                'type' => 8, 'prefix' => (string) (TrackingLookups::prefixId('lost_package') ?? ''), 'prefix_ref_id' => $id,
            ]);

            return true;
        });
        if ($approved === 'not_pending') {
            return response(Money::refuse('รายการนี้ถูกยกเลิกแล้ว อนุมัติไม่ได้ (ต้อง RollBack ก่อน)'));
        }
        if ($approved === true) {
            LostPackageMailer::approved($id);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/lost_package').'";',
            Lang::get('success_process_no').$prefix.Alerts::js($this->php($request, 'id'))));
    }

    /**
     * /_admin/_lost_package/email_approved, /email_cancel — เดิมเป็นไฟล์ include แต่เปิดตรงได้โดยไม่ต้องล็อกอิน
     * และรับ email_id ผ่าน extract($_GET) = ใครก็สั่งส่งอีเมลหาลูกค้าได้ → ที่นี่ต้องมีสิทธิ์ และไม่ส่งอะไร (ส่งจาก cancel/save_approved เท่านั้น)
     */
    public function emailDirect(): Response
    {
        return response('');
    }
}
