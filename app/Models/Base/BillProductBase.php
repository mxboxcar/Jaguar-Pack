<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/BillProduct.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `bill_product` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property int|null $user int
 * @property string|null $total decimal(20,2) default '0.00'
 * @property string|null $usecash decimal(20,2) default '0.00'
 * @property string|null $exchange_rate decimal(20,3) default '0.000'
 * @property string|null $attachment varchar(45)
 * @property \Carbon\CarbonImmutable|null $submit_date datetime
 * @property string|null $submit_remark varchar(225)
 * @property int|null $status int default '0'
 * @property string|null $quote text
 * @property int|null $ship int
 * @property int|null $is_lock int default '0'
 * @property string|null $cancel_remark text
 * @property string|null $admin_quote text
 * @property int|null $who_did_this_1 int
 * @property \Carbon\CarbonImmutable|null $who_did_this_1_date datetime
 * @property int|null $who_did_this_3 int
 * @property \Carbon\CarbonImmutable|null $who_did_this_3_date datetime
 * @property string|null $run_id varchar(50) default ''
 * @property int|null $is_print int default '0'
 */
abstract class BillProductBase extends Model
{
    protected $table = 'bill_product';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'user', 'total', 'usecash', 'exchange_rate', 'attachment', 'submit_date', 'submit_remark', 'status', 'quote', 'ship', 'is_lock', 'cancel_remark', 'admin_quote', 'who_did_this_1', 'who_did_this_1_date', 'who_did_this_3', 'who_did_this_3_date', 'run_id', 'is_print'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'submit_date' => LegacyDateTime::class.':datetime',
            'who_did_this_1_date' => LegacyDateTime::class.':datetime',
            'who_did_this_3_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
