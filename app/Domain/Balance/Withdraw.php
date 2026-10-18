<?php

namespace App\Domain\Balance;

use App\Domain\Money\Money;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Lookup;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * _balance/save_withdraw.php (ถอนเงินบาท) และ _balance_cn/save_withdraw.php (แลกหยวนเป็นบาท)
 *
 * เดิม: ตรวจยอดแล้ว INSERT 2 ตารางแยกกัน ไม่มี transaction และไม่ล็อก → กดซ้อนกันเร็วๆ ถอนเกินยอดได้
 * ใหม่: ห่อ DB::transaction() + ล็อกแถว user (FOR UPDATE) แล้วตรวจยอดภายใน transaction
 */
final class Withdraw
{
    /** เดิม: str_replace('-', ''), ' ', "\t" */
    public static function cleanAmount(string $v): string
    {
        return str_replace(['-', ' ', "\t"], '', $v);
    }

    /**
     * cleanAmount() + ตัวคั่นหลักพัน: "1,000.50" → "1000.50", มี , แบบอื่น ("1,00") → null (ปฏิเสธ)
     * เดิม "1,00" ลง DB เป็น 1.00 เงียบๆ (MySQL ตัดที่ ,) และ "1,000" = 1.00 ด้วย — ช่องนี้ JS เดิมเติมยอดดิบไม่มี ,
     */
    public static function amount(string $v): ?string
    {
        $v = self::cleanAmount($v);
        if (Money::ambiguousComma($v)) {
            return null;
        }

        return str_replace(',', '', $v);
    }

    /**
     * เงื่อนไขเดิม (เทียบแบบ PHP 7.4):
     *   if($wantcash <= 0) error
     *   if($wantcash > getCurrentCash() || $wantcash == "0.00" || $wantcash == "0" || $wantcash == 0) error
     */
    public static function invalid(string $wantPhp, mixed $current): bool
    {
        if (Php74::cmp($wantPhp, 0) <= 0) {
            return true;
        }

        return Php74::cmp($wantPhp, $current) > 0
            || Php74::eq($wantPhp, '0.00') || Php74::eq($wantPhp, '0') || Php74::eq($wantPhp, 0);
    }

    public static function errorJs(): string
    {
        return "ErrorBox('".Lang::get('error_usingcash')."', 'CloseLoading()'); ";
    }

    /**
     * ตรวจยอด + INSERT คำขอถอน/แลก และรายการหักเงิน ใน transaction เดียว
     *
     * @param  Closure(): mixed  $current  ยอดเงินปัจจุบัน (getCurrentCash / getCurrentCashCN)
     * @return bool false = ยอดไม่พอ/จำนวนไม่ถูกต้อง (ไม่ได้บันทึก)
     */
    public static function submit(int $uid, string $wantPhp, string $wantSql, Closure $current, array $bank, string $withdrawTable,
        string $ledgerTable, string $prefixModule, string $detailText, int $type): bool
    {
        return DB::transaction(function () use ($uid, $wantPhp, $wantSql, $current, $bank, $withdrawTable, $ledgerTable, $prefixModule, $detailText, $type) {
            DB::table('user')->where('id', $uid)->lockForUpdate()->value('id');
            if (self::invalid($wantPhp, $current())) {
                return false;
            }
            $id = DB::table($withdrawTable)->insertGetId([
                'create_date' => DB::raw('NOW()'),
                'create_by' => $uid,
                'mod_date' => DB::raw('NOW()'),
                'mod_by' => $uid,
                'is_delete' => 0,
                'status' => '1',
                'user' => $uid,
                'amount' => $wantSql,
                'date' => DB::raw('NOW()'),
                'attachment' => '',
                'remark' => '',
                'approved_remark' => '',
                'branch_no' => $bank[0],
                'branch_bank' => $bank[1],
                'branch_name' => $bank[2],
            ]);
            DB::table($ledgerTable)->insert([
                'create_date' => DB::raw('NOW()'),
                'create_by' => $uid,
                'mod_date' => DB::raw('NOW()'),
                'mod_by' => $uid,
                'is_delete' => 0,
                'user' => $uid,
                'amount' => '-'.$wantSql,
                'detail' => $detailText.' '.Ledger::prefixText($prefixModule).$id,
                'type' => $type,
                'prefix' => Ledger::prefixId($prefixModule),
                'prefix_ref_id' => (string) $id,
            ]);

            return true;
        });
    }

    public static function currentCash(int $uid): mixed
    {
        return Lookup::getCurrentCash($uid);
    }

    public static function currentCashCn(int $uid): mixed
    {
        return Lookup::getCurrentCashCN($uid);
    }
}
