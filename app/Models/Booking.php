<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Models\Concerns\ScopedToManagedHotels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;
    use ScopedToManagedHotels;

    protected $fillable = [
        'code',
        'user_id',
        'hotel_id',
        'room_type_id',
        'room_id',
        'guest_name',
        'guest_phone',
        'check_in',
        'check_out',
        'nights',
        'guests',
        'rooms_count',
        'subtotal',
        'discount_amount',
        'total_price',
        'promotion_id',
        'voucher_code',
        'special_request',
        'status',
        'expires_at',
        'confirmed_at',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'check_in' => 'date',
        'check_out' => 'date',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'nights' => 'integer',
        'guests' => 'integer',
        'rooms_count' => 'integer',
    ];

    /* ---------------------------------------------------------------------
     | Relasi
     * -------------------------------------------------------------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function promotionUsage(): HasOne
    {
        return $this->hasOne(PromotionUsage::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /* ---------------------------------------------------------------------
     | Scope
     * -------------------------------------------------------------------- */

    /** Booking yang menahan stok kamar (belum selesai / belum batal). */
    public function scopeOccupying(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::occupyingStatuses());
    }

    /**
     * Booking yang rentang tanggalnya beririsan dengan rentang $checkIn..$checkOut.
     * Rumus irisan: check_in < tanggal_keluar AND check_out > tanggal_masuk.
     */
    public function scopeOverlapping(Builder $query, string $checkIn, string $checkOut): Builder
    {
        return $query->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);
    }

    public function scopeExpiredPending(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::PendingPayment->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /* ---------------------------------------------------------------------
     | Helper
     * -------------------------------------------------------------------- */

    public function getIsPaidAttribute(): bool
    {
        return $this->payments()->where('status', 'paid')->exists();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->cancellable() && $this->check_in->isFuture();
    }

    /** Label status berbahasa Indonesia untuk ditampilkan di Blade/Filament. */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }
}
