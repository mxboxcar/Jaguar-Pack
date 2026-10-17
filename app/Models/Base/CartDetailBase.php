<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/CartDetail.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `cart_detail` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property int|null $cart_id int
 * @property string|null $url text
 * @property string|null $product_name text
 * @property string|null $shop_name text
 * @property string|null $main_image text
 * @property string|null $price varchar(50)
 * @property string|null $color text
 * @property string|null $amount varchar(50)
 * @property string|null $remark text
 * @property string|null $upload_file varchar(50)
 * @property string|null $size text
 * @property int|null $user int
 * @property int|null $is_craft int default '0'
 * @property int|null $is_qc int default '0'
 */
abstract class CartDetailBase extends Model
{
    protected $table = 'cart_detail';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'cart_id', 'url', 'product_name', 'shop_name', 'main_image', 'price', 'color', 'amount', 'remark', 'upload_file', 'size', 'user', 'is_craft', 'is_qc'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
