<?php

namespace App\Http\Controllers\MoneyMove\Concerns;

use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;

/**
 * ค่าจากฟอร์มแบบเว็บเดิม (ดู LegacyInput): in() = ค่าที่ลง DB, php() = ค่าในตัวแปร PHP เดิม (' เป็น '')
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

    /** ค่า POST ที่ไม่มี key เลย = null (เดิม undefined index) */
    protected function has(Request $request, string $key): bool
    {
        return array_key_exists($key, $request->request->all());
    }
}
