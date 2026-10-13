<?php

namespace App\Models;

use App\Models\Concerns\ScopedToManagedHotels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory;
    use ScopedToManagedHotels;
    use SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'base_price',
        'capacity',
        'bed_type',
        'size_m2',
        'total_rooms',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'capacity' => 'integer',
        'size_m2' => 'integer',
        'total_rooms' => 'integer',
        'is_active' => 'boolean',
    ];

    /* ---------------------------------------------------------------------
     | Relasi
     * -------------------------------------------------------------------- */

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Hotel induk tanpa isolasi pemilik.
     * Dipakai oleh alur publik (booking), di mana ScopedToManagedHotels tidak berlaku.
     */
    public function publicHotel(): BelongsTo
    {
        // Kunci asing ditulis eksplisit: nama relasi publicHotel() akan membuat
        // Laravel menebak kolom `public_hotel_id` yang tidak ada di tabel.
        return $this->belongsTo(Hotel::class, 'hotel_id')
            ->withoutGlobalScope(Hotel::managedHotelsScopeName());
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RoomTypePhoto::class)->orderBy('sort_order');
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_room_type');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function seasonalPrices(): HasMany
    {
        return $this->hasMany(SeasonalPrice::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_room_type');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Tipe kamar yang tampil di halaman tamu (aktif, tanpa isolasi pemilik). */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->withoutGlobalScope(static::managedHotelsScopeName())
            ->where('is_active', true);
    }
}
