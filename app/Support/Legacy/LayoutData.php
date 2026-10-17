<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * ตัวเลขบน layout/เมนู (เดิมเป็น SQL ฝังใน menu.php / menu_mobile.php)
 * cache ต่อ request: เมนูหลักกับเมนูมือถือเรียกซ้ำ
 */
final class LayoutData
{
    /** @var array<string, mixed> */
    private static array $memo = [];

    public static function flush(): void
    {
        self::$memo = [];
    }

    private static function memo(string $k, callable $fn): mixed
    {
        return array_key_exists($k, self::$memo) ? self::$memo[$k] : (self::$memo[$k] = $fn());
    }

    /** SELECT * FROM notice WHERE is_show = 1 → mysqli_num_rows */
    public static function noticeCount(): int
    {
        return self::memo('notice', fn () => DB::table('notice')->where('is_show', 1)->count());
    }

    /** COUNT แชทที่แอดมินยังไม่อ่าน ('' ถ้าเป็น 0 — เดิมไม่แสดงเลข 0) */
    public static function chatUnreadAdmin(): int
    {
        return self::memo('chat_admin', fn () => DB::table('chat')->where('is_read_admin', '0')->count('id'));
    }

    public static function chatUnreadUser(): int
    {
        return self::memo('chat_user', fn () => DB::table('chat')->where('is_read', '0')->where('to', (string) Legacy::userId())->count('id'));
    }

    public static function goodsconfirmPending(): int
    {
        return self::memo('goodsconfirm', fn () => DB::table('goodsconfirm')->where('status', 0)->count('id'));
    }
}
