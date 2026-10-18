<?php

namespace App\Domain\Money;

use App\Support\Legacy\Alerts;
use Illuminate\Support\Facades\DB;

/**
 * กติกาเงินที่ฝั่ง Laravel แก้จากเว็บเดิม (เจ้าของอนุมัติ 2026-10-18) — สถานะที่ยอมให้เปลี่ยน + ยอดสุทธิของรายการในสมุดเงิน
 *
 * สถานะของ refill / bill_transfer / balance_withdraw: 1 = รอดำเนินการ, 2 = สำเร็จ (เติม/โอน/จ่ายแล้ว), -1 = ยกเลิก (คืนเงินแล้ว)
 * ใช้ภายใน DB::transaction() หลัง lockForUpdate() แถวรายการ + แถว user แล้วเท่านั้น
 */
final class Money
{
    public const PENDING = '1';

    public const DONE = '2';

    public const CANCELLED = '-1';

    /** สถานะเป็นค่าใดค่าหนึ่งใน $allowed (เทียบแบบตัวเลขของ DB: '1' / 1) */
    public static function statusIn(mixed $status, array $allowed): bool
    {
        if ($status === null || ! is_numeric((string) $status) || (float) $status != (int) $status) {
            return false;
        }

        return in_array((string) (int) $status, array_map('strval', $allowed), true);
    }

    /** ล็อกแถว user (mutex ของยอดเงินลูกค้า แบบเดียวกับ _transfer/save, Withdraw::submit, save_payment) */
    public static function lockUser(mixed $uid): void
    {
        DB::table('user')->where('id', $uid)->lockForUpdate()->value('id');
    }

    /** ยอดสุทธิของรายการหนึ่งในสมุดเงิน (SUM amount ของ prefix + prefix_ref_id ที่ยังไม่ถูกลบ) */
    public static function net(string $table, mixed $prefixId, mixed $refId, ?callable $scope = null): float
    {
        $q = DB::table($table)->where('prefix', $prefixId)->where('prefix_ref_id', $refId)->where('is_delete', 0);
        if ($scope !== null) {
            $scope($q);
        }

        return round((float) $q->sum('amount'), 2);
    }

    /** ยอดที่คืนได้: เท่าที่รายการถูกตัดอยู่จริง (-ยอดสุทธิ) แต่ไม่เกินยอดของรายการ */
    public static function refundable(float $amount, float $net): float
    {
        return round(max(0.0, min($amount, -$net)), 2);
    }

    /** ยอดเงินคงเหลือของลูกค้า (SUM balance ที่ไม่ถูกลบ) */
    public static function cash(mixed $uid, string $table = 'balance'): float
    {
        return round((float) DB::table($table)->where('user', $uid)->where('is_delete', '0')->sum('amount'), 2);
    }

    /** ตัวเลขเงิน 2 ตำแหน่งแบบที่ลงคอลัมน์ decimal(20,2) */
    public static function fmt(float $v): string
    {
        return number_format(round($v, 2), 2, '.', '');
    }

    /**
     * ยอดเงินที่ลูกค้าแจ้งเติม (_refill/save, _payment/save): ต้องเป็นตัวเลขบวก ทศนิยมไม่เกิน 2 ตำแหน่ง (ลบ , ออกก่อนแบบเดิม)
     * เดิมรับอะไรก็ได้: ติดลบ / 0 / "abc" (MySQL non-strict เก็บเป็น 0)
     */
    public static function validRefillAmount(string $amount): bool
    {
        $a = str_replace(',', '', trim($amount));

        return preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $a) === 1 && (float) $a > 0;
    }

    /** ErrorBox แบบเดิมของหน้าแอดมิน/ลูกค้า (ปิด loading แล้วแจ้ง) */
    public static function refuse(string $text): string
    {
        return Alerts::error('CloseLoading()', $text);
    }
}
