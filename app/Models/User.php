<?php

namespace App\Models;

use App\Models\Base\UserBase;

/**
 * ตาราง `user` — เพิ่ม relation / scope ของโมดูลได้ที่นี่ (คอลัมน์/ค่าพื้นฐานอยู่ใน Base ห้ามแก้)
 * รหัสผ่านเก็บเป็น LegacyCrypt::encrypt(รหัส) (ไม่ใช่ hash) — ห้ามส่ง password ออกไปใน JSON/View
 */
class User extends UserBase
{
    protected $hidden = ['password', 'pin'];

    /** เงื่อนไขเดียวกับ system_check_login.php: ไม่ถูกลบ, เปิดใช้งาน, ไม่ถูกปฏิเสธ */
    public function isActiveAccount(): bool
    {
        return (int) $this->is_delete === 0 && (int) $this->is_enable === 1 && (int) $this->is_approved !== -1;
    }

    /** ตาราง user ไม่มี remember_token */
    public function getRememberTokenName()
    {
        return '';
    }
}
