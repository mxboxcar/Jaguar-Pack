<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/LostPackage.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `lost_package` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property int|null $order_id int
 * @property string|null $attachment varchar(50)
 * @property string|null $submit_remark text
 * @property string|null $cashback decimal(20,2)
 * @property int|null $user int
 * @property int|null $status int
 * @property \Carbon\CarbonImmutable|null $approved_date datetime
 * @property string|null $cancel_remark text
 * @property string|null $approved_upload varchar(50)
 * @property string|null $approved_remark text
 * @property string|null $admin_quote text
 */
abstract class LostPackageBase extends Model
{
    protected $table = 'lost_package';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'order_id', 'attachment', 'submit_remark', 'cashback', 'user', 'status', 'approved_date', 'cancel_remark', 'approved_upload', 'approved_remark', 'admin_quote'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'approved_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
