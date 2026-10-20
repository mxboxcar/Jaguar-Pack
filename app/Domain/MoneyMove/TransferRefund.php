<?php

namespace App\Domain\MoneyMove;

use App\Domain\Money\Money;
use Illuminate\Support\Facades\DB;

/** เงินคืนของรายการโอนเงินไปจีน (bill_transfer) — SR มีสองกระเป๋า: usecash (บาท, balance) + usecash_cn (หยวน, balance_cn) */
final class TransferRefund
{
    /**
     * คืนเงินของรายการโอนที่ยกเลิก (ใช้ร่วม _transfer/cancel และ _admin/_transfer/cancel) — เรียกภายใน transaction หลัง lock แถวรายการ + user
     * ต่อกระเป๋า: คืน = min(ยอดของรายการ, -ยอดสุทธิของรายการในกระเป๋านั้น) → ไม่คืนเกินที่ถูกตัด, กดซ้ำ/ส่งพร้อมกันไม่คืนซ้ำ
     */
    public static function refund(object $row, mixed $by, mixed $owner): void
    {
        $pid = Lookups::prefixId('transfer');
        foreach (['balance' => $row->usecash, 'balance_cn' => $row->usecash_cn ?? null] as $table => $amount) {
            $refund = Money::refundable((float) $amount, Money::net($table, $pid, $row->id));
            if ($refund <= 0) {
                continue;
            }
            DB::table($table)->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $by, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $by, 'is_delete' => 0,
                'user' => $owner, 'amount' => Money::fmt($refund), 'detail' => 'ยกเลิกรายการ '.Lookups::prefix('transfer').$row->id, 'type' => 5,
                'prefix' => (string) ($pid ?? ''), 'prefix_ref_id' => $row->id,
            ]);
        }
    }
}
