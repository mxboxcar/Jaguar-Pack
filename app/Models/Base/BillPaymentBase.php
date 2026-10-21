<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/BillPayment.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `bill_payment` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property string|null $total decimal(20,2)
 * @property \Carbon\CarbonImmutable|null $submit_date datetime
 * @property string|null $submit_remark text
 * @property \Carbon\CarbonImmutable|null $approved_date datetime
 * @property int|null $status int
 * @property string|null $price_shipping decimal(20,2)
 * @property string|null $price_thai_storage decimal(20,2)
 * @property string|null $price_thai_shipping decimal(20,2)
 * @property int|null $user int
 * @property string|null $send_name varchar(225)
 * @property string|null $send_address text
 * @property string|null $send_phone varchar(50)
 * @property int|null $shipping_type int
 * @property string|null $export_tracking varchar(100)
 * @property \Carbon\CarbonImmutable|null $export_date datetime
 * @property string|null $export_remark text
 * @property string|null $export_price decimal(20,2)
 * @property string|null $export_attachment varchar(100)
 * @property string|null $cancel_remark text
 * @property string|null $ref_id varchar(100)
 * @property int|null $is_print int
 * @property \Carbon\CarbonImmutable|null $print_date datetime
 * @property string|null $sender_name varchar(225)
 * @property string|null $sender_address text
 * @property string|null $sender_phone varchar(50)
 * @property int|null $sender_type int
 * @property string|null $sender_charge decimal(12,2)
 * @property string|null $discount decimal(12,2) default '0.00'
 * @property string|null $vat decimal(12,2) default '0.00'
 * @property string|null $vat_before decimal(12,2) default '0.00'
 * @property string|null $shipvat decimal(12,2) default '0.00'
 * @property string|null $shipvat_before decimal(12,2) default '0.00'
 * @property string|null $is_vat decimal(12,2) default '0.00'
 * @property string|null $v decimal(12,2) default '0.00'
 * @property string|null $shipv decimal(12,2) default '0.00'
 */
abstract class BillPaymentBase extends Model
{
    protected $table = 'bill_payment';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'total', 'submit_date', 'submit_remark', 'approved_date', 'status', 'price_shipping', 'price_thai_storage', 'price_thai_shipping', 'user', 'send_name', 'send_address', 'send_phone', 'shipping_type', 'export_tracking', 'export_date', 'export_remark', 'export_price', 'export_attachment', 'cancel_remark', 'ref_id', 'is_print', 'print_date', 'sender_name', 'sender_address', 'sender_phone', 'sender_type', 'sender_charge', 'discount', 'vat', 'vat_before', 'shipvat', 'shipvat_before', 'is_vat', 'v', 'shipv'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'submit_date' => LegacyDateTime::class.':datetime',
            'approved_date' => LegacyDateTime::class.':datetime',
            'export_date' => LegacyDateTime::class.':datetime',
            'print_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
