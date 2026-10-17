<?php

namespace App\Domain\Order;

/**
 * escape ค่าจาก DB/ผู้ใช้ที่ไฟล์เดิม echo ดิบ (stored XSS) — ไม่ encode ซ้ำ entity ที่มีอยู่แล้ว
 * (ชื่อสินค้าจาก taobao มัก มี &amp; อยู่ใน DB → หน้าเดิมแสดงเป็น & — ถ้า double encode จะเห็น "&amp;")
 */
final class Html
{
    public static function h(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }

    /** nl2br(ค่าดิบ) เดิม → escape ก่อนแล้วค่อย nl2br */
    public static function nl2br(mixed $v): string
    {
        return nl2br(self::h($v));
    }

    /** ค่าที่เดิมฝังใน string JS แบบ '...' (เช่น var x = '<?php echo $div ?>') */
    public static function js(mixed $v): string
    {
        return str_replace(['\\', "'", "\r", "\n", '</', '"'], ['\\\\', "\\'", '\\r', '\\n', '<\\/', '\\x22'], (string) $v);
    }
}
