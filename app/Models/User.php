<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
    ];

    /* ---------------------------------------------------------------------
     | Relasi
     * -------------------------------------------------------------------- */

    /** Hotel yang dimiliki (khusus peran owner). */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'owner_id');
    }

    /** Hotel tempat pengguna bekerja sebagai staf. */
    public function staffedHotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'hotel_staff')->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /* ---------------------------------------------------------------------
     | Helper peran
     * -------------------------------------------------------------------- */

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin->value);
    }

    public function isOwner(): bool
    {
        return $this->hasRole(UserRole::Owner->value);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Staff->value);
    }

    public function isGuest(): bool
    {
        return ! $this->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::Owner->value,
            UserRole::Staff->value,
        ]);
    }

    /**
     * Kontrol akses panel Filament.
     * - panel "admin"  : hanya super_admin
     * - panel "owner"  : owner dan staff
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isSuperAdmin(),
            'owner' => $this->isOwner() || $this->isStaff(),
            default => $this->isSuperAdmin(),
        };
    }

    /**
     * Daftar ID hotel yang dikelola pengguna.
     *
     * - super_admin / guest          => null (tidak dibatasi)
     * - owner                        => hotel dengan owner_id miliknya
     * - staff                        => hotel pada pivot hotel_staff
     *
     * Memakai query builder (bukan Eloquent) supaya global scope tidak rekursif.
     */
    public function managedHotelIds(): ?array
    {
        if ($this->isSuperAdmin() || $this->isGuest()) {
            return null;
        }

        if ($this->isOwner()) {
            return DB::table('hotels')
                ->where('owner_id', $this->getKey())
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();
        }

        return DB::table('hotel_staff')
            ->where('user_id', $this->getKey())
            ->pluck('hotel_id')
            ->all();
    }
}
