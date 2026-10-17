<?php

namespace App\Support\Legacy;

use phpseclib3\Crypt\Rijndael;
use RuntimeException;

/**
 * encrypt()/decrypt() ของ system_config.php เดิม ให้ได้ผลตรงกันแบบ byte ต่อ byte
 *
 * เดิม: trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $text, MCRYPT_MODE_ECB, iv)))
 *  - Rijndael block 256 บิต (ไม่ใช่ AES), key = salt ตามความยาวจริง (16 ไบต์ = key 128 บิต)
 *  - ECB (iv ไม่มีผล), mcrypt เติม \0 ให้ครบ block (ไม่ใช่ PKCS7) — ข้อความว่างก็ได้ 1 block
 *  - ถอดแล้ว trim() ตัด \0 และช่องว่างหัวท้าย
 * ใช้ทั้ง session (user_id/user_role) และรหัสผ่านในตาราง user (เก็บเป็น encrypt(รหัส))
 */
final class LegacyCrypt
{
    private const BLOCK = 32;

    private static ?Rijndael $cipher = null;

    private static ?string $cipherKey = null;

    public static function encrypt(string $text, ?string $salt = null): string
    {
        $len = strlen($text);
        $padded = $len === 0 ? self::BLOCK : (int) (ceil($len / self::BLOCK) * self::BLOCK);
        $text = str_pad($text, $padded, "\0");

        return trim(base64_encode(self::cipher($salt)->encrypt($text)));
    }

    public static function decrypt(?string $text, ?string $salt = null): string
    {
        $raw = base64_decode((string) $text);
        if ($raw === false || $raw === '') {
            return '';
        }
        // mcrypt เติม \0 ให้ ciphertext ที่ไม่ครบ block ด้วย (ข้อมูลเสียก็ยังถอดได้ ได้ขยะออกมา)
        $rem = strlen($raw) % self::BLOCK;
        if ($rem !== 0) {
            $raw .= str_repeat("\0", self::BLOCK - $rem);
        }

        return trim(self::cipher($salt)->decrypt($raw));
    }

    private static function cipher(?string $salt): Rijndael
    {
        $salt ??= (string) config('legacy.session.salt');
        if ($salt === '') {
            throw new RuntimeException('LEGACY_SESSION_SALT ไม่ได้ตั้งค่าใน .env');
        }
        if (self::$cipher === null || self::$cipherKey !== $salt) {
            $c = new Rijndael('ecb');
            $c->setBlockLength(256);
            $c->setKeyLength(self::keyBits($salt));
            $c->setKey(self::normalizeKey($salt));
            $c->disablePadding();
            self::$cipher = $c;
            self::$cipherKey = $salt;
        }

        return self::$cipher;
    }

    /** mcrypt: key ยาว 16/24/32 ใช้ตามนั้น, ความยาวอื่นเติม \0 ให้ถึงขนาดถัดไป (เกิน 32 ตัดทิ้ง) */
    private static function normalizeKey(string $salt): string
    {
        $bits = self::keyBits($salt);

        return substr(str_pad($salt, $bits / 8, "\0"), 0, $bits / 8);
    }

    private static function keyBits(string $salt): int
    {
        $n = strlen($salt);

        return $n <= 16 ? 128 : ($n <= 24 ? 192 : 256);
    }
}
