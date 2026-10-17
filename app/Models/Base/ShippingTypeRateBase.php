<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/ShippingTypeRate.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `shipping_type_rate` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property int|null $shipping_type_id int
 * @property int|null $type int
 * @property string|null $min decimal(20,4) default '0.0000'
 * @property string|null $max decimal(20,4) default '0.0000'
 * @property string|null $value decimal(20,2) default '0.00'
 */
abstract class ShippingTypeRateBase extends Model
{
    protected $table = 'shipping_type_rate';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'shipping_type_id', 'type', 'min', 'max', 'value'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
