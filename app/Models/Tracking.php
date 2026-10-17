<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ตาราง tracking เดิมของ jl (ใช้ร่วมกับโค้ดเก่า ห้ามเปลี่ยน schema จากฝั่งนี้)
 */
class Tracking extends Model
{
    protected $table = 'tracking';

    public $timestamps = false;

    /** ค่าวันที่ว่างในระบบเดิม */
    public const ZERO_DATE = '0000-00-00 00:00:00';

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

    public static function hasDate(?string $value): bool
    {
        return $value !== null && $value !== '' && $value !== self::ZERO_DATE;
    }
}
