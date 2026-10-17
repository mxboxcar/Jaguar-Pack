<?php

namespace App\Support\Legacy;

/**
 * JS ที่ ajax ของเว็บเดิมตอบกลับ (หน้าเว็บ eval() ผล) — getPermissionAlert, getSuccess, getError, getNodataAlert
 * ข้อความเป็น resource (HTML ของเราเอง) ไม่ใช่ input ผู้ใช้ — ถ้าจะใส่ค่าจากผู้ใช้ให้ผ่าน Alerts::js() ก่อน
 */
final class Alerts
{
    /** escape สำหรับ string ใน JS แบบ single quote */
    public static function js(mixed $s): string
    {
        return str_replace(["\\", "'", "\r", "\n", '</'], ["\\\\", "\\'", '\\r', '\\n', '<\\/'], (string) $s);
    }

    /** getPermissionAlert($module) → "ErrorBox('...');" (ajax) */
    public static function permission(string $module): string
    {
        return "ErrorBox('".Lang::get('no_permission').'<br />'.Lang::get('module_pos').Lang::get($module)."');";
    }

    /** getPermissionAlert_Page($module) → <script> ที่เด้งกลับ dashboard (หน้าเต็ม) */
    public static function permissionPage(string $module): string
    {
        return "<script>ErrorBox('".Lang::get('no_permission').'<br />'.Lang::get('module_pos').Lang::get($module)
            ."', 'window.location.href = \"".Legacy::href('dashboard')."\"');</script>";
    }

    public static function noData(string $fn = ''): string
    {
        return $fn === ''
            ? "<script>ErrorBox('".Lang::get('no_data')."');</script>"
            : "<script>ErrorBox('".Lang::get('no_data')."', '$fn');</script>";
    }

    /** getSuccess($fn, $customtext) */
    public static function success(string $fn = '', string $customText = ''): string
    {
        return $fn === ''
            ? "SuccessBox('".$customText."', '".Lang::get('success_process')."');"
            : "SuccessBox('".$customText."','".Lang::get('success_process')."', '$fn');";
    }

    /** getError($fn, $text) — ข้อความ "PIN จ่ายเงินไม่ถูกต้อง" นับเป็นความพยายามผิดใน ips ด้วย (เหมือนเดิม) */
    public static function error(string $fn, string $text): string
    {
        if ($text === 'PIN จ่ายเงินไม่ถูกต้อง') {
            Jail::recordFailure(Legacy::clientIp().'[----]'.Legacy::userAgent(), (string) (Legacy::user()->code ?? ''), 'pin');
        }

        return $fn === '' ? "ErrorBox('".$text."');" : "ErrorBox('".$text."', '$fn');";
    }
}
