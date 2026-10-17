<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/UserRateHistory.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `user_rate_history` ของระบบเดิม
 *
 * @property int $id int unsigned
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int $user_id int
 * @property int $actor_id int
 * @property int $old_type int default '0'
 * @property int $new_type int default '0'
 * @property int $old_option tinyint default '0'
 * @property int $new_option tinyint default '0'
 * @property string|null $summary text
 */
abstract class UserRateHistoryBase extends Model
{
    protected $table = 'user_rate_history';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['id', 'create_date', 'user_id', 'actor_id', 'old_type', 'new_type', 'old_option', 'new_option', 'summary'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime,zero',
        ];
    }
}
