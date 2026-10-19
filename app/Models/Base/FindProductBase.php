<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/FindProduct.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `find_product` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property int|null $status int default '0'
 * @property int|null $type int default '0'
 * @property string|null $name varchar(225)
 * @property string|null $package varchar(225)
 * @property string|null $category varchar(225)
 * @property string|null $capacity varchar(225)
 * @property string|null $brand varchar(225)
 * @property string|null $material varchar(225)
 * @property string|null $product_code varchar(225)
 * @property string|null $color varchar(225)
 * @property string|null $size varchar(225)
 * @property string|null $weight varchar(225)
 * @property string|null $qty varchar(225)
 * @property string|null $price varchar(225)
 * @property string|null $remark text
 */
abstract class FindProductBase extends Model
{
    protected $table = 'find_product';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'status', 'type', 'name', 'package', 'category', 'capacity', 'brand', 'material', 'product_code', 'color', 'size', 'weight', 'qty', 'price', 'remark'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
