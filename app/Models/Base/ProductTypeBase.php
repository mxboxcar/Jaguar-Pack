<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/ProductType.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `product_type` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property string|null $name varchar(225)
 * @property string|null $desc varchar(225)
 * @property string|null $kg decimal(20,2)
 * @property string|null $cubic decimal(20,2)
 * @property string|null $amount decimal(20,2)
 * @property int|null $type int
 * @property int|null $is_default int
 * @property string|null $sale_kg decimal(20,2)
 * @property string|null $sale_cubic decimal(20,2)
 * @property string|null $sale_amount decimal(20,2)
 * @property int|null $is_show int
 */
abstract class ProductTypeBase extends Model
{
    protected $table = 'product_type';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'name', 'desc', 'kg', 'cubic', 'amount', 'type', 'is_default', 'sale_kg', 'sale_cubic', 'sale_amount', 'is_show'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
