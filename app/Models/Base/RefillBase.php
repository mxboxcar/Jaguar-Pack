<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/Refill.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `refill` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int default '0'
 * @property int $id int
 * @property int|null $status int
 * @property int|null $user int
 * @property string|null $amount decimal(20,2)
 * @property \Carbon\CarbonImmutable|null $date datetime
 * @property string|null $attachment varchar(45)
 * @property string|null $remark varchar(225)
 * @property string|null $approved_remark varchar(225)
 * @property \Carbon\CarbonImmutable|null $approved_date datetime
 * @property string|null $cancel_remark varchar(225)
 * @property int|null $approved_user int default '0'
 */
abstract class RefillBase extends Model
{
    protected $table = 'refill';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'status', 'user', 'amount', 'date', 'attachment', 'remark', 'approved_remark', 'approved_date', 'cancel_remark', 'approved_user'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'date' => LegacyDateTime::class.':datetime',
            'approved_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
