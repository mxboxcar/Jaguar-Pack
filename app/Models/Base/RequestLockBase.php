<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/RequestLock.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `request_lock` ของระบบเดิม
 *
 * @property string $fp char(64)
 * @property \Carbon\CarbonImmutable|null $created_at datetime
 */
abstract class RequestLockBase extends Model
{
    protected $table = 'request_lock';

    protected $primaryKey = 'fp';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['fp', 'created_at'];

    protected function casts(): array
    {
        return [
            'created_at' => LegacyDateTime::class.':datetime,zero',
        ];
    }
}
