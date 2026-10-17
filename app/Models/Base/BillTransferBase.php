<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/BillTransfer.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `bill_transfer` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property string|null $acc_name varchar(225)
 * @property string|null $acc_no varchar(225)
 * @property string|null $acc_bank varchar(225)
 * @property string|null $acc_branch varchar(225)
 * @property string|null $attachment varchar(50)
 * @property string|null $total decimal(20,2)
 * @property string|null $rate decimal(20,2)
 * @property string|null $usecash decimal(20,2)
 * @property string|null $remark text
 * @property int|null $status int
 * @property string|null $cancel_remark text
 * @property int|null $user int
 * @property string|null $approved_upload varchar(50)
 * @property string|null $approved_remark text
 * @property \Carbon\CarbonImmutable|null $approved_date datetime
 */
abstract class BillTransferBase extends Model
{
    protected $table = 'bill_transfer';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'acc_name', 'acc_no', 'acc_bank', 'acc_branch', 'attachment', 'total', 'rate', 'usecash', 'remark', 'status', 'cancel_remark', 'user', 'approved_upload', 'approved_remark', 'approved_date'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'approved_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
