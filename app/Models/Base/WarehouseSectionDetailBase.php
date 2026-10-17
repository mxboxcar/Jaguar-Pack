<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/WarehouseSectionDetail.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `warehouse_section_detail` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property int $section_id int default '0'
 * @property string $width decimal(10,1) default '0.0'
 * @property string $height decimal(10,1) default '0.0'
 * @property string $long decimal(10,1) default '0.0'
 * @property int $user int default '0'
 * @property int $tracking_id int default '0'
 * @property int|null $warehouse_id int
 * @property string|null $weight decimal(10,1)
 * @property string|null $weight_thai decimal(10,1)
 */
abstract class WarehouseSectionDetailBase extends Model
{
    protected $table = 'warehouse_section_detail';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'section_id', 'width', 'height', 'long', 'user', 'tracking_id', 'warehouse_id', 'weight', 'weight_thai'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
