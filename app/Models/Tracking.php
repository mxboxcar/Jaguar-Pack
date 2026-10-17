<?php

namespace App\Models;

use App\Casts\LegacyDateTime;
use App\Models\Base\TrackingBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ตาราง `tracking` — เพิ่ม relation / scope ของโมดูลได้ที่นี่ (คอลัมน์/ค่าพื้นฐานอยู่ใน Base ห้ามแก้)
 */
class Tracking extends TrackingBase
{
    /** ค่าวันที่ว่างในระบบเดิม */
    public const ZERO_DATE = LegacyDateTime::ZERO_DATETIME;

    /** ตัวกรองสถานะในหน้า list (ค่าเดียวกับ select เดิม) */
    public const STATUS_IN_WAREHOUSE = 0;

    public const STATUS_OUT_OF_CHINA = 1;

    public const STATUS_ARRIVED_THAI = 2;

    public const STATUS_UNPAID = 3;

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('tracking.is_delete', 0);
    }

    public function scopeOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->where('tracking.user', $userId);
    }

    /** ค้นหาเลข tracking / order / quote แบบ bind ค่า (โค้ดเดิมต่อ string ตรง → SQL injection) */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $q->where(fn (Builder $w) => $w
            ->where('tracking.tracking', 'like', $like)
            ->orWhere('tracking.order', 'like', $like)
            ->orWhere('tracking.quote', 'like', $like));
    }

    public function scopeStatus(Builder $q, ?int $status): Builder
    {
        return match ($status) {
            self::STATUS_IN_WAREHOUSE => $q->where('tracking.date_warehouse', '!=', self::ZERO_DATE),
            self::STATUS_OUT_OF_CHINA => $q->where('tracking.date_outofchina', '!=', self::ZERO_DATE),
            self::STATUS_ARRIVED_THAI => $q->where('tracking.date_thai', '!=', self::ZERO_DATE),
            self::STATUS_UNPAID => $q->where('tracking.payment_id', 0),
            default => $q,
        };
    }

    /** รับได้ทั้งค่าที่ cast แล้ว (Carbon|null) และ string ดิบ */
    public static function hasDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        return $value !== null && $value !== '' && ! str_starts_with((string) $value, '0000-00-00');
    }
}
