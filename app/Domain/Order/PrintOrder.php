<?php

namespace App\Domain\Order;

use App\Support\Legacy\Lang;

/**
 * helper ของหน้า "พิมพ์รายการสั่งซื้อ" (SR เท่านั้น: _admin/_printorder/*)
 */
final class PrintOrder
{
    /** getstatusprint() ของ system_status_function.php (SR) — switch แบบ == ของ 7.4 */
    public static function getstatusprint(mixed $value): string
    {
        if (Php74::eq($value, '0')) {
            return "<span class='label label-danger'>".Lang::get('unalready_print').'</span>';
        }
        if (Php74::eq($value, '1')) {
            return "<span class='label label-success'>".Lang::get('already_print').'</span>';
        }

        return 'N/A';
    }

    /** เลขที่ใบรายการสั่งซื้อ: ($run_id == '0' ? 'ORD-'.$id : $run_id) — prefix 'ORD-' ตายตัวในไฟล์เดิม */
    public static function runNo(?string $runId, mixed $id): string
    {
        return Php74::eq($runId, '0') ? 'ORD-'.$id : (string) $runId;
    }
}
