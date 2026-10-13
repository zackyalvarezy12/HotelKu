<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomTypePhoto extends Model
{
    use HasFactory;

    protected $fillable = ['room_type_id', 'path', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
