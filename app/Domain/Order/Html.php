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

    /**
     * ค่าใน string JS '...' ที่อยู่ใน attribute HTML (onclick="f('x')") — escape แบบ JS แล้ว escape แบบ HTML โดย encode ซ้ำเสมอ
     * (h() ไม่ encode entity ซ้ำ: ค่า "&#39;" จะถูก browser ถอดเป็น ' ใน attribute ก่อนรัน JS = หลุดออกจาก string ได้)
     */
    public static function jsAttr(mixed $v): string
    {
        return htmlspecialchars(self::js($v), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', true);
    }

    /**
     * ค่าใน '...' ที่อยู่ภายใน string JS "..." ซึ่งถูก eval() ภายหลัง (fn_public = "loadAjaxGrid('a', 'x')") — escape JS สองชั้น
     */
    public static function jsNested(mixed $v): string
    {
        return self::js(self::js($v));
    }

    /**
     * ค่าจาก DB ที่ใช้เป็น href (url สินค้า/รูป ที่ลูกค้ากรอกตอนสั่งซื้อ): escape แบบ h() และตัด scheme ที่รันสคริปต์ได้
     * (javascript:/vbscript:/data: → "#" — ตรวจหลังถอด entity เพราะ h() ไม่ encode entity ซ้ำ)
     */
    public static function url(mixed $v): string
    {
        $h = self::h($v);
        $probe = strtolower((string) preg_replace('/[\x00-\x20]+/', '', html_entity_decode($h, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return preg_match('/^(javascript|vbscript|data):/', $probe) ? '#' : $h;
    }
}
