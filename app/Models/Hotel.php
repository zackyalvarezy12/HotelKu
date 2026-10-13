<?php

namespace App\Models;

use App\Enums\HotelStatus;
use App\Models\Concerns\ScopedToManagedHotels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Hotel extends Model
{
    use HasFactory;
    use ScopedToManagedHotels;
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'city',
        'address',
        'latitude',
        'longitude',
        'description',
        'phone',
        'email',
        'logo',
        'check_in_time',
        'check_out_time',
        'star_rating',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'status' => HotelStatus::class,
        'latitude' => 'float',
        'longitude' => 'float',
        'star_rating' => 'integer',
        'verified_at' => 'datetime',
    ];

    /** Slug otomatis dari nama hotel bila belum diisi. */
    protected static function booted(): void
    {
        static::saving(function (self $hotel) {
            if (blank($hotel->slug)) {
                $hotel->slug = static::uniqueSlug($hotel->name, $hotel->getKey());
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (static::withoutGlobalScopes()
            ->withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ---------------------------------------------------------------------
     | Relasi
     * -------------------------------------------------------------------- */

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hotel_staff')->withTimestamps();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HotelPhoto::class)->orderBy('sort_order');
    }

    public function coverPhoto(): HasMany
    {
        return $this->hasMany(HotelPhoto::class)->where('is_cover', true);
    }

    /**
     * Satu foto utama untuk kartu hotel (HasOne, bukan HasMany + limit).
     * `limit()` pada eager load di Laravel 10 membatasi seluruh query relasi,
     * bukan per-induk, sehingga bisa salah menampilkan foto.
     */
    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(HotelPhoto::class)
            ->orderByDesc('is_cover')
            ->orderBy('sort_order');
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_hotel');
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Tipe kamar aktif untuk halaman tamu.
     * withoutGlobalScope dipakai karena relasi ini juga dibaca pada halaman
     * publik, di mana isolasi pemilik tidak berlaku.
     */
    public function publicRoomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class)
            ->withoutGlobalScope(RoomType::managedHotelsScopeName())
            ->where('is_active', true);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /* ---------------------------------------------------------------------
     | Scope
     * -------------------------------------------------------------------- */

    /** Hanya hotel yang sudah diverifikasi admin (tampil di halaman tamu). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', HotelStatus::Active->value);
    }

    public function scopeInCity(Builder $query, ?string $city): Builder
    {
        return $query->when($city, fn (Builder $q) => $q->where('city', $city));
    }

    /**
     * Hotel yang boleh tampil di halaman tamu: hanya status aktif, dan TANPA
     * isolasi pemilik. ScopedToManagedHotels ditujukan untuk panel admin/owner,
     * sehingga harus dilepas di halaman publik (mis. owner yang sedang menjelajah).
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->withoutGlobalScope(static::managedHotelsScopeName())
            ->where('status', HotelStatus::Active->value);
    }

    /** Kolom bayangan "harga mulai dari" = harga termurah tipe kamar aktif. */
    public function scopeWithMinPrice(Builder $query): Builder
    {
        return $query->addSelect(['min_price' => RoomType::query()
            ->selectRaw('MIN(base_price)')
            ->whereColumn('room_types.hotel_id', 'hotels.id')
            ->publiclyVisible(),
        ]);
    }

    /**
     * Relasi & kolom standar untuk kartu hotel di halaman tamu.
     * Hanya hotel aktif yang punya minimal satu tipe kamar aktif (bisa dipesan).
     */
    public function scopeForListing(Builder $query): Builder
    {
        return $query->publiclyVisible()
            ->whereHas('publicRoomTypes')
            ->with(['primaryPhoto', 'facilities'])
            ->withCount('publicRoomTypes')
            ->withMinPrice();
    }

    /* ---------------------------------------------------------------------
     | Accessor
     * -------------------------------------------------------------------- */

    public function getIsActiveAttribute(): bool
    {
        return $this->status === HotelStatus::Active;
    }

    /* ---------------------------------------------------------------------
     | Aksi status verifikasi (dipakai panel super admin & pengujian)
     * -------------------------------------------------------------------- */

    public function markAsVerified(): void
    {
        $this->update([
            'status' => HotelStatus::Active,
            'verified_at' => now(),
        ]);
    }

    public function markAsSuspended(): void
    {
        $this->update(['status' => HotelStatus::Suspended]);
    }

    public function markAsPending(): void
    {
        $this->update([
            'status' => HotelStatus::Pending,
            'verified_at' => null,
        ]);
    }

    /** Rata-rata rating dari ulasan (dihitung bila kolom tersedia). */
    public function getRatingAverageAttribute(): float
    {
        return round((float) ($this->reviews_avg_rating ?? $this->reviews()->avg('rating') ?? 0), 1);
    }
}
