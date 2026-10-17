<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/Mall.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `mall` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property string $name varchar(225)
 * @property string $image varchar(225)
 * @property string $tracking varchar(225)
 * @property string|null $code varchar(100)
 * @property int|null $category_id int
 * @property string|null $weight decimal(10,2)
 * @property string|null $width decimal(10,2)
 * @property string|null $height decimal(10,2)
 * @property string|null $long decimal(10,2)
 * @property string $detail text
 * @property string $desc text
 * @property int|null $is_show int default '0'
 */
abstract class MallBase extends Model
{
    protected $table = 'mall';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'name', 'image', 'tracking', 'code', 'category_id', 'weight', 'width', 'height', 'long', 'detail', 'desc', 'is_show'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
