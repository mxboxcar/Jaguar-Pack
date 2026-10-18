<?php

namespace App\Http\Controllers\Messaging\Concerns;

use App\Support\Legacy\LegacyInput;
use Illuminate\Http\Request;

/**
 * ค่าจากฟอร์มแบบเว็บเดิม (ดู LegacyInput): in() = ค่าที่ลง DB (ใช้ bind), php() = ค่าในตัวแปร PHP เดิม (' เป็น '')
 * ค่าที่เป็น array ให้เป็น '' (เดิมจะได้คำว่า "Array"); php() ให้ $default (null) เมื่อไม่มี key = undefined index เดิม
 */
trait ReadsLegacyInput
{
    protected function in(Request $request, string $key): string
    {
        $v = LegacyInput::post($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }

    protected function php(Request $request, string $key, ?string $default = ''): ?string
    {
        if (! array_key_exists($key, $request->request->all())) {
            return $default;
        }
        $v = LegacyInput::postPhp($request, $key);

        return is_scalar($v) ? (string) $v : '';
    }
}
