<?php

namespace App\Domain\Messaging;

/**
 * ข้อความแชท (chat.text) ที่แสดงในหน้า message / admin message
 *
 * เดิม echo ข้อความดิบลง HTML = stored XSS (ลูกค้าส่ง <script> ไปรันในหน้าแอดมินได้)
 * แต่ปุ่มแนบรูปของหน้าเดิมส่ง HTML มาเป็นข้อความเอง:
 *   <a class="fancybox" target="_blank" href="URL"><img src="URL" style="max-width: 120px;max-height: 120px;"></a>
 * → ยอมเฉพาะรูปแบบนั้นแบบตรงตัว (URL http/https ไม่มีอักขระพิเศษ) นอกนั้น escape ทั้งหมด
 */
final class ChatText
{
    private const IMAGE = '~^<a class="fancybox" target="_blank" href="(https?://[^"\'<>\s`]+)"><img src="(https?://[^"\'<>\s`]+)" style="max-width: 120px;max-height: 120px;"></a>$~';

    public static function render(mixed $text): string
    {
        $text = (string) $text;
        if (preg_match(self::IMAGE, $text, $m) && $m[1] === $m[2]) {
            return $text;
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
    }

    /** ค่าจาก DB ที่ฝังใน HTML (ชื่อแผนก, รหัสลูกค้า) — เดิม echo ดิบ */
    public static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
    }
}
