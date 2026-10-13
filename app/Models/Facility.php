<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon', 'category'];

    protected static function booted(): void
    {
        static::saving(function (self $facility) {
            if (blank($facility->slug)) {
                $facility->slug = Str::slug($facility->name);
            }
        });
    }

    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'facility_hotel');
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'facility_room_type');
    }
}
