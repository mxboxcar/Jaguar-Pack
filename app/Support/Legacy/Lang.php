<?php

namespace App\Support\Legacy;

/**
 * $resource['key'] เดิม (resource/{th,en,cn}.php → lang/{th,en,cn}/legacy.php)
 *
 * พฤติกรรมเหมือนเดิม: key ที่ไม่มีคืน '' (เดิม undefined index → echo ว่าง) ไม่ fallback ข้ามภาษา
 * ใช้ผ่าน helper res('key') หรือ Lang::get('key', 'en')
 */
final class Lang
{
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    public static function get(string $key, ?string $lang = null): string
    {
        $all = self::all($lang);

        return isset($all[$key]) ? (string) $all[$key] : '';
    }

    /** @return array<string, string> */
    public static function all(?string $lang = null): array
    {
        $lang ??= Legacy::lang();
        if (! in_array($lang, Legacy::LANGS, true)) {
            $lang = 'th';
        }
        if (! isset(self::$cache[$lang])) {
            $file = lang_path($lang.'/legacy.php');
            self::$cache[$lang] = is_file($file) ? (array) require $file : [];
        }

        return self::$cache[$lang];
    }
}
