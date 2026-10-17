<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/Lot.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `lot` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property string|null $name varchar(150)
 * @property int|null $type int
 * @property int|null $status int
 * @property \Carbon\CarbonImmutable|null $date_in datetime
 * @property \Carbon\CarbonImmutable|null $date_out datetime
 * @property \Carbon\CarbonImmutable|null $date_arrive datetime
 * @property \Carbon\CarbonImmutable|null $date_est datetime
 * @property string|null $name_admin text
 * @property string|null $attachment varchar(50)
 * @property string|null $attachment_name varchar(225)
 * @property int|null $is_print int
 * @property int|null $is_mail int
 * @property int|null $shipping_amount int
 * @property int|null $is_verified int
 */
abstract class LotBase extends Model
{
    protected $table = 'lot';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'name', 'type', 'status', 'date_in', 'date_out', 'date_arrive', 'date_est', 'name_admin', 'attachment', 'attachment_name', 'is_print', 'is_mail', 'shipping_amount', 'is_verified'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'date_in' => LegacyDateTime::class.':datetime',
            'date_out' => LegacyDateTime::class.':datetime',
            'date_arrive' => LegacyDateTime::class.':datetime',
            'date_est' => LegacyDateTime::class.':datetime',
        ];
    }
}
