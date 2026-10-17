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

    /**
     * คอลัมน์ที่ห้าม mass assign (fill/create/update ด้วย array) — กันลูกค้าส่ง role=1 / is_approved=1 / commission
     * มากับฟอร์มแก้โปรไฟล์แล้วได้สิทธิ์แอดมิน ต้องการตั้งค่าเหล่านี้ให้กำหนดทีละตัว: $user->role = ...; $user->save();
     */
    protected $guarded = [
        'id', 'username', 'password', 'pin', 'code', 'role', 'type', 'is_enable', 'is_approved', 'approved_date',
        'is_delete', 'option_price', 'diff', 'commission', 'line_uid', 'create_by', 'create_date', 'mod_by', 'mod_date',
    ];

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
