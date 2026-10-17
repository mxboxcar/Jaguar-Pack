<?php

namespace App\Auth;

use App\Models\User;
use App\Support\Legacy\LegacyCrypt;
use App\Support\Legacy\LegacyInput;

/**
 * ตรวจรหัสผ่านแบบ system_check_login.php เดิมทุกกรณี
 *
 * SELECT * FROM user WHERE username = '$username' AND is_delete = 0 AND password = encrypt($password)
 *   AND is_enable = '1' AND is_approved != '-1'
 *
 * ค่า $username/$password ในโค้ดเดิมผ่าน addslashes_mssql (' → '') แล้วตัด \ ทิ้ง:
 *  - username ถูกฝังใน SQL → MySQL อ่าน '' กลับเป็น ' → ค่าที่เทียบจริง = ค่าดิบที่ตัด \ ออก
 *  - password ถูก encrypt() ในฝั่ง PHP ก่อน → เข้ารหัสค่าที่ ' กลายเป็น '' แล้ว (ต้องทำเหมือนกันไม่งั้นรหัสที่มี ' จะเข้าไม่ได้)
 * การเทียบทำใน SQL เหมือนเดิม (collation utf8_general_ci ไม่สนตัวพิมพ์/ช่องว่างท้าย — ผลเท่าเดิม)
 */
final class LegacyLogin
{
    public static function findByCredentials(string $rawUsername, string $rawPassword): ?User
    {
        $username = LegacyInput::sqlValue($rawUsername);
        $password = LegacyInput::phpValue($rawPassword);
        if ($username === '' || $password === '') {
            return null;
        }

        return User::query()
            ->where('username', $username)
            ->where('is_delete', 0)
            ->where('password', LegacyCrypt::encrypt($password))
            ->where('is_enable', '1')
            ->where('is_approved', '!=', '-1')
            ->first();
    }
}
