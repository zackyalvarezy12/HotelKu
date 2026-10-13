<?php

namespace App\Models;

use App\Models\Concerns\ScopedToManagedHotels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;
    use ScopedToManagedHotels;

    protected $fillable = ['hotel_id', 'user_id', 'booking_id', 'last_message_at'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Jumlah pesan yang belum dibaca oleh pengguna tertentu. */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->getKey())
            ->count();
    }

    /** Tandai semua pesan lawan bicara sebagai sudah dibaca. */
    public function markAsReadFor(User $user): void
    {
        $this->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->getKey())
            ->update(['read_at' => now()]);
    }

    /** Jumlah pesan masuk yang belum dibaca pada seluruh inbox pengguna. */
    public static function unreadTotalFor(User $user): int
    {
        return Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->getKey())
            ->whereHas('conversation', fn (Builder $query) => $query
                ->withoutGlobalScopes()
                ->forUser($user))
            ->count();
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        // Pengguna pihak hotel melihat percakapan hotelnya; tamu melihat miliknya sendiri.
        return $user->managedHotelIds() === null
            ? $query->where('user_id', $user->getKey())
            : $query->whereIn('hotel_id', $user->managedHotelIds());
    }
}
