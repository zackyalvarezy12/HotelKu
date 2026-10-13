<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Models\Concerns\ScopedToManagedHotels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;
    use ScopedToManagedHotels;

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'banner',
        'type',
        'code',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_nights',
        'min_total',
        'booking_start',
        'booking_end',
        'stay_start',
        'stay_end',
        'valid_days',
        'quota',
        'quota_per_user',
        'used_count',
        'is_active',
        'is_public',
    ];

    protected $casts = [
        'type' => PromotionType::class,
        'discount_type' => DiscountType::class,
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_total' => 'decimal:2',
        'min_nights' => 'integer',
        'quota' => 'integer',
        'quota_per_user' => 'integer',
        'used_count' => 'integer',
        'booking_start' => 'date',
        'booking_end' => 'date',
        'stay_start' => 'date',
        'stay_end' => 'date',
        'valid_days' => 'array',   // JSON: [0,1,2,3,4,5,6]; null = semua hari
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    /* ---------------------------------------------------------------------
     | Relasi
     * -------------------------------------------------------------------- */

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'promotion_room_type');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /* ---------------------------------------------------------------------
     | Scope
     * -------------------------------------------------------------------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Promo publik yang periodenya sedang berjalan (untuk halaman tamu). */
    public function scopeCurrentlyRunning(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('booking_start')->orWhere('booking_start', '<=', $today);
            })
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('booking_end')->orWhere('booking_end', '>=', $today);
            });
    }

    /** Sisa kuota (null = tanpa batas). */
    public function remainingQuota(): ?int
    {
        return $this->quota === null ? null : max(0, $this->quota - $this->used_count);
    }

    public function hasQuotaLeft(): bool
    {
        return $this->quota === null || $this->used_count < $this->quota;
    }

    /** Apakah promo berlaku untuk tipe kamar tertentu. */
    public function appliesToRoomType(int $roomTypeId): bool
    {
        $ids = $this->relationLoaded('roomTypes')
            ? $this->roomTypes->pluck('id')->all()
            : $this->roomTypes()->pluck('room_types.id')->all();

        // Kosong = berlaku untuk semua tipe kamar.
        return $ids === [] || in_array($roomTypeId, $ids, true);
    }

    /** Apakah $date (hari) termasuk hari berlakunya promo. */
    public function isValidOnDay(\DateTimeInterface $date): bool
    {
        $days = $this->valid_days;

        if (blank($days)) {
            return true;
        }

        return in_array((int) $date->format('w'), array_map('intval', $days), true);
    }
}
