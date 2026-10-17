<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/LotMailQueue.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `lot_mail_queue` ของระบบเดิม
 *
 * @property int $id int
 * @property int $lot_id int
 * @property int $user_id int
 * @property int $status tinyint default '0'
 * @property int $attempts int default '0'
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 * @property \Carbon\CarbonImmutable|null $sent_date datetime
 */
abstract class LotMailQueueBase extends Model
{
    protected $table = 'lot_mail_queue';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['id', 'lot_id', 'user_id', 'status', 'attempts', 'create_date', 'sent_date'];

    protected function casts(): array
    {
        return [
            'create_date' => LegacyDateTime::class.':datetime,zero',
            'sent_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
