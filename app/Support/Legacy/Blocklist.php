<?php

namespace App\Support\Legacy;

use Normalizer;

/**
 * ชื่อต้องห้าม + บัญชีดำมิจฉาชีพ ของ system_status_function.php เดิม (ย้ายมาเหมือนเดิมทุกขั้น)
 * รายชื่อบัญชีดำ: ไฟล์ system_blocklist.php (config legacy.blocklist_file) — ไฟล์สร้างจากเครื่อง vr
 */
final class Blocklist
{
    private const BLOCKED_WORDS = ['admin', 'administrator', 'root', 'moderator', 'superuser', 'packcargo', 'pack'];

    /** @var array<string, array{name:string, reason:string}>|null */
    private static ?array $list = null;

    public static function normalizeText(string $input): string
    {
        if (class_exists(Normalizer::class)) {
            $input = Normalizer::normalize($input, Normalizer::FORM_KD) ?: $input;
        }
        $input = (string) preg_replace('/\p{Mn}+/u', '', $input);
        $input = (string) preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}\x{FEFF}]/u', '', $input);
        $s = mb_strtolower($input, 'UTF-8');
        $map = [
            '4' => 'a', '@' => 'a', '^' => 'a', 'α' => 'a', 'а' => 'a', 'ä' => 'a',
            '8' => 'b', 'ß' => 'b', 'в' => 'b',
            '(' => 'c', '{' => 'c', '[' => 'c', '<' => 'c', '¢' => 'c', 'с' => 'c',
            'đ' => 'd', 'ԁ' => 'd',
            '3' => 'e', '€' => 'e', 'е' => 'e', 'ë' => 'e',
            '6' => 'g', '9' => 'g',
            '1' => 'i', '!' => 'i', '|' => 'i', '¡' => 'i', 'l' => 'i', 'ł' => 'i',
            'í' => 'i', 'ι' => 'i', 'і' => 'i', '¦' => 'i',
            'м' => 'm', 'rn' => 'm', 'nn' => 'm',
            'и' => 'n', 'п' => 'n', 'ñ' => 'n', 'ᴎ' => 'n',
            '0' => 'o', 'ø' => 'o', '°' => 'o', 'о' => 'o', 'σ' => 'o',
            '5' => 's', '$' => 's', 'ѕ' => 's',
            '7' => 't', '+' => 't', 'т' => 't',
            'µ' => 'u', 'ц' => 'u',
            '2' => 'z',
        ];
        $s = strtr($s, $map);
        $s = (string) preg_replace('/[^a-z]/', '', $s);
        $s = str_replace('rn', 'm', $s);

        return (string) preg_replace('/(.)\1+/', '$1', $s);
    }

    public static function isBlockedName(string $input): bool
    {
        $s = self::normalizeText($input);
        if ($s === '') {
            return false;
        }
        foreach (self::BLOCKED_WORDS as $word) {
            $w = self::normalizeText($word);
            if ($w === '') {
                continue;
            }
            if (strlen($w) <= 4) {
                if ($s === $w) {
                    return true;
                }

                continue;
            }
            if (str_contains($s, $w)) {
                return true;
            }
            $n = strlen($w);
            $h = strlen($s);
            for ($len = max(1, $n - 1); $len <= $n + 1; $len++) {
                for ($i = 0; $i + $len <= $h; $i++) {
                    if (levenshtein(substr($s, $i, $len), $w) <= 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function normalizePersonName(mixed $s): string
    {
        $s = trim((string) $s);
        $s = str_replace(["\xE2\x80\x8B", "\xE2\x80\x8C", "\xE2\x80\x8D", "\xEF\xBB\xBF", "\xC2\xA0"], '', $s);
        $titles = ['นางสาว', 'น.ส.', 'นส.', 'นาย', 'นาง', 'ด.ช.', 'ด.ญ.', 'ดร.', 'คุณ', 'mr.', 'mrs.', 'ms.', 'miss', 'mr', 'mrs', 'ms'];
        foreach ($titles as $t) {
            if (mb_strpos($s, $t) === 0) {
                $s = trim(mb_substr($s, mb_strlen($t)));
                break;
            }
        }
        $s = (string) preg_replace('/\s+/u', '', $s);

        return mb_strtolower($s, 'UTF-8');
    }

    /** @return array{name:string, reason:string}|false */
    public static function blockedPersonReason(mixed $name): array|false
    {
        if (self::$list === null) {
            self::$list = [];
            $f = (string) config('legacy.blocklist_file');
            if ($f !== '' && @is_readable($f)) {
                $BLOCKED_PERSONS = [];
                include $f;
                if (is_array($BLOCKED_PERSONS)) {
                    self::$list = $BLOCKED_PERSONS;
                }
            }
        }
        $key = self::normalizePersonName($name);

        return ($key === '' || ! isset(self::$list[$key])) ? false : self::$list[$key];
    }

    /** blocklist_guard($name, $where, $extra): โดน → บันทึก log + คืน array, ผ่าน → false */
    public static function guard(mixed $name, string $where, string $extra = ''): array|false
    {
        $hit = self::blockedPersonReason($name);
        if ($hit === false) {
            return false;
        }
        $clean = fn ($v) => str_replace(["\t", "\n"], ' ', (string) $v);
        $line = date('Y-m-d H:i:s')."\t".$where."\tกรอก=".$clean($name)."\tตรงกับ=".$hit['name']
            ."\tuser=".(Legacy::userId() ?? '')."\tip=".Legacy::clientIp()."\t".$clean($extra)."\n";
        $log = (string) config('legacy.blocklist_hit_log');
        if ($log !== '') {
            @mkdir(dirname($log), 02750, true);
            @file_put_contents($log, $line, FILE_APPEND | LOCK_EX);
        }

        return $hit;
    }
}
