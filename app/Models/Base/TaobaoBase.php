<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/Taobao.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `taobao` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property string $rate decimal(20,2) default '0.00'
 * @property string $charge decimal(20,2) default '0.00'
 * @property string $price decimal(20,2)
 * @property string $taobao_name varchar(225)
 * @property string $taobao_password varchar(225)
 * @property int $status int
 * @property int $user int
 * @property string $approved_remark text
 * @property \Carbon\CarbonImmutable|null $approved_date datetime
 * @property string $approved_upload varchar(50)
 * @property string $cancel_remark text
 * @property string $usecash decimal(20,2)
 * @property string $usecash_cn decimal(20,2)
 * @property string $remark text
 * @property string $remark_admin text
 */
abstract class TaobaoBase extends Model
{
    protected $table = 'taobao';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'rate', 'charge', 'price', 'taobao_name', 'taobao_password', 'status', 'user', 'approved_remark', 'approved_date', 'approved_upload', 'cancel_remark', 'usecash', 'usecash_cn', 'remark', 'remark_admin'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'approved_date' => LegacyDateTime::class.':datetime,zero',
        ];
    }
}
