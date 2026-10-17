<?php

namespace App\Support\Legacy;

/**
 * ป้ายสถานะ (HTML) ของ system_status_function.php เดิม — คืน HTML เดิมทุกตัวอักษร
 * ค่าที่ไม่รู้จัก → "N/A" (ยกเว้น getShipType/getShippingTypeName ที่เดิมคืน null)
 */
final class Status
{
    /** @param array<string, array{0:string,1:string}> $map value => [css class, resource key] */
    private static function label(mixed $value, array $map): string
    {
        // switch เดิมเทียบแบบหลวม: '0' กับ 0 กับ '' (ว่าง → ไม่ตรง '0' ใน PHP 7.4? '' == '0' เป็น false)
        $key = is_bool($value) ? (string) (int) $value : (string) $value;
        foreach ($map as $k => [$css, $res]) {
            if ($key === (string) $k || (is_numeric($key) && is_numeric((string) $k) && (float) $key == (float) $k)) {
                return "<span class='label ".$css."'>".Lang::get($res).'</span>';
            }
        }

        return 'N/A';
    }

    public static function getlotstatus(mixed $v): string
    {
        return self::label($v, ['0' => ['label-danger', 'date_in'], '1' => ['label-info', 'date_out'], '2' => ['label-success', 'date_arrive']]);
    }

    public static function getstatusgoods(mixed $v): string
    {
        return self::label($v, [
            '-1' => ['label-inverse', 'cancel'], '0' => ['label-danger', 'no_owner'], '1' => ['label-info', 'wait_for_approve'],
            '2' => ['label-success', 'paid'], '3' => ['label-success', 'export_complete'],
        ]);
    }

    public static function getstatus(mixed $v): string
    {
        return self::label($v, [
            '-1' => ['label-inverse', 'cancel'], '0' => ['label-danger', 'no_paid'], '1' => ['label-info', 'wait_for_approve'],
            '2' => ['label-success', 'paid'], '3' => ['label-success', 'export_complete'],
        ]);
    }

    public static function getstatusexport(mixed $v): string
    {
        return self::label($v, [
            '-1' => ['label-inverse', 'cancel'], '0' => ['label-danger', 'no_paid'], '1' => ['label-info', 'wait_for_approve'],
            '2' => ['label-danger', 'wait_for_export'], '3' => ['label-success', 'export_complete'],
        ]);
    }

    public static function getstatusorder(mixed $v): string
    {
        return self::label($v, [
            '-1' => ['label-inverse', 'cancel'], '0' => ['label-info', 'order_status_0'], '1' => ['label-danger', 'order_status_1'],
            '2' => ['label-info', 'order_status_2'], '3' => ['label-success', 'order_status_3'],
        ]);
    }

    public static function getstatususer(mixed $v): string
    {
        return self::label($v, ['-1' => ['label-inverse', 'cancel'], '0' => ['label-info', 'wait_for_approve'], '1' => ['label-danger', 'approveddd']]);
    }

    public static function getsender(mixed $v): string
    {
        return match ((string) $v) {
            '0' => (string) config('legacy.project_name'),
            '1' => Lang::get('write_by_user'),
            '2' => Lang::get('no_show_up'),
            default => 'N/A',
        };
    }

    /** เดิม switch แบบหลวม: 0 == '' == null → รถ */
    public static function getShipType(mixed $v, bool $isDiv = true): ?string
    {
        $n = ($v === null || $v === '' || (is_numeric($v) && (float) $v == 0)) ? 0 : ((is_numeric($v) && (float) $v == 1) ? 1 : null);
        if ($n === null) {
            return null;
        }
        if (! $isDiv) {
            return Lang::get($n === 0 ? 'car' : 'ship');
        }

        return $n === 0
            ? '<span class="label label-inverse">'.Lang::get('car').'</span>'
            : '<span class="label label-danger">'.Lang::get('ship').'</span>';
    }

    public static function getShippingTypeName(mixed $v): ?string
    {
        $n = ($v === null || $v === '') ? 0 : (is_numeric($v) ? (float) $v : null);

        return match (true) {
            $n === 0 || $n === 0.0 => Lang::get('use_by_fixed'),
            $n == 1 => Lang::get('use_by_calculate_kg'),
            $n == 2 => Lang::get('use_by_calculate_cubic'),
            default => null,
        };
    }
}
