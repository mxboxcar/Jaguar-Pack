<?php

namespace App\Domain\Messaging;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lookup;

/**
 * ข้อความประกาศ (notice.text) — CKEditor ของแอดมิน แล้ว JS htmlEscape() ก่อนส่ง → ใน DB เป็น HTML ที่ถูก entity-encode
 * เป็น HTML ที่แอดมินเขียนเอง (ตั้งใจให้แสดงเป็น HTML) จึงแสดงแบบเดิม
 */
final class NoticeText
{
    private static ?string $prefix = null;

    /** html_entity_decode() ของ PHP 7.4 (ไม่ถอด &#039;) */
    public static function decode(mixed $text): string
    {
        return html_entity_decode((string) $text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }

    /** ข้อความย่อในตาราง: substr(strip_tags(html_entity_decode($text)), 0, 400) — ตัดเป็น byte แบบเดิม */
    public static function excerpt(mixed $text): string
    {
        return substr(strip_tags(self::decode($text)), 0, 400);
    }

    /** getPrefix('notice') — เดิมเรียก query ทุกแถว (N+1) ที่นี่จำไว้ต่อ request */
    public static function prefix(): string
    {
        return self::$prefix ??= (string) Lookup::getPrefix('notice');
    }

    public static function flush(): void
    {
        self::$prefix = null;
    }

    /** หัวข้ออีเมล/หน้ารายละเอียด: "แจ้งข่าวประกาศ วันที่ " + getthaidate(วันที่, true, true) */
    public static function subject(mixed $createDate): string
    {
        $exp = explode(' ', (string) $createDate);

        return 'แจ้งข่าวประกาศ วันที่ '.Format::getthaidate($exp[0], true, true);
    }
}
