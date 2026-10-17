<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/CrawlGuardSignup.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `crawl_guard_signup` ของระบบเดิม
 *
 * @property int $id bigint unsigned
 * @property \Carbon\CarbonImmutable|null $ts datetime
 * @property int $user int
 * @property string $ip varchar(45)
 * @property string $net varchar(50)
 */
abstract class CrawlGuardSignupBase extends Model
{
    protected $table = 'crawl_guard_signup';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['id', 'ts', 'user', 'ip', 'net'];

    protected function casts(): array
    {
        return [
            'ts' => LegacyDateTime::class.':datetime,zero',
        ];
    }
}
