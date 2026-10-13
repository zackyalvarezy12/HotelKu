<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['hotel_id', 'path', 'is_cover', 'sort_order'];

    protected $casts = [
        'is_cover' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Hanya satu foto utama (is_cover) per hotel. */
    protected static function booted(): void
    {
        static::saving(function (self $photo) {
            if ($photo->is_cover && $photo->hotel_id) {
                static::where('hotel_id', $photo->hotel_id)
                    ->whereKeyNot($photo->getKey())
                    ->update(['is_cover' => false]);
            }
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
