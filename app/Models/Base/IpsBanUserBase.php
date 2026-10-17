<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/IpsBanUser.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `ips_ban_user` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $unban_date datetime
 * @property int $id int
 * @property int|null $user int
 * @property int|null $time int
 * @property int|null $is_clear int default '0'
 */
abstract class IpsBanUserBase extends Model
{
    protected $table = 'ips_ban_user';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['unban_date', 'id', 'user', 'time', 'is_clear'];

    protected function casts(): array
    {
        return [
            'unban_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
