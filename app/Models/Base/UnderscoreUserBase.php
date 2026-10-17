<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/UnderscoreUser.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `_user` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int default '0'
 * @property int $id int
 * @property string|null $username varchar(45)
 * @property string|null $password varchar(45)
 * @property string|null $code varchar(45)
 * @property int|null $role int
 * @property string|null $name varchar(225)
 * @property string|null $email varchar(225)
 * @property string|null $phone varchar(45)
 * @property int|null $is_enable int default '1'
 * @property int|null $type int default '1'
 * @property string|null $identity varchar(50)
 * @property string|null $address text
 * @property int|null $is_approved int default '0'
 * @property \Carbon\CarbonImmutable|null $approved_date datetime default '0000-00-00 00:00:00'
 * @property string|null $cancel_remark text
 * @property string|null $phone2 varchar(45)
 * @property int|null $option_price int
 * @property string|null $diff decimal(10,2)
 * @property string|null $lineid varchar(45)
 * @property string|null $pin varchar(10)
 */
abstract class UnderscoreUserBase extends Model
{
    protected $table = '_user';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'username', 'password', 'code', 'role', 'name', 'email', 'phone', 'is_enable', 'type', 'identity', 'address', 'is_approved', 'approved_date', 'cancel_remark', 'phone2', 'option_price', 'diff', 'lineid', 'pin'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
            'approved_date' => LegacyDateTime::class.':datetime,zero',
        ];
    }
}
