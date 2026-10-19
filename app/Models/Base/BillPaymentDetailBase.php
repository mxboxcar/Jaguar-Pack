<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/BillPaymentDetail.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `bill_payment_detail` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int|null $id int
 * @property int $tracking_id int
 * @property int|null $no int default '0'
 * @property string|null $tracking varchar(225)
 * @property string|null $order varchar(225)
 * @property string|null $price_kg decimal(20,2) default '0.00'
 * @property string|null $price_cubic decimal(20,2) default '0.00'
 * @property string|null $price_shipping decimal(20,2) default '0.00'
 * @property string|null $price_amount decimal(20,2) default '0.00'
 * @property string|null $rate_kg decimal(20,2)
 * @property string|null $rate_cubic decimal(20,2)
 * @property int|null $product_type_id int
 * @property int|null $lot_id int
 * @property int|null $user int
 * @property string|null $weight decimal(20,1) default '0.0'
 * @property string|null $width decimal(20,1) default '0.0'
 * @property string|null $height decimal(20,1) default '0.0'
 * @property string|null $long decimal(20,1) default '0.0'
 * @property int|null $amount int default '1'
 * @property int|null $ship int default '0'
 * @property int|null $payment_id int default '0'
 * @property int|null $is_craft int default '0'
 * @property int|null $is_qc int default '0'
 * @property string|null $quote text
 * @property string|null $price_qc decimal(20,2) default '0.00'
 * @property string|null $price_repack decimal(20,2) default '0.00'
 * @property string|null $price_express decimal(20,2) default '0.00'
 * @property string|null $price_else decimal(20,2) default '0.00'
 * @property string|null $price_craft decimal(20,2) default '0.00'
 * @property \Carbon\CarbonImmutable|null $date_warehouse datetime default '0000-00-00 00:00:00'
 * @property \Carbon\CarbonImmutable|null $date_outofchina datetime default '0000-00-00 00:00:00'
 * @property \Carbon\CarbonImmutable|null $date_thai datetime default '0000-00-00 00:00:00'
 * @property \Carbon\CarbonImmutable|null $date_finish datetime default '0000-00-00 00:00:00'
 * @property string|null $remark text
 * @property string|null $remark_shop text
 * @property int|null $header int default '0'
 * @property \Carbon\CarbonImmutable|null $date_section datetime default '0000-00-00 00:00:00'
 * @property string|null $subuser varchar(225)
 * @property int|null $is_takeapic int default '0'
 */
abstract class BillPaymentDetailBase extends Model
{
    protected $table = 'bill_payment_detail';

    protected $primaryKey = 'tracking_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['tracking_id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'tracking_id', 'no', 'tracking', 'order', 'price_kg', 'price_cubic', 'price_shipping', 'price_amount', 'rate_kg', 'rate_cubic', 'product_type_id', 'lot_id', 'user', 'weight', 'width', 'height', 'long', 'amount', 'ship', 'payment_id', 'is_craft', 'is_qc', 'quote', 'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft', 'date_warehouse', 'date_outofchina', 'date_thai', 'date_finish', 'remark', 'remark_shop', 'header', 'date_section', 'subuser', 'is_takeapic'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'date_warehouse' => LegacyDateTime::class.':datetime,zero',
            'date_outofchina' => LegacyDateTime::class.':datetime,zero',
            'date_thai' => LegacyDateTime::class.':datetime,zero',
            'date_finish' => LegacyDateTime::class.':datetime,zero',
            'date_section' => LegacyDateTime::class.':datetime,zero',
        ];
    }
}
