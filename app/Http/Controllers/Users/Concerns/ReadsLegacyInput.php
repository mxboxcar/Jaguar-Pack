<?php

namespace App\Http\Controllers\Users\Concerns;

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

    /** ค่า GET แบบเดิม: strip_tags(trim()) (ค่าที่ไม่ใช่ string = '') */
    protected function get(Request $request, string $key): string
    {
        $v = $request->query->all()[$key] ?? '';

        return is_scalar($v) ? strip_tags(trim((string) $v)) : '';
    }

    /**
     * MakePaging() เดิม: LIMIT (10*page - 10), 10 โดย page เป็น string จาก POST (7.4: "2abc" → 2, "abc" → 0)
     * ผลติดลบ/ไม่ใช่จำนวนเต็ม = เดิม SQL error (ไม่มีแถว) → null
     */
    protected function pageOffset(string $page): ?int
    {
        $start = \App\Domain\Users\Php74::num($page) * 10 - 10;
        if ($start < 0 || (float) $start !== floor((float) $start)) {
            return null;
        }

        return (int) $start;
    }
}
