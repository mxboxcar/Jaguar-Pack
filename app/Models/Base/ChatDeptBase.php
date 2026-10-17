<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/ChatDept.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * ตาราง `chat_dept` ของระบบเดิม
 *
 * @property int $id int
 * @property string $name varchar(225)
 * @property string|null $icon varchar(100)
 */
abstract class ChatDeptBase extends Model
{
    protected $table = 'chat_dept';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = ['id', 'name', 'icon'];

    protected function casts(): array
    {
        return [
        ];
    }
}
