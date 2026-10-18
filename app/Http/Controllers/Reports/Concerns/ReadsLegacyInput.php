<?php

namespace App\Http\Controllers\Reports\Concerns;

use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;

/**
 * ค่าจากฟอร์มแบบเว็บเดิม (ดู LegacyInput): in() = ค่าที่ลง DB/ใช้ใน SQL, php() = ค่าในตัวแปร PHP เดิม (' เป็น '')
 * ค่าที่เป็น array (เช่น ส่ง x[]=) ให้เป็น '' — ไฟล์เดิมจะได้คำว่า "Array" ไปต่อ SQL
 */
trait ReadsLegacyInput
{
    protected function in(Request $request, string $key): string
    {
        $v = LegacyInput::post($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }

    protected function php(Request $request, string $key): string
    {
        $v = LegacyInput::postPhp($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }

    /** $_GET[$key] หลัง strip_tags(trim()) ของ system_config.php เดิม */
    protected function get(Request $request, string $key): string
    {
        $v = LegacyInput::query($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }
}
