<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * วันที่ของระบบเดิม: "ไม่มีค่า" มักเก็บเป็น '0000-00-00 00:00:00' (ไม่ใช่ NULL)
 *
 * อ่าน : NULL / '' / 0000-00-00... → null, อย่างอื่น → CarbonImmutable (Asia/Bangkok)
 * เขียน: Carbon/DateTime → 'Y-m-d H:i:s' (หรือ 'Y-m-d'), string → เขียนตามนั้นเป๊ะ (เขียน '0000-00-00 00:00:00' ตรงๆ ได้)
 *        null → '0000-00-00 00:00:00' ถ้าคอลัมน์ใช้ค่าศูนย์ (พารามิเตอร์ zero) ไม่งั้น NULL
 * ต้องการค่าดิบแบบเดิม: $model->getRawOriginal('date_thai') หรือ Legacy::isZeroDate()
 */
class LegacyDateTime implements CastsAttributes
{
    public const ZERO_DATETIME = '0000-00-00 00:00:00';

    public const ZERO_DATE = '0000-00-00';

    private bool $zero;

    private bool $dateOnly;

    public function __construct(string $format = 'datetime', string $zero = '')
    {
        $this->dateOnly = $format === 'date';
        $this->zero = $zero === 'zero';
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if (self::isEmpty($value)) {
            return null;
        }

        return CarbonImmutable::parse((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return $this->zero ? ($this->dateOnly ? self::ZERO_DATE : self::ZERO_DATETIME) : null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format($this->dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s');
        }

        return (string) $value;
    }

    public static function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if ($value instanceof DateTimeInterface) {
            return false;
        }

        return str_starts_with((string) $value, '0000-00-00');
    }
}
