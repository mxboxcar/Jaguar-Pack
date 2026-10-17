<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/Chat.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `chat` ของระบบเดิม
 *
 * @property int $id int
 * @property string $text text
 * @property \Carbon\CarbonImmutable|null $date_read datetime
 * @property int $is_read int
 * @property int|null $is_read_admin int
 * @property int $dept int
 * @property int $from int
 * @property int $to int
 * @property int|null $create_by int
 * @property \Carbon\CarbonImmutable|null $create_date datetime
 */
abstract class ChatBase extends Model
{
    protected $table = 'chat';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['id', 'text', 'date_read', 'is_read', 'is_read_admin', 'dept', 'from', 'to', 'create_by', 'create_date'];

    protected function casts(): array
    {
        return [
            'date_read' => LegacyDateTime::class.':datetime,zero',
            'create_date' => LegacyDateTime::class.':datetime',
        ];
    }
}
