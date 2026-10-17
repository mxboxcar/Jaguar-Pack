<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/SessionV2.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `sessions_v2` ของระบบเดิม
 *
 * @property string $id varchar(32)
 * @property \Carbon\CarbonImmutable|null $modified_timestamp timestamp default 'CURRENT_TIMESTAMP'
 * @property string|null $data mediumtext
 */
abstract class SessionV2Base extends Model
{
    protected $table = 'sessions_v2';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['id', 'modified_timestamp', 'data'];

    protected function casts(): array
    {
        return [
            'modified_timestamp' => LegacyDateTime::class.':datetime,zero',
        ];
    }
}
