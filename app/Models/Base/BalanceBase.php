<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/Balance.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `balance` ของระบบเดิม
 *
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $mod_date datetime
 * @property int|null $mod_by int
 * @property int|null $is_delete int
 * @property int $id int
 * @property int|null $user int
 * @property string|null $amount decimal(20,2)
 * @property string|null $detail varchar(225)
 * @property int|null $type int
 * @property int|null $prefix int
 * @property int|null $prefix_ref_id int
 * @property int|null $ref_id_2 int
 */
abstract class BalanceBase extends Model
{
    protected $table = 'balance';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['create_date', 'create_by', 'mod_date', 'mod_by', 'is_delete', 'id', 'user', 'amount', 'detail', 'type', 'prefix', 'prefix_ref_id', 'ref_id_2'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime',
            'mod_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
